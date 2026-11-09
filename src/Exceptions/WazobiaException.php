<?php

declare(strict_types=1);

namespace WazobiaMail\Exceptions;

use Exception;
use Throwable;

class WazobiaException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        protected ?array $responseBody = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }
}
