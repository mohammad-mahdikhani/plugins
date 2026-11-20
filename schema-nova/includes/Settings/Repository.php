<?php
/**
 * Persistent plugin settings.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Settings;

use SchemaNova\Support\Arr;

final class Repository
{
    public const OPTION = 'schema_nova_settings';

    private static ?self $instance = null;

    /** @var array<string, mixed> */
    private array $data = [];

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $stored = get_option(self::OPTION, []);
        $this->data = is_array($stored) ? $stored : [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        $name = get_bloginfo('name');
        $url  = home_url('/');

        return [
            'version'         => SCHEMA_NOVA_VERSION,
            'wizard_complete' => false,
            'organization'    => [
                'type'            => 'Organization',
                'name'            => $name,
                'legal_name'      => '',
                'alternate_name'  => '',
                'description'     => get_bloginfo('description'),
                'url'             => $url,
                'logo'            => '',
                'email'           => get_option('admin_email'),
                'telephone'       => '',
                'founding_date'   => '',
                'vat_id'          => '',
                'tax_id'          => '',
                'iso6523'         => '',
                'naics'           => '',
                'same_as'         => [],
                'address'         => [
                    'street'   => '',
                    'locality' => '',
                    'region'   => '',
                    'postal'   => '',
                    'country'  => 'IR',
                ],
                'geo'             => [
                    'lat' => '',
                    'lng' => '',
                ],
                'opening_hours'   => [],
                'price_range'     => '',
                'area_served'     => 'IR',
                'contact_type'    => 'customer service',
                'return_days'     => 7,
                'return_fees'     => 'https://schema.org/FreeReturn',
                'shipping_rate'   => '',
                'shipping_currency' => 'IRR',
            ],
            'site'            => [
                'kind'              => 'mixed',
                'in_language'       => get_bloginfo('language') ?: 'fa-IR',
                'search_path'       => '?s={search_term_string}',
                'sitelinks_search'  => true,
            ],
            'types'           => [
                'post'    => 'BlogPosting',
                'page'    => 'WebPage',
                'product' => 'Product',
            ],
            'features'        => [
                'website'         => true,
                'organization'    => true,
                'webpage'         => true,
                'breadcrumb'      => true,
                'article'         => true,
                'product'         => true,
                'faq_auto'        => true,
                'video_auto'      => true,
                'image_metadata'  => true,
                'speakable'       => false,
                'profile'         => true,
            ],
            'output'          => [
                'disable_competitors' => false,
                'in_footer'           => false,
            ],
            'titles'          => [
                'control'       => true,
                'separator'     => ' | ',
                'brand_at_end'  => true,
                'sync_og'       => true,
            ],
            'optimizer'       => [
                'enabled'            => true,
                'mode'               => 'suggest',
                'interval_days'      => 3,
                'lookback_days'      => 28,
                'min_impressions'    => 50,
                'ctr_gap'            => 0.25,
                'max_pages_per_run'  => 25,
                'similarity_min'     => 0.22,
                'rollback_on_drop'   => true,
                'rollback_drop_pct'  => 0.15,
                'keep_brand'         => true,
            ],
            'connector'       => [
                'provider' => 'generic',
                'gsc'      => [
                    'client_id'     => '',
                    'client_secret' => '',
                    'refresh_token' => '',
                    'site_url'      => $url,
                    'access_token'  => '',
                    'token_expires' => 0,
                ],
                'ga4'      => [
                    'property_id'   => '',
                    'access_token'  => '',
                    'client_email'  => '',
                    'private_key'   => '',
                ],
                'generic'  => [
                    'endpoint'   => '',
                    'method'     => 'GET',
                    'auth_type'  => 'bearer',
                    'token'      => '',
                    'header_name'=> 'Authorization',
                    'extra_headers' => '',
                    'body'       => '',
                    'map'        => [
                        'rows'        => 'rows',
                        'page'        => 'page',
                        'query'       => 'query',
                        'clicks'      => 'clicks',
                        'impressions' => 'impressions',
                        'ctr'         => 'ctr',
                        'position'    => 'position',
                    ],
                ],
            ],
        ];
    }

    public function ensureDefaults(): void
    {
        $merged = self::mergeDefaults($this->data, self::defaults());
        if ($merged !== $this->data) {
            $this->data = $merged;
            $this->save();
        }
    }

    /**
     * @param array<string, mixed> $stored
     * @param array<string, mixed> $defaults
     * @return array<string, mixed>
     */
    public static function mergeDefaults(array $stored, array $defaults): array
    {
        foreach ($defaults as $key => $value) {
            if (! array_key_exists($key, $stored)) {
                $stored[$key] = $value;
                continue;
            }
            if (is_array($value) && self::isAssoc($value) && is_array($stored[$key])) {
                $stored[$key] = self::mergeDefaults($stored[$key], $value);
            }
        }

        return $stored;
    }

    /**
     * @param array<string, mixed> $array
     */
    private static function isAssoc(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    public function all(): array
    {
        return $this->data;
    }

    public function get(string $path, mixed $default = null): mixed
    {
        return Arr::get($this->data, $path, $default);
    }

    public function set(string $path, mixed $value): void
    {
        $this->data = Arr::set($this->data, $path, $value);
        $this->save();
    }

    /**
     * @param array<string, mixed> $values
     */
    public function merge(array $values): void
    {
        $this->data = array_replace_recursive($this->data, $values);
        $this->save();
    }

    public function save(): void
    {
        update_option(self::OPTION, $this->data, false);
    }
}
