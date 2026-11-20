<?php
/**
 * Admin page markup (Persian-first, RTL-aware).
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Admin;

use SchemaNova\Optimizer\History;
use SchemaNova\Schema\Context;
use SchemaNova\Schema\Graph;
use SchemaNova\Schema\Validator;
use SchemaNova\Settings\Repository;

final class Pages
{
    public static function render(string $page): void
    {
        $saved = isset($_GET['saved']);
        if ($saved) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(sn__('Saved.')) . '</p></div>';
        }

        match ($page) {
            'schema-nova-org'       => self::org(),
            'schema-nova-types'     => self::types(),
            'schema-nova-optimizer' => self::optimizer(),
            'schema-nova-connect'   => self::connect(),
            'schema-nova-tools'     => self::tools(),
            default                 => self::dashboard(),
        };
    }

    /**
     * @param array<string, mixed> $post
     */
    public static function save(string $page, array $post): void
    {
        $s = Repository::instance();

        if ($page === 'schema-nova-org') {
            $org = $post['org'] ?? [];
            if (is_array($org)) {
                if (isset($org['same_as']) && is_string($org['same_as'])) {
                    $org['same_as'] = array_values(array_filter(array_map('trim', preg_split('/\s+/', $org['same_as']) ?: [])));
                }
                $s->merge(['organization' => self::sanitize($org)]);
            }
        }

        if ($page === 'schema-nova-types') {
            $s->merge([
                'site'     => self::sanitize((array) ($post['site'] ?? [])),
                'types'    => self::sanitize((array) ($post['types'] ?? [])),
                'features' => self::checkboxes((array) ($post['features'] ?? []), array_keys((array) $s->get('features', []))),
                'output'   => [
                    'disable_competitors' => ! empty($post['output']['disable_competitors']),
                    'in_footer'           => ! empty($post['output']['in_footer']),
                ],
                'titles'   => [
                    'control'      => ! empty($post['titles']['control']),
                    'separator'    => sanitize_text_field((string) ($post['titles']['separator'] ?? ' | ')),
                    'brand_at_end' => ! empty($post['titles']['brand_at_end']),
                    'sync_og'      => ! empty($post['titles']['sync_og']),
                ],
            ]);
        }

        if ($page === 'schema-nova-optimizer') {
            $opt = (array) ($post['optimizer'] ?? []);
            $s->merge([
                'optimizer' => [
                    'enabled'           => ! empty($opt['enabled']),
                    'mode'              => in_array($opt['mode'] ?? '', ['suggest', 'auto'], true) ? $opt['mode'] : 'suggest',
                    'interval_days'     => max(1, (int) ($opt['interval_days'] ?? 3)),
                    'lookback_days'     => max(7, (int) ($opt['lookback_days'] ?? 28)),
                    'min_impressions'   => max(1, (int) ($opt['min_impressions'] ?? 50)),
                    'ctr_gap'           => min(0.9, max(0.05, (float) ($opt['ctr_gap'] ?? 0.25))),
                    'max_pages_per_run' => max(1, (int) ($opt['max_pages_per_run'] ?? 25)),
                    'similarity_min'    => min(0.9, max(0.05, (float) ($opt['similarity_min'] ?? 0.22))),
                    'rollback_on_drop'  => ! empty($opt['rollback_on_drop']),
                    'rollback_drop_pct' => min(0.8, max(0.05, (float) ($opt['rollback_drop_pct'] ?? 0.15))),
                    'keep_brand'        => ! empty($opt['keep_brand']),
                ],
            ]);
        }

        if ($page === 'schema-nova-connect') {
            $s->set('connector.provider', sanitize_key((string) ($post['provider'] ?? 'generic')));
            $s->merge([
                'connector' => [
                    'gsc'     => self::sanitize((array) ($post['gsc'] ?? [])),
                    'ga4'     => self::sanitize((array) ($post['ga4'] ?? [])),
                    'generic' => self::sanitizeGeneric((array) ($post['generic'] ?? [])),
                ],
            ]);
        }

        $s->set('wizard_complete', true);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function sanitize(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            $key = sanitize_key((string) $k);
            if (is_array($v)) {
                $out[$key] = self::sanitize($v);
            } else {
                $out[$key] = sanitize_text_field((string) $v);
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $posted
     * @param list<string>         $keys
     * @return array<string, bool>
     */
    private static function checkboxes(array $posted, array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = ! empty($posted[$key]);
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function sanitizeGeneric(array $data): array
    {
        $clean = self::sanitize($data);
        if (isset($data['body'])) {
            $clean['body'] = sanitize_textarea_field((string) $data['body']);
        }
        if (isset($data['extra_headers'])) {
            $clean['extra_headers'] = sanitize_textarea_field((string) $data['extra_headers']);
        }
        if (isset($data['map']) && is_array($data['map'])) {
            $clean['map'] = self::sanitize($data['map']);
        }

        return $clean;
    }

    private static function dashboard(): void
    {
        $s      = Repository::instance();
        $graph  = Graph::build(Context::forHome());
        $issues = Validator::check($graph);
        $score  = Validator::score($issues);
        $state  = get_option('schema_nova_optimizer_state', []);
        $status = get_transient('schema_nova_connector_status');
        $history = History::latest(8);

        echo '<header class="sn-hero">';
        echo '<div><p class="sn-kicker">' . esc_html(sn__('Schema Nova')) . '</p>';
        echo '<h1>' . esc_html(sn__('Rich results, without fighting your theme.')) . '</h1>';
        echo '<p class="sn-lede">' . esc_html(sn__('A connected JSON-LD graph for every template, plus titles and metas that follow real search intent from the analytics API you connect.')) . '</p></div>';
        echo '<div class="sn-score" data-score="' . esc_attr((string) $score) . '"><strong>' . esc_html((string) $score) . '</strong><span>' . esc_html(sn__('Graph health')) . '</span></div>';
        echo '</header>';

        echo '<div class="sn-grid">';
        self::card(sn__('Organization'), (string) $s->get('organization.name'), admin_url('admin.php?page=schema-nova-org'), sn__('Edit identity'));
        self::card(sn__('Data source'), strtoupper((string) $s->get('connector.provider', 'generic')), admin_url('admin.php?page=schema-nova-connect'), sn__('Connect API'));
        $mode = (string) $s->get('optimizer.mode', 'suggest');
        self::card(sn__('Optimizer'), $mode === 'auto' ? sn__('Auto-apply') : sn__('Suggest only'), admin_url('admin.php?page=schema-nova-optimizer'), sn__('Tune rules'));
        $last = is_array($status) ? (int) ($status['rows'] ?? 0) : 0;
        self::card(sn__('SERP rows'), (string) $last, admin_url('admin.php?page=schema-nova-connect'), sn__('Sync now'));
        echo '</div>';

        echo '<section class="sn-panel"><h2>' . esc_html(sn__('This page’s graph')) . '</h2>';
        echo '<p class="sn-help">' . esc_html(sn__('Admin screens use the site home graph so you can inspect Organization + WebSite without leaving wp-admin.')) . '</p>';
        echo '<pre class="sn-json">' . esc_html(Graph::encode($graph)) . '</pre>';
        if ($issues !== []) {
            echo '<ul class="sn-issues">';
            foreach ($issues as $issue) {
                echo '<li class="is-' . esc_attr($issue['severity']) . '">' . esc_html($issue['message']) . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p class="sn-ok">' . esc_html(sn__('No required-field issues on the home graph.')) . '</p>';
        }
        echo '</section>';

        echo '<section class="sn-panel"><h2>' . esc_html(sn__('Recent title & meta proposals')) . '</h2>';
        if ($history === []) {
            echo '<p>' . esc_html(sn__('Nothing yet. Connect an API, then run the optimizer.')) . '</p>';
        } else {
            echo '<table class="sn-table"><thead><tr><th>' . esc_html(sn__('Page')) . '</th><th>' . esc_html(sn__('Field')) . '</th><th>' . esc_html(sn__('New value')) . '</th><th>' . esc_html(sn__('Status')) . '</th></tr></thead><tbody>';
            foreach ($history as $row) {
                $statusLabel = ! empty($row['rolled_back']) ? sn__('Rolled back') : (! empty($row['applied']) ? sn__('Applied') : sn__('Suggested'));
                echo '<tr><td>' . esc_html((string) $row['page_url']) . '</td><td>' . esc_html((string) $row['field_name']) . '</td><td>' . esc_html((string) $row['new_value']) . '</td><td>' . esc_html($statusLabel) . '</td></tr>';
            }
            echo '</tbody></table>';
        }
        if (is_array($state) && ! empty($state['last_run'])) {
            echo '<p class="sn-help">' . esc_html(sprintf(sn__('Last optimizer run: %s'), wp_date('Y-m-d H:i', (int) $state['last_run']))) . '</p>';
        }
        echo '</section>';
    }

    private static function org(): void
    {
        $org = (array) Repository::instance()->get('organization', []);
        echo '<h1>' . esc_html(sn__('Organization identity')) . '</h1>';
        echo '<p class="sn-lede">' . esc_html(sn__('These fields power Knowledge Panel eligibility, logos, merchant return policy, and every page’s publisher node.')) . '</p>';
        echo '<form method="post" class="sn-form">';
        wp_nonce_field('schema_nova_save', 'schema_nova_nonce');
        echo '<input type="hidden" name="schema_nova_page" value="schema-nova-org" />';

        echo '<div class="sn-panel">';
        self::select('org[type]', sn__('Schema subtype'), (string) ($org['type'] ?? 'Organization'), [
            'Organization' => 'Organization',
            'OnlineStore' => 'OnlineStore (shop)',
            'LocalBusiness' => 'LocalBusiness',
            'NewsMediaOrganization' => 'NewsMediaOrganization',
            'EducationalOrganization' => 'EducationalOrganization',
            'MedicalOrganization' => 'MedicalOrganization',
            'NGO' => 'NGO',
            'Restaurant' => 'Restaurant',
            'Corporation' => 'Corporation',
        ]);
        self::text('org[name]', sn__('Name'), (string) ($org['name'] ?? ''));
        self::text('org[legal_name]', sn__('Legal name'), (string) ($org['legal_name'] ?? ''));
        self::text('org[alternate_name]', sn__('Alternate name'), (string) ($org['alternate_name'] ?? ''));
        self::textarea('org[description]', sn__('Description'), (string) ($org['description'] ?? ''));
        self::text('org[url]', sn__('URL'), (string) ($org['url'] ?? ''));
        self::text('org[logo]', sn__('Logo URL'), (string) ($org['logo'] ?? ''));
        self::text('org[email]', sn__('Email'), (string) ($org['email'] ?? ''));
        self::text('org[telephone]', sn__('Telephone (with country code)'), (string) ($org['telephone'] ?? ''));
        self::text('org[founding_date]', sn__('Founding date (YYYY-MM-DD)'), (string) ($org['founding_date'] ?? ''));
        echo '</div>';

        echo '<div class="sn-panel"><h2>' . esc_html(sn__('Address')) . '</h2>';
        $addr = (array) ($org['address'] ?? []);
        self::text('org[address][street]', sn__('Street'), (string) ($addr['street'] ?? ''));
        self::text('org[address][locality]', sn__('City'), (string) ($addr['locality'] ?? ''));
        self::text('org[address][region]', sn__('Region'), (string) ($addr['region'] ?? ''));
        self::text('org[address][postal]', sn__('Postal code'), (string) ($addr['postal'] ?? ''));
        self::text('org[address][country]', sn__('Country (ISO-2, e.g. IR)'), (string) ($addr['country'] ?? 'IR'));
        $geo = (array) ($org['geo'] ?? []);
        self::text('org[geo][lat]', sn__('Latitude'), (string) ($geo['lat'] ?? ''));
        self::text('org[geo][lng]', sn__('Longitude'), (string) ($geo['lng'] ?? ''));
        echo '</div>';

        echo '<div class="sn-panel"><h2>' . esc_html(sn__('Trust & merchant extras')) . '</h2>';
        self::textarea('org[same_as]', sn__('sameAs profiles (one URL per line or space)'), implode("\n", (array) ($org['same_as'] ?? [])));
        self::text('org[vat_id]', sn__('VAT ID'), (string) ($org['vat_id'] ?? ''));
        self::text('org[tax_id]', sn__('Tax ID'), (string) ($org['tax_id'] ?? ''));
        self::text('org[iso6523]', sn__('ISO 6523 (e.g. 0199:LEI)'), (string) ($org['iso6523'] ?? ''));
        self::text('org[price_range]', sn__('Price range (e.g. $$)'), (string) ($org['price_range'] ?? ''));
        self::text('org[return_days]', sn__('Return window (days)'), (string) ($org['return_days'] ?? '7'));
        self::text('org[shipping_rate]', sn__('Default shipping rate'), (string) ($org['shipping_rate'] ?? ''));
        self::text('org[shipping_currency]', sn__('Shipping currency'), (string) ($org['shipping_currency'] ?? 'IRR'));
        echo '</div>';

        echo '<p><button class="button button-primary sn-btn">' . esc_html(sn__('Save organization')) . '</button></p>';
        echo '</form>';
    }

    private static function types(): void
    {
        $s = Repository::instance();
        echo '<h1>' . esc_html(sn__('Types, features, titles')) . '</h1>';
        echo '<form method="post" class="sn-form">';
        wp_nonce_field('schema_nova_save', 'schema_nova_nonce');
        echo '<input type="hidden" name="schema_nova_page" value="schema-nova-types" />';

        echo '<div class="sn-panel"><h2>' . esc_html(sn__('Site kind')) . '</h2>';
        self::select('site[kind]', sn__('Primary site type'), (string) $s->get('site.kind', 'mixed'), [
            'mixed' => sn__('Mixed'),
            'blog'  => sn__('Blog / magazine'),
            'shop'  => sn__('Store'),
            'local' => sn__('Local business'),
            'news'  => sn__('News'),
            'education' => sn__('Education'),
        ]);
        self::text('site[in_language]', sn__('Language (BCP 47)'), (string) $s->get('site.in_language', 'fa-IR'));
        self::text('site[search_path]', sn__('Internal search URL template'), (string) $s->get('site.search_path', '?s={search_term_string}'));
        echo '</div>';

        echo '<div class="sn-panel"><h2>' . esc_html(sn__('Default schema per post type')) . '</h2>';
        foreach (get_post_types(['public' => true], 'objects') as $type) {
            if (in_array($type->name, ['attachment', 'revision'], true)) {
                continue;
            }
            self::select(
                'types[' . $type->name . ']',
                $type->labels->singular_name . ' (' . $type->name . ')',
                (string) $s->get('types.' . $type->name, $type->name === 'page' ? 'WebPage' : 'BlogPosting'),
                [
                    'WebPage' => 'WebPage',
                    'BlogPosting' => 'BlogPosting',
                    'NewsArticle' => 'NewsArticle',
                    'Article' => 'Article',
                    'Product' => 'Product',
                    'Service' => 'Service',
                    'Course' => 'Course',
                    'FAQPage' => 'FAQPage',
                ]
            );
        }
        echo '</div>';

        echo '<div class="sn-panel"><h2>' . esc_html(sn__('Features')) . '</h2>';
        $features = (array) $s->get('features', []);
        foreach ($features as $key => $on) {
            self::checkbox('features[' . $key . ']', $key, (bool) $on);
        }
        echo '</div>';

        echo '<div class="sn-panel"><h2>' . esc_html(sn__('Output')) . '</h2>';
        self::checkbox('output[disable_competitors]', sn__('Disable Rank Math / Yoast / AIOSEO JSON-LD (avoid duplicates)'), (bool) $s->get('output.disable_competitors', false));
        self::checkbox('output[in_footer]', sn__('Print JSON-LD in footer instead of head'), (bool) $s->get('output.in_footer', false));
        self::checkbox('titles[control]', sn__('Let Schema Nova control title + meta description'), (bool) $s->get('titles.control', true));
        self::checkbox('titles[sync_og]', sn__('Mirror title/meta to Open Graph and Twitter'), (bool) $s->get('titles.sync_og', true));
        self::text('titles[separator]', sn__('Title separator'), (string) $s->get('titles.separator', ' | '));
        echo '</div>';

        echo '<p><button class="button button-primary sn-btn">' . esc_html(sn__('Save')) . '</button></p></form>';
    }

    private static function optimizer(): void
    {
        $o = (array) Repository::instance()->get('optimizer', []);
        echo '<h1>' . esc_html(sn__('SERP-aware titles & metas')) . '</h1>';
        echo '<p class="sn-lede">' . esc_html(sn__('Every few days the plugin reads queries, CTR and position from your connected API, then rewrites titles that underperform — only when the query still matches the page.')) . '</p>';
        echo '<form method="post" class="sn-form">';
        wp_nonce_field('schema_nova_save', 'schema_nova_nonce');
        echo '<input type="hidden" name="schema_nova_page" value="schema-nova-optimizer" />';
        echo '<div class="sn-panel">';
        self::checkbox('optimizer[enabled]', sn__('Enable optimizer'), ! empty($o['enabled']));
        self::select('optimizer[mode]', sn__('Mode'), (string) ($o['mode'] ?? 'suggest'), [
            'suggest' => sn__('Suggest (review in history, apply from the post editor)'),
            'auto'    => sn__('Auto-apply when rules match'),
        ]);
        self::text('optimizer[interval_days]', sn__('Change at most every N days'), (string) ($o['interval_days'] ?? '3'));
        self::text('optimizer[lookback_days]', sn__('Analytics lookback (days)'), (string) ($o['lookback_days'] ?? '28'));
        self::text('optimizer[min_impressions]', sn__('Minimum impressions'), (string) ($o['min_impressions'] ?? '50'));
        self::text('optimizer[ctr_gap]', sn__('CTR gap vs expected (0.25 = 25% below)'), (string) ($o['ctr_gap'] ?? '0.25'));
        self::text('optimizer[max_pages_per_run]', sn__('Max pages per run'), (string) ($o['max_pages_per_run'] ?? '25'));
        self::text('optimizer[similarity_min]', sn__('Query–content overlap minimum'), (string) ($o['similarity_min'] ?? '0.22'));
        self::checkbox('optimizer[keep_brand]', sn__('Keep brand name in the title'), ! empty($o['keep_brand']));
        self::checkbox('optimizer[rollback_on_drop]', sn__('Roll back if CTR drops after a change'), ! empty($o['rollback_on_drop']));
        echo '</div>';
        echo '<p><button class="button button-primary sn-btn">' . esc_html(sn__('Save optimizer')) . '</button> ';
        echo '<button type="button" class="button sn-btn-ghost" id="sn-run-optimizer">' . esc_html(sn__('Run now')) . '</button></p></form>';

        $history = History::latest(30);
        echo '<section class="sn-panel"><h2>' . esc_html(sn__('History')) . '</h2>';
        echo '<table class="sn-table"><thead><tr><th>' . esc_html(sn__('When')) . '</th><th>' . esc_html(sn__('URL')) . '</th><th>' . esc_html(sn__('From → To')) . '</th><th>' . esc_html(sn__('Why')) . '</th></tr></thead><tbody>';
        foreach ($history as $row) {
            echo '<tr><td>' . esc_html((string) $row['created_at']) . '</td><td>' . esc_html((string) $row['page_url']) . '</td><td><del>' . esc_html((string) $row['old_value']) . '</del><br>' . esc_html((string) $row['new_value']) . '</td><td>' . esc_html((string) $row['reason']) . '</td></tr>';
        }
        echo '</tbody></table></section>';
    }

    private static function connect(): void
    {
        $s = Repository::instance();
        $provider = (string) $s->get('connector.provider', 'generic');
        $g = (array) $s->get('connector.generic', []);
        $gsc = (array) $s->get('connector.gsc', []);
        $ga4 = (array) $s->get('connector.ga4', []);

        echo '<h1>' . esc_html(sn__('Connect analytics / Search Console')) . '</h1>';
        echo '<p class="sn-lede">' . esc_html(sn__('Point Schema Nova at any Analytics-like API. Map page, query, clicks, impressions, CTR and position. Search Console is best for intent; GA4 and generic REST are fully supported.')) . '</p>';
        echo '<form method="post" class="sn-form">';
        wp_nonce_field('schema_nova_save', 'schema_nova_nonce');
        echo '<input type="hidden" name="schema_nova_page" value="schema-nova-connect" />';

        echo '<div class="sn-panel">';
        self::select('provider', sn__('Provider'), $provider, [
            'generic' => sn__('Generic REST (any analytics API)'),
            'gsc'     => 'Google Search Console',
            'ga4'     => 'Google Analytics 4',
        ]);
        echo '</div>';

        echo '<div class="sn-panel" data-sn-panel="generic"><h2>' . esc_html(sn__('Generic REST')) . '</h2>';
        echo '<p class="sn-help">' . esc_html(sn__('Expected JSON: { "rows": [ { "page": "https://…", "query": "…", "clicks": 10, "impressions": 200, "ctr": 0.05, "position": 4.2 } ] }. Rename fields below if your API differs.')) . '</p>';
        self::text('generic[endpoint]', sn__('Endpoint URL'), (string) ($g['endpoint'] ?? ''));
        self::select('generic[method]', sn__('Method'), (string) ($g['method'] ?? 'GET'), ['GET' => 'GET', 'POST' => 'POST']);
        self::select('generic[auth_type]', sn__('Auth'), (string) ($g['auth_type'] ?? 'bearer'), [
            'bearer' => 'Bearer token',
            'header' => sn__('Custom header'),
            'basic'  => 'Basic',
            'none'   => sn__('None'),
        ]);
        self::text('generic[token]', sn__('Token / API key'), (string) ($g['token'] ?? ''));
        self::text('generic[header_name]', sn__('Custom header name'), (string) ($g['header_name'] ?? 'Authorization'));
        self::textarea('generic[extra_headers]', sn__('Extra headers (Name: value per line)'), (string) ($g['extra_headers'] ?? ''));
        self::textarea('generic[body]', sn__('POST body (optional JSON)'), (string) ($g['body'] ?? ''));
        $map = (array) ($g['map'] ?? []);
        echo '<h3>' . esc_html(sn__('Field map')) . '</h3>';
        foreach (['rows', 'page', 'query', 'clicks', 'impressions', 'ctr', 'position'] as $field) {
            self::text('generic[map][' . $field . ']', $field, (string) ($map[$field] ?? $field));
        }
        echo '<p class="sn-help">' . esc_html(sn__('You can also POST rows to /wp-json/schema-nova/v1/ingest with header X-Schema-Nova-Token.')) . '</p>';
        echo '</div>';

        echo '<div class="sn-panel" data-sn-panel="gsc"><h2>Google Search Console</h2>';
        self::text('gsc[site_url]', sn__('Property URL (or sc-domain:example.com)'), (string) ($gsc['site_url'] ?? home_url('/')));
        self::text('gsc[client_id]', 'OAuth client ID', (string) ($gsc['client_id'] ?? ''));
        self::text('gsc[client_secret]', 'OAuth client secret', (string) ($gsc['client_secret'] ?? ''));
        self::text('gsc[refresh_token]', 'Refresh token', (string) ($gsc['refresh_token'] ?? ''));
        self::text('gsc[access_token]', sn__('Access token (optional if refresh token is set)'), (string) ($gsc['access_token'] ?? ''));
        echo '</div>';

        echo '<div class="sn-panel" data-sn-panel="ga4"><h2>Google Analytics 4</h2>';
        self::text('ga4[property_id]', 'Property ID', (string) ($ga4['property_id'] ?? ''));
        self::text('ga4[access_token]', 'Access token', (string) ($ga4['access_token'] ?? ''));
        echo '</div>';

        echo '<p><button class="button button-primary sn-btn">' . esc_html(sn__('Save connection')) . '</button> ';
        echo '<button type="button" class="button" id="sn-test-connector">' . esc_html(sn__('Test connection')) . '</button> ';
        echo '<button type="button" class="button" id="sn-sync-connector">' . esc_html(sn__('Sync data')) . '</button></p></form>';
        echo '<div id="sn-connect-result" class="sn-flash" hidden></div>';
    }

    private static function tools(): void
    {
        $url = home_url('/');
        $test = 'https://search.google.com/test/rich-results?url=' . rawurlencode($url);

        echo '<h1>' . esc_html(sn__('Tools')) . '</h1>';
        echo '<div class="sn-panel">';
        echo '<p><a class="button button-primary" target="_blank" rel="noopener" href="' . esc_url($test) . '">' . esc_html(sn__('Open Google Rich Results Test (homepage)')) . '</a></p>';
        echo '<p><a class="button" target="_blank" rel="noopener" href="https://search.google.com/search-console">' . esc_html(sn__('Open Search Console enhancements')) . '</a></p>';
        echo '<p class="sn-help">' . esc_html(sn__('After deploying schema, use URL Inspection on a few live URLs. Allow time for recrawl. Rich result reports only appear once Google finds valid markup of a supported type.')) . '</p>';
        echo '</div>';
    }

    private static function card(string $label, string $value, string $url, string $cta): void
    {
        echo '<a class="sn-card" href="' . esc_url($url) . '"><span>' . esc_html($label) . '</span><strong>' . esc_html($value) . '</strong><em>' . esc_html($cta) . '</em></a>';
    }

    private static function text(string $name, string $label, string $value): void
    {
        echo '<label class="sn-field"><span>' . esc_html($label) . '</span>';
        echo '<input type="text" class="regular-text" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" /></label>';
    }

    private static function textarea(string $name, string $label, string $value): void
    {
        echo '<label class="sn-field"><span>' . esc_html($label) . '</span>';
        echo '<textarea name="' . esc_attr($name) . '" rows="4">' . esc_textarea($value) . '</textarea></label>';
    }

    /**
     * @param array<string, string> $options
     */
    private static function select(string $name, string $label, string $value, array $options): void
    {
        echo '<label class="sn-field"><span>' . esc_html($label) . '</span><select name="' . esc_attr($name) . '">';
        foreach ($options as $k => $lab) {
            echo '<option value="' . esc_attr($k) . '"' . selected($value, $k, false) . '>' . esc_html($lab) . '</option>';
        }
        echo '</select></label>';
    }

    private static function checkbox(string $name, string $label, bool $on): void
    {
        echo '<label class="sn-check"><input type="checkbox" name="' . esc_attr($name) . '" value="1" ' . checked($on, true, false) . ' /> ' . esc_html($label) . '</label>';
    }
}
