<?php
/**
 * Request-time page facts used by every schema type builder.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Schema;

use SchemaNova\Settings\Repository;
use SchemaNova\Support\Text;
use SchemaNova\Support\Url;
use WP_Post;
use WP_Term;
use WP_User;

final class Context
{
    public string $url;
    public string $home;
    public string $title;
    public string $description;
    public string $language;
    public string $kind;
    public ?int $postId = null;
    public string $postType = '';
    public ?WP_Post $post = null;
    public ?WP_User $author = null;
    public ?WP_Term $term = null;
    /** @var list<array{url:string,name:string}> */
    public array $images = [];
    /** @var list<array{name:string,url:string}> */
    public array $crumbs = [];
    /** @var list<array{question:string,answer:string}> */
    public array $faqs = [];
    /** @var list<array<string, mixed>> */
    public array $videos = [];
    /** @var array<string, mixed> */
    public array $overrides = [];
    public Repository $settings;

    public static function fromWp(): self
    {
        $ctx            = new self();
        $ctx->settings  = Repository::instance();
        $ctx->home      = Url::home();
        $ctx->url       = Url::current();
        $ctx->language  = (string) $ctx->settings->get('site.in_language', get_bloginfo('language') ?: 'fa-IR');
        $ctx->kind      = Detector::kind();
        $ctx->title     = Detector::title();
        $ctx->description = Detector::description();
        $ctx->images    = Images::collect();
        $ctx->crumbs    = Breadcrumbs::collect();
        $ctx->faqs      = Detector::faqs();
        $ctx->videos    = Detector::videos();

        if (is_singular()) {
            $post = get_queried_object();
            if ($post instanceof WP_Post) {
                $ctx->post     = $post;
                $ctx->postId   = (int) $post->ID;
                $ctx->postType = $post->post_type;
                $author        = get_userdata((int) $post->post_author);
                $ctx->author   = $author instanceof WP_User ? $author : null;
                $stored        = get_post_meta($post->ID, '_schema_nova', true);
                $ctx->overrides = is_array($stored) ? $stored : [];
            }
        } elseif (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            if ($term instanceof WP_Term) {
                $ctx->term = $term;
            }
        }

        return $ctx;
    }

    /**
     * Stable home-page context for admin previews.
     */
    public static function forHome(): self
    {
        $ctx              = new self();
        $ctx->settings    = Repository::instance();
        $ctx->home        = Url::home();
        $ctx->url         = $ctx->home;
        $ctx->language    = (string) $ctx->settings->get('site.in_language', 'fa-IR');
        $ctx->kind        = 'home';
        $ctx->title       = get_bloginfo('name');
        $ctx->description = get_bloginfo('description');
        $ctx->images      = [];
        $logo             = Images::logo();
        if ($logo !== '') {
            $ctx->images[] = ['url' => $logo, 'name' => $ctx->title];
        }
        $ctx->crumbs = [
            ['name' => $ctx->title, 'url' => $ctx->home],
        ];
        $ctx->faqs      = [];
        $ctx->videos    = [];
        $ctx->overrides = [];

        return $ctx;
    }

    public function orgId(): string
    {
        return Url::id($this->home, 'organization');
    }

    public function siteId(): string
    {
        return Url::id($this->home, 'website');
    }

    public function pageId(): string
    {
        return Url::id($this->url, 'webpage');
    }

    public function entityId(string $fragment): string
    {
        return Url::id($this->url, $fragment);
    }

    public function override(string $key, mixed $fallback = null): mixed
    {
        return $this->overrides[$key] ?? $fallback;
    }

    public function feature(string $key): bool
    {
        return (bool) $this->settings->get('features.' . $key, true);
    }

    public function headline(): string
    {
        $custom = $this->override('headline');
        if (is_string($custom) && $custom !== '') {
            return Text::clean($custom, 110);
        }

        return Text::clean($this->title, 110);
    }
}
