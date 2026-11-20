<?php
/**
 * Recipe markup from per-page overrides.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Schema\Types;

use SchemaNova\Schema\Context;
use SchemaNova\Schema\Images;
use SchemaNova\Schema\Node;
use SchemaNova\Support\Text;

final class Recipe
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function maybe(Context $ctx): array
    {
        $o = $ctx->overrides;
        if (($o['schema_type'] ?? '') !== 'Recipe' && empty($o['recipe_ingredients'])) {
            return [];
        }
        if (empty($o['recipe_ingredients'])) {
            return [];
        }

        $ingredients = self::lines((string) $o['recipe_ingredients']);
        $steps       = self::lines((string) ($o['recipe_instructions'] ?? ''));
        $instructions = [];
        foreach ($steps as $i => $step) {
            $instructions[] = [
                '@type'    => 'HowToStep',
                'position' => $i + 1,
                'text'     => $step,
            ];
        }

        $node = Node::make('Recipe', $ctx->entityId('recipe'), [
            'name'                => Text::clean((string) ($o['recipe_name'] ?? $ctx->title)),
            'image'               => Images::urls($ctx->images),
            'description'         => $ctx->description,
            'recipeIngredient'    => $ingredients,
            'recipeInstructions'  => $instructions,
            'prepTime'            => self::duration((string) ($o['prep_time'] ?? '')),
            'cookTime'            => self::duration((string) ($o['cook_time'] ?? '')),
            'recipeYield'         => (string) ($o['recipe_yield'] ?? ''),
            'author'              => $ctx->author ? Node::ref($ctx->entityId('author')) : Node::ref($ctx->orgId()),
            'datePublished'       => $ctx->post ? Node::isoDate($ctx->post->post_date_gmt ?: $ctx->post->post_date) : '',
        ]);

        return [$node];
    }

    /**
     * @return list<string>
     */
    private static function lines(string $text): array
    {
        $parts = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $out   = [];
        foreach ($parts as $part) {
            $part = Text::clean($part);
            if ($part !== '') {
                $out[] = $part;
            }
        }

        return $out;
    }

    private static function duration(string $minutes): string
    {
        $n = (int) $minutes;
        if ($n <= 0) {
            return '';
        }

        return 'PT' . $n . 'M';
    }
}
