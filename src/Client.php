<?php
declare(strict_types=1);

namespace KeyChat\Bots;

final class Client
{
    public const DEFAULT_BASE_URL = 'https://chat.wokey.pro/_keychat';

    public function __construct(
        private readonly string $token,
        private readonly string $baseUrl = self::DEFAULT_BASE_URL,
    ) {
        if ($token === '') throw new \InvalidArgumentException('Key Chat bot token is required');
    }

    public function sendMessage(
        string $chatId,
        array $content,
        ?string $idempotencyKey = null,
        ?string $replyToEventId = null,
        ?string $replaceEventId = null,
    ): array {
        $payload = ['chat_id' => $chatId, 'content' => $content];
        foreach (['idempotency_key' => $idempotencyKey, 'reply_to_event_id' => $replyToEventId, 'replace_event_id' => $replaceEventId] as $key => $value) {
            if ($value !== null) $payload[$key] = $value;
        }
        return $this->request('POST', '/v1/bot/messages', $payload);
    }

    public function editMessage(string $chatId, string $eventId, array $content, ?string $idempotencyKey = null): array
    {
        return $this->sendMessage($chatId, $content, $idempotencyKey, replaceEventId: $eventId);
    }

    public function reply(string $chatId, string $eventId, array $content, ?string $idempotencyKey = null): array
    {
        return $this->sendMessage($chatId, $content, $idempotencyKey, replyToEventId: $eventId);
    }

    public function uploadMedia(string $path, ?string $mimeType = null, ?string $filename = null): array
    {
        if (!is_file($path)) throw new \InvalidArgumentException("Media file not found: {$path}");
        $file = new \CURLFile($path, $mimeType ?? mime_content_type($path) ?: 'application/octet-stream', $filename ?? basename($path));
        return $this->request('POST', '/v1/bot/media', null, ['file' => $file]);
    }

    private function request(string $method, string $path, ?array $json = null, ?array $multipart = null): array
    {
        $curl = curl_init(rtrim($this->baseUrl, '/') . $path);
        $headers = ['Authorization: Bearer ' . $this->token];
        $options = [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 120];
        if ($json !== null) {
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_POSTFIELDS] = json_encode($json, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } elseif ($multipart !== null) {
            $options[CURLOPT_POSTFIELDS] = $multipart;
        }
        $options[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        if ($response === false) throw new \RuntimeException(curl_error($curl));
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        $body = $response === '' ? [] : json_decode($response, true, flags: JSON_THROW_ON_ERROR);
        if ($status < 200 || $status >= 300) throw new KeyChatException($status, $body);
        return is_array($body) ? $body : [];
    }

    public static function commandButton(string $command, array $options = []): array { return [...$options, 'command' => $command]; }
    public static function callbackButton(string $id, string $text, ?string $data = null, array $options = []): array { return [...$options, 'id' => $id, 'text' => $text, 'action' => ['type' => 'callback', 'data' => $data]]; }
    public static function messageButton(string $text, string $message, array $options = []): array { return [...$options, 'text' => $text, 'action' => ['type' => 'send_message', 'text' => $message]]; }
    public static function urlButton(string $text, string $url, array $options = []): array { return [...$options, 'text' => $text, 'action' => ['type' => 'open_url', 'url' => $url]]; }
}
