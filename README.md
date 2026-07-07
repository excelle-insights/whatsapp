# Excelle Insights WhatsApp Cloud API

[![Latest Version](https://img.shields.io/packagist/v/excelle-insights/whatsapp.svg?style=flat-square)](https://packagist.org/packages/excelle-insights/whatsapp)
[![PHP](https://img.shields.io/badge/PHP-^8.1-777BB4?style=flat-square&logo=php)](https://php.net)
[![License](https://img.shields.io/packagist/l/excelle-insights/whatsapp.svg?style=flat-square)](LICENSE)

A PHP package for integrating with the **Meta WhatsApp Cloud API**. Handles OAuth authentication, WABA discovery, message templates, inbound/outbound messaging, webhooks, and HTTP request logging.

## Features

- **OAuth 2.0** — Meta Login for Business with long-lived token exchange and auto-refresh
- **WABA Discovery** — Auto-detect all WhatsApp Business Accounts the user can access
- **Business Profiles** — Link WABAs, fetch/update business profile data, sync phone numbers
- **Template Management** — Create, list, delete message templates; track approval via webhooks
- **Message Sending** — Text, template, and media messages
- **Webhook Processing** — Verify webhooks, receive inbound messages, handle status callbacks
- **HTTP Request Logging** — Every API call logged to `http_request_logs` table
- **PDO-based** — Uses MySQL via PDO (extensible to other databases)
- **Configurable Table Prefix** — All WhatsApp tables use the `WHATSAPP_TABLE_PREFIX` env var

## Installation

```bash
composer require excelle-insights/whatsapp
```

## Environment Setup

Create a `.env` file in your project root:

```env
# Meta / WhatsApp Cloud API
WHATSAPP_GRAPH_API=https://graph.facebook.com
WHATSAPP_API_VERSION=v22.0
WHATSAPP_APP_ID=your_app_id
WHATSAPP_APP_SECRET=your_app_secret
WHATSAPP_REDIRECT_URI=https://your-app.com/auth-callback.php
WHATSAPP_TABLE_PREFIX=whatsapp
WHATSAPP_WEBHOOK_VERIFY_TOKEN=your_verify_token

# Database
DB_DSN=mysql:host=127.0.0.1;dbname=myapp
DB_USER=root
DB_PASSWORD=
```

## Quick Start

```php
<?php
require 'vendor/autoload.php';

use ExcelleInsights\WhatsApp\Facade\WhatsAppManager;

$whatsapp = new WhatsAppManager();
```

### 1. OAuth Authentication

Generate the Meta authorization URL:

```php
echo $whatsapp->getAuthUrl();
// Redirect the user to this URL for Meta Login
```

Handle the callback (your `WHATSAPP_REDIRECT_URI` endpoint):

```php
use ExcelleInsights\WhatsApp\Controller\OAuthController;

$oauth = new OAuthController($whatsapp);
echo $oauth->handleCallback();
// On success, all discovered WABAs are auto-linked
```

Or use a direct System User token (skip OAuth):

```php
$whatsapp->setDirectToken('EAAT...');
```

### 2. Send a Text Message

```php
$result = $whatsapp->sendText(1, '254712345678', 'Hello from WhatsApp API!');
echo $result->wam_id;
```

### 3. Send a Template Message

```php
$result = $whatsapp->sendTemplate(1, '254712345678', 'welcome_msg', [
    'language' => 'en_US',
    'components' => [
        ['type' => 'body', 'parameters' => [['type' => 'text', 'text' => 'John']]],
    ],
]);
```

### 4. List Templates

```php
// Local (synced)
$local = $whatsapp->getAllTemplates(1);

// From Meta API
$remote = $whatsapp->getTemplatesFromMeta(1);
```

### 5. Webhook Setup

```php
use ExcelleInsights\WhatsApp\Controller\WebhookController;

$webhook = new WebhookController($whatsapp);

// GET request — verification
$challenge = $webhook->verify();

// POST request — incoming messages / status updates
$response = $webhook->process();
```

## Database Migrations

Run the provided Phinx migrations to create the required tables:

```bash
# Using the package migrate script
php vendor/excelle-insights/whatsapp/migrate.php

# Or manually with Phinx
vendor/bin/phinx migrate -c vendor/excelle-insights/whatsapp/phinx.php
```

Tables created (all prefixed by `WHATSAPP_TABLE_PREFIX`):

| Table | Purpose |
|-------|---------|
| `{prefix}_business_profiles` | Linked Meta Business/WABA accounts |
| `{prefix}_access_tokens` | Per-user OAuth tokens |
| `{prefix}_templates` | Message templates |
| `{prefix}_template_components` | Template body/header/footer/buttons |
| `{prefix}_messages` | Inbound and outbound messages |
| `{prefix}_message_media` | Media attachments |
| `{prefix}_webhook_logs` | Raw webhook audit logs |
| `http_request_logs` | API request/response logs (non-prefixed) |

## Advanced Usage

### Custom HTTP Client

Inject a custom HTTP client for testing or custom logging:

```php
use ExcelleInsights\WhatsApp\Facade\WhatsAppManager;

$httpClient = new MyCustomHttpClient();
$whatsapp = new WhatsAppManager($httpClient);
```

### Custom PDO Connection

Pass an existing PDO instance:

```php
$pdo = new PDO('mysql:host=...;dbname=...', 'user', 'pass');
$whatsapp = new WhatsAppManager(null, $pdo);
```

## HTTP Request Logging

All API calls are automatically logged to the `http_request_logs` table with:
- HTTP method, URL, request/response headers and bodies
- Response status code and duration
- Sensitive headers (Authorization, X-API-Key) are redacted

## Testing

```bash
vendor/bin/phpunit
```

## License

MIT — see [LICENSE](LICENSE).
