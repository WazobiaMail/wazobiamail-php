<?php

declare(strict_types=1);

namespace WazobiaMail\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase as BaseTestCase;
use WazobiaMail\Configuration;
use WazobiaMail\WazobiaMailClient;

abstract class TestCase extends BaseTestCase
{
    /**
     * @var array<int, array{request: \Psr\Http\Message\RequestInterface, response: \Psr\Http\Message\ResponseInterface}>
     */
    protected array $history = [];

    /**
     * Create a WazobiaMailClient with a mocked Guzzle client.
     *
     * @param array<int, GuzzleResponse> $responses
     * @param string $apiKey
     * @return WazobiaMailClient
     */
    protected function createMockClient(array $responses = [], string $apiKey = 'wzbm_test_key_12345'): WazobiaMailClient
    {
        $this->history = [];
        $historyMiddleware = Middleware::history($this->history);

        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($historyMiddleware);

        $guzzle = new Client([
            'handler' => $handlerStack,
            'base_uri' => 'https://app.wazobiamail.com/api/v1/',
        ]);

        $config = new Configuration([
            'apiKey' => $apiKey,
            'client' => $guzzle,
        ]);

        return new WazobiaMailClient($config);
    }

    protected function jsonResponse(int $status, array $data, array $headers = []): GuzzleResponse
    {
        $headers = array_merge(['Content-Type' => 'application/json'], $headers);
        return new GuzzleResponse($status, $headers, json_encode($data) ?: '{}');
    }

    protected function getLastRequest(): ?\Psr\Http\Message\RequestInterface
    {
        if (empty($this->history)) {
            return null;
        }

        $last = end($this->history);
        return $last['request'] ?? null;
    }
}
