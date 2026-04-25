<?php

declare(strict_types=1);

namespace Golded\Ftn\Support;

final class MojibakeRepairer
{
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

        foreach ($lines as $index => $line) {
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
        $decodedHeader = self::decodeMimeHeader($line);

        if ($decodedHeader !== null && $decodedHeader !== $line) {
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

    private static function reinterpret(string $text, string $visibleEncoding, string $intendedEncoding): ?string
    {
        $bytes = @iconv('UTF-8', "{$visibleEncoding}//IGNORE", $text);

        if (! is_string($bytes) || $bytes === '') {
            return null;
        }

        if ($intendedEncoding === 'UTF-8' && ! mb_check_encoding($bytes, 'UTF-8')) {
            return null;
        }

        $converted = @iconv($intendedEncoding, 'UTF-8//IGNORE', $bytes);

        if (! is_string($converted) || $converted === '') {
            return null;
        }

        return $converted;
    }

    private static function scoreText(string $text): float
    {
        $score = 0.0;

        foreach (self::DAMAGE_MARKERS as $marker) {
            if ($marker === '°') {
                preg_match_all('/(?<!\d)°(?!\d)/u', $text, $matches);
                $score -= count($matches[0]) * 2.5;

                continue;
            }

            $score -= substr_count($text, $marker) * 2.5;
        }

        foreach (self::PLAUSIBLE_CHARACTERS as $character) {
            $score += substr_count($text, $character) * 1.5;
        }

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
        if (preg_match('/=\?.+\?[QB]\?.+\?=/i', $line) !== 1) {
            return null;
        }

        $decoded = @iconv_mime_decode($line, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');

        return is_string($decoded) ? $decoded : null;
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
