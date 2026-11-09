<?php

declare(strict_types=1);

namespace WazobiaMail\Resources;

use WazobiaMail\Exceptions\WazobiaException;

class SmtpRelays extends AbstractResource
{
    /**
     * List all SMTP relays on the account.
     *
     * @return array
     * @throws WazobiaException
     */
    public function list(): array
    {
        $response = $this->http->get('/smtp-relays');
        return $response->getData() ?? [];
    }

    /**
     * Read one SMTP relay.
     *
     * @param string $id
     * @return array
     * @throws WazobiaException
     */
    public function get(string $id): array
    {
        $response = $this->http->get('/smtp-relays/' . rawurlencode($id));
        return $response->getData() ?? [];
    }

    /**
     * Create an SMTP relay configuration.
     *
     * @param array{
     *     domainId: string,
     *     name: string,
     *     host: string,
     *     port: int,
     *     username?: string,
     *     password?: string
     * } $params
     * @return array
     * @throws WazobiaException
     */
    public function create(array $params): array
    {
        $response = $this->http->post('/smtp-relays', $params);
        return $response->getData() ?? [];
    }

    /**
     * Update an SMTP relay configuration.
     *
     * @param string $id
     * @param array<string, mixed> $params
     * @return array
     * @throws WazobiaException
     */
    public function update(string $id, array $params): array
    {
        $response = $this->http->patch('/smtp-relays/' . rawurlencode($id), $params);
        return $response->getData() ?? [];
    }

    /**
     * Delete an SMTP relay.
     *
     * @param string $id
     * @return array
     * @throws WazobiaException
     */
    public function delete(string $id): array
    {
        $response = $this->http->delete('/smtp-relays/' . rawurlencode($id));
        return $response->getData() ?? [];
    }
}
