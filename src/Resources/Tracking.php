<?php

declare(strict_types=1);

namespace WazobiaMail\Resources;

use WazobiaMail\Exceptions\WazobiaException;

class Tracking extends AbstractResource
{
    /**
     * List tracked messages with pagination and filtering.
     *
     * @param array{
     *     offset?: int,
     *     limit?: int,
     *     status?: string,
     *     domainId?: string
     * } $query
     * @return array
     * @throws WazobiaException
     */
    public function list(array $query = []): array
    {
        $response = $this->http->get('/sending/tracking/messages', $query);
        return $response->getData() ?? [];
    }

    /**
     * Read one tracked message record with its timeline of delivery events.
     *
     * @param string $trackingId
     * @return array
     * @throws WazobiaException
     */
    public function get(string $trackingId): array
    {
        $response = $this->http->get('/sending/tracking/messages/' . rawurlencode($trackingId));
        return $response->getData() ?? [];
    }
}
