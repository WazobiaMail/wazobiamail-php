<?php

declare(strict_types=1);

namespace WazobiaMail\Http;

use Psr\Http\Message\ResponseInterface;

class Response
{
    protected ?array $decoded = null;

    public function __construct(
        protected int $statusCode,
        protected array $headers,
        protected string $rawBody,
    ) {
        $decoded = json_decode($this->rawBody, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $this->decoded = $decoded;
        }
    }

    public static function fromPsrResponse(ResponseInterface $response): self
    {
        return new self(
            $response->getStatusCode(),
            $response->getHeaders(),
            (string) $response->getBody()
        );
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getRawBody(): string
    {
        return $this->rawBody;
    }

    public function isSuccess(): bool
    {
        return ($this->decoded['success'] ?? false) === true;
    }

    public function getMessage(): ?string
    {
        return $this->decoded['message'] ?? null;
    }

    /**
     * Get the unwrapped payload `data` from the API response envelope.
     */
    public function getData(): mixed
    {
        if ($this->decoded !== null && array_key_exists('data', $this->decoded)) {
            return $this->decoded['data'];
        }

        return $this->decoded;
    }

    /**
     * Get the entire decoded response envelope.
     */
    public function toArray(): ?array
    {
        return $this->decoded;
    }
}
