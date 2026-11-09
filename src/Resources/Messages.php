<?php

declare(strict_types=1);

namespace WazobiaMail\Resources;

use WazobiaMail\Exceptions\WazobiaException;

class Messages extends AbstractResource
{
    /**
     * Send an email message.
     *
     * @param array{
     *     from?: string,
     *     to: string|array<string>,
     *     subject: string,
     *     html?: string,
     *     text?: string,
     *     cc?: string|array<string>,
     *     bcc?: string|array<string>,
     *     replyTo?: string,
     *     templateId?: string,
     *     templateVariables?: array<string, mixed>,
     *     attachments?: array<int, array{filename: string, content: string, contentType?: string}>
     * } $params
     * @param string|null $idempotencyKey Optional unique key to prevent duplicate sends on network retries
     * @return array The API response data
     * @throws WazobiaException
     */
    public function send(array $params, ?string $idempotencyKey = null): array
    {
        $headers = [];
        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        $response = $this->http->post('/sending/messages', $params, $headers);
        return $response->getData() ?? [];
    }

    /**
     * Send an email with raw file uploads (multipart).
     *
     * @param array<string, mixed> $params
     * @param array<int, array{name: string, path: string, filename?: string}> $files
     * @param string|null $idempotencyKey
     * @return array
     * @throws WazobiaException
     */
    public function sendWithAttachments(array $params, array $files = [], ?string $idempotencyKey = null): array
    {
        $headers = [];
        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        $multipart = [];
        foreach ($params as $key => $value) {
            $multipart[] = [
                'name' => $key,
                'contents' => is_array($value) ? json_encode($value) : (string) $value,
            ];
        }

        foreach ($files as $file) {
            $multipart[] = [
                'name' => 'files',
                'contents' => fopen($file['path'], 'r'),
                'filename' => $file['filename'] ?? basename($file['path']),
            ];
        }

        $response = $this->http->postMultipart('/sending/messages/with-attachments', $multipart, $headers);
        return $response->getData() ?? [];
    }

    /**
     * Check spam and deliverability score before sending.
     *
     * @param array{
     *     from?: string,
     *     subject: string,
     *     html?: string,
     *     text?: string
     * } $params
     * @return array
     * @throws WazobiaException
     */
    public function checkSpam(array $params): array
    {
        $response = $this->http->post('/sending/spam-check', $params);
        return $response->getData() ?? [];
    }
}
