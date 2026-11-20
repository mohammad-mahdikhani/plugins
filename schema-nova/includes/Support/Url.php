<?php
/**
 * URL helpers.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Support;

final class Url
{
    public static function home(): string
    {
        $url = home_url('/');
        return trailingslashit($url);
    }

    public static function current(): string
    {
        if (function_exists('wp_get_canonical_url')) {
            $canonical = wp_get_canonical_url();
            if (is_string($canonical) && $canonical !== '') {
                return $canonical;
            }
        }

        global $wp;
        if (isset($wp) && is_object($wp) && isset($wp->request)) {
            return home_url(user_trailingslashit($wp->request));
        }

        return self::home();
    }

    public static function id(string $url, string $fragment): string
    {
        $url = strtok($url, '#') ?: $url;
        $url = rtrim($url, '/');

        return $url . '/#' . ltrim($fragment, '#');
    }

    public static function abs(string $maybe): string
    {
        if ($maybe === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $maybe)) {
            return $maybe;
        }

        return home_url($maybe);
    }

    public static function normalize(string $url): string
    {
        $url = trim($url);
        $url = strtok($url, '#') ?: $url;
        $parts = wp_parse_url($url);
        if (! is_array($parts) || empty($parts['host'])) {
            return untrailingslashit($url);
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host   = strtolower($parts['host']);
        $path   = $parts['path'] ?? '/';
        $query  = isset($parts['query']) ? '?' . $parts['query'] : '';

        return $scheme . '://' . $host . untrailingslashit($path) . $query;
    }
}
