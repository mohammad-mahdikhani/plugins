<?php
/**
 * Build a unique, Google-compliant title and meta description from SERP queries.
 *
 * Follows Search Central title-link and snippet guidelines: descriptive, concise,
 * no stuffing, no boilerplate, unique per page, query language that still
 * matches on-page content.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Optimizer;

use SchemaNova\Support\Text;

final class TitleBuilder
{
    /**
     * @param list<array{query:string,impressions:int,clicks:int,ctr:float,position:float,intent:string}> $queries
     * @return array{title:string,description:string,primary_query:string,intent:string,reason:string}|null
     */
    public static function propose(
        string $currentTitle,
        string $pageText,
        array $queries,
        string $brand,
        string $language,
        bool $keepBrand,
        string $separator,
        float $minSimilarity
    ): ?array {
        if ($queries === []) {
            return null;
        }

        usort(
            $queries,
            static fn ($a, $b) => ($b['impressions'] <=> $a['impressions']) ?: ($b['clicks'] <=> $a['clicks'])
        );

        $primary = null;
        foreach ($queries as $row) {
            $query = trim((string) $row['query']);
            if ($query === '') {
                continue;
            }
            if ($brand !== '' && Text::lower($query) === Text::lower($brand)) {
                continue;
            }
            $similarity = Text::overlap($query, $pageText . ' ' . $currentTitle);
            if ($similarity < $minSimilarity) {
                continue;
            }
            $primary = $row + ['similarity' => $similarity];
            break;
        }

        if ($primary === null) {
            return null;
        }

        $query  = trim((string) $primary['query']);
        $intent = (string) ($primary['intent'] ?: Intent::classify($query, $brand));
        $shape  = Intent::titleShape($intent, $language);

        $core = self::titleCaseAware($query, $language);
        if (! Text::contains($core, Text::substr($currentTitle, 0, 18)) && Text::len($currentTitle) > 12) {
            // Keep a short unique hint from the current title if the query is generic.
            $hint = Text::limit($currentTitle, 28);
            if (! Text::contains($core, $hint) && Text::overlap($hint, $core) < 0.5) {
                $core = $core . ' — ' . $hint;
            }
        }

        $title = $shape['prefix'] . $core . $shape['suffix'];
        $title = preg_replace('/\s+/u', ' ', $title) ?? $title;
        $title = trim($title);

        if ($keepBrand && $brand !== '' && ! Text::contains($title, $brand)) {
            $withBrand = $title . $separator . $brand;
            if (Text::len($withBrand) <= 65) {
                $title = $withBrand;
            }
        }

        $title = Text::limit($title, 62);

        if (Text::lower($title) === Text::lower($currentTitle)) {
            return null;
        }

        $description = self::description($query, $pageText, $intent, $language);

        return [
            'title'          => $title,
            'description'    => $description,
            'primary_query'  => $query,
            'intent'         => $intent,
            'reason'         => sprintf(
                'Primary query "%s" (%s intent, %d impressions, position %.1f, CTR %.1f%%).',
                $query,
                $intent,
                (int) $primary['impressions'],
                (float) $primary['position'],
                ((float) $primary['ctr']) * 100
            ),
        ];
    }

    public static function description(string $query, string $pageText, string $intent, string $language): string
    {
        $snippet = Text::limit($pageText, 140);
        $fa      = str_starts_with(strtolower($language), 'fa');

        $lead = match ($intent) {
            Intent::TRANSACTIONAL => $fa
                ? 'قیمت، موجودی و خرید ' . $query . '. '
                : 'See price and buy ' . $query . '. ',
            Intent::COMMERCIAL => $fa
                ? 'مقایسه و بررسی ' . $query . '. '
                : 'Compare and review ' . $query . '. ',
            Intent::INFORMATIONAL => $fa
                ? 'پاسخ کامل برای «' . $query . '»: '
                : 'A clear answer for “' . $query . '”: ',
            default => $query . ' — ',
        };

        $text = $lead . $snippet;
        return Text::limit($text, 158);
    }

    private static function titleCaseAware(string $query, string $language): string
    {
        if (str_starts_with(strtolower($language), 'fa')) {
            return $query;
        }

        $small = ['a', 'an', 'the', 'and', 'or', 'for', 'of', 'in', 'on', 'to'];
        $parts = preg_split('/\s+/', $query) ?: [];
        foreach ($parts as $i => $part) {
            if ($i > 0 && in_array(strtolower($part), $small, true)) {
                $parts[$i] = strtolower($part);
                continue;
            }
            $parts[$i] = ucfirst($part);
        }

        return implode(' ', $parts);
    }
}
