<?php
/**
 * WooCommerce-aware defaults.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\WooCommerce;

use SchemaNova\Settings\Repository;

final class Hooks
{
    public function register(): void
    {
        add_action('init', [$this, 'mapProductType'], 20);
    }

    public function mapProductType(): void
    {
        if (! class_exists('WooCommerce')) {
            return;
        }

        $types = Repository::instance()->get('types', []);
        if (is_array($types) && empty($types['product'])) {
            Repository::instance()->set('types.product', 'Product');
        }
    }
}
