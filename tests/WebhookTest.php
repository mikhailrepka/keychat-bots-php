<?php
declare(strict_types=1);

use KeyChat\Bots\Webhook;
use PHPUnit\Framework\TestCase;

final class WebhookTest extends TestCase
{
    public function testVerifiesWebhook(): void
    {
        $body = json_encode(['api_version' => '1', 'type' => 'endpoint.test', 'data' => []], JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $body, 'secret');
        $event = Webhook::verify($body, ['X-KeyChat-Timestamp' => $timestamp, 'X-KeyChat-Signature' => 'v1=' . $signature], 'secret');
        self::assertSame('endpoint.test', $event['type']);
    }
}
