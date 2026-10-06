<?php

declare(strict_types=1);

namespace Golded\Ftn\Support;

use ValueError;

final class Text
{
    public static function parseBody(string $raw): string
    {
        $raw = rtrim($raw, "\x00");

        return str_replace(["\r\n", "\r"], ["\n", "\n"], $raw);
    }

    public static function toUtf8(string $value, string $charset = 'CP850'): string
    {
        $value = rtrim($value, "\x00");

        try {
            $converted = mb_convert_encoding($value, 'UTF-8', $charset);
        } catch (ValueError $error) {
            $iconvCharset = match (strtoupper($charset)) {
                'CP437', 'IBM437' => 'CP437',
                'CP1125' => 'CP1125',
                default => null,
            };

            if ($iconvCharset === null) {
                throw $error;
            }

            $converted = iconv($iconvCharset, 'UTF-8', $value);
        }

        return $converted === false ? '' : $converted;
    }

    public static function readNullPaddedField(string $raw, int $offset, int $length): string
    {
        $field = substr($raw, $offset, $length);
        $nullPosition = strpos($field, "\x00");

        if ($nullPosition !== false) {
            return substr($field, 0, $nullPosition);
        }

        return $field;
    }

    public static function syntheticId(string $from, string $to, string $subject, ?string $date, string $body): string
    {
        return 'hash:'.md5("{$from}\x00{$to}\x00{$subject}\x00{$date}\x00".substr($body, 0, 200));
    }
}
