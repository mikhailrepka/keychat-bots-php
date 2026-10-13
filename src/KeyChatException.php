<?php
declare(strict_types=1);

namespace KeyChat\Bots;

final class KeyChatException extends \RuntimeException
{
    public function __construct(public readonly int $status, public readonly mixed $body)
    {
        parent::__construct("Key Chat API error: {$status}", $status);
    }
}
