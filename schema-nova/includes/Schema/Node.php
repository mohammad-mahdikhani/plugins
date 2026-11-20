<?php
/**
 * JSON-LD node helpers.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Schema;

use SchemaNova\Support\Arr;

final class Node
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function make(string $type, string $id, array $data = []): array
    {
        return Arr::compact(array_merge(
            [
                '@type' => $type,
                '@id'   => $id,
            ],
            $data
        ));
    }

    /**
     * @return array{ '@id': string }
     */
    public static function ref(string $id): array
    {
        return ['@id' => $id];
    }

    /**
     * @param list<string> $urls
     * @return array<string, mixed>|string|list<mixed>
     */
    public static function image(array $urls, string $id = ''): mixed
    {
        $urls = array_values(array_filter($urls));
        if ($urls === []) {
            return [];
        }

        if (count($urls) === 1 && $id === '') {
            return $urls[0];
        }

        $objects = [];
        foreach ($urls as $i => $url) {
            $objects[] = Arr::compact([
                '@type'      => 'ImageObject',
                '@id'        => $id !== '' && $i === 0 ? $id : null,
                'url'        => $url,
                'contentUrl' => $url,
            ]);
        }

        return count($objects) === 1 ? $objects[0] : $objects;
    }

    public static function isoDate(?string $mysql, string $timezone = ''): string
    {
        if ($mysql === null || $mysql === '' || $mysql === '0000-00-00 00:00:00') {
            return '';
        }

        $ts = strtotime($mysql);
        if ($ts === false) {
            return '';
        }

        $tz = $timezone !== '' ? $timezone : wp_timezone_string();
        try {
            $dt = new \DateTimeImmutable('@' . $ts);
            $dt = $dt->setTimezone(new \DateTimeZone($tz ?: 'UTC'));
        } catch (\Exception $e) {
            return gmdate('c', $ts);
        }

        return $dt->format('c');
    }
}
