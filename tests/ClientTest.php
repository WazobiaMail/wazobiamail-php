<?php

declare(strict_types=1);

namespace WazobiaMail\Tests;

use WazobiaMail\Configuration;
use WazobiaMail\Exceptions\AuthenticationException;
use WazobiaMail\WazobiaMailClient;

class ClientTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('WAZOBIA_API_KEY');
        putenv('WAZOBIAMAIL_API_KEY');
        unset($_ENV['WAZOBIA_API_KEY'], $_ENV['WAZOBIAMAIL_API_KEY']);
        parent::tearDown();
    }

    public function testConstructWithStringApiKey(): void
    {
        $client = new WazobiaMailClient('wzbm_live_abc123');
        $this->assertSame('wzbm_live_abc123', $client->getConfiguration()->getApiKey());
        $this->assertSame('https://app.wazobiamail.com/api/v1/', $client->getConfiguration()->getBaseUrl());
    }

    public function testConstructWithArrayOptions(): void
    {
        $client = new WazobiaMailClient([
            'apiKey' => '  wzbm_custom_key  ',
            'baseUrl' => 'https://custom.wazobiamail.com/api/v1/',
            'timeout' => 45.5,
        ]);

        $this->assertSame('wzbm_custom_key', $client->getConfiguration()->getApiKey());
        $this->assertSame('https://custom.wazobiamail.com/api/v1/', $client->getConfiguration()->getBaseUrl());
        $this->assertSame(45.5, $client->getConfiguration()->getTimeout());
    }

    public function testConstructWithConfigurationObject(): void
    {
        $config = new Configuration(['apiKey' => 'wzbm_from_config']);
        $client = new WazobiaMailClient($config);

        $this->assertSame('wzbm_from_config', $client->getConfiguration()->getApiKey());
    }

    public function testConstructFromWazobiaApiKeyEnv(): void
    {
        putenv('WAZOBIA_API_KEY=wzbm_from_env_1');
        $client = new WazobiaMailClient();

        $this->assertSame('wzbm_from_env_1', $client->getConfiguration()->getApiKey());
    }

    public function testConstructFromWazobiamailApiKeyEnv(): void
    {
        putenv('WAZOBIAMAIL_API_KEY=wzbm_from_env_2');
        $client = new WazobiaMailClient();

        $this->assertSame('wzbm_from_env_2', $client->getConfiguration()->getApiKey());
    }

    public function testConstructThrowsWhenNoApiKeyProvided(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('WazobiaMail API key is required');

        new WazobiaMailClient();
    }

    public function testRequestIncludesRequiredHeaders(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(200, ['success' => true, 'data' => []]),
        ], 'wzbm_header_test');

        $client->domains()->list();

        $request = $this->getLastRequest();
        $this->assertNotNull($request);
        $this->assertSame(['wzbm_header_test'], $request->getHeader('X-API-Key'));
        $this->assertSame(['application/json'], $request->getHeader('Accept'));
        $this->assertStringContainsString('wazobiamail-php/' . Configuration::VERSION, $request->getHeaderLine('User-Agent'));
    }
}
