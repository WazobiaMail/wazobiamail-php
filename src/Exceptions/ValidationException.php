<?php

declare(strict_types=1);

namespace WazobiaMail\Exceptions;

use Throwable;

class ValidationException extends WazobiaException
{
    /**
     * @param array<string, mixed> $errors
     */
    public function __construct(
        string $message = 'Validation failed',
        int $code = 422,
        ?Throwable $previous = null,
        ?array $responseBody = null,
        protected array $errors = [],
    ) {
        parent::__construct($message, $code, $previous, $responseBody);
    }

    /**
     * @return array<string, mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
