<?php
/**
 * PSR-4 autoloader for the SchemaNova namespace.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova;

final class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register([self::class, 'load']);
    }

    public static function load(string $class): void
    {
        $prefix = 'SchemaNova\\';
        if (! str_starts_with($class, $prefix)) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $path     = SCHEMA_NOVA_DIR . 'includes/' . str_replace('\\', '/', $relative) . '.php';

        if (is_readable($path)) {
            require_once $path;
        }
    }
}
