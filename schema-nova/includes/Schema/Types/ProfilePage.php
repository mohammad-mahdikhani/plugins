<?php
/**
 * ProfilePage for author archives.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Schema\Types;

use SchemaNova\Schema\Context;
use SchemaNova\Schema\Node;

final class ProfilePage
{
    /**
     * @return array<string, mixed>
     */
    public static function build(Context $ctx): array
    {
        $person = Person::build($ctx);
        if ($person === []) {
            return [];
        }

        return Node::make('ProfilePage', $ctx->entityId('profile'), [
            'url'        => $ctx->url,
            'name'       => $ctx->title,
            'isPartOf'   => Node::ref($ctx->siteId()),
            'mainEntity' => Node::ref($person['@id'] ?? $ctx->entityId('person')),
        ]);
    }
}
