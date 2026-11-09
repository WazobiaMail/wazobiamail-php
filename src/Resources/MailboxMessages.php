<?php

declare(strict_types=1);

namespace WazobiaMail\Resources;

use WazobiaMail\Exceptions\ForbiddenException;
use WazobiaMail\Exceptions\WazobiaException;

/**
 * Enterprise Mailbox Messages / Inbox API.
 *
 * Note: These endpoints strictly require an active Enterprise subscription tier.
 * Accounts on other tiers will receive an HTTP 403 Forbidden response.
 */
class MailboxMessages extends AbstractResource
{
    /**
     * List emails received in a hosted mailbox.
     *
     * @param string $mailboxId Mailbox ID
     * @param array{
     *     page?: int,
     *     limit?: int,
     *     unreadOnly?: bool
     * } $query Query parameters
     * @return array Paginated list of messages
     * @throws ForbiddenException If the account is not on an active Enterprise plan
     * @throws WazobiaException
     */
    public function list(string $mailboxId, array $query = []): array
    {
        $response = $this->http->get('/mailboxes/' . rawurlencode($mailboxId) . '/messages', $query);
        return $response->getData() ?? [];
    }

    /**
     * Retrieve the complete detail of an email message including HTML/text body and attachments.
     *
     * @param string $mailboxId Mailbox ID
     * @param string $messageId Message ID
     * @return array Full message details
     * @throws ForbiddenException If the account is not on an active Enterprise plan
     * @throws WazobiaException
     */
    public function get(string $mailboxId, string $messageId): array
    {
        $response = $this->http->get(
            '/mailboxes/' . rawurlencode($mailboxId) . '/messages/' . rawurlencode($messageId)
        );
        return $response->getData() ?? [];
    }

    /**
     * Purge an email message from the mailbox.
     *
     * @param string $mailboxId Mailbox ID
     * @param string $messageId Message ID
     * @return array
     * @throws ForbiddenException If the account is not on an active Enterprise plan
     * @throws WazobiaException
     */
    public function delete(string $mailboxId, string $messageId): array
    {
        $response = $this->http->delete(
            '/mailboxes/' . rawurlencode($mailboxId) . '/messages/' . rawurlencode($messageId)
        );
        return $response->getData() ?? [];
    }
}
