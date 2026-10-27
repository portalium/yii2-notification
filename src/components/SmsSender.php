<?php

namespace portalium\notification\components;

use Yii;
use yii\base\Component;

/**
 * Sends one informational SMS through the configured provider.
 *
 * The two supported providers share this small dispatcher for now. Separate
 * provider classes are deferred until a third integration makes the switch
 * materially harder to maintain.
 */
class SmsSender extends Component
{
    const NETGSM_URL = 'https://api.netgsm.com.tr/sms/rest/v2/send';
    const VERIMOR_URL = 'https://sms.verimor.com.tr/v2/send.json';
    const HTTP_CONNECT_TIMEOUT = 3;
    const HTTP_TIMEOUT = 10;

    /**
     * @param string $provider Configured provider identifier.
     * @param string $to E.164 phone number; international numbers are accepted.
     * @param string $message Message text. Provider-specific encoding and segment limits apply.
     * @param array $config Credentials and sender configuration for the selected provider.
     * @return array A result with success, provider, status, retryable and, on success, message_id.
     */
    public function send($provider, $to, $message, array $config, $type = 'informational')
    {
        $provider = is_string($provider) ? strtolower(trim($provider)) : '';
        if (!in_array($provider, ['netgsm', 'verimor'], true)) {
            return $this->failure(null, 'unsupported_provider');
        }

        if (!is_string($to) || !preg_match('/^\+[1-9]\d{1,14}\z/', $to)) {
            return $this->failure($provider, 'invalid_phone_number');
        }

        if (!in_array($type, ['otp', 'informational'], true)) {
            return $this->failure($provider, 'unsupported_message_type, accepted types: otp, informational');
        }

        if (!is_string($message) || trim($message) === '') {
            return $this->failure($provider, 'message_required');
        }

        switch ($provider) {
            case 'netgsm':
                return $this->sendWithNetgsm($to, $message, $config, $type);
            case 'verimor':
                return $this->sendWithVerimor($to, $message, $config, $type);
            default:
                return $this->failure(null, 'unsupported_provider');
        }
    }

    /**
     * Sends JSON over cURL so connection errors and total timeouts are observable.
     *
     * Provider credentials are sent only in the request; request bodies and
     * provider response bodies are intentionally excluded from logs/results.
     * Delivery remains synchronous but bounded; a queue needs a separate
     * persisted delivery and duplicate-reconciliation policy.
     */
    protected function postJson($url, array $payload, array $headers = [])
    {
        if (!function_exists('curl_init')) {
            Yii::error('SMS sending requires the PHP cURL extension.', __METHOD__);
            return [
                'http_code' => null,
                'body' => null,
                'transport_error' => true,
                'status' => 'not_sent',
                'retryable' => false,
                'error' => 'curl_unavailable',
            ];
        }

        $content = json_encode($payload);
        if ($content === false) {
            return [
                'http_code' => null,
                'body' => null,
                'transport_error' => false,
                'status' => 'not_sent',
                'retryable' => false,
                'error' => 'request_encoding_error',
            ];
        }

        $requestHeaders = array_merge([
            'Content-Type: application/json; charset=utf-8',
            'Accept: application/json',
        ], $headers);
        $handle = curl_init($url);
        if ($handle === false) {
            Yii::error('Unable to initialize the SMS HTTP transport.', __METHOD__);
            return [
                'http_code' => null,
                'body' => null,
                'transport_error' => true,
                'status' => 'not_sent',
                'retryable' => false,
                'error' => 'transport_initialization_error',
            ];
        }

        $configured = curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $requestHeaders,
            CURLOPT_POSTFIELDS => $content,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => self::HTTP_CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => self::HTTP_TIMEOUT,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        if (!$configured) {
            curl_close($handle);
            Yii::error('Unable to configure the SMS HTTP transport.', __METHOD__);
            return [
                'http_code' => null,
                'body' => null,
                'transport_error' => true,
                'status' => 'not_sent',
                'retryable' => false,
                'error' => 'transport_configuration_error',
            ];
        }

        $body = curl_exec($handle);
        $httpCode = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        if ($body === false) {
            $curlError = curl_errno($handle);
            $requestSize = (int) curl_getinfo($handle, CURLINFO_REQUEST_SIZE);
            $classification = $this->classifyTransportFailure($curlError, $requestSize);
            curl_close($handle);

            Yii::warning(
                'SMS transport failed; cURL error ' . $curlError . ', delivery status ' . $classification['status'],
                __METHOD__
            );

            return [
                'http_code' => $httpCode ?: null,
                'body' => null,
                'transport_error' => true,
                'status' => $classification['status'],
                'retryable' => $classification['retryable'],
                'error' => 'transport_error',
            ];
        }

        curl_close($handle);

        return [
            'http_code' => $httpCode,
            'body' => $body,
            'transport_error' => false,
            'status' => null,
            'retryable' => false,
        ];
    }

    /**
     * Classifies only errors known to happen before an HTTP request is sent as safe to retry.
     * Timeouts after request bytes were sent and other cURL failures remain ambiguous.
     */
    protected function classifyTransportFailure($curlError, $requestSize)
    {
        $retryableBeforeSend = [
            'CURLE_COULDNT_RESOLVE_PROXY',
            'CURLE_COULDNT_RESOLVE_HOST',
            'CURLE_COULDNT_CONNECT',
        ];
        foreach ($retryableBeforeSend as $errorName) {
            if (defined($errorName) && $curlError === constant($errorName)) {
                return ['status' => 'not_sent', 'retryable' => true];
            }
        }

        $permanentBeforeSend = [
            'CURLE_SSL_CONNECT_ERROR',
            'CURLE_PEER_FAILED_VERIFICATION',
            'CURLE_SSL_CACERT',
            'CURLE_SSL_ISSUER_ERROR',
        ];
        foreach ($permanentBeforeSend as $errorName) {
            if (defined($errorName) && $curlError === constant($errorName)) {
                return ['status' => 'not_sent', 'retryable' => false];
            }
        }

        if (defined('CURLE_OPERATION_TIMEDOUT') && $curlError === CURLE_OPERATION_TIMEDOUT && $requestSize === 0) {
            return ['status' => 'not_sent', 'retryable' => true];
        }

        return ['status' => 'unknown', 'retryable' => false];
    }

    private function sendWithNetgsm($to, $message, array $config, $type)
    {
        foreach (['usercode', 'password', 'msgheader'] as $key) {
            if (!isset($config[$key]) || $config[$key] === '') {
                return $this->failure('netgsm', 'provider_not_configured');
            }
        }

        $payload = [
            'msgheader' => $config['msgheader'],
            'messages' => [[
                'msg' => $message,
                'no' => $this->formatNetgsmNumber($to),
            ]],
            'encoding' => 'TR',
            // This sender is for OTP/transactional notices only. Marketing SMS
            // needs an explicit IYS-aware message type and consent handling.
            'iysfilter' => '0',
        ];
        $authorization = 'Authorization: Basic ' . base64_encode($config['usercode'] . ':' . $config['password']);
        $response = $this->postJson(self::NETGSM_URL, $payload, [$authorization]);

        if ($response['transport_error'] || !empty($response['request_error'])) {
            return $this->transportFailure('netgsm', $response);
        }

        $body = json_decode($response['body'], true);
        if ($response['http_code'] === 200 && is_array($body) && isset($body['code'])) {
            if ((string) $body['code'] === '00') {
                return $this->accepted('netgsm', isset($body['jobid']) ? (string) $body['jobid'] : null);
            }

            return $this->failure(
                'netgsm',
                'provider_rejected',
                'not_sent',
                false,
                $response['http_code'],
                $this->safeProviderCode($body['code'])
            );
        }

        return $this->classifyHttpFailure('netgsm', $response['http_code'], $body);
    }

    private function sendWithVerimor($to, $message, array $config, $type)
    {
        foreach (['username', 'password'] as $key) {
            if (!isset($config[$key]) || $config[$key] === '') {
                return $this->failure('verimor', 'provider_not_configured');
            }
        }

        $payload = [
            'username' => $config['username'],
            'password' => $config['password'],
            'messages' => [[
                'msg' => $message,
                'dest' => $this->formatVerimorNumber($to),
            ]],
        ];
        if (isset($config['source_addr']) && $config['source_addr'] !== '') {
            $payload['source_addr'] = $config['source_addr'];
        }

        // custom_id is deferred: provider-side correlation alone does not make
        // retries idempotent, and this sender has no persisted reconciliation.
        // Verimor documents automatic data-coding when the optional field is
        // omitted. Provider-specific segment limits are intentionally not
        // generalized here because the effective limit depends on encoding.
        $response = $this->postJson(self::VERIMOR_URL, $payload);
        if ($response['transport_error'] || !empty($response['request_error'])) {
            return $this->transportFailure('verimor', $response);
        }

        $responseBody = trim((string) $response['body']);
        if (
            $response['http_code'] >= 200
            && $response['http_code'] < 300
            && preg_match('/^\d+\z/', $responseBody)
        ) {
            // The numeric campaign ID confirms acceptance, not handset delivery.
            return $this->accepted('verimor', $responseBody);
        }

        return $this->classifyHttpFailure('verimor', $response['http_code'], $responseBody);
    }

    private function classifyHttpFailure($provider, $httpCode, $body)
    {
        $providerCode = $this->extractProviderCode($provider, $body);
        if ($httpCode === 429) {
            return $this->failure($provider, 'provider_rate_limited', 'not_sent', true, $httpCode, $providerCode);
        }

        if (
            $httpCode === null
            || ($httpCode >= 300 && $httpCode < 400)
            || $httpCode === 408
            || $httpCode >= 500
            || ($httpCode >= 200 && $httpCode < 300)
        ) {
            // A redirect, gateway error, timeout response or malformed success
            // response may follow provider acceptance.
            return $this->failure($provider, 'provider_outcome_unknown', 'unknown', false, $httpCode, $providerCode);
        }

        return $this->failure($provider, 'provider_rejected', 'not_sent', false, $httpCode, $providerCode);
    }

    private function extractProviderCode($provider, $body)
    {
        // Raw provider text may echo message or recipient data, so only stable
        // machine-readable codes are allowed into the caller-visible result.
        if ($provider === 'netgsm' && is_array($body) && isset($body['code'])) {
            return $this->safeProviderCode($body['code']);
        }

        if ($provider === 'verimor' && is_string($body)) {
            return $this->safeProviderCode(trim($body));
        }

        return null;
    }

    private function safeProviderCode($value)
    {
        $value = (string) $value;
        return preg_match('/^[A-Z0-9_]{1,64}\z/', $value) ? $value : null;
    }

    private function formatNetgsmNumber($to)
    {
        $number = substr($to, 1);
        if (strpos($number, '90') === 0) {
            return substr($number, 2);
        }

        return '00' . $number;
    }

    private function formatVerimorNumber($to)
    {
        $number = substr($to, 1);
        if (strpos($number, '90') === 0) {
            return $number;
        }

        return '00' . $number;
    }

    private function transportFailure($provider, array $response)
    {
        if (!empty($response['transport_error'])) {
            return $this->failure(
                $provider,
                $response['error'],
                $response['status'],
                $response['retryable'],
                $response['http_code']
            );
        }

        return $this->failure($provider, $response['error']);
    }

    private function accepted($provider, $messageId)
    {
        return [
            'success' => true,
            'provider' => $provider,
            'status' => 'accepted',
            'retryable' => false,
            'message_id' => $messageId,
        ];
    }

    private function failure($provider, $error, $status = 'not_sent', $retryable = false, $httpCode = null, $providerCode = null)
    {
        $result = [
            'success' => false,
            'provider' => $provider,
            'status' => $status,
            'retryable' => $retryable,
            'error' => $error,
        ];

        if ($httpCode !== null) {
            $result['http_code'] = $httpCode;
        }
        if ($providerCode !== null) {
            $result['provider_code'] = $providerCode;
        }

        return $result;
    }
}
