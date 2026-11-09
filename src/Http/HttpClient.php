<?php

declare(strict_types=1);

namespace WazobiaMail\Http;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use WazobiaMail\Configuration;
use WazobiaMail\Exceptions\AuthenticationException;
use WazobiaMail\Exceptions\ForbiddenException;
use WazobiaMail\Exceptions\NotFoundException;
use WazobiaMail\Exceptions\RateLimitException;
use WazobiaMail\Exceptions\ServerException;
use WazobiaMail\Exceptions\ValidationException;
use WazobiaMail\Exceptions\WazobiaException;

class HttpClient
{
    protected ClientInterface $client;

    public function __construct(
        protected Configuration $config
    ) {
        $this->client = $config->getCustomClient() ?? new Client([
            'base_uri' => $config->getBaseUrl(),
            'timeout' => $config->getTimeout(),
            'http_errors' => false,
        ]);
    }

    /**
     * @throws WazobiaException
     */
    public function get(string $path, array $query = [], array $headers = []): Response
    {
        return $this->request('GET', $path, [
            'query' => $query,
            'headers' => $headers,
        ]);
    }

    /**
     * @throws WazobiaException
     */
    public function post(string $path, array $data = [], array $headers = []): Response
    {
        return $this->request('POST', $path, [
            'json' => empty($data) ? (object) [] : $data,
            'headers' => $headers,
        ]);
    }

    /**
     * @throws WazobiaException
     */
    public function patch(string $path, array $data = [], array $headers = []): Response
    {
        return $this->request('PATCH', $path, [
            'json' => empty($data) ? (object) [] : $data,
            'headers' => $headers,
        ]);
    }

    /**
     * @throws WazobiaException
     */
    public function put(string $path, array $data = [], array $headers = []): Response
    {
        return $this->request('PUT', $path, [
            'json' => empty($data) ? (object) [] : $data,
            'headers' => $headers,
        ]);
    }

    /**
     * @throws WazobiaException
     */
    public function delete(string $path, array $data = [], array $headers = []): Response
    {
        $options = ['headers' => $headers];
        if (!empty($data)) {
            $options['json'] = $data;
        }

        return $this->request('DELETE', $path, $options);
    }

    /**
     * @param array<int, array{name: string, contents: mixed, filename?: string, headers?: array}> $multipart
     * @throws WazobiaException
     */
    public function postMultipart(string $path, array $multipart, array $headers = []): Response
    {
        return $this->request('POST', $path, [
            'multipart' => $multipart,
            'headers' => $headers,
        ]);
    }

    /**
     * @throws WazobiaException
     */
    protected function request(string $method, string $path, array $options = []): Response
    {
        $normalizedPath = ltrim($path, '/');
        $headers = array_merge($this->defaultHeaders(), $options['headers'] ?? []);
        $options['headers'] = $headers;

        try {
            $psrResponse = $this->client->request($method, $normalizedPath, $options);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return $this->handleResponse(Response::fromPsrResponse($e->getResponse()));
            }

            throw new ServerException('Network communication error: ' . $e->getMessage(), 0, $e);
        } catch (GuzzleException $e) {
            throw new ServerException('HTTP client error: ' . $e->getMessage(), 0, $e);
        }

        $response = Response::fromPsrResponse($psrResponse);
        return $this->handleResponse($response);
    }

    protected function defaultHeaders(): array
    {
        return [
            'X-API-Key' => $this->config->getApiKey(),
            'Accept' => 'application/json',
            'User-Agent' => sprintf('wazobiamail-php/%s (PHP %s)', Configuration::VERSION, PHP_VERSION),
        ];
    }

    /**
     * @throws WazobiaException
     */
    protected function handleResponse(Response $response): Response
    {
        $status = $response->getStatusCode();
        if ($status >= 200 && $status < 300) {
            return $response;
        }

        $body = $response->toArray() ?? [];
        $message = $body['message'] ?? $body['error'] ?? 'API request failed with status ' . $status;

        switch ($status) {
            case 401:
                throw new AuthenticationException($message, 401, null, $body);

            case 403:
                throw new ForbiddenException($message, 403, null, $body);

            case 404:
                throw new NotFoundException($message, 404, null, $body);

            case 422:
                $errors = $body['errors'] ?? [];
                throw new ValidationException($message, 422, null, $body, is_array($errors) ? $errors : []);

            case 429:
                $retryAfter = null;
                $headers = $response->getHeaders();
                if (isset($headers['Retry-After'][0])) {
                    $retryAfter = (int) $headers['Retry-After'][0];
                }
                throw new RateLimitException($message, 429, null, $body, $retryAfter);

            default:
                if ($status >= 500) {
                    throw new ServerException($message, $status, null, $body);
                }
                throw new WazobiaException($message, $status, null, $body);
        }
    }
}
