<?php

namespace portalium\notification\components;

use yii\base\Component;
use portalium\notification\models\Notification as NotificationModel;
use portalium\notification\models\NotificationDevice;
use portalium\user\models\User;
use Yii;

class Notification extends Component
{

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

    public function sendPushNotification($deviceToken, $title, $message, $data = [], $priority = null, $sound = null)
    {
        // FCM'nin etkin olup olmadığını kontrol et
        if (!Yii::$app->setting->getValue('notification::fcm_enabled')) {
            return ['success' => false, 'error' => 'FCM is disabled'];
        }

        // Gerekli ayarları al
        $projectId = Yii::$app->setting->getValue('notification::fcm_project_id');
        $serviceAccount = Yii::$app->setting->getValue('notification::fcm_service_account_json');
        $apiUrlTemplate = Yii::$app->setting->getValue('notification::fcm_api_url');

        if (empty($projectId)) {
            return ['success' => false, 'error' => 'FCM project ID not configured'];
        }
        
        if (empty($deviceToken)) {
            return ['success' => false, 'error' => 'Device token is required'];
        }

        // API URL'ini oluştur
        $apiUrl = str_replace('{project_id}', $projectId, $apiUrlTemplate);

        // Access token al
        $accessToken = $this->getAccessToken($serviceAccount);
        if (!$accessToken) {
            Yii::error("Unable to get access token for FCM", __METHOD__);
            return ['success' => false, 'error' => 'Unable to get access token'];
        }

        // Default değerler setting'lerden al
        if ($priority === null) {
            $priority = Yii::$app->setting->getValue('notification::fcm_default_priority') ?: 'high';
        }
        
        if ($sound === null) {
            $sound = Yii::$app->setting->getValue('notification::fcm_default_sound') ?: 'default';
        }

        $notificationIcon = Yii::$app->setting->getValue('notification::fcm_icon') ?: 'ic_notification';
        $notificationColor = Yii::$app->setting->getValue('notification::fcm_color') ?: '#FF6600';

        // FCM v1 mesaj yapısını oluştur
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

        // Ek veri varsa ekle
        if (!empty($data)) {
            $messagePayload['message']['data'] = $data;
        }

        // HTTP başlıklarını hazırla
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];
        Yii::warning("Sending FCM push notification: " . json_encode($messagePayload), __METHOD__);
        // cURL ile FCM v1 API'sine istek gönder
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($messagePayload));

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Hata kontrolü
        if ($error) {
            Yii::warning("cURL Error: " . $error, __METHOD__);
            return ['success' => false, 'error' => 'cURL Error: ' . $error];
        }

        // Yanıtı çözümle
        $response = json_decode($result, true);
        
        if ($httpCode === 200 && isset($response['name'])) {
            Yii::info("Push notification sent successfully: " . json_encode($response), __METHOD__);
            return [
                'success' => true,
                'message' => 'Push notification sent successfully',
                'response' => $response
            ];
        } else {
            Yii::warning("FCM v1 API Error: HTTP $httpCode - " . json_encode($response), __METHOD__);
            return [
                'success' => false,
                'error' => 'FCM v1 API Error',
                'http_code' => $httpCode,
                'response' => $response
            ];
        }
    }

    /**
     * Google Service Account JSON dosyasından access token al
     */
    private function getAccessToken($serviceAccount)
    {
        try {
            // Service account JSON dosyasını oku
            if (!$serviceAccount || !isset($serviceAccount['private_key'], $serviceAccount['client_email'])) {
                Yii::error("Invalid service account JSON", __METHOD__);
                return null;
            }

            // JWT oluştur
            $now = time();
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
            openssl_sign(
                $headerEncoded . '.' . $payloadEncoded,
                $signature,
                $serviceAccount['private_key'],
                'sha256WithRSAEncryption'
            );

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

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $response = json_decode($result, true);
                Yii::warning("Access token obtained: " . $response['access_token'], __METHOD__);
                return $response['access_token'] ?? null;
            }

            return null;

        } catch (\Exception $e) {
            Yii::error("Access token error: " . $e->getMessage(), __METHOD__);
            return null;
        }
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
            return ['success' => false, 'error' => 'No active devices found for user'];
        }

        $results = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($deviceTokens as $deviceToken) {
            $result = $this->sendPushNotification($deviceToken, $title, $message, $data);
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

        foreach ($devices as $device) {
            $result = $this->sendPushNotification($device->device_token, $title, $message, $data);
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

        foreach ($devices as $device) {
            $result = $this->sendPushNotification($device->device_token, $title, $message, $data);
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