<?php
/**
 * Plugin bootstrap.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova;

use SchemaNova\Admin\Ajax;
use SchemaNova\Admin\Assets;
use SchemaNova\Admin\Menu;
use SchemaNova\Admin\Metabox;
use SchemaNova\Blocks\Register as Blocks;
use SchemaNova\Compat\Competitors;
use SchemaNova\Connectors\Manager as ConnectorManager;
use SchemaNova\Cron\Jobs;
use SchemaNova\Frontend\Head;
use SchemaNova\Frontend\Meta;
use SchemaNova\Frontend\Titles;
use SchemaNova\Rest\Controller as RestController;
use SchemaNova\Settings\Repository;
use SchemaNova\WooCommerce\Hooks as WooHooks;

final class Plugin
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function boot(): void
    {
        I18n::boot();
        Repository::instance()->ensureDefaults();

        (new Head())->register();
        (new Titles())->register();
        (new Meta())->register();
        (new Jobs())->register();
        (new RestController())->register();
        (new Blocks())->register();
        (new Competitors())->register();
        (new WooHooks())->register();
        (new ConnectorManager())->register();

        if (is_admin()) {
            (new Menu())->register();
            (new Assets())->register();
            (new Metabox())->register();
            (new Ajax())->register();
        }
    }
}
