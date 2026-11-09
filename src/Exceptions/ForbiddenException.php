<?php

declare(strict_types=1);

namespace WazobiaMail\Exceptions;

class ForbiddenException extends WazobiaException
{
    /**
     * Determine if this 403 Forbidden error was caused by an account tier requiring an Enterprise subscription.
     */
    public function isEnterprisePlanRequired(): bool
    {
        $message = strtolower($this->getMessage());
        if (str_contains($message, 'enterprise')) {
            return true;
        }

        if (is_array($this->responseBody)) {
            $bodyStr = strtolower(json_encode($this->responseBody) ?: '');
            return str_contains($bodyStr, 'enterprise');
        }

        return false;
    }
}
