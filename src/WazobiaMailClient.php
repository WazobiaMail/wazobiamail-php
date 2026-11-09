<?php

declare(strict_types=1);

namespace WazobiaMail;

use WazobiaMail\Http\HttpClient;
use WazobiaMail\Resources\Domains;
use WazobiaMail\Resources\Mailboxes;
use WazobiaMail\Resources\MailboxMessages;
use WazobiaMail\Resources\Messages;
use WazobiaMail\Resources\SmtpRelays;
use WazobiaMail\Resources\Templates;
use WazobiaMail\Resources\Tracking;
use WazobiaMail\Resources\Webhooks;

class WazobiaMailClient
{
    protected Configuration $config;
    protected HttpClient $http;

    protected ?Messages $messages = null;
    protected ?Domains $domains = null;
    protected ?Mailboxes $mailboxes = null;
    protected ?MailboxMessages $mailboxMessages = null;
    protected ?Tracking $tracking = null;
    protected ?Templates $templates = null;
    protected ?SmtpRelays $relays = null;
    protected ?Webhooks $webhooks = null;

    /**
     * @param array<string, mixed>|string|Configuration|null $config
     */
    public function __construct(array|string|Configuration|null $config = null)
    {
        if ($config instanceof Configuration) {
            $this->config = $config;
        } else {
            $this->config = new Configuration($config);
        }

        $this->http = new HttpClient($this->config);
    }

    public function messages(): Messages
    {
        return $this->messages ??= new Messages($this->http);
    }

    public function domains(): Domains
    {
        return $this->domains ??= new Domains($this->http);
    }

    public function mailboxes(): Mailboxes
    {
        return $this->mailboxes ??= new Mailboxes($this->http);
    }

    /**
     * Access the Enterprise Mailbox Inbox API.
     * Note: Requires an active Enterprise plan on your account.
     */
    public function mailboxMessages(): MailboxMessages
    {
        return $this->mailboxMessages ??= new MailboxMessages($this->http);
    }

    public function tracking(): Tracking
    {
        return $this->tracking ??= new Tracking($this->http);
    }

    public function templates(): Templates
    {
        return $this->templates ??= new Templates($this->http);
    }

    public function relays(): SmtpRelays
    {
        return $this->relays ??= new SmtpRelays($this->http);
    }

    public function smtpRelays(): SmtpRelays
    {
        return $this->relays();
    }

    public function webhooks(): Webhooks
    {
        return $this->webhooks ??= new Webhooks();
    }

    public function getConfiguration(): Configuration
    {
        return $this->config;
    }

    public function getHttpClient(): HttpClient
    {
        return $this->http;
    }
}
