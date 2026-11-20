<?php
/**
 * Global helpers. WordPress plugin files traditionally expose a few functions.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

use SchemaNova\I18n;
use SchemaNova\Settings\Repository;
use SchemaNova\Support\Text;

if (! function_exists('sn__')) {
    /**
     * Translate a string, with built-in Persian fallbacks.
     */
    function sn__(string $text): string
    {
        return I18n::translate($text);
    }
}

if (! function_exists('schema_nova_settings')) {
    function schema_nova_settings(): Repository
    {
        return Repository::instance();
    }
}

if (! function_exists('schema_nova_clean_text')) {
    function schema_nova_clean_text(string $value, int $max = 0): string
    {
        return Text::clean($value, $max);
    }
}

if (! function_exists('array_is_list')) {
    /**
     * PHP 8.0 polyfill.
     *
     * @param array<mixed> $array
     */
    function array_is_list(array $array): bool
    {
        if ($array === []) {
            return true;
        }

        return array_keys($array) === range(0, count($array) - 1);
    }
}
