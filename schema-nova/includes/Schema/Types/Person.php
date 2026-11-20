<?php
/**
 * Person node for authors and profile pages.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Schema\Types;

use SchemaNova\Schema\Context;
use SchemaNova\Schema\Node;
use SchemaNova\Support\Text;

final class Person
{
    /**
     * @return array<string, mixed>
     */
    public static function build(Context $ctx): array
    {
        $user = $ctx->author;
        if ($ctx->kind === 'profile') {
            $obj = get_queried_object();
            if ($obj instanceof \WP_User) {
                $user = $obj;
            }
        }
        if (! $user) {
            return [];
        }

        $id = $ctx->kind === 'profile' ? $ctx->entityId('person') : $ctx->entityId('author');
        $url = get_author_posts_url($user->ID);
        $same = [];
        foreach (['url', 'twitter', 'facebook'] as $field) {
            $val = get_the_author_meta($field, $user->ID);
            if (is_string($val) && $val !== '') {
                $same[] = $val;
            }
        }

        return Node::make('Person', $id, [
            'name'        => $user->display_name,
            'url'         => $url,
            'image'       => get_avatar_url($user->ID, ['size' => 256]) ?: '',
            'description' => Text::clean((string) get_the_author_meta('description', $user->ID), 300),
            'sameAs'      => $same,
            'jobTitle'    => (string) get_the_author_meta('job_title', $user->ID),
            'worksFor'    => Node::ref($ctx->orgId()),
        ]);
    }
}
