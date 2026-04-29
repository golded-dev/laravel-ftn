<?php

declare(strict_types=1);

namespace Golded\Ftn\Support;

use Golded\Ftn\ControlLine;
use Golded\Ftn\FtnAddress;
use Golded\Ftn\MessageControlLines;

final class ControlLines
{
    public static function extractMsgid(string $raw): ?string
    {
        if (preg_match('/\x01MSGID:\s*(.+?)(?:[\r\n\x00]|\x01|$)/s', $raw, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }

    public static function parseMessage(string $text): MessageControlLines
    {
        $kludges = [];
        $msgid = null;
        $reply = null;
        $charset = null;
        $seenBy = [];
        $path = [];
        $tearline = null;
        $origin = null;
        $originAddress = null;

        foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $line) {
            $line = rtrim($line, "\x00");

            if ($line === '') {
                continue;
            }

            if (self::isQuotedLine($line)) {
                continue;
            }

            if (str_starts_with($line, "\x01")) {
                $controlLine = self::parseKludge($line);

                if (!$controlLine instanceof ControlLine) {
                    continue;
                }

                $kludges[] = $controlLine;

                match ($controlLine->name) {
                    'MSGID' => $msgid ??= $controlLine->value,
                    'REPLY' => $reply ??= $controlLine->value,
                    'CHRS', 'CHARSET' => $charset ??= $controlLine->value,
                    default => null,
                };

                continue;
            }

            if (preg_match('/^SEEN-BY:\s*(.*)$/', $line, $matches) === 1) {
                $seenBy[] = trim($matches[1]);

                continue;
            }

            if (preg_match('/^PATH:\s*(.*)$/', $line, $matches) === 1) {
                $path[] = trim($matches[1]);

                continue;
            }

            if (str_starts_with($line, '---')) {
                $tearline ??= $line;

                continue;
            }

            if (preg_match('/^\s\* Origin:\s*(.*)$/', $line, $matches) === 1) {
                $origin ??= trim($matches[1]);

                if (!$originAddress instanceof FtnAddress && preg_match('/\((?<address>\d+:\d+\/\d+(?:\.\d+)?(?:@[A-Za-z0-9][A-Za-z0-9._-]*)?)\)\s*$/', $line, $addressMatches) === 1) {
                    $originAddress = FtnAddress::tryFromString($addressMatches['address']);
                }
            }
        }

        return new MessageControlLines(
            kludges: $kludges,
            msgid: $msgid,
            reply: $reply,
            charset: $charset,
            seenBy: $seenBy,
            path: $path,
            tearline: $tearline,
            origin: $origin,
            originAddress: $originAddress,
        );
    }

    private static function parseKludge(string $line): ?ControlLine
    {
        if (preg_match('/^\x01(?<name>[A-Za-z][A-Za-z0-9-]*):\s*(?<value>.*)$/', $line, $matches) !== 1) {
            return null;
        }

        return new ControlLine(
            name: strtoupper($matches['name']),
            value: trim($matches['value']),
            raw: $line,
        );
    }

    private static function isQuotedLine(string $line): bool
    {
        return preg_match('/^\s*(?:>|[A-Za-z0-9]{1,6}>)/', $line) === 1;
    }
}
