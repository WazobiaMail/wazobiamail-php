<?php

declare(strict_types=1);

namespace WazobiaMail\Exceptions;

use Throwable;

class RateLimitException extends WazobiaException
{
    public function __construct(
        string $message = 'Too many requests',
        int $code = 429,
        ?Throwable $previous = null,
        ?array $responseBody = null,
        protected ?int $retryAfter = null,
    ) {
        parent::__construct($message, $code, $previous, $responseBody);
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
