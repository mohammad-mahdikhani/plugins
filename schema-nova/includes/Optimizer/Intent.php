<?php
/**
 * Classify a search query into Google-style intent buckets.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Optimizer;

use SchemaNova\Support\Text;

final class Intent
{
    public const INFORMATIONAL = 'informational';
    public const COMMERCIAL    = 'commercial';
    public const TRANSACTIONAL = 'transactional';
    public const NAVIGATIONAL  = 'navigational';

    /**
     * @var array<string, list<string>>
     */
    private const CUES = [
        self::TRANSACTIONAL => [
            'خرید', 'قیمت', 'سفارش', 'فروش', 'تخفیف', 'ارزان', 'پرداخت',
            'buy', 'price', 'order', 'cheap', 'discount', 'coupon', 'shop',
            'قیمت روز', 'فروشگاه',
        ],
        self::COMMERCIAL => [
            'بهترین', 'مقایسه', 'بررسی', 'نقد', 'vs', 'علیه', 'تفاوت',
            'best', 'review', 'compare', 'vs', 'alternative', 'top',
        ],
        self::NAVIGATIONAL => [
            'ورود', 'login', 'سایت', 'رسمی', 'official', 'دانلود از',
        ],
        self::INFORMATIONAL => [
            'چیست', 'چگونه', 'راهنما', 'آموزش', 'چرا', 'معنی', 'تعریف',
            'how', 'what', 'why', 'guide', 'tutorial', 'meaning',
            'wiki', 'مثال',
        ],
    ];

    public static function classify(string $query, string $brand = ''): string
    {
        $q = Text::lower($query);

        if ($brand !== '' && Text::contains($q, $brand) && count(Text::tokens($query)) <= 3) {
            return self::NAVIGATIONAL;
        }

        foreach (self::CUES as $intent => $cues) {
            if (Text::containsAny($q, $cues)) {
                return $intent;
            }
        }

        return self::INFORMATIONAL;
    }

    /**
     * Prefix/suffix patterns that match intent without stuffing keywords.
     *
     * @return array{prefix:string,suffix:string}
     */
    public static function titleShape(string $intent, string $language): array
    {
        $fa = str_starts_with(strtolower($language), 'fa');

        return match ($intent) {
            self::TRANSACTIONAL => $fa
                ? ['prefix' => '', 'suffix' => ' | خرید']
                : ['prefix' => 'Buy ', 'suffix' => ''],
            self::COMMERCIAL => $fa
                ? ['prefix' => 'بهترین ', 'suffix' => '']
                : ['prefix' => 'Best ', 'suffix' => ''],
            self::INFORMATIONAL => $fa
                ? ['prefix' => '', 'suffix' => ' | راهنما']
                : ['prefix' => '', 'suffix' => ' Guide'],
            default => ['prefix' => '', 'suffix' => ''],
        };
    }
}
