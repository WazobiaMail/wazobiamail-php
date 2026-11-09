<?php

declare(strict_types=1);

namespace WazobiaMail\Tests;

class MailboxesTest extends TestCase
{
    public function testCreateMailbox(): void
    {
        $mailbox = [
            'id' => 'box_123',
            'email' => 'support@example.com',
            'displayName' => 'Support Team',
        ];

        $client = $this->createMockClient([
            $this->jsonResponse(201, ['success' => true, 'data' => $mailbox]),
        ]);

        $result = $client->mailboxes()->create([
            'domainId' => 'dom_123',
            'localPart' => 'support',
            'displayName' => 'Support Team',
        ]);

        $this->assertSame($mailbox, $result);
        $request = $this->getLastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/mailboxes', $request->getUri()->getPath());
    }

    public function testGetConnectionSettings(): void
    {
        $connection = [
            'username' => 'support@example.com',
            'imap' => ['host' => 'imap.wazobiamail.com', 'port' => 993],
            'smtp' => ['host' => 'smtp.wazobiamail.com', 'port' => 587],
        ];

        $client = $this->createMockClient([
            $this->jsonResponse(200, ['success' => true, 'data' => $connection]),
        ]);

        $result = $client->mailboxes()->getConnection('box_123');
        $this->assertSame($connection, $result);
        $this->assertSame('/api/v1/mailboxes/box_123/connection', $this->getLastRequest()->getUri()->getPath());
    }
}
