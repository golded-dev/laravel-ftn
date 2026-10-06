<?php

declare(strict_types=1);

namespace Golded\Ftn\Support;

final class MojibakeRepairer
{
    private const string LITERAL_DEGREE = '/([0-9][ \t]*°|°[ \t]*[CF](?![A-Za-z]))/u';

    /**
     * @var list<string>
     */
    private const array VISIBLE_ENCODINGS = [
        'CP850',
        'CP437',
        'CP865',
        'ISO-8859-1',
        'Windows-1252',
    ];

    /**
     * @var list<string>
     */
    private const array INTENDED_ENCODINGS = [
        'UTF-8',
        'ISO-8859-1',
        'Windows-1252',
        'CP850',
    ];

    /**
     * @var list<string>
     */
    private const array DAMAGE_MARKERS = [
        'Ã',
        'Â',
        'â',
        '�',
        '°',
        '÷',
        'õ',
        'Õ',
        '┼',
        '▀',
        '³',
    ];

    /**
     * @var list<string>
     */
    private const array PLAUSIBLE_CHARACTERS = [
        'å',
        'æ',
        'ø',
        'ä',
        'ö',
        'ü',
        'ß',
        'Å',
        'Æ',
        'Ø',
        'Ä',
        'Ö',
        'Ü',
    ];

    /**
     * @var list<string>
     */
    private const array PLAUSIBLE_WORDS = [
        'møde',
        'för',
        'daß',
        'müßte',
        'gehört',
        'geändert',
        'ændret',
        'på',
        'ikke',
    ];

    public static function repair(
        string $text,
        ?string $declaredCharset = null,
        bool $preferQuotedLines = true,
    ): MojibakeRepairResult {
        $lines = preg_split("/\r\n|\n|\r/", $text);

        if ($lines === false) {
            $lines = [$text];
        }

        $changed = false;
        $confidence = 0.0;
        $armourEnd = null;

        foreach ($lines as $index => $line) {
            if ($armourEnd === null && preg_match('/^-----BEGIN PGP (SIGNED MESSAGE|MESSAGE|SIGNATURE|PUBLIC KEY BLOCK|PRIVATE KEY BLOCK)-----$/D', $line, $begin) === 1) {
                $kind = $begin[1] === 'SIGNED MESSAGE' ? 'SIGNATURE' : $begin[1];
                $armourEnd = "-----END PGP {$kind}-----";
            }

            if ($armourEnd !== null) {
                if ($line === $armourEnd) {
                    $armourEnd = null;
                }

                continue;
            }

            if ($line === '') {
                continue;
            }

            $preferRepair = $preferQuotedLines && self::isQuotedLine($line);
            $repair = self::repairLine($line, $declaredCharset, $preferRepair);

            if (! $repair->changed) {
                continue;
            }

            $lines[$index] = $repair->text;
            $changed = true;
            $confidence += $repair->confidence;
        }

        return new MojibakeRepairResult(
            implode("\n", $lines),
            $changed,
            $changed ? min(1.0, $confidence / max(count($lines), 1)) : 0.0,
        );
    }

    private static function repairLine(
        string $line,
        ?string $declaredCharset,
        bool $preferRepair,
    ): MojibakeRepairResult {
        $byteCount = (ord($line[0]) - 32) & 63;

        if ($byteCount > 0 && strlen($line) === 1 + 4 * (int) ceil($byteCount / 3)
            && preg_match('/^[\x20-\x60]+$/', $line) === 1) {
            return new MojibakeRepairResult($line, false, 0.0);
        }

        $decodedHeader = self::decodeMimeHeader($line);

        if ($decodedHeader !== null && $decodedHeader !== $line && ! self::introducesUnsafeCharacters($line, $decodedHeader)) {
            return new MojibakeRepairResult($decodedHeader, true, 0.95);
        }

        if (self::isAsciiOnly($line)) {
            return new MojibakeRepairResult($line, false, 0.0);
        }

        $originalScore = self::scoreText($line);
        $bestText = $line;
        $bestScore = 0.0;

        foreach (self::visibleEncodings($declaredCharset) as $visibleEncoding) {
            foreach (self::intendedEncodings($declaredCharset) as $intendedEncoding) {
                if ($visibleEncoding === $intendedEncoding) {
                    continue;
                }

                $candidate = self::reinterpret($line, $visibleEncoding, $intendedEncoding);

                if ($candidate === null) {
                    continue;
                }

                if ($candidate === $line) {
                    continue;
                }

                if ($intendedEncoding !== 'UTF-8' && mb_strlen(trim($line)) > 1
                    && preg_match('/[A-Za-z]/', $line) !== 1 && ! self::hasPlausibleCharacter($line)) {
                    continue;
                }

                if (self::introducesUnsafeCharacters($line, $candidate)) {
                    continue;
                }

                $score = self::scoreText($candidate) - $originalScore;

                if ($score <= $bestScore) {
                    continue;
                }

                $bestText = $candidate;
                $bestScore = $score;
            }
        }

        $threshold = $preferRepair ? 1.5 : 2.5;

        if ($bestScore < $threshold) {
            return new MojibakeRepairResult($line, false, 0.0);
        }

        return new MojibakeRepairResult(
            $bestText,
            true,
            min(0.9, 0.4 + ($bestScore / 10.0)),
        );
    }

    /**
     * @return list<string>
     */
    private static function visibleEncodings(?string $declaredCharset): array
    {
        $encoding = self::normalizeCharset($declaredCharset);

        if ($encoding === null) {
            return self::VISIBLE_ENCODINGS;
        }

        return array_values(array_unique([$encoding, ...self::VISIBLE_ENCODINGS]));
    }

    /**
     * @return list<string>
     */
    private static function intendedEncodings(?string $declaredCharset): array
    {
        $encoding = self::normalizeCharset($declaredCharset);

        if ($encoding === null) {
            return self::INTENDED_ENCODINGS;
        }

        return array_values(array_unique([$encoding, ...self::INTENDED_ENCODINGS]));
    }

    private static function normalizeCharset(?string $charset): ?string
    {
        if ($charset === null || trim($charset) === '') {
            return null;
        }

        return CharsetDetector::detect("\x01CHRS: {$charset}", $charset);
    }

    private static function reinterpret(
        string $text,
        string $visibleEncoding,
        string $intendedEncoding,
        bool $framed = false,
    ): ?string {
        $framed = $framed || preg_match('/[\x{2500}-\x{259F}]/u', str_replace(['┼', '▀'], '', $text)) === 1;

        if ($intendedEncoding === 'UTF-8') {
            $utf8 = self::reinterpretSegments($text, self::LITERAL_DEGREE, $visibleEncoding, $intendedEncoding, $framed);

            if ($utf8 !== null) {
                return $utf8;
            }
        }

        if (self::hasPlausibleCharacter($text)) {
            $words = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

            if ($words === false) {
                return null;
            }

            foreach ($words as $index => $word) {
                if (self::hasPlausibleCharacter($word)) {
                    continue;
                }
                if (preg_match('/[A-Za-z]/', $word) !== 1) {
                    continue;
                }
                if (! str_contains($word, 'µ') && ! array_any(self::DAMAGE_MARKERS, static fn (string $marker): bool => str_contains($word, $marker))) {
                    continue;
                }

                $candidate = self::reinterpret($word, $visibleEncoding, $intendedEncoding, $framed);

                if ($candidate !== null && self::scoreText($candidate) - self::scoreText($word) >= 2.5) {
                    $words[$index] = $candidate;
                }
            }

            return implode('', $words);
        }

        return self::reinterpretSegments(
            $text,
            '/([0-9][ \t]*°|°[ \t]*[CF](?![A-Za-z])|[\x{2500}-\x{259F}]+)/u',
            $visibleEncoding,
            $intendedEncoding,
            $framed,
        );
    }

    private static function hasPlausibleCharacter(string $text): bool
    {
        return array_any(self::PLAUSIBLE_CHARACTERS, static fn (string $char): bool => str_contains($text, $char));
    }

    private static function introducesUnsafeCharacters(string $original, string $candidate): bool
    {
        $pattern = '/[\x00-\x08\x0A-\x1F\x7F-\x{009F}\x{FFFD}]/u';
        preg_match_all($pattern, $original, $before);
        preg_match_all($pattern, $candidate, $after);
        $counts = array_count_values($before[0]);
        return array_any(array_count_values($after[0]), fn($count, $char): bool => $count > ($counts[$char] ?? 0));
    }

    private static function reinterpretSegments(
        string $text,
        string $pattern,
        string $visibleEncoding,
        string $intendedEncoding,
        bool $framed,
    ): ?string {
        $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE);

        if ($parts === false) {
            return null;
        }

        $candidate = '';

        foreach ($parts as $index => [$part, $offset]) {
            $neighbors = ($offset > 0 ? substr($text, $offset - 1, 1) : '').substr($text, $offset + strlen($part), 1);
            $damagedLetter = ! $framed && in_array($part, ['┼', '▀'], true)
                && preg_match('/[A-Za-z]/', $neighbors) === 1;

            if (($index % 2 === 1 && ! $damagedLetter) || $part === '') {
                $candidate .= $part;

                continue;
            }

            $converted = self::reinterpretPart($part, $visibleEncoding, $intendedEncoding);

            if ($converted === null) {
                return null;
            }

            $candidate .= $converted;
        }

        return $candidate;
    }

    private static function reinterpretPart(string $text, string $visibleEncoding, string $intendedEncoding): ?string
    {
        $bytes = self::convertStrict('UTF-8', $visibleEncoding, $text);

        if (! is_string($bytes) || $bytes === '') {
            return null;
        }

        if ($intendedEncoding === 'UTF-8' && ! mb_check_encoding($bytes, 'UTF-8')) {
            return null;
        }

        $converted = self::convertStrict($intendedEncoding, 'UTF-8', $bytes);

        if (! is_string($converted) || $converted === '') {
            return null;
        }

        return $converted;
    }

    private static function convertStrict(string $from, string $to, string $text): string|false
    {
        // Invalid candidates are expected; capture iconv diagnostics locally.
        set_error_handler(static fn (): bool => true);

        try {
            return iconv($from, $to, $text);
        } finally {
            restore_error_handler();
        }
    }

    private static function scoreText(string $text): float
    {
        $score = 0.0;

        foreach (self::DAMAGE_MARKERS as $marker) {
            if ($marker === '°') {
                preg_match_all('/°/u', preg_replace(self::LITERAL_DEGREE, '', $text) ?? $text, $matches);
                $score -= count($matches[0]) * 2.5;

                continue;
            }

            $score -= substr_count($text, $marker) * 2.5;
        }

        foreach (self::PLAUSIBLE_CHARACTERS as $character) {
            $score += substr_count($text, $character) * 1.5;
        }

        preg_match_all('/[A-Za-z]µ(?=[A-Za-z])/u', $text, $microSigns);
        $score -= count($microSigns[0]) * 2.5;

        $lower = mb_strtolower($text);

        foreach (self::PLAUSIBLE_WORDS as $word) {
            if (str_contains($lower, $word)) {
                $score += 2.0;
            }
        }

        return $score;
    }

    private static function decodeMimeHeader(string $line): ?string
    {
        if (preg_match_all('/=\?([^?\s]+)\?([QB])\?([^?]*)\?=/i', $line, $words, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) === 0) {
            return null;
        }

        $decoded = '';
        $offset = 0;

        foreach ($words as $word) {
            $charset = $word[1][0];
            $payload = $word[3][0];
            $bytes = strtoupper($word[2][0]) === 'B'
                ? base64_decode($payload, true)
                : quoted_printable_decode(str_replace('_', ' ', $payload));

            if ($bytes === false) {
                return null;
            }

            $part = self::convertStrict($charset, 'UTF-8', $bytes);

            if ($part === false && in_array(strtoupper(str_replace('_', '-', $charset)), ['ASCII', 'US-ASCII'], true)) {
                $part = self::convertStrict('ISO-8859-1', 'UTF-8', $bytes);
            }

            if ($part === false) {
                return null;
            }

            $between = substr($line, $offset, $word[0][1] - $offset);

            // RFC 2047 ignores whitespace only between adjacent encoded words.
            if ($offset === 0 || trim($between) !== '') {
                $decoded .= $between;
            }

            $decoded .= $part;
            $offset = $word[0][1] + strlen($word[0][0]);
        }

        return $decoded.substr($line, $offset);
    }

    private static function isAsciiOnly(string $text): bool
    {
        return preg_match('/[^\x00-\x7F]/', $text) !== 1;
    }

    private static function isQuotedLine(string $line): bool
    {
        return preg_match('/^\s*[A-Za-z0-9]{0,4}>/', $line) === 1;
    }
}
