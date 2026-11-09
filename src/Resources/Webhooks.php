<?php

declare(strict_types=1);

namespace WazobiaMail\Resources;

use WazobiaMail\Exceptions\AuthenticationException;
use WazobiaMail\Exceptions\ValidationException;

class Webhooks
{
    /**
     * Verify the HMAC SHA-256 signature of an incoming WazobiaMail webhook.
     *
     * @param string $payload The raw request body
     * @param string $signature The signature header received (e.g. from X-Wazobia-Signature)
     * @param string $secret The webhook signing secret configured in your dashboard
     * @return bool True if valid, false otherwise
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        if ($signature === '' || $secret === '') {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify the webhook signature and decode the JSON event payload.
     *
     * @param string $payload The raw request body
     * @param string $signature The signature header received
     * @param string $secret The webhook signing secret
     * @return array Decoded event data
     * @throws AuthenticationException If signature verification fails
     * @throws ValidationException If JSON decoding fails
     */
    public function constructEvent(string $payload, string $signature, string $secret): array
    {
        if (!$this->verifySignature($payload, $signature, $secret)) {
            throw new AuthenticationException('Invalid webhook signature');
        }

        $decoded = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw new ValidationException('Invalid JSON payload in webhook');
        }

        return $decoded;
    }
}
