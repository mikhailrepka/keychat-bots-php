# Key Chat Bots SDK for PHP

Small PHP 8.1+ SDK for Key Chat Bot Runtime API and signed webhooks.

## Install

The package is published on [Packagist](https://packagist.org/packages/keychat/bots):

```bash
composer require keychat/bots
```

Run the command inside an existing Composer project. For a new project, create `composer.json` first:

```bash
composer init --name=your-name/your-bot --no-interaction
composer require keychat/bots
```

Local development:

```bash
git clone https://github.com/mikhailrepka/keychat-bots-php.git
cd keychat-bots-php
composer install
composer test
```

For a local checkout, add a Composer `path` repository pointing to `../keychat-bots-php`, then run `composer require keychat/bots:@dev`.

## Connect and reply

Create a bot in Key Chat and issue its runtime token. In the existing bot's editor, enter a public HTTPS webhook URL and save it; Key Chat reveals the signing secret once. Store the values on your server as `KEYCHAT_BOT_TOKEN` and `KEYCHAT_WEBHOOK_SECRET`. Rotating the webhook secret invalidates the previous value immediately.

```php
<?php
use KeyChat\Bots\Client;

$bot = new Client(getenv('KEYCHAT_BOT_TOKEN'));
$result = $bot->sendMessage($chatId, [
    'text' => '**Choose an action**',
    'format' => 'markdown',
    'keyboard' => ['rows' => [
        [Client::commandButton('/help')],
        [Client::callbackButton('details', 'Details', 'job-42')],
    ]],
    'menu' => ['rows' => [[Client::commandButton('/start'), Client::commandButton('/settings')]]],
], idempotencyKey: bin2hex(random_bytes(16)));
```

Omit `menu` to keep the current bot menu; send `'menu' => null` to hide it. Callback buttons do not post a visible user message. Use `editMessage()` to replace the content of a bot message and `reply()` for replies.

```php
$media = $bot->uploadMedia(__DIR__ . '/result.png', 'image/png');
$bot->sendMessage($chatId, ['attachments' => [['type' => 'image', ...$media]]]);
```

## Receive webhooks

Verify the raw request body before JSON decoding:

```php
<?php
use KeyChat\Bots\Webhook;

$rawBody = file_get_contents('php://input');
$result = Webhook::dispatch($rawBody, getallheaders(), getenv('KEYCHAT_WEBHOOK_SECRET'), [
    'message.created' => function (array $event) use ($bot): array {
        $bot->sendMessage($event['data']['chat_id'], ['text' => 'Echo: ' . $event['data']['message']['text']]);
        return [];
    },
    'button.clicked' => fn (array $event): array => ['ok' => true],
    'inline.query' => fn (array $event): array => ['results' => [[
        'id' => 'one', 'type' => 'text', 'text' => $event['data']['query'],
    ]]],
]);
header('Content-Type: application/json');
echo json_encode($result, JSON_THROW_ON_ERROR);
```

Queued events may be retried. Deduplicate by webhook `event.id` and use an `idempotency_key` for outbound messages.

A complete endpoint is included in [`examples/webhook.php`](./examples/webhook.php). Run it locally with `KEYCHAT_BOT_TOKEN=… KEYCHAT_WEBHOOK_SECRET=… php -S 127.0.0.1:3000 -t examples` and configure `/webhook.php` as the webhook path.

## Documentation

Build and serve the generated API reference:

```bash
composer install
composer docs
php -S 127.0.0.1:8000 -t docs
```

Full bot platform documentation: <https://chat.wokey.pro/docs/bots/>. Default API endpoint: `https://chat.wokey.pro/_keychat`; pass the second constructor argument for local tests.
