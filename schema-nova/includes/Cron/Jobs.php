<?php
/**
 * Scheduled SERP sync + title/meta optimization.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Cron;

use SchemaNova\Connectors\Manager;
use SchemaNova\Optimizer\Engine;
use SchemaNova\Settings\Repository;

final class Jobs
{
    public function register(): void
    {
        add_action('schema_nova_sync_serp', [$this, 'sync']);
        add_action('schema_nova_optimize', [$this, 'optimize']);
        add_filter('cron_schedules', [$this, 'schedules']);
    }

    /**
     * @param array<string, array<string, mixed>> $schedules
     * @return array<string, array<string, mixed>>
     */
    public function schedules(array $schedules): array
    {
        $days = max(1, (int) Repository::instance()->get('optimizer.interval_days', 3));
        $schedules['schema_nova_interval'] = [
            'interval' => $days * DAY_IN_SECONDS,
            'display'  => sprintf('Schema Nova every %d days', $days),
        ];

        return $schedules;
    }

    public function sync(): void
    {
        (new Manager())->sync();
    }

    public function optimize(): void
    {
        (new Engine())->run(false);
    }
}
