<?php

declare(strict_types=1);

namespace WazobiaMail\Resources;

use WazobiaMail\Exceptions\ValidationException;
use WazobiaMail\Exceptions\WazobiaException;

class Messages extends AbstractResource
{
    /**
     * Send an email message.
     *
     * Accepts the ergonomic flat shape (`from`, `to`, `subject`, `html`, `text`,
     * `replyTo`, `attachments`) as well as the canonical nested `content` shape
     * used by the WazobiaMail API.
     *
     * Inline (base64) attachments are automatically uploaded through the
     * multipart send endpoint because the JSON endpoint only accepts URL
     * attachments.
     *
     * @param array<string, mixed> $params
     * @param string|null $idempotencyKey Optional unique key to prevent duplicate sends on network retries
     * @return array The API response data
     * @throws WazobiaException
     */
    public function send(array $params, ?string $idempotencyKey = null): array
    {
        $payload = $this->normalizePayload($params);

        $inline = [];
        if (!empty($payload['attachments'])) {
            [$urlAttachments, $inline] = $this->splitAttachments($payload['attachments']);
            if ($urlAttachments === []) {
                unset($payload['attachments']);
            } else {
                $payload['attachments'] = $urlAttachments;
            }
        }

        if ($inline !== []) {
            return $this->sendMultipart($payload, $inline, $idempotencyKey);
        }

        $headers = [];
        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        $response = $this->http->post('/sending/messages', $payload, $headers);
        return $response->getData() ?? [];
    }

    /**
     * Send an email with raw file uploads (multipart).
     *
     * Each file may be provided as:
     * - `['path' => '/tmp/file.pdf', 'filename' => 'invoice.pdf']`
     * - `['contents' => $binary, 'filename' => 'invoice.pdf', 'contentType' => 'application/pdf']`
     *
     * @param array<string, mixed> $params
     * @param array<int, array<string, mixed>> $files
     * @param string|null $idempotencyKey
     * @return array
     * @throws WazobiaException
     */
    public function sendWithAttachments(array $params, array $files = [], ?string $idempotencyKey = null): array
    {
        $payload = $this->normalizePayload($params);

        $urlAttachments = [];
        if (!empty($payload['attachments'])) {
            [$urlAttachments, $inlineFromPayload] = $this->splitAttachments($payload['attachments']);
            $files = array_merge($inlineFromPayload, $files);
        }
        if ($urlAttachments !== []) {
            $payload['attachments'] = $urlAttachments;
        } else {
            unset($payload['attachments']);
        }

        $normalizedFiles = [];
        foreach ($files as $file) {
            $normalizedFiles[] = $this->normalizeFile($file);
        }

        if ($normalizedFiles === []) {
            throw new ValidationException('At least one attachment is required');
        }

        return $this->sendMultipart($payload, $normalizedFiles, $idempotencyKey);
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

    /**
     * Normalize flat and nested inputs into the API's canonical send payload.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    protected function normalizePayload(array $params): array
    {
        $nested = isset($params['content']) && is_array($params['content'])
            ? $params['content']
            : [];

        $recipients = $params['to'] ?? $params['recipients'] ?? $nested['to'] ?? [];
        $content = [
            'subject' => (string) ($nested['subject'] ?? $params['subject'] ?? ''),
        ];

        $from = $nested['from'] ?? $params['from'] ?? $params['from_email'] ?? $params['sender'] ?? null;
        if ($from !== null && $from !== '') {
            $content['from'] = (string) $from;
        }

        $html = $nested['htmlBody'] ?? $nested['html_body']
            ?? $params['html'] ?? $params['htmlBody'] ?? $params['html_body'] ?? null;
        if ($html !== null) {
            $content['htmlBody'] = (string) $html;
        }

        $text = $nested['textBody'] ?? $nested['text_body']
            ?? $params['text'] ?? $params['textBody'] ?? $params['text_body'] ?? null;
        if ($text !== null) {
            $content['textBody'] = (string) $text;
        }

        $replyTo = $nested['replyTo'] ?? $nested['reply_to'] ?? $params['replyTo'] ?? $params['reply_to'] ?? null;
        if ($replyTo !== null && $replyTo !== '') {
            $content['replyTo'] = (string) $replyTo;
        }

        $payload = [
            'to' => $this->recipientList($recipients),
            'cc' => $this->recipientList($params['cc'] ?? $nested['cc'] ?? []),
            'bcc' => $this->recipientList($params['bcc'] ?? $nested['bcc'] ?? []),
            'content' => $content,
        ];

        $envelope = $params['envelopeSender'] ?? $params['envelope_sender'] ?? null;
        if ($envelope !== null && $envelope !== '') {
            $payload['envelopeSender'] = (string) $envelope;
        }

        if (!empty($params['attachments'])) {
            $payload['attachments'] = is_array($params['attachments'])
                ? array_values($params['attachments'])
                : [$params['attachments']];
        }

        return $payload;
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    protected function recipientList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_string($value)) {
            return array_values(array_filter(array_map('trim', explode(',', $value)), static fn ($v) => $v !== ''));
        }
        if (is_array($value)) {
            return array_values(array_map('strval', $value));
        }

        return [(string) $value];
    }

    /**
     * @param array<int, mixed> $attachments
     * @return array{0: array<int, array{url: string, fileName: string}>, 1: array<int, array{contents: string, filename: string, contentType: string}>}
     */
    protected function splitAttachments(array $attachments): array
    {
        $urls = [];
        $inline = [];

        foreach ($attachments as $attachment) {
            if (!is_array($attachment)) {
                continue;
            }

            $url = $attachment['url'] ?? null;
            if (is_string($url) && $url !== '') {
                $name = $attachment['fileName'] ?? $attachment['file_name'] ?? $attachment['filename'] ?? basename(parse_url($url, PHP_URL_PATH) ?: 'attachment');
                $urls[] = ['url' => $url, 'fileName' => (string) $name];
                continue;
            }

            $contents = $attachment['contents'] ?? $attachment['content'] ?? null;
            if (is_string($contents) && $contents !== '') {
                if (isset($attachment['contentType']) || isset($attachment['content_type']) || isset($attachment['filename']) || isset($attachment['fileName'])) {
                    $inline[] = $this->normalizeFile($attachment);
                    continue;
                }
                // A bare base64 string is treated as inline file content.
                $inline[] = [
                    'contents' => base64_decode($contents, true) ?: $contents,
                    'filename' => 'attachment',
                    'contentType' => 'application/octet-stream',
                ];
            }
        }

        return [$urls, $inline];
    }

    /**
     * @param array<string, mixed> $file
     * @return array{contents: string, filename: string, contentType: string}
     */
    protected function normalizeFile(array $file): array
    {
        $name = (string) ($file['filename'] ?? $file['fileName'] ?? $file['name'] ?? 'attachment');
        $type = (string) ($file['contentType'] ?? $file['content_type'] ?? 'application/octet-stream');

        if (isset($file['contents']) && is_string($file['contents'])) {
            $contents = $file['contents'];
        } elseif (isset($file['content']) && is_string($file['content'])) {
            $contents = base64_decode($file['content'], true);
            if ($contents === false) {
                $contents = $file['content'];
            }
        } elseif (isset($file['path']) && is_string($file['path'])) {
            if (!is_readable($file['path'])) {
                throw new ValidationException(sprintf('Attachment is not readable: %s', $file['path']));
            }
            $contents = file_get_contents($file['path']);
            if ($contents === false) {
                throw new ValidationException(sprintf('Unable to read attachment: %s', $file['path']));
            }
            if (!isset($file['filename'])) {
                $name = basename($file['path']);
            }
        } else {
            throw new ValidationException('Attachment must contain a path, contents, or base64 content');
        }

        if ($contents === '') {
            throw new ValidationException('Attachment content must not be empty');
        }

        return ['contents' => $contents, 'filename' => $name, 'contentType' => $type];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, array{contents: string, filename: string, contentType: string}> $files
     * @param string|null $idempotencyKey
     * @return array
     * @throws WazobiaException
     */
    protected function sendMultipart(array $payload, array $files, ?string $idempotencyKey = null): array
    {
        $multipart = [
            [
                'name' => 'message',
                'contents' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'headers' => ['Content-Type' => 'application/json'],
            ],
        ];

        foreach ($files as $file) {
            $multipart[] = [
                'name' => 'files',
                'contents' => $file['contents'],
                'filename' => $file['filename'],
                'headers' => ['Content-Type' => $file['contentType']],
            ];
        }

        $headers = [];
        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        $response = $this->http->postMultipart('/sending/messages/with-attachments', $multipart, $headers);
        return $response->getData() ?? [];
    }
}
