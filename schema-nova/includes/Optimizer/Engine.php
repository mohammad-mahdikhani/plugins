<?php
/**
 * Pull SERP rows, pick underperforming URLs, propose (or apply) new titles/metas.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Optimizer;

use SchemaNova\Connectors\Manager;
use SchemaNova\Settings\Repository;
use SchemaNova\Support\Text;
use SchemaNova\Support\Url;

final class Engine
{
    /**
     * @return array{synced:int,proposed:int,applied:int,skipped:int,errors:list<string>}
     */
    public function run(bool $forceApply = false): array
    {
        $settings  = Repository::instance();
        $optimizer = $settings->get('optimizer', []);
        $optimizer = is_array($optimizer) ? $optimizer : [];

        $result = [
            'synced'   => 0,
            'proposed' => 0,
            'applied'  => 0,
            'skipped'  => 0,
            'errors'   => [],
        ];

        if (empty($optimizer['enabled']) && ! $forceApply) {
            $result['errors'][] = 'Optimizer is disabled.';
            return $result;
        }

        $sync = (new Manager())->sync();
        if (! empty($sync['error'])) {
            $result['errors'][] = (string) $sync['error'];
            return $result;
        }
        $result['synced'] = (int) ($sync['rows'] ?? 0);

        $pages = $this->pagesFromStore();
        $mode  = $forceApply ? 'auto' : (string) ($optimizer['mode'] ?? 'suggest');
        $minImpressions = (int) ($optimizer['min_impressions'] ?? 50);
        $ctrGap         = (float) ($optimizer['ctr_gap'] ?? 0.25);
        $maxPages       = (int) ($optimizer['max_pages_per_run'] ?? 25);
        $similarity     = (float) ($optimizer['similarity_min'] ?? 0.22);
        $intervalDays   = (int) ($optimizer['interval_days'] ?? 3);
        $brand          = (string) $settings->get('organization.name', get_bloginfo('name'));
        $language       = (string) $settings->get('site.in_language', 'fa-IR');
        $keepBrand      = (bool) ($optimizer['keep_brand'] ?? true);
        $separator      = (string) $settings->get('titles.separator', ' | ');

        $processed = 0;
        foreach ($pages as $pageUrl => $queries) {
            if ($processed >= $maxPages) {
                break;
            }

            $agg = $this->aggregate($queries);
            if ($agg['impressions'] < $minImpressions) {
                $result['skipped']++;
                continue;
            }
            if (CtrModel::gap($agg['ctr'], $agg['position']) < $ctrGap) {
                $result['skipped']++;
                continue;
            }

            $postId = url_to_postid($pageUrl);
            if ($postId <= 0) {
                $result['skipped']++;
                continue;
            }

            if ($this->tooSoon($postId, $intervalDays)) {
                $result['skipped']++;
                continue;
            }

            $post = get_post($postId);
            if (! $post) {
                $result['skipped']++;
                continue;
            }

            $currentTitle = (string) get_post_meta($postId, '_schema_nova_title', true);
            if ($currentTitle === '') {
                $currentTitle = get_the_title($postId);
            }
            $pageText = Text::clean($post->post_title . ' ' . $post->post_excerpt . ' ' . $post->post_content, 4000);

            $proposal = TitleBuilder::propose(
                $currentTitle,
                $pageText,
                $queries,
                $brand,
                $language,
                $keepBrand,
                $separator,
                $similarity
            );

            if ($proposal === null) {
                $result['skipped']++;
                continue;
            }

            $apply = $mode === 'auto';
            History::add([
                'object_id'  => $postId,
                'page_url'   => $pageUrl,
                'field_name' => 'title',
                'old_value'  => $currentTitle,
                'new_value'  => $proposal['title'],
                'reason'     => $proposal['reason'],
                'mode'       => $mode,
                'applied'    => $apply,
            ]);
            History::add([
                'object_id'  => $postId,
                'page_url'   => $pageUrl,
                'field_name' => 'description',
                'old_value'  => (string) get_post_meta($postId, '_schema_nova_meta_description', true),
                'new_value'  => $proposal['description'],
                'reason'     => $proposal['reason'],
                'mode'       => $mode,
                'applied'    => $apply,
            ]);

            $result['proposed']++;
            $processed++;

            if ($apply) {
                $this->apply($postId, $proposal);
                $result['applied']++;
            }
        }

        if (! empty($optimizer['rollback_on_drop'])) {
            $this->maybeRollback((float) ($optimizer['rollback_drop_pct'] ?? 0.15));
        }

        update_option('schema_nova_optimizer_state', [
            'last_run' => time(),
            'result'   => $result,
        ], false);

        return $result;
    }

    /**
     * @param array{title:string,description:string} $proposal
     */
    public function apply(int $postId, array $proposal): void
    {
        if (! get_post_meta($postId, '_schema_nova_original_title', true)) {
            update_post_meta($postId, '_schema_nova_original_title', get_the_title($postId));
        }
        update_post_meta($postId, '_schema_nova_title', $proposal['title']);
        update_post_meta($postId, '_schema_nova_meta_description', $proposal['description']);
        update_post_meta($postId, '_schema_nova_optimized_at', time());
    }

    public function rollbackPost(int $postId): void
    {
        $original = (string) get_post_meta($postId, '_schema_nova_original_title', true);
        if ($original !== '') {
            update_post_meta($postId, '_schema_nova_title', $original);
        } else {
            delete_post_meta($postId, '_schema_nova_title');
        }
        delete_post_meta($postId, '_schema_nova_meta_description');
        delete_post_meta($postId, '_schema_nova_optimized_at');
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function pagesFromStore(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'schema_nova_serp';
        $rows  = $wpdb->get_results("SELECT * FROM {$table} ORDER BY impressions DESC LIMIT 2000", ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        if (! is_array($rows)) {
            return [];
        }

        $byPage = [];
        foreach ($rows as $row) {
            $url = Url::normalize((string) $row['page_url']);
            $byPage[$url][] = [
                'query'       => (string) $row['query_text'],
                'impressions' => (int) $row['impressions'],
                'clicks'      => (int) $row['clicks'],
                'ctr'         => (float) $row['ctr'],
                'position'    => (float) $row['position'],
                'intent'      => (string) $row['intent'],
            ];
        }

        return $byPage;
    }

    /**
     * @param list<array<string, mixed>> $queries
     * @return array{clicks:int,impressions:int,ctr:float,position:float}
     */
    private function aggregate(array $queries): array
    {
        $clicks = 0;
        $impr   = 0;
        $posSum = 0.0;
        foreach ($queries as $q) {
            $clicks += (int) $q['clicks'];
            $impr   += (int) $q['impressions'];
            $posSum += (float) $q['position'] * (int) $q['impressions'];
        }
        $ctr = $impr > 0 ? $clicks / $impr : 0.0;
        $pos = $impr > 0 ? $posSum / $impr : 10.0;

        return [
            'clicks'      => $clicks,
            'impressions' => $impr,
            'ctr'         => $ctr,
            'position'    => $pos,
        ];
    }

    private function tooSoon(int $postId, int $intervalDays): bool
    {
        $at = (int) get_post_meta($postId, '_schema_nova_optimized_at', true);
        if ($at <= 0) {
            return false;
        }

        return (time() - $at) < ($intervalDays * DAY_IN_SECONDS);
    }

    private function maybeRollback(float $dropPct): void
    {
        // Conservative: if a page was optimized and its latest CTR is worse than
        // the previous stored CTR by $dropPct, restore the original title.
        global $wpdb;
        $table = $wpdb->prefix . 'schema_nova_history';
        $rows  = $wpdb->get_results(
            "SELECT * FROM {$table} WHERE applied = 1 AND rolled_back = 0 AND field_name = 'title' ORDER BY id DESC LIMIT 100", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            ARRAY_A
        );
        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $row) {
            $postId = (int) $row['object_id'];
            if ($postId <= 0) {
                continue;
            }
            $before = (float) get_post_meta($postId, '_schema_nova_ctr_before', true);
            $after  = (float) get_post_meta($postId, '_schema_nova_ctr_after', true);
            if ($before <= 0 || $after <= 0) {
                continue;
            }
            if (($before - $after) / $before >= $dropPct) {
                $this->rollbackPost($postId);
                History::markRolledBack((int) $row['id']);
            }
        }
    }
}
