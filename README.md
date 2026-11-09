# WazobiaMail PHP SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wazobiamail/wazobiamail-php.svg?style=flat-square)](https://packagist.org/packages/wazobiamail/wazobiamail-php)
[![Total Downloads](https://img.shields.io/packagist/dt/wazobiamail/wazobiamail-php.svg?style=flat-square)](https://packagist.org/packages/wazobiamail/wazobiamail-php)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![PHP Version Require](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg?style=flat-square)](composer.json)

The official PHP SDK for [WazobiaMail](https://wazobiamail.com)—business email infrastructure, transactional sending, domain setup with automated DNS generation, hosted mailboxes, and enterprise programmatic inbox automation.

---

## Requirements

- **PHP 8.1** or higher (`8.1`, `8.2`, `8.3`, `8.4`, `8.5+`)
- `ext-json`
- `guzzlehttp/guzzle: ^7.5`

---

## Installation

Install via Composer:

```bash
composer require wazobiamail/wazobiamail-php
```

---

## Quickstart & Authentication

### 1. Initialization

Initialize the client with your WazobiaMail API key (`wzbm_...`). You can create API keys in your [WazobiaMail Dashboard](https://app.wazobiamail.com/dashboard/smtp-api).

```php
use WazobiaMail\WazobiaMailClient;

// Provide API key explicitly
$client = new WazobiaMailClient('wzbm_live_your_api_key_here');

// Or read automatically from environment (WAZOBIA_API_KEY or WAZOBIAMAIL_API_KEY)
$client = new WazobiaMailClient();
```

### 2. Configuration Options

```php
use WazobiaMail\WazobiaMailClient;

$client = new WazobiaMailClient([
    'apiKey' => 'wzbm_live_your_api_key_here',
    'baseUrl' => 'https://app.wazobiamail.com/api/v1', // Optional custom gateway
    'timeout' => 45.0, // Timeout in seconds (default: 30s)
]);
```

---

## Usage Guide

### 1. Sending Transactional Email

```php
$result = $client->messages()->send([
    'from' => 'billing@yourdomain.com',
    'to' => 'customer@example.com',
    'subject' => 'November Invoice #1042',
    'html' => '<h1>Your Invoice</h1><p>Thank you for your business!</p>',
    'text' => "Your Invoice\nThank you for your business!",
    'cc' => ['accounting@yourdomain.com'],
    'attachments' => [
        [
            'filename' => 'invoice-1042.pdf',
            'content' => base64_encode(file_get_contents('/path/to/invoice.pdf')),
            'contentType' => 'application/pdf',
        ],
    ],
], idempotencyKey: 'inv_1042_attempt_1'); // Optional idempotency key prevents duplicate sends
```

#### Deliverability & Spam Score Checking

```php
$spamReport = $client->messages()->checkSpam([
    'from' => 'newsletter@yourdomain.com',
    'subject' => 'Weekly Product Updates',
    'html' => '<p>Check out our latest releases...</p>',
]);

echo "Spam score: " . $spamReport['score'];
```

---

### 2. Domain Lifecycle & DNS Records

Provision domains and inspect the exact cryptographic records (SPF, DKIM, DMARC, MX) required for mail exchange.

```php
// 1. Add a sending/receiving domain
$domain = $client->domains()->create([
    'domain' => 'mail.mycompany.com',
    'useRootDomain' => true,
    'hostInboxesOnMainDomain' => true,
]);

$domainId = $domain['id'];

// 2. Fetch DNS records to configure at your registrar
$dns = $client->domains()->getDnsRecords($domainId);

echo "SPF: " . $dns['domain']['spfRecord'] . "\n";
echo "DKIM Host: " . $dns['domain']['dkimHost'] . "\n";
echo "DKIM Value: " . $dns['domain']['dkimValue'] . "\n";

// 3. Trigger asynchronous DNS verification
$verifyStatus = $client->domains()->verify($domainId, force: true);
```

#### Cloudflare Auto-Sync

```php
// Trigger automated Cloudflare DNS synchronization
$client->domains()->syncCloudflare($domainId);
```

---

### 3. Hosted Mailbox Management

```php
// 1. Create a hosted mailbox
$mailbox = $client->mailboxes()->create([
    'domainId' => $domainId,
    'localPart' => 'support',
    'displayName' => 'Customer Support',
    'quotaMb' => 5120, // 5 GB
]);

$mailboxId = $mailbox['id'];

// 2. Inspect SMTP/IMAP connection details
$connection = $client->mailboxes()->getConnection($mailboxId);

// 3. Issue or rotate IMAP/SMTP credentials
$credentials = $client->mailboxes()->issueCredentials($mailboxId);

// 4. Check total mailbox storage usage across the account
$storage = $client->mailboxes()->getStorage();
```

---

### 4. Enterprise Mailbox Inbox API

> **Enterprise Plan Required**: The Mailbox Messages endpoints require an active **Enterprise plan**. Calling these endpoints on lower tiers strictly returns a `403 Forbidden` response.

Inspect and process incoming customer emails programmatically without managing dedicated IMAP daemons.

```php
use WazobiaMail\Exceptions\ForbiddenException;

try {
    // 1. List received messages (with pagination and unread filtering)
    $inbox = $client->mailboxMessages()->list($mailboxId, [
        'page' => 1,
        'limit' => 25,
        'unreadOnly' => true,
    ]);

    foreach ($inbox['items'] as $message) {
        echo "From: " . $message['from'][0]['email'] . "\n";
        echo "Subject: " . $message['subject'] . "\n";
        echo "Preview: " . $message['preview'] . "\n";

        // 2. Read full message body and attachments
        $detail = $client->mailboxMessages()->get($mailboxId, $message['id']);
        echo "HTML Body: " . $detail['htmlBody'] . "\n";

        // 3. Purge processed message
        $client->mailboxMessages()->delete($mailboxId, $message['id']);
    }
} catch (ForbiddenException $e) {
    if ($e->isEnterprisePlanRequired()) {
        echo "An active Enterprise plan is required to query mailbox inboxes programmatically.";
    }
}
```

---

### 5. Delivery Tracking & Analytics

```php
// List tracked messages
$tracked = $client->tracking()->list(['limit' => 20]);

// Inspect message delivery timeline (sent, delivered, opened, clicked, bounced)
$timeline = $client->tracking()->get('trk_abc12345');
```

---

### 6. Webhooks Verification

Securely verify incoming webhooks from WazobiaMail using HMAC SHA-256 signatures:

```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WAZOBIA_SIGNATURE'] ?? '';
$secret = getenv('WAZOBIA_WEBHOOK_SECRET');

if ($client->webhooks()->verifySignature($payload, $signature, $secret)) {
    $event = json_decode($payload, true);
    // Process event: 'email.delivered', 'email.bounced', etc.
} else {
    http_response_code(400);
    exit('Invalid signature');
}
```

---

## Error Handling

All SDK exceptions extend `WazobiaMail\Exceptions\WazobiaException`:

| Exception | HTTP Code | Description |
| :--- | :--- | :--- |
| `AuthenticationException` | `401` | Missing, invalid, or expired API key |
| `ForbiddenException` | `403` | Plan or permission restriction. Check `$e->isEnterprisePlanRequired()`. |
| `NotFoundException` | `404` | Requested domain, mailbox, or message was not found |
| `ValidationException` | `422` | Request validation failure. Inspect `$e->getErrors()`. |
| `RateLimitException` | `429` | Request rate limit exceeded. Inspect `$e->getRetryAfter()`. |
| `ServerException` | `500+` | Server error or upstream gateway outage |

```php
use WazobiaMail\Exceptions\ValidationException;
use WazobiaMail\Exceptions\ForbiddenException;
use WazobiaMail\Exceptions\WazobiaException;

try {
    $client->messages()->send([...]);
} catch (ValidationException $e) {
    print_r($e->getErrors());
} catch (ForbiddenException $e) {
    echo "Forbidden: " . $e->getMessage();
} catch (WazobiaException $e) {
    echo "WazobiaMail error: " . $e->getMessage();
}
```

---

## Testing

Run the test suite with PHPUnit:

```bash
composer test
```

Or directly:

```bash
vendor/bin/phpunit
```

---

## License

The WazobiaMail PHP SDK is open-sourced software licensed under the [MIT license](LICENSE).
