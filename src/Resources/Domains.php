<?php

declare(strict_types=1);

namespace WazobiaMail\Resources;

use WazobiaMail\Exceptions\WazobiaException;

class Domains extends AbstractResource
{
    /**
     * List all sending and hosted domains on your account.
     *
     * @return array
     * @throws WazobiaException
     */
    public function list(): array
    {
        $response = $this->http->get('/sending/domains');
        return $response->getData() ?? [];
    }

    /**
     * Read one domain record.
     *
     * @param string $domainId
     * @return array
     * @throws WazobiaException
     */
    public function get(string $domainId): array
    {
        $response = $this->http->get('/sending/domains/' . rawurlencode($domainId));
        return $response->getData() ?? [];
    }

    /**
     * Add a domain for sending or hosting inboxes.
     *
     * @param array{
     *     domain: string,
     *     useRootDomain?: bool,
     *     hostInboxesOnMainDomain?: bool,
     *     createDefaultInboxes?: bool,
     *     sendingSubdomain?: string
     * } $params
     * @return array
     * @throws WazobiaException
     */
    public function create(array $params): array
    {
        $response = $this->http->post('/sending/domains', $params);
        return $response->getData() ?? [];
    }

    /**
     * Update an existing domain configuration.
     *
     * @param string $domainId
     * @param array<string, mixed> $params
     * @return array
     * @throws WazobiaException
     */
    public function update(string $domainId, array $params): array
    {
        $response = $this->http->patch('/sending/domains/' . rawurlencode($domainId), $params);
        return $response->getData() ?? [];
    }

    /**
     * Delete a domain from the account.
     *
     * @param string $domainId
     * @return array
     * @throws WazobiaException
     */
    public function delete(string $domainId): array
    {
        $response = $this->http->delete('/sending/domains/' . rawurlencode($domainId));
        return $response->getData() ?? [];
    }

    /**
     * Retrieve the required DNS records (SPF, DKIM, DMARC, MX) for the domain.
     *
     * @param string $domainId
     * @return array
     * @throws WazobiaException
     */
    public function getDnsRecords(string $domainId): array
    {
        $response = $this->http->get('/sending/domains/' . rawurlencode($domainId) . '/dns-records');
        return $response->getData() ?? [];
    }

    /**
     * Retrieve mail-specific DNS settings for the domain.
     *
     * @param string $domainId
     * @return array
     * @throws WazobiaException
     */
    public function getMailDns(string $domainId): array
    {
        $response = $this->http->get('/sending/domains/' . rawurlencode($domainId) . '/mail-dns');
        return $response->getData() ?? [];
    }

    /**
     * Trigger DNS verification check for the domain.
     *
     * @param string $domainId
     * @param bool $force Force re-querying authoritative DNS nameservers
     * @return array
     * @throws WazobiaException
     */
    public function verify(string $domainId, bool $force = false): array
    {
        $response = $this->http->post(
            '/sending/domains/' . rawurlencode($domainId) . '/verify',
            ['force' => $force]
        );
        return $response->getData() ?? [];
    }

    /**
     * Get Cloudflare OAuth connection URL for automated DNS management.
     *
     * @return array
     * @throws WazobiaException
     */
    public function connectCloudflare(): array
    {
        $response = $this->http->post('/sending/domains/cloudflare/connect');
        return $response->getData() ?? [];
    }

    /**
     * Auto-publish DNS records to connected Cloudflare account.
     *
     * @param string $domainId
     * @return array
     * @throws WazobiaException
     */
    public function syncCloudflare(string $domainId): array
    {
        $response = $this->http->post('/sending/domains/cloudflare/sync', ['domainId' => $domainId]);
        return $response->getData() ?? [];
    }

    /**
     * Check Cloudflare OAuth connection status.
     *
     * @return array
     * @throws WazobiaException
     */
    public function getCloudflareStatus(): array
    {
        $response = $this->http->get('/sending/domains/cloudflare/status');
        return $response->getData() ?? [];
    }

    /**
     * Disconnect Cloudflare integration.
     *
     * @return array
     * @throws WazobiaException
     */
    public function disconnectCloudflare(): array
    {
        $response = $this->http->delete('/sending/domains/cloudflare/disconnect');
        return $response->getData() ?? [];
    }
}
