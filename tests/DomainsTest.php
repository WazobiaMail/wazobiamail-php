<?php

declare(strict_types=1);

namespace WazobiaMail\Tests;

class DomainsTest extends TestCase
{
    public function testListDomains(): void
    {
        $domains = [
            ['id' => 'dom_1', 'name' => 'example.com', 'isVerified' => true],
            ['id' => 'dom_2', 'name' => 'sub.example.com', 'isVerified' => false],
        ];

        $client = $this->createMockClient([
            $this->jsonResponse(200, ['success' => true, 'data' => $domains]),
        ]);

        $result = $client->domains()->list();
        $this->assertSame($domains, $result);
        $this->assertSame('/api/v1/sending/domains', $this->getLastRequest()->getUri()->getPath());
    }

    public function testGetDnsRecords(): void
    {
        $dnsData = [
            'domain' => [
                'id' => 'dom_1',
                'spfRecord' => 'v=spf1 include:relay.wazobiamail.com ~all',
                'dkimHost' => 'wazobiamail._domainkey.example.com',
                'dkimValue' => 'v=DKIM1; k=rsa; p=MIGf...',
            ],
        ];

        $client = $this->createMockClient([
            $this->jsonResponse(200, ['success' => true, 'data' => $dnsData]),
        ]);

        $result = $client->domains()->getDnsRecords('dom_1');
        $this->assertSame($dnsData, $result);
        $this->assertSame('/api/v1/sending/domains/dom_1/dns-records', $this->getLastRequest()->getUri()->getPath());
    }

    public function testVerifyDomain(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(200, ['success' => true, 'data' => ['isVerified' => true]]),
        ]);

        $result = $client->domains()->verify('dom_1', true);
        $this->assertTrue($result['isVerified']);

        $request = $this->getLastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/v1/sending/domains/dom_1/verify', $request->getUri()->getPath());
        $body = json_decode((string) $request->getBody(), true);
        $this->assertTrue($body['force']);
    }

    public function testCloudflareConnectUsesGet(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(200, ['success' => true, 'data' => ['authUrl' => 'https://cloudflare.com/oauth']]),
        ]);

        $result = $client->domains()->connectCloudflare();
        $this->assertSame('https://cloudflare.com/oauth', $result['authUrl']);

        $request = $this->getLastRequest();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/api/v1/sending/domains/cloudflare/connect', $request->getUri()->getPath());
    }
}
