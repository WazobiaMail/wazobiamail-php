<?php

declare(strict_types=1);

namespace WazobiaMail\Tests;

class MessagesTest extends TestCase
{
    public function testSendEmail(): void
    {
        $expectedData = [
            'result' => [
                'trackingId' => 'trk_01hxy12345',
                'status' => 'queued',
            ],
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
            'replyTo' => 'support@example.com',
        ];

        $result = $client->messages()->send($payload, 'idem_unique_key_123');

        $this->assertSame($expectedData, $result);

        $request = $this->getLastRequest();
        $this->assertNotNull($request);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/sending/messages', $request->getUri()->getPath());
        $this->assertSame(['idem_unique_key_123'], $request->getHeader('Idempotency-Key'));

        $sentBody = json_decode((string) $request->getBody(), true);
        $this->assertSame(['customer@example.com'], $sentBody['to']);
        $this->assertArrayNotHasKey('subject', $sentBody);
        $this->assertSame('November Invoice', $sentBody['content']['subject']);
        $this->assertSame('billing@example.com', $sentBody['content']['from']);
        $this->assertSame('<p>Please find attached invoice.</p>', $sentBody['content']['htmlBody']);
        $this->assertSame('Please find attached invoice.', $sentBody['content']['textBody']);
        $this->assertSame('support@example.com', $sentBody['content']['replyTo']);
    }

    public function testSendUrlAttachmentStaysJson(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(200, ['success' => true, 'data' => ['result' => ['trackingId' => 'trk_1']]]),
        ]);

        $client->messages()->send([
            'to' => ['customer@example.com'],
            'subject' => 'Invoice',
            'html' => '<p>Invoice</p>',
            'attachments' => [
                ['url' => 'https://cdn.example.com/invoice.pdf', 'filename' => 'invoice.pdf'],
            ],
        ]);

        $request = $this->getLastRequest();
        $this->assertSame('/api/v1/sending/messages', $request->getUri()->getPath());
        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame(
            [['url' => 'https://cdn.example.com/invoice.pdf', 'fileName' => 'invoice.pdf']],
            $body['attachments']
        );
    }

    public function testSendInlineAttachmentUsesMultipartEndpoint(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(200, ['success' => true, 'data' => ['result' => ['trackingId' => 'trk_2']]]),
        ]);

        $contents = 'Invoice PDF Content Sample';
        $client->messages()->send([
            'to' => 'customer@example.com',
            'subject' => 'Invoice',
            'html' => '<p>Invoice</p>',
            'attachments' => [
                [
                    'filename' => 'invoice.pdf',
                    'content' => base64_encode($contents),
                    'contentType' => 'application/pdf',
                ],
            ],
        ], 'idem_attachment_key');

        $request = $this->getLastRequest();
        $this->assertSame('/api/v1/sending/messages/with-attachments', $request->getUri()->getPath());
        $this->assertStringStartsWith('multipart/form-data; boundary=', $request->getHeaderLine('Content-Type'));
        $this->assertSame(['idem_attachment_key'], $request->getHeader('Idempotency-Key'));

        $body = (string) $request->getBody();
        $this->assertStringContainsString('name="message"', $body);
        $this->assertStringContainsString('"subject":"Invoice"', $body);
        $this->assertStringContainsString('name="files"; filename="invoice.pdf"', $body);
        $this->assertStringContainsString($contents, $body);
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
