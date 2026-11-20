<?php
/**
 * REST ingest so an external analytics tool can push rows.
 *
 * POST /wp-json/schema-nova/v1/ingest
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Rest;

use SchemaNova\Connectors\Manager;
use SchemaNova\Connectors\Normalizer;
use SchemaNova\Optimizer\Engine;
use SchemaNova\Settings\Repository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class Controller
{
    public function register(): void
    {
        add_action('rest_api_init', [$this, 'routes']);
    }

    public function routes(): void
    {
        register_rest_route('schema-nova/v1', '/ingest', [
            'methods'             => 'POST',
            'callback'            => [$this, 'ingest'],
            'permission_callback' => [$this, 'canIngest'],
        ]);

        register_rest_route('schema-nova/v1', '/optimize', [
            'methods'             => 'POST',
            'callback'            => [$this, 'optimize'],
            'permission_callback' => static fn () => current_user_can('manage_options'),
        ]);

        register_rest_route('schema-nova/v1', '/preview', [
            'methods'             => 'GET',
            'callback'            => [$this, 'preview'],
            'permission_callback' => static fn () => current_user_can('manage_options'),
        ]);
    }

    public function canIngest(WP_REST_Request $request): bool
    {
        if (current_user_can('manage_options')) {
            return true;
        }

        $provided = (string) $request->get_header('x-schema-nova-token');
        $expected = (string) Repository::instance()->get('connector.generic.token', '');

        return $expected !== '' && hash_equals($expected, $provided);
    }

    public function ingest(WP_REST_Request $request): WP_REST_Response
    {
        $body  = $request->get_json_params();
        $items = [];
        if (isset($body['rows']) && is_array($body['rows'])) {
            $items = $body['rows'];
        } elseif (is_array($body) && array_is_list($body)) {
            $items = $body;
        }

        $brand = (string) Repository::instance()->get('organization.name', '');
        $rows  = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $norm = Normalizer::row($item, $brand);
            if ($norm !== null) {
                $rows[] = $norm;
            }
        }

        $count = (new Manager())->store($rows);

        return new WP_REST_Response(['stored' => $count], 200);
    }

    public function optimize(): WP_REST_Response
    {
        $result = (new Engine())->run(false);
        return new WP_REST_Response($result, 200);
    }

    public function preview(WP_REST_Request $request)
    {
        $url = (string) $request->get_param('url');
        if ($url === '') {
            return new WP_Error('missing_url', 'Pass ?url=', ['status' => 400]);
        }

        $postId = url_to_postid($url);
        if ($postId <= 0) {
            return new WP_Error('not_found', 'No post matches that URL.', ['status' => 404]);
        }

        $title = (string) get_post_meta($postId, '_schema_nova_title', true);
        $desc  = (string) get_post_meta($postId, '_schema_nova_meta_description', true);

        return new WP_REST_Response([
            'post_id'     => $postId,
            'title'       => $title !== '' ? $title : get_the_title($postId),
            'description' => $desc,
        ], 200);
    }
}
