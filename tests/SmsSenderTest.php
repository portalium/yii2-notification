<?php

namespace portalium\notification\tests;

use PHPUnit\Framework\TestCase;
use portalium\notification\components\SmsSender;

class SmsSenderTest extends TestCase
{
    public function testNetgsmUsesExpectedRequestAndReturnsAcceptedJob()
    {
        $sender = new TestableSmsSender([
            'http_code' => 200,
            'body' => '{"code":"00","jobid":"12345"}',
            'transport_error' => false,
        ]);

        $result = $sender->send('netgsm', '+905311234567', 'Doğrulama kodunuz 123456', [
            'usercode' => 'user',
            'password' => 'secret',
            'msgheader' => 'YAZICI',
        ]);

        $this->assertSame([
            'success' => true,
            'provider' => 'netgsm',
            'status' => 'accepted',
            'retryable' => false,
            'message_id' => '12345',
        ], $result);
        $this->assertSame(SmsSender::NETGSM_URL, $sender->url);
        $this->assertSame('5311234567', $sender->payload['messages'][0]['no']);
        $this->assertSame('TR', $sender->payload['encoding']);
        $this->assertSame('0', $sender->payload['iysfilter']);
        $this->assertStringStartsWith('Authorization: Basic ', $sender->headers[0]);
    }

    public function testVerimorUsesJsonCredentialsAndReturnsCampaignId()
    {
        $sender = new TestableSmsSender([
            'http_code' => 200,
            'body' => '20210',
            'transport_error' => false,
        ]);

        $result = $sender->send('verimor', '+905311234567', 'Türkçe mesaj: ığüşöçİ', [
            'username' => 'account',
            'password' => 'secret',
            'source_addr' => 'YAZICI',
        ]);

        $this->assertSame('20210', $result['message_id']);
        $this->assertSame(SmsSender::VERIMOR_URL, $sender->url);
        $this->assertSame('905311234567', $sender->payload['messages'][0]['dest']);
        $this->assertSame('account', $sender->payload['username']);
        $this->assertSame('secret', $sender->payload['password']);
        $this->assertSame('YAZICI', $sender->payload['source_addr']);
        $this->assertSame('Türkçe mesaj: ığüşöçİ', $sender->payload['messages'][0]['msg']);
        $this->assertArrayNotHasKey('datacoding', $sender->payload);
    }

    public function testInvalidPhoneDoesNotSendRequest()
    {
        $sender = new TestableSmsSender([]);

        $result = $sender->send('netgsm', '05311234567', 'Test message', []);

        $this->assertFalse($result['success']);
        $this->assertSame('invalid_phone_number', $result['error']);
        $this->assertNull($sender->url);
    }

    public function testProviderRejectionDoesNotExposeRawResponse()
    {
        $sender = new TestableSmsSender([
            'http_code' => 400,
            'body' => 'INSUFFICIENT_CREDITS',
            'transport_error' => false,
        ]);

        $result = $sender->send('verimor', '+905311234567', 'Test message', [
            'username' => 'account',
            'password' => 'secret',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('not_sent', $result['status']);
        $this->assertFalse($result['retryable']);
        $this->assertSame('INSUFFICIENT_CREDITS', $result['provider_code']);
        $this->assertArrayNotHasKey('body', $result);
    }

    public function testProviderRateLimitIsSafeToRetry()
    {
        $sender = new TestableSmsSender([
            'http_code' => 429,
            'body' => 'RATE_LIMIT',
            'transport_error' => false,
        ]);

        $result = $sender->send('verimor', '+905311234567', 'Test message', [
            'username' => 'account',
            'password' => 'secret',
        ]);

        $this->assertSame('not_sent', $result['status']);
        $this->assertTrue($result['retryable']);
    }

    public function testGatewayFailureIsAmbiguousAndMustNotBeRetriedAutomatically()
    {
        $sender = new TestableSmsSender([
            'http_code' => 503,
            'body' => 'temporarily unavailable',
            'transport_error' => false,
        ]);

        $result = $sender->send('verimor', '+905311234567', 'Test message', [
            'username' => 'account',
            'password' => 'secret',
        ]);

        $this->assertSame('unknown', $result['status']);
        $this->assertFalse($result['retryable']);
        $this->assertArrayNotHasKey('provider_code', $result);
    }

    public function testRedirectResponseIsAmbiguous()
    {
        $sender = new TestableSmsSender([
            'http_code' => 302,
            'body' => '',
            'transport_error' => false,
        ]);

        $result = $sender->send('verimor', '+905311234567', 'Test message', [
            'username' => 'account',
            'password' => 'secret',
        ]);

        $this->assertSame('unknown', $result['status']);
        $this->assertFalse($result['retryable']);
    }

    public function testUnparseableSuccessResponseIsAmbiguous()
    {
        $sender = new TestableSmsSender([
            'http_code' => 200,
            'body' => 'unexpected provider response',
            'transport_error' => false,
        ]);

        $result = $sender->send('verimor', '+905311234567', 'Test message', [
            'username' => 'account',
            'password' => 'secret',
        ]);

        $this->assertSame('unknown', $result['status']);
        $this->assertFalse($result['retryable']);
    }

    public function testUnknownProviderIsNotReturnedOrDispatched()
    {
        $sender = new TestableSmsSender([]);

        $result = $sender->send('attacker-controlled-provider', '+905311234567', 'Test', []);

        $this->assertNull($result['provider']);
        $this->assertSame('unsupported_provider', $result['error']);
        $this->assertNull($sender->url);
    }

    public function testTransportClassificationKeepsPreSendFailuresDistinctFromAmbiguousFailures()
    {
        $sender = new TestableSmsSender([]);

        $this->assertSame(
            ['status' => 'not_sent', 'retryable' => true],
            $sender->classifyForTest(CURLE_COULDNT_CONNECT, 0)
        );
        $this->assertSame(
            ['status' => 'not_sent', 'retryable' => false],
            $sender->classifyForTest(CURLE_PEER_FAILED_VERIFICATION, 0)
        );
        $this->assertSame(
            ['status' => 'not_sent', 'retryable' => true],
            $sender->classifyForTest(CURLE_OPERATION_TIMEDOUT, 0)
        );
        $this->assertSame(
            ['status' => 'unknown', 'retryable' => false],
            $sender->classifyForTest(CURLE_OPERATION_TIMEDOUT, 128)
        );
        $this->assertSame(
            ['status' => 'unknown', 'retryable' => false],
            $sender->classifyForTest(CURLE_RECV_ERROR, 128)
        );
    }
}

class TestableSmsSender extends SmsSender
{
    public $response;
    public $url;
    public $payload;
    public $headers;

    public function __construct(array $response)
    {
        $this->response = $response;
    }

    protected function postJson($url, array $payload, array $headers = [])
    {
        $this->url = $url;
        $this->payload = $payload;
        $this->headers = $headers;

        return $this->response;
    }

    public function classifyForTest($curlError, $requestSize)
    {
        return $this->classifyTransportFailure($curlError, $requestSize);
    }
}

foreach ([
    'CURLE_COULDNT_CONNECT' => 7,
    'CURLE_PEER_FAILED_VERIFICATION' => 60,
    'CURLE_OPERATION_TIMEDOUT' => 28,
    'CURLE_RECV_ERROR' => 56,
] as $constant => $value) {
    if (!defined($constant)) {
        define($constant, $value);
    }
}
