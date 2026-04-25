<?php

declare(strict_types=1);

namespace Golded\Ftn\Support;

final class ControlLines
{
    public static function extractMsgid(string $raw): ?string
    {
        if (preg_match('/\x01MSGID:\s*(.+?)(?:[\r\n\x00]|\x01|$)/s', $raw, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }
}
