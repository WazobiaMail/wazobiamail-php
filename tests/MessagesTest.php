<?php

declare(strict_types=1);

namespace WazobiaMail\Tests;

class MessagesTest extends TestCase
{
    public function testSendEmail(): void
    {
        $expectedData = [
            'id' => 'msg_01hxy12345',
            'status' => 'queued',
        ];

        $client = $this->createMockClient([
            $this->jsonResponse(200, [
                'success' => true,
                'message' => 'Email queued',
                'data' => $expectedData,
            ]),
        ]);

        $payload = [
            'from' => 'billing@example.com',
            'to' => 'customer@example.com',
            'subject' => 'November Invoice',
            'html' => '<p>Please find attached invoice.</p>',
            'text' => 'Please find attached invoice.',
        ];

        $result = $client->messages()->send($payload, 'idem_unique_key_123');

        $this->assertSame($expectedData, $result);

        $request = $this->getLastRequest();
        $this->assertNotNull($request);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/sending/messages', $request->getUri()->getPath());
        $this->assertSame(['idem_unique_key_123'], $request->getHeader('Idempotency-Key'));

        $sentBody = json_decode((string) $request->getBody(), true);
        $this->assertSame('billing@example.com', $sentBody['from']);
        $this->assertSame('customer@example.com', $sentBody['to']);
        $this->assertSame('November Invoice', $sentBody['subject']);
    }

    public function testCheckSpam(): void
    {
        $expectedData = [
            'score' => 0.1,
            'isSpam' => false,
            'rules' => [],
        ];

        $client = $this->createMockClient([
            $this->jsonResponse(200, [
                'success' => true,
                'data' => $expectedData,
            ]),
        ]);

        $result = $client->messages()->checkSpam([
            'from' => 'info@example.com',
            'subject' => 'Weekly Newsletter',
            'html' => '<p>Weekly update...</p>',
        ]);

        $this->assertSame($expectedData, $result);
        $request = $this->getLastRequest();
        $this->assertSame('/api/v1/sending/spam-check', $request->getUri()->getPath());
    }
}
