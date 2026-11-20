<?php
/**
 * String helpers used by schema builders and the SERP optimizer.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Support;

final class Text
{
    public static function clean(string $value, int $max = 0): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = wp_strip_all_tags($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = trim($value);

        if ($max > 0) {
            $value = self::limit($value, $max);
        }

        return $value;
    }

    public static function limit(string $value, int $max): string
    {
        if (self::len($value) <= $max) {
            return $value;
        }

        $slice = self::substr($value, 0, max(1, $max - 1));
        $slice = preg_replace('/\s+\S*$/u', '', $slice) ?? $slice;

        return rtrim($slice, " \t.,;:،؛-") . '…';
    }

    public static function len(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return (int) mb_strlen($value, 'UTF-8');
        }

        return strlen($value);
    }

    public static function substr(string $value, int $start, int $length): string
    {
        if (function_exists('mb_substr')) {
            return (string) mb_substr($value, $start, $length, 'UTF-8');
        }

        return substr($value, $start, $length);
    }

    public static function lower(string $value): string
    {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value, 'UTF-8');
        }

        return strtolower($value);
    }

    /**
     * Token overlap between a query and page text. Good enough for "does this
     * title still describe the page?" without a remote NLP service.
     */
    public static function overlap(string $needle, string $haystack): float
    {
        $needleTokens   = self::tokens($needle);
        $haystackTokens = self::tokens($haystack);

        if ($needleTokens === [] || $haystackTokens === []) {
            return 0.0;
        }

        $haystackSet = array_fill_keys($haystackTokens, true);
        $hits        = 0;

        foreach ($needleTokens as $token) {
            if (isset($haystackSet[$token])) {
                $hits++;
            }
        }

        return $hits / count($needleTokens);
    }

    /**
     * @return list<string>
     */
    public static function tokens(string $value): array
    {
        $value = self::lower(self::clean($value));
        $value = str_replace(['‌', 'ـ'], ' ', $value);
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $stop = [
            'the', 'and', 'for', 'with', 'from', 'this', 'that', 'you', 'your',
            'در', 'به', 'از', 'که', 'این', 'را', 'با', 'برای', 'یک', 'تا',
            'است', 'های', 'می', 'یا', 'هم', 'بر', 'شود', 'شده',
        ];

        $out = [];
        foreach ($parts as $part) {
            if (self::len($part) < 2 || in_array($part, $stop, true)) {
                continue;
            }
            $out[] = $part;
        }

        return array_values(array_unique($out));
    }

    public static function contains(string $haystack, string $needle): bool
    {
        return self::len($needle) > 0 && str_contains(self::lower($haystack), self::lower($needle));
    }

    /**
     * @param list<string> $needles
     */
    public static function containsAny(string $haystack, array $needles): bool
    {
        $haystack = self::lower($haystack);
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, self::lower($needle))) {
                return true;
            }
        }

        return false;
    }
}
