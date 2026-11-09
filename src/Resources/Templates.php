<?php

declare(strict_types=1);

namespace WazobiaMail\Resources;

use WazobiaMail\Exceptions\WazobiaException;

class Templates extends AbstractResource
{
    /**
     * List all email templates on your account.
     *
     * @return array
     * @throws WazobiaException
     */
    public function list(): array
    {
        $response = $this->http->get('/templates');
        return $response->getData() ?? [];
    }

    /**
     * Read one email template.
     *
     * @param string $id
     * @return array
     * @throws WazobiaException
     */
    public function get(string $id): array
    {
        $response = $this->http->get('/templates/' . rawurlencode($id));
        return $response->getData() ?? [];
    }

    /**
     * Create a new email template.
     *
     * @param array{
     *     name: string,
     *     subject: string,
     *     htmlContent: string,
     *     textContent?: string,
     *     description?: string
     * } $params
     * @return array
     * @throws WazobiaException
     */
    public function create(array $params): array
    {
        $response = $this->http->post('/templates', $params);
        return $response->getData() ?? [];
    }

    /**
     * Update an email template.
     *
     * @param string $id
     * @param array<string, mixed> $params
     * @return array
     * @throws WazobiaException
     */
    public function update(string $id, array $params): array
    {
        $response = $this->http->put('/templates/' . rawurlencode($id), $params);
        return $response->getData() ?? [];
    }

    /**
     * Delete an email template.
     *
     * @param string $id
     * @return array
     * @throws WazobiaException
     */
    public function delete(string $id): array
    {
        $response = $this->http->delete('/templates/' . rawurlencode($id));
        return $response->getData() ?? [];
    }
}
