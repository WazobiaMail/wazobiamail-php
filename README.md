# WazobiaMail PHP SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wazobiamail/wazobiamail-php.svg?style=flat-square)](https://packagist.org/packages/wazobiamail/wazobiamail-php)
[![Total Downloads](https://img.shields.io/packagist/dt/wazobiamail/wazobiamail-php.svg?style=flat-square)](https://packagist.org/packages/wazobiamail/wazobiamail-php)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![PHP Version Require](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg?style=flat-square)](composer.json)
[![Docs](https://img.shields.io/badge/Docs-docs.wazobiamail.com-blueviolet.svg?style=flat-square)](https://docs.wazobiamail.com)

The official PHP SDK for [WazobiaMail](https://wazobiamail.com)—business email infrastructure, transactional sending, domain setup with automated DNS generation, hosted mailboxes, and enterprise programmatic inbox automation.

> 📚 **Official Platform Documentation**: Visit [docs.wazobiamail.com](https://docs.wazobiamail.com) for exhaustive API references, architecture guides, DNS setup walkthroughs, and webhook configurations.

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

### 🌐 Subdomain vs. Apex / Root Domain Architecture & Edge Cases

When configuring custom domains for transactional sending, choosing between a **subdomain** (e.g. `mail.yourdomain.com` or `send.yourdomain.com`) and an **apex/root domain** (`yourdomain.com`) is a critical architectural decision.

#### 1. Why Using a Subdomain is Recommended (Root Domain NOT Used)

In production environments, **we strongly recommend using a dedicated sending subdomain** rather than your root/apex domain:

- **Preserve Existing Business Email (Google Workspace / Microsoft 365):**
  If your root domain (`yourdomain.com`) already receives corporate email via Google Workspace or Microsoft 365, adding MX records pointing to WazobiaMail at the root apex `@` will **break your existing company email inbox routing**. Using `mail.yourdomain.com` isolates transactional email routing completely.
- **Isolate Sender Reputation:**
  Transactional emails (password resets, invoices, OTP codes) have high engagement and deliverability requirements. Sending from a subdomain (`mail.yourdomain.com`) ensures that any accidental marketing spam complaints won't degrade your corporate employee email deliverability.
- **Avoid SPF 10-Lookup Limit Exhaustion (RFC 7208):**
  SPF records have a strict limit of 10 DNS lookups. Root domains often accumulate multiple SaaS providers (Google, Zendesk, Salesforce, HubSpot). A dedicated subdomain gives WazobiaMail its own clean, independent SPF record without lookup count contention.

#### 2. DNS Hostnames When Root Domain is NOT Used

When verifying a subdomain like `mail.yourdomain.com`, ensure you configure your DNS provider (Cloudflare, Route53, Namecheap, GoDaddy) with the correct hostnames:

| Record Type | Host / Name (if domain is `mail.yourdomain.com`) | Target / Value | Notes |
| :--- | :--- | :--- | :--- |
| **TXT (SPF)** | `mail` (or `mail.yourdomain.com`) | `v=spf1 include:_spf.wazobiamail.com ~all` | **Do NOT** place on `@` root |
| **TXT (DKIM)** | `wzbm._domainkey.mail` | `k=rsa; p=MIGfMA0GCSqGSIb3...` | Selector is scoped to the subdomain |
| **MX (Hosted)**| `mail` (priority 10 / 20) | `mx1.wazobiamail.com` / `mx2.wazobiamail.com` | Only required if receiving mail or hosted inboxes |
| **CNAME (Tracking)** | `email.mail` | `trk.wazobiamail.com` | Used for custom branded click/open tracking |
| **TXT (DMARC)** | `_dmarc.mail` (or fallback to `_dmarc`) | `v=DMARC1; p=reject; rua=mailto:dmarc@...` | Resolvers fall back to apex `_dmarc` if omitted |

#### 3. Critical Edge Cases & Troubleshooting

##### ⚠️ Edge Case 1: `UnverifiedSenderDomain` (HTTP 422 / 403)
- If you verified `mail.yourdomain.com` in WazobiaMail, your `from` address **must** match that subdomain:
  - ✅ **Valid**: `'from' => 'Acme <billing@mail.yourdomain.com>'`
  - ❌ **Invalid**: `'from' => 'Acme <billing@yourdomain.com>'` (Fails because apex `yourdomain.com` is unverified)
- Attempting to send from an unverified parent domain will throw a `ValidationException` or `ForbiddenException`.

##### 💡 Edge Case 2: Reply-To Decoupling (Best Practice)
- You can send from the verified subdomain while routing customer replies to your corporate root domain mailbox:
  ```php
  $client->messages()->send([
      'from' => 'Acme Orders <orders@mail.yourdomain.com>', // Verified sending subdomain
      'replyTo' => 'support@yourdomain.com',               // Your corporate inbox on Google/Outlook
      'to' => 'customer@example.com',
      'subject' => 'Your receipt',
      'html' => '<p>Thank you for your purchase!</p>',
  ]);
  ```
- This gives you the deliverability benefits of a dedicated sending subdomain while preserving user-friendly reply handling.

##### ☁️ Edge Case 3: Cloudflare Proxy Status (Grey Cloud vs. Orange Cloud)
- **Problem**: In Cloudflare DNS, leaving the proxy enabled (**Orange Cloud**) for mail or tracking records breaks SMTP traffic, DNS verification, and SSL certificate issuance.
- **Solution**: Always toggle Cloudflare records to **"DNS only" (Grey Cloud / Proxied: Off)** for all WazobiaMail SPF, DKIM, MX, and tracking records. If you use `$client->domains()->syncCloudflare($domainId)`, this is handled automatically.

##### ⏳ Edge Case 4: Negative DNS Caching (SOA Minimum TTL Delay)
- If you run `$client->domains()->verify($domainId)` immediately after publishing DNS records before they propagate, recursive resolvers may cache the negative response (`NXDOMAIN` or empty record).
- Subsequent verification checks will continue to fail until the DNS TTL expires (often 5 to 60 minutes).
- **Tip**: Test propagation first with `dig TXT mail.yourdomain.com` or `dig TXT wzbm._domainkey.mail.yourdomain.com +trace` before triggering verification, or pass `['force' => true]`.

##### 🛡️ Edge Case 5: DMARC Subdomain Policy (`sp=`) Inheritance
- If your apex domain (`yourdomain.com`) has a DMARC record with `sp=reject` or `sp=quarantine`:
  - Any subdomain (e.g. `mail.yourdomain.com`) automatically inherits that rejection policy unless it defines its own `_dmarc.mail.yourdomain.com` record.
  - If SPF or DKIM alignment fails on the subdomain, recipient mail servers (Gmail, Yahoo, Outlook) will reject or spam-folder your emails immediately. Ensure both SPF and DKIM pass before sending production traffic.

##### 📬 Edge Case 6: Headless Inboxes & Hosted Mailboxes on Subdomains
- When provisioning hosted mailboxes (`$client->mailboxes()->create([...])`), the mailbox address is generated under the specific verified domain (e.g. `support@mail.yourdomain.com`).
- Inbound receiving requires the MX records for that exact subdomain to be pointing to WazobiaMail. Do not point the root domain MX to WazobiaMail if you have existing email services there.

##### 🔁 Edge Case 7: Network Timeouts & Idempotency
- If an HTTP request times out due to transient network issues, the email may already have been queued by the API Gateway.
- Always provide an `idempotencyKey` when dispatching critical transactional messages. Retrying with the same key returns the existing tracking receipt without duplicate sending.

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

### 5. Delivery Tracking, Stats, Read Receipts & Click Receipts

WazobiaMail provides built-in, granular observability for every transactional email dispatched.

#### 1. Read Receipts (Open Tracking)
Every HTML email sent through WazobiaMail can automatically include an invisible, privacy-compliant 1x1 tracking pixel. When the recipient opens the email in their client (Apple Mail, Gmail, Outlook, etc.):
- An `email.opened` event is recorded.
- Captures exact timestamp, recipient IP geolocation, device type, and client user-agent.
- Tracks unique opens vs. total open counts to measure audience engagement.
- Queryable via `$client->tracking()->get($trackingId)` or streamed via Webhooks.

#### 2. Click Receipts (Link Tracking)
Links in your email content can be rewritten using your verified tracking domain (`email.mail.yourdomain.com` or `trk.wazobiamail.com`):
- When a recipient clicks any link, a `link.clicked` receipt is created with microsecond precision.
- Records the original destination URL, recipient email, click timestamp, and click count.
- Enables fraud detection, user engagement scoring, and automated lifecycle triggers.

#### 3. Delivery Lifecycle & Timeline
Every message transitions through well-defined lifecycle states:
`queued` ➔ `sent` ➔ `delivered` ➔ `opened` ➔ `clicked` ➔ `bounced` / `complained`

```php
// Inspect message delivery timeline and receipt counters
$timeline = $client->tracking()->get('trk_abc12345');

echo "Status: " . $timeline['data']['status'] . "\n";
echo "Delivered At: " . $timeline['data']['deliveredAt'] . "\n";
echo "Read Receipts (Open Count): " . $timeline['data']['openCount'] . "\n";
echo "Click Receipts (Click Count): " . $timeline['data']['clickCount'] . "\n";

foreach ($timeline['data']['events'] as $event) {
    echo "[{$event['occurredAt']}] {$event['eventType']} - Recipient: {$event['recipient']}\n";
    if (!empty($event['linkUrl'])) {
        echo "  Clicked URL: {$event['linkUrl']}\n";
    }
}

// Query aggregate delivery statistics
$stats = $client->tracking()->list([
    'status' => 'delivered',
    'limit' => 50,
]);

echo "Total tracked: " . $stats['data']['total'] . "\n";
foreach ($stats['data']['messages'] as $msg) {
    echo "{$msg['trackingId']}: {$msg['status']} ({$msg['openCount']} opens, {$msg['clickCount']} clicks)\n";
}
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
