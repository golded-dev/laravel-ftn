<?php

declare(strict_types=1);

namespace Golded\Ftn\Support;

final class Text
{
    public static function parseBody(string $raw): string
    {
        $raw = rtrim($raw, "\x00");

        return str_replace(["\r\n", "\r"], ["\n", "\n"], $raw);
    }

    public static function toUtf8(string $value, string $charset = 'CP850'): string
    {
        $converted = mb_convert_encoding(rtrim($value, "\x00"), 'UTF-8', $charset);

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
