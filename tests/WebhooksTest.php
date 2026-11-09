<?php

declare(strict_types=1);

namespace WazobiaMail\Tests;

use WazobiaMail\Exceptions\AuthenticationException;
use WazobiaMail\WazobiaMailClient;

class WebhooksTest extends TestCase
{
    public function testVerifySignatureSuccess(): void
    {
        $client = new WazobiaMailClient('wzbm_dummy_key');
        $payload = json_encode(['event' => 'email.delivered', 'id' => 'evt_123']) ?: '';
        $secret = 'whsec_test_secret_key';
        $signature = hash_hmac('sha256', $payload, $secret);

        $this->assertTrue($client->webhooks()->verifySignature($payload, $signature, $secret));
    }

    public function testVerifySignatureFailure(): void
    {
        $client = new WazobiaMailClient('wzbm_dummy_key');
        $payload = json_encode(['event' => 'email.bounced']) ?: '';
        $secret = 'whsec_test_secret_key';

        $this->assertFalse($client->webhooks()->verifySignature($payload, 'invalid_sig', $secret));
    }

    public function testConstructEvent(): void
    {
        $client = new WazobiaMailClient('wzbm_dummy_key');
        $eventData = ['event' => 'email.opened', 'id' => 'evt_456'];
        $payload = json_encode($eventData) ?: '';
        $secret = 'whsec_test_secret_key';
        $signature = hash_hmac('sha256', $payload, $secret);

        $parsed = $client->webhooks()->constructEvent($payload, $signature, $secret);
        $this->assertSame($eventData, $parsed);
    }

    public function testConstructEventThrowsOnInvalidSignature(): void
    {
        $client = new WazobiaMailClient('wzbm_dummy_key');
        $payload = '{"event":"email.opened"}';

        $this->expectException(AuthenticationException::class);
        $client->webhooks()->constructEvent($payload, 'bad_sig', 'secret');
    }
}
