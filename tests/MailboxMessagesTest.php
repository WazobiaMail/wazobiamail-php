<?php

declare(strict_types=1);

namespace WazobiaMail\Tests;

use WazobiaMail\Exceptions\ForbiddenException;

class MailboxMessagesTest extends TestCase
{
    public function testListInboxMessages(): void
    {
        $messages = [
            'items' => [
                [
                    'id' => 'msg_1',
                    'subject' => 'Welcome',
                    'from' => [['name' => 'Alice', 'email' => 'alice@test.invalid']],
                    'preview' => 'Hello team...',
                    'isUnread' => true,
                ],
            ],
            'total' => 1,
            'page' => 1,
            'limit' => 20,
        ];

        $client = $this->createMockClient([
            $this->jsonResponse(200, ['success' => true, 'data' => $messages]),
        ]);

        $result = $client->mailboxMessages()->list('box_123', ['page' => 1, 'limit' => 20, 'unreadOnly' => true]);

        $this->assertSame($messages, $result);
        $request = $this->getLastRequest();
        $this->assertSame('/api/v1/mailboxes/box_123/messages', $request->getUri()->getPath());
        $this->assertSame('page=1&limit=20&unreadOnly=1', $request->getUri()->getQuery());
    }

    public function testGetInboxMessageDetail(): void
    {
        $messageDetail = [
            'id' => 'msg_1',
            'subject' => 'Invoice Receipt',
            'htmlBody' => '<p>Invoice details...</p>',
            'textBody' => 'Invoice details...',
            'attachments' => [
                ['id' => 'att_1', 'name' => 'invoice.pdf', 'sizeBytes' => 10240],
            ],
        ];

        $client = $this->createMockClient([
            $this->jsonResponse(200, ['success' => true, 'data' => $messageDetail]),
        ]);

        $result = $client->mailboxMessages()->get('box_123', 'msg_1');
        $this->assertSame($messageDetail, $result);
        $this->assertSame('/api/v1/mailboxes/box_123/messages/msg_1', $this->getLastRequest()->getUri()->getPath());
    }

    public function testDeleteInboxMessage(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(200, ['success' => true, 'message' => 'Message deleted']),
        ]);

        $result = $client->mailboxMessages()->delete('box_123', 'msg_1');
        $request = $this->getLastRequest();
        $this->assertSame('DELETE', $request->getMethod());
        $this->assertSame('/api/v1/mailboxes/box_123/messages/msg_1', $request->getUri()->getPath());
    }

    public function testEnterpriseGatingThrowsForbiddenException(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(403, [
                'error' => 'Forbidden',
                'message' => 'This endpoint requires an active Enterprise plan.',
            ]),
        ]);

        try {
            $client->mailboxMessages()->list('box_123');
            $this->fail('Expected ForbiddenException was not thrown');
        } catch (ForbiddenException $e) {
            $this->assertSame(403, $e->getCode());
            $this->assertTrue($e->isEnterprisePlanRequired());
            $this->assertStringContainsString('Enterprise plan', $e->getMessage());
        }
    }
}
