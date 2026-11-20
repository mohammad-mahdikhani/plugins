<?php
/**
 * VideoObject nodes (YouTube, Vimeo, Aparat, self-hosted).
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Schema\Types;

use SchemaNova\Schema\Context;
use SchemaNova\Schema\Node;

final class Video
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function buildAll(Context $ctx): array
    {
        $nodes = [];
        foreach ($ctx->videos as $i => $video) {
            $nodes[] = Node::make('VideoObject', $ctx->entityId('video-' . ($i + 1)), array_merge(
                $video,
                [
                    'publisher' => Node::ref($ctx->orgId()),
                    'isPartOf'  => Node::ref($ctx->pageId()),
                ]
            ));
        }

        return $nodes;
    }
}
