<?php
/**
 * Built-in Persian translations so the UI works without compiling .mo files.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova;

final class I18n
{
    /** @var array<string, string> */
    private static array $map = [];

    public static function boot(): void
    {
        load_plugin_textdomain('schema-nova', false, dirname(SCHEMA_NOVA_BASENAME) . '/languages');

        $locale = function_exists('determine_locale') ? determine_locale() : get_locale();
        if (str_starts_with($locale, 'fa')) {
            $file = SCHEMA_NOVA_DIR . 'languages/fa.php';
            if (is_readable($file)) {
                $loaded = include $file;
                if (is_array($loaded)) {
                    self::$map = $loaded;
                }
            }
        }
    }

    public static function translate(string $text): string
    {
        if (isset(self::$map[$text])) {
            return self::$map[$text];
        }

        return translate($text, 'schema-nova'); // phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction,WordPress.WP.I18n.NonSingularStringLiteralDomain
    }

    public static function isRtl(): bool
    {
        return function_exists('is_rtl') ? is_rtl() : true;
    }
}
