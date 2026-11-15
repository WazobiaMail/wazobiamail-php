<?php

declare(strict_types=1);

namespace WazobiaMail;

use GuzzleHttp\ClientInterface;
use WazobiaMail\Exceptions\AuthenticationException;
use WazobiaMail\Exceptions\ValidationException;

class Configuration
{
    public const DEFAULT_BASE_URL = 'https://app.wazobiamail.com/api/v1';
    public const DEFAULT_TIMEOUT = 30.0;
    public const VERSION = '0.1.3';

    protected string $apiKey;
    protected string $baseUrl;
    protected float $timeout;
    protected ?ClientInterface $customClient = null;

    /**
     * @param array{
     *     apiKey?: string,
     *     baseUrl?: string,
     *     timeout?: float|int,
     *     client?: ClientInterface
     * }|string|null $config
     * @throws AuthenticationException
     */
    public function __construct(array|string|null $config = null)
    {
        if (is_string($config)) {
            $config = ['apiKey' => $config];
        }

        $config = $config ?? [];

        $apiKey = $config['apiKey'] ?? $config['api_key'] ?? $this->resolveApiKeyFromEnvironment();
        if ($apiKey === null || trim($apiKey) === '') {
            throw new AuthenticationException(
                'WazobiaMail API key is required. Pass it to the constructor or set the WAZOBIA_API_KEY environment variable.'
            );
        }

        $apiKey = trim($apiKey);
        if (preg_match('/[\r\n\0]/', $apiKey) === 1) {
            throw new AuthenticationException('WazobiaMail API key contains illegal control characters.');
        }

        $this->apiKey = $apiKey;
        $baseUrl = trim((string) ($config['baseUrl'] ?? $config['base_url'] ?? self::DEFAULT_BASE_URL));
        if (
            preg_match('/[\r\n\0]/', $baseUrl) === 1
            || (!str_starts_with($baseUrl, 'https://') && !str_starts_with($baseUrl, 'http://'))
        ) {
            throw new ValidationException('baseUrl must start with http:// or https:// without control characters.');
        }

        $this->baseUrl = rtrim($baseUrl, '/') . '/';
        $this->timeout = (float) ($config['timeout'] ?? self::DEFAULT_TIMEOUT);
        $this->customClient = $config['client'] ?? null;
    }

    /**
     * Mask sensitive credentials if Configuration is dumped or logged.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $masked = strlen($this->apiKey) <= 8
            ? '***'
            : substr($this->apiKey, 0, 5) . '...' . substr($this->apiKey, -4);

        return [
            'apiKey' => $masked,
            'baseUrl' => $this->baseUrl,
            'timeout' => $this->timeout,
        ];
    }

    protected function resolveApiKeyFromEnvironment(): ?string
    {
        $envKey = getenv('WAZOBIA_API_KEY');
        if ($envKey !== false && trim($envKey) !== '') {
            return trim($envKey);
        }

        $envKey = getenv('WAZOBIAMAIL_API_KEY');
        if ($envKey !== false && trim($envKey) !== '') {
            return trim($envKey);
        }

        if (!empty($_ENV['WAZOBIA_API_KEY'])) {
            return trim($_ENV['WAZOBIA_API_KEY']);
        }

        if (!empty($_ENV['WAZOBIAMAIL_API_KEY'])) {
            return trim($_ENV['WAZOBIAMAIL_API_KEY']);
        }

        if (!empty($_SERVER['WAZOBIA_API_KEY'])) {
            return trim($_SERVER['WAZOBIA_API_KEY']);
        }

        return null;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getTimeout(): float
    {
        return $this->timeout;
    }

    public function getCustomClient(): ?ClientInterface
    {
        return $this->customClient;
    }
}
