<?php
/**
 * Array helpers.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Support;

final class Arr
{
    /**
     * @param array<string, mixed> $array
     */
    public static function get(array $array, string $path, mixed $default = null): mixed
    {
        if ($path === '') {
            return $default;
        }

        $segments = explode('.', $path);
        $value    = $array;

        foreach ($segments as $segment) {
            if (is_object($value)) {
                $value = (array) $value;
            }
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $array
     * @return array<string, mixed>
     */
    public static function set(array $array, string $path, mixed $value): array
    {
        $segments = explode('.', $path);
        $ref      = &$array;

        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $ref[$segment] = $value;
                break;
            }
            if (! isset($ref[$segment]) || ! is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }

        return $array;
    }

    /**
     * Drop empty values so JSON-LD does not emit null/"" fields.
     *
     * @param mixed $value
     */
    public static function compact(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $out = [];
        foreach ($value as $key => $item) {
            $item = self::compact($item);
            if ($item === null || $item === '' || $item === []) {
                continue;
            }
            $out[$key] = $item;
        }

        return $out;
    }

    /**
     * @param list<mixed> $items
     * @return list<mixed>
     */
    public static function unique(array $items): array
    {
        $seen = [];
        $out  = [];
        foreach ($items as $item) {
            $hash = is_scalar($item) ? (string) $item : wp_json_encode($item);
            if (isset($seen[$hash])) {
                continue;
            }
            $seen[$hash] = true;
            $out[]       = $item;
        }

        return $out;
    }
}
