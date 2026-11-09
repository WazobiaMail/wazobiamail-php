<?php

declare(strict_types=1);

namespace WazobiaMail\Tests;

use WazobiaMail\Exceptions\AuthenticationException;
use WazobiaMail\Exceptions\ForbiddenException;
use WazobiaMail\Exceptions\NotFoundException;
use WazobiaMail\Exceptions\RateLimitException;
use WazobiaMail\Exceptions\ServerException;
use WazobiaMail\Exceptions\ValidationException;

class ExceptionsTest extends TestCase
{
    public function test401ThrowsAuthenticationException(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(401, ['message' => 'Invalid API key']),
        ]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid API key');
        $client->domains()->list();
    }

    public function test403ThrowsForbiddenException(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(403, ['message' => 'Insufficient permissions']),
        ]);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('Insufficient permissions');
        $client->mailboxes()->list();
    }

    public function test404ThrowsNotFoundException(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(404, ['message' => 'Mailbox not found']),
        ]);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Mailbox not found');
        $client->mailboxes()->get('non_existent');
    }

    public function test422ThrowsValidationExceptionWithErrors(): void
    {
        $errors = [
            'to' => 'Recipient email address is required',
            'subject' => 'Subject line cannot be empty',
        ];

        $client = $this->createMockClient([
            $this->jsonResponse(422, [
                'message' => 'Validation error',
                'errors' => $errors,
            ]),
        ]);

        try {
            $client->messages()->send([]);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->getCode());
            $this->assertSame($errors, $e->getErrors());
        }
    }

    public function test429ThrowsRateLimitExceptionWithRetryAfter(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(429, ['message' => 'Rate limit exceeded'], ['Retry-After' => '60']),
        ]);

        try {
            $client->domains()->list();
            $this->fail('Expected RateLimitException was not thrown');
        } catch (RateLimitException $e) {
            $this->assertSame(429, $e->getCode());
            $this->assertSame(60, $e->getRetryAfter());
        }
    }

    public function test500ThrowsServerException(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(500, ['message' => 'Internal server error']),
        ]);

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Internal server error');
        $client->domains()->list();
    }
}
