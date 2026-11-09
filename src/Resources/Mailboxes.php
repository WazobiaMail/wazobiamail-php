<?php

declare(strict_types=1);

namespace WazobiaMail\Resources;

use WazobiaMail\Exceptions\WazobiaException;

class Mailboxes extends AbstractResource
{
    /**
     * List all hosted mailboxes on your account.
     *
     * @return array
     * @throws WazobiaException
     */
    public function list(): array
    {
        $response = $this->http->get('/mailboxes');
        return $response->getData() ?? [];
    }

    /**
     * Read one hosted mailbox.
     *
     * @param string $id
     * @return array
     * @throws WazobiaException
     */
    public function get(string $id): array
    {
        $response = $this->http->get('/mailboxes/' . rawurlencode($id));
        return $response->getData() ?? [];
    }

    /**
     * Create a hosted mailbox.
     *
     * @param array{
     *     domainId: string,
     *     localPart: string,
     *     displayName?: string,
     *     quotaMb?: int
     * } $params
     * @return array
     * @throws WazobiaException
     */
    public function create(array $params): array
    {
        $response = $this->http->post('/mailboxes', $params);
        return $response->getData() ?? [];
    }

    /**
     * Update mailbox display name or password.
     *
     * @param string $id
     * @param array{
     *     displayName?: string,
     *     password?: string
     * } $params
     * @return array
     * @throws WazobiaException
     */
    public function update(string $id, array $params): array
    {
        $response = $this->http->patch('/mailboxes/' . rawurlencode($id), $params);
        return $response->getData() ?? [];
    }

    /**
     * Delete a hosted mailbox.
     *
     * @param string $id
     * @return array
     * @throws WazobiaException
     */
    public function delete(string $id): array
    {
        $response = $this->http->delete('/mailboxes/' . rawurlencode($id));
        return $response->getData() ?? [];
    }

    /**
     * Read SMTP, IMAP and POP3 public connection settings for email clients.
     *
     * @param string $id
     * @return array
     * @throws WazobiaException
     */
    public function getConnection(string $id): array
    {
        $response = $this->http->get('/mailboxes/' . rawurlencode($id) . '/connection');
        return $response->getData() ?? [];
    }

    /**
     * Issue new SMTP and IMAP credentials for the mailbox.
     *
     * @param string $id
     * @return array
     * @throws WazobiaException
     */
    public function issueCredentials(string $id): array
    {
        $response = $this->http->post('/mailboxes/' . rawurlencode($id) . '/credentials');
        return $response->getData() ?? [];
    }

    /**
     * Rotate mailbox credentials.
     *
     * @param string $id
     * @return array
     * @throws WazobiaException
     */
    public function rotateCredentials(string $id): array
    {
        $response = $this->http->post('/mailboxes/' . rawurlencode($id) . '/credentials/rotate');
        return $response->getData() ?? [];
    }

    /**
     * Read total mailbox storage currently in use across the account.
     *
     * @return array
     * @throws WazobiaException
     */
    public function getStorage(): array
    {
        $response = $this->http->get('/mailboxes/storage');
        return $response->getData() ?? [];
    }
}
