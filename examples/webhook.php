<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use KeyChat\Bots\Client;
use KeyChat\Bots\Webhook;

$bot = new Client((string) getenv('KEYCHAT_BOT_TOKEN'));
$rawBody = (string) file_get_contents('php://input');

try {
    $result = Webhook::dispatch($rawBody, getallheaders(), (string) getenv('KEYCHAT_WEBHOOK_SECRET'), [
        'message.created' => function (array $event) use ($bot): array {
            $bot->sendMessage($event['data']['chat_id'], ['text' => 'Echo: ' . $event['data']['message']['text']]);
            return [];
        },
        'button.clicked' => fn (): array => ['ok' => true],
        'inline.query' => fn (array $event): array => ['results' => [[
            'id' => 'result', 'type' => 'text', 'text' => $event['data']['query'],
        ]]],
    ]);
    header('Content-Type: application/json');
    echo json_encode($result, JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => $error->getMessage()], JSON_THROW_ON_ERROR);
}
