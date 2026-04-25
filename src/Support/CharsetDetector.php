<?php

declare(strict_types=1);

namespace Golded\Ftn\Support;

final class CharsetDetector
{
    private const array MAP = [
        'CP850' => 'CP850',
        'IBM850' => 'CP850',
        'IBMPC' => 'CP850',
        'IBM' => 'CP850',
        'LATIN-1' => 'ISO-8859-1',
        'LATIN1' => 'ISO-8859-1',
        '8859-1' => 'ISO-8859-1',
        'ISO-8859-1' => 'ISO-8859-1',
        'ISO8859-1' => 'ISO-8859-1',
        'ASCII' => 'ASCII',
        'USASCII' => 'ASCII',
        'CP866' => 'CP866',
        'IBM866' => 'CP866',
        'KOI8-R' => 'KOI8-R',
        'KOI8R' => 'KOI8-R',
        'CP437' => 'CP437',
        'IBM437' => 'CP437',
        'CP1251' => 'CP1251',
        'CP1252' => 'CP1252',
        'CP1250' => 'CP1250',
        'LATIN-2' => 'ISO-8859-2',
        'ISO-8859-2' => 'ISO-8859-2',
    ];

    public static function detect(string $rawBody, string $fallback = 'CP850'): string
    {
        if (preg_match('/\x01(?:CHRS|CHARSET):\s*(\S+)/i', $rawBody, $matches) === 1) {
            $name = strtoupper($matches[1]);

            return self::MAP[$name] ?? $fallback;
        }

        return $fallback;
    }
}
