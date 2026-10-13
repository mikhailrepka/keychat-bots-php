<?php
declare(strict_types=1);

namespace KeyChat\Bots;

final class Webhook
{
    public static function verify(string $rawBody, array $headers, string $secret, int $toleranceSeconds = 300): array
    {
        $normalized = [];
        foreach ($headers as $key => $value) $normalized[strtolower((string) $key)] = is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
        $timestamp = $normalized['x-keychat-timestamp'] ?? '';
        $signature = preg_replace('/^v1=/', '', $normalized['x-keychat-signature'] ?? '') ?? '';
        if (!ctype_digit($timestamp) || !preg_match('/^[a-f0-9]{64}$/i', $signature)) throw new \UnexpectedValueException('Invalid Key Chat webhook signature headers');
        if (abs(time() - (int) $timestamp) > $toleranceSeconds) throw new \UnexpectedValueException('Stale Key Chat webhook');
        $expected = hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
        if (!hash_equals($expected, $signature)) throw new \UnexpectedValueException('Invalid Key Chat webhook signature');
        $event = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($event)) throw new \UnexpectedValueException('Webhook payload must be an object');
        return $event;
    }

    public static function dispatch(string $rawBody, array $headers, string $secret, array $handlers): mixed
    {
        $event = self::verify($rawBody, $headers, $secret);
        $handler = $handlers[(string) ($event['type'] ?? '')] ?? $handlers['*'] ?? null;
        return $handler ? $handler($event) : [];
    }
}
