<?php

namespace portalium\notification\components;

use yii\base\Component;
use portalium\notification\models\Notification as NotificationModel;
use portalium\notification\models\NotificationDevice;
use portalium\user\models\User;
use Yii;

class Notification extends Component
{

    /** FCM disa donuk cagrilarinda baglanti ve toplam sure siniri (saniye). */
    const CURL_CONNECT_TIMEOUT = 3;
    const CURL_TIMEOUT = 5;

    /** OAuth2 access token onbellegi; token omru 3600 sn oldugundan pay birakilir. */
    const ACCESS_TOKEN_CACHE_KEY = 'portalium.notification.fcm.access_token';
    const ACCESS_TOKEN_CACHE_TTL = 3300;

    /** Sunucu saati ile gercek UTC arasindaki sapma onbellegi. */
    const CLOCK_OFFSET_CACHE_KEY = 'portalium.notification.fcm.clock_offset';
    const CLOCK_OFFSET_CACHE_TTL = 3600;

    public function addNotification($id_to, $title, $text)
    { 
        if($id_to == null || User::findOne($id_to) == null)
            return;
        $model = new NotificationModel();
        $model->id_to = $id_to;
        $model->text = $text;
        $model->title = $title;
        $model->save();
    }

    public function sendEmail($user, $text, $title)
    {
        Yii::$app->site->mailer->setViewPath(Yii::getAlias('@portalium/notification/mail'));
        return Yii::$app
            ->site
            ->mailer
            ->compose(
                ['html' => 'notificationEmail-html', 'text' => 'notificationEmail-text'],
                ['user' => $user,'text'=> $text, 'title'=> $title]
                )
            ->setFrom([Yii::$app->setting->getValue('email::address') => Yii::$app->setting->getValue('email::displayname')])
            ->setTo($user->email)
            ->setSubject('Notification ' .  Yii::$app->setting->getValue('app::title'))
            ->send();

    }

    public function sendSms($to, $message)
    {
        if (!Yii::$app->setting->getValue('notification::sms_enabled')) {
            return [
                'success' => false,
                'provider' => null,
                'status' => 'not_sent',
                'retryable' => false,
                'error' => 'sms_disabled',
            ];
        }

        // Only the configured provider is used. Automatic failover is avoided
        // because a lost response can leave the original delivery ambiguous.
        $provider = Yii::$app->setting->getValue('notification::sms_provider');
        $providerConfig = [];

        if ($provider === 'netgsm') {
            $providerConfig = [
                'usercode' => Yii::$app->setting->getValue('notification::sms_netgsm_usercode'),
                'password' => Yii::$app->setting->getValue('notification::sms_netgsm_password'),
                'msgheader' => Yii::$app->setting->getValue('notification::sms_netgsm_msgheader'),
            ];
        } elseif ($provider === 'verimor') {
            $providerConfig = [
                'username' => Yii::$app->setting->getValue('notification::sms_verimor_username'),
                'password' => Yii::$app->setting->getValue('notification::sms_verimor_password'),
                'source_addr' => Yii::$app->setting->getValue('notification::sms_verimor_source_addr'),
            ];
        }

        return (new SmsSender())->send($provider, $to, $message, $providerConfig);
    }

    public function sendPushNotification($deviceToken, $title, $message, $data = [], $priority = null, $sound = null)
    {
        if (empty($deviceToken)) {
            return ['success' => false, 'error' => 'Device token is required'];
        }

        $results = $this->sendPushToTokens([$deviceToken], $title, $message, $data, $priority, $sound);

        // Ayar/token eksikligi tek bir hata dizisi dondurur.
        if (isset($results['error'])) {
            return $results['error'];
        }

        return $results[0];
    }

    /**
     * Tek bir cihaz icin FCM v1 mesaj govdesini kurar.
     */
    private function buildFcmPayload($deviceToken, $title, $message, $data = [], $priority = null, $sound = null)
    {
        if ($priority === null) {
            $priority = Yii::$app->setting->getValue('notification::fcm_default_priority') ?: 'high';
        }

        if ($sound === null) {
            $sound = Yii::$app->setting->getValue('notification::fcm_default_sound') ?: 'default';
        }

        $notificationIcon = Yii::$app->setting->getValue('notification::fcm_icon') ?: 'ic_notification';
        $notificationColor = Yii::$app->setting->getValue('notification::fcm_color') ?: '#FF6600';

        $messagePayload = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $message
                ],
                'android' => [
                    'priority' => strtoupper($priority),
                    'notification' => [
                        'icon' => $notificationIcon,
                        'color' => $notificationColor,
                        'sound' => $sound,
                    ]
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'alert' => [
                                'title' => $title,
                                'body' => $message
                            ],
                            'sound' => $sound
                        ]
                    ]
                ]
            ]
        ];

        // Ek veri varsa ekle - FCM data degerlerinin tumu string olmali
        if (!empty($data)) {
            $messagePayload['message']['data'] = array_map('strval', $data);
        }

        return $messagePayload;
    }

    /**
     * Hazirlanmis FCM mesajlarini curl_multi ile es zamanli gonderir.
     *
     * Cihazlar tek tek, ardisik olarak gonderildiginde her cihazin gecikmesi
     * toplama ekleniyordu; burada tum istekler ayni anda ucusa gecer, boylece
     * toplam sure en yavas cihazin suresi kadar olur.
     *
     * @param array $requests [['key' => <sonuc anahtari>, 'payload' => <mesaj dizisi>], ...]
     * @return array key => sonuc dizisi
     */
    private function executeFcmRequests($apiUrl, $accessToken, array $requests)
    {
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];

        $multi   = curl_multi_init();
        $handles = [];

        foreach ($requests as $request) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CURL_CONNECT_TIMEOUT);
            curl_setopt($ch, CURLOPT_TIMEOUT, self::CURL_TIMEOUT);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request['payload']));

            curl_multi_add_handle($multi, $ch);
            $handles[] = ['key' => $request['key'], 'ch' => $ch];
        }

        do {
            $status = curl_multi_exec($multi, $running);
            if ($running) {
                curl_multi_select($multi, 1.0);
            }
        } while ($running && $status === CURLM_OK);

        // curl_multi'de handle basina hata kodu curl_errno ile degil, info_read
        // kuyrugundan okunur; once hepsini handle'a gore toplariz.
        $errors = [];
        while ($info = curl_multi_info_read($multi)) {
            $errors[$this->handleKey($info['handle'])] = $info['result'];
        }

        $results = [];

        foreach ($handles as $handle) {
            $ch    = $handle['ch'];
            $body  = curl_multi_getcontent($ch);
            $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $errno = $errors[$this->handleKey($ch)] ?? curl_errno($ch);

            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);

            // Zaman asimi, olu/yavas token'larda en sik gorulen hata; ayrica isaretlenir
            // ki sorun teshisinde genel bir "FCM API Error"a karismasin.
            if ($errno === CURLE_OPERATION_TIMEDOUT) {
                Yii::warning('FCM request timed out after ' . self::CURL_TIMEOUT . 's', __METHOD__);
                $results[$handle['key']] = [
                    'success'   => false,
                    'error'     => 'FCM request timed out',
                    'timed_out' => true,
                ];
                continue;
            }

            if ($error) {
                $results[$handle['key']] = ['success' => false, 'error' => 'cURL Error: ' . $error];
                continue;
            }

            $response = json_decode($body, true);

            if ($code === 200 && isset($response['name'])) {
                $results[$handle['key']] = [
                    'success'  => true,
                    'message'  => 'Push notification sent successfully',
                    'response' => $response
                ];
            } else {
                $results[$handle['key']] = [
                    'success'   => false,
                    'error'     => 'FCM v1 API Error',
                    'http_code' => $code,
                    'response'  => $response
                ];
            }
        }

        curl_multi_close($multi);

        return $results;
    }

    /**
     * Bir curl handle'ini dizi anahtari olarak kullanilabilir hale getirir.
     * PHP 8'de handle bir CurlHandle nesnesi, oncesinde resource'tur.
     */
    private function handleKey($handle)
    {
        return is_object($handle) ? spl_object_id($handle) : (int) $handle;
    }

    /**
     * Birden cok cihaz token'ina ayni bildirimi es zamanli gonderir.
     *
     * FCM ayarlari ve access token bir kez cozulur, istekler curl_multi ile
     * paralel akar. Donen dizi token sirasini korur.
     *
     * @return array Ayar/token eksikse ['error' => <hata dizisi>], aksi halde token sirasina gore sonuclar
     */
    private function sendPushToTokens(array $deviceTokens, $title, $message, $data = [], $priority = null, $sound = null)
    {
        if (!Yii::$app->setting->getValue('notification::fcm_enabled')) {
            return ['error' => ['success' => false, 'error' => 'FCM is disabled']];
        }

        $projectId      = Yii::$app->setting->getValue('notification::fcm_project_id');
        $serviceAccount = Yii::$app->setting->getValue('notification::fcm_service_account_json');
        $apiUrlTemplate = Yii::$app->setting->getValue('notification::fcm_api_url');

        if (empty($projectId)) {
            return ['error' => ['success' => false, 'error' => 'FCM project ID not configured']];
        }

        $apiUrl = str_replace('{project_id}', $projectId, $apiUrlTemplate);

        $accessToken = $this->getAccessToken($serviceAccount);
        if (!$accessToken) {
            Yii::error("Unable to get access token for FCM", __METHOD__);
            return ['error' => ['success' => false, 'error' => 'Unable to get access token']];
        }

        $requests = [];
        $results  = [];

        foreach ($deviceTokens as $index => $deviceToken) {
            if (empty($deviceToken)) {
                $results[$index] = ['success' => false, 'error' => 'Device token is required'];
                continue;
            }

            $requests[] = [
                'key'     => $index,
                'payload' => $this->buildFcmPayload($deviceToken, $title, $message, $data, $priority, $sound),
            ];
        }

        if (!empty($requests)) {
            $results += $this->executeFcmRequests($apiUrl, $accessToken, $requests);
        }

        ksort($results);

        return $results;
    }

    /**
     * Google Service Account JSON dosyasından access token al
     */
    private function getAccessToken($serviceAccount)
    {
        if (!$serviceAccount || !isset($serviceAccount['private_key'], $serviceAccount['client_email'])) {
            Yii::error("[FCM] Invalid service account JSON. Keys: " . (is_array($serviceAccount) ? implode(',', array_keys($serviceAccount)) : 'N/A (not array)'), __METHOD__);
            return null;
        }

        // Token 3600 sn gecerli; her bildirimde yeniden almak yerine onbellekten okunur.
        // Anahtar client_email'e baglidir, boylece service account degisince onbellek dogal olarak tazelenir.
        $cache = Yii::$app->has('cache') ? Yii::$app->cache : null;
        $cacheKey = self::ACCESS_TOKEN_CACHE_KEY . '.' . md5($serviceAccount['client_email']);

        if ($cache !== null) {
            $cached = $cache->get($cacheKey);
            if (!empty($cached)) {
                return $cached;
            }
        }

        $token = $this->fetchAccessToken($serviceAccount);

        if ($token !== null && $cache !== null) {
            $cache->set($cacheKey, $token, self::ACCESS_TOKEN_CACHE_TTL);
        }

        return $token;
    }

    /**
     * Google OAuth2'den yeni bir access token alir (onbellek atlanarak).
     */
    private function fetchAccessToken($serviceAccount)
    {
        try {
//            Yii::warning("[FCM DEBUG] client_email=" . $serviceAccount['client_email'] . " | private_key starts=" . substr($serviceAccount['private_key'], 0, 40) . " | private_key ends=" . substr($serviceAccount['private_key'], -20) . " | has_newlines=" . (strpos($serviceAccount['private_key'], "\n") !== false ? 'yes' : 'NO - PROBLEM!'), __METHOD__);

            // JWT oluştur — sunucu saati hatalıysa Google'dan gerçek UTC zamanını al
            $now = $this->getGoogleUtcTime();
            $header = [
                'alg' => 'RS256',
                'typ' => 'JWT'
            ];

            $payload = [
                'iss' => $serviceAccount['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600
            ];

            $headerEncoded = $this->base64UrlEncode(json_encode($header));
            $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

            $signature = '';
            $privateKey = $serviceAccount['private_key'];
            
            $privateKey = str_replace('\\n', "\n", $privateKey);
            
            $signResult = openssl_sign(
                $headerEncoded . '.' . $payloadEncoded,
                $signature,
                $privateKey,
                'sha256WithRSAEncryption'
            );
//            Yii::warning("Sunucu saati: " . date('Y-m-d H:i:s') . "\n");
//            Yii::warning("Unix timestamp: " . time() . "\n");
//            Yii::warning("Timezone: " . date_default_timezone_get() . "\n");
//            Yii::warning("[FCM DEBUG] openssl_sign result=" . ($signResult ? 'SUCCESS' : 'FAILED') . " | openssl_error=" . openssl_error_string() . " | signature_length=" . strlen($signature), __METHOD__);

            $jwt = $headerEncoded . '.' . $payloadEncoded . '.' . $this->base64UrlEncode($signature);

            // Access token al
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CURL_CONNECT_TIMEOUT);
            curl_setopt($ch, CURLOPT_TIMEOUT, self::CURL_TIMEOUT);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

//            Yii::warning("[FCM DEBUG] OAuth2 response httpCode=" . $httpCode . " | body=" . $result, __METHOD__);

            if ($httpCode === 200) {
                $response = json_decode($result, true);
//                Yii::warning("Access token obtained: " . $response['access_token'], __METHOD__);
                return $response['access_token'] ?? null;
            }

            return null;

        } catch (\Exception $e) {
            Yii::error("Access token error: " . $e->getMessage(), __METHOD__);
            return null;
        }
    }

    /**
     * Sunucu saati yanlışsa Google'ın HTTP Date header'ından gerçek UTC zamanını döndürür.
     * Başarısız olursa time()'a düşer.
     */
    private function getGoogleUtcTime(): int
    {
        static $offset = null;

        if ($offset !== null) {
            return time() + $offset;
        }

        $cache = Yii::$app->has('cache') ? Yii::$app->cache : null;
        if ($cache !== null) {
            $cached = $cache->get(self::CLOCK_OFFSET_CACHE_KEY);
            if ($cached !== false) {
                $offset = (int) $cached;
                return time() + $offset;
            }
        }

        $offset = 0;

        try {
            $ch = curl_init('https://www.googleapis.com/');
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CURL_CONNECT_TIMEOUT);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            $response = curl_exec($ch);
            curl_close($ch);

            if ($response && preg_match('/Date:\s*(.+)\r?\n/i', $response, $m)) {
                $ts = strtotime(trim($m[1]));
                if ($ts !== false && abs($ts - time()) < 86400) {
                    $offset = $ts - time();
                }
            }
        } catch (\Throwable $e) {
            // sessizce dus, sapma 0 kalir
        }

        if ($cache !== null) {
            $cache->set(self::CLOCK_OFFSET_CACHE_KEY, $offset, self::CLOCK_OFFSET_CACHE_TTL);
        }

        return time() + $offset;
    }

    /**
     * Base64 URL encode
     */
    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Kullanıcının cihazını deaktive et
     */
    public function deactivateDevice($deviceToken)
    {
        return NotificationDevice::deactivateDevice($deviceToken);
    }

    /**
     * Kullanıcıya push notification gönder (tüm aktif cihazlarına)
     */
    public function sendPushToUser($userId, $title, $message, $data = [], $workspaceId = null, $deviceType = null)
    {
        // Kullanıcının aktif device token'larını al
        $deviceTokens = NotificationDevice::getActiveTokensByUser($userId, $workspaceId, $deviceType);
        
        if (empty($deviceTokens)) {
//            Yii::warning("No active devices found for user $userId in workspace $workspaceId with device type $deviceType", __METHOD__);
            return ['success' => false, 'error' => 'No active devices found for user'];
        }

        $results = [];
        $successCount = 0;
        $failCount = 0;

        $sendResults = $this->sendPushToTokens($deviceTokens, $title, $message, $data);

        if (isset($sendResults['error'])) {
            return $sendResults['error'];
        }

        foreach ($deviceTokens as $index => $deviceToken) {
            $result = $sendResults[$index];
            $results[] = [
                'device_token' => substr($deviceToken, 0, 20) . '...', // Güvenlik için kısa göster
                'result' => $result
            ];

            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }
        }
//        Yii::warning(
//            "sendPushToUser results for user $userId: success=$successCount, fail=$failCount, total=" . count($deviceTokens),
//            __METHOD__
//        );
        return [
            'success' => $successCount > 0,
            'summary' => [
                'total_devices' => count($deviceTokens),
                'success_count' => $successCount,
                'fail_count' => $failCount
            ],
            'details' => $results
        ];
    }

    /**
     * Kullanıcıya push notification gönder (tek bir notification modeline göre)
     * @param NotificationModel $notificationModel
     * @return array Sonuç dizisi (başarı durumu ve detaylar)
     */
    public function sendPushToUserByNotification($notificationModel)
    {
        $userId = $notificationModel->id_to;
        $title = $notificationModel->title;
        $message = $notificationModel->text;

        return $this->sendPushToUser($userId, $title, $message);
    }

    /**
     * Workspace'deki tüm kullanıcılara push notification gönder
     */
    public function sendPushToWorkspace($workspaceId, $title, $message, $data = [], $deviceType = null)
    {
        // Workspace'deki aktif cihazları al
        $devices = NotificationDevice::find()
            ->where(['id_workspace' => $workspaceId, 'is_active' => NotificationDevice::STATUS_ACTIVE]);
            
        if ($deviceType !== null) {
            $devices->andWhere(['device_type' => $deviceType]);
        }
        
        $devices = $devices->all();
        
        if (empty($devices)) {
            return ['success' => false, 'error' => 'No active devices found for workspace'];
        }

        $results = [];
        $successCount = 0;
        $failCount = 0;

        $tokens = array_map(function ($device) {
            return $device->device_token;
        }, $devices);

        $sendResults = $this->sendPushToTokens($tokens, $title, $message, $data);

        if (isset($sendResults['error'])) {
            return $sendResults['error'];
        }

        foreach ($devices as $index => $device) {
            $result = $sendResults[$index];
            $results[] = [
                'user_id' => $device->id_user,
                'device_type' => $device->device_type,
                'result' => $result
            ];

            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        return [
            'success' => $successCount > 0,
            'summary' => [
                'total_devices' => count($devices),
                'success_count' => $successCount,
                'fail_count' => $failCount
            ],
            'details' => $results
        ];
    }

    /**
     * Çoklu kullanıcıya push notification gönder
     */
    public function sendPushToMultipleUsers($userIds, $title, $message, $data = [], $workspaceId = null, $deviceType = null)
    {
        if (empty($userIds)) {
            return ['success' => false, 'error' => 'No user IDs provided'];
        }

        $query = NotificationDevice::find()
            ->where(['id_user' => $userIds, 'is_active' => NotificationDevice::STATUS_ACTIVE]);
            
        if ($workspaceId !== null) {
            $query->andWhere(['id_workspace' => $workspaceId]);
        }
        
        if ($deviceType !== null) {
            $query->andWhere(['device_type' => $deviceType]);
        }
        
        $devices = $query->all();
        
        if (empty($devices)) {
            return ['success' => false, 'error' => 'No active devices found for users'];
        }

        $results = [];
        $successCount = 0;
        $failCount = 0;

        $tokens = array_map(function ($device) {
            return $device->device_token;
        }, $devices);

        $sendResults = $this->sendPushToTokens($tokens, $title, $message, $data);

        if (isset($sendResults['error'])) {
            return $sendResults['error'];
        }

        foreach ($devices as $index => $device) {
            $result = $sendResults[$index];
            $results[] = [
                'user_id' => $device->id_user,
                'device_type' => $device->device_type,
                'result' => $result
            ];

            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        return [
            'success' => $successCount > 0,
            'summary' => [
                'total_devices' => count($devices),
                'success_count' => $successCount,
                'fail_count' => $failCount
            ],
            'details' => $results
        ];
    }

    /**
     * Kullanıcının aktif cihaz sayısını al
     */
    public function getUserDeviceCount($userId, $workspaceId = null, $deviceType = null)
    {
        $query = NotificationDevice::find()
            ->where(['id_user' => $userId, 'is_active' => NotificationDevice::STATUS_ACTIVE]);
            
        if ($workspaceId !== null) {
            $query->andWhere(['id_workspace' => $workspaceId]);
        }
        
        if ($deviceType !== null) {
            $query->andWhere(['device_type' => $deviceType]);
        }
        
        return $query->count();
    }

    /**
     * Eski cihazları temizle
     */
    public function cleanupOldDevices($days = 30)
    {
        return NotificationDevice::cleanupOldDevices($days);
    }
}