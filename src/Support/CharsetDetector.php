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
        'CP-866' => 'CP866',
        '+7FIDO' => 'CP866',
        '+7_FIDO' => 'CP866',
        'FIDO7' => 'CP866',
        'FIDO_7' => 'CP866',
        'RUS' => 'CP866',
        'KOI8-R' => 'KOI8-R',
        'KOI8R' => 'KOI8-R',
        'KOI' => 'KOI8-R',
        'KOI8' => 'KOI8-R',
        'GOST' => 'KOI8-R',
        'CP20866' => 'KOI8-R',
        'KOI8-U' => 'KOI8-U',
        'KOI8U' => 'KOI8-U',
        'KOU' => 'KOI8-U',
        'KOI-U' => 'KOI8-U',
        'CP21866' => 'KOI8-U',
        'CP1125' => 'CP1125',
        'UKR' => 'CP1125',
        'CP437' => 'CP437',
        'IBM437' => 'CP437',
        'CP1251' => 'CP1251',
        'WIN' => 'CP1251',
        'WIN-1251' => 'CP1251',
        'WINDOWS-1251' => 'CP1251',
        'CP-1251' => 'CP1251',
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
