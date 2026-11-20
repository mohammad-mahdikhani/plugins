---
name: wordpress
description: Operate nahal.website through the Easy MCP AI WordPress server. Use when reading or changing posts, pages, media, menus, comments, Rank Math SEO, WP Rocket cache, or site health on nahal.website.
---

# nahal.website WordPress

The `wordpress` MCP server is Easy MCP AI 2.5.0 on `https://nahal.website`. Tools run as the WordPress user that owns the API token. Switched-off tools are absent from the tool list.

## Before a multi-step change

Call `wp_get_skill` and follow it before a review, a new page, a redesign, or an update:

- `site-building` — pages, posts, banners, footers, going live
- `page-builders` — Elementor, Breakdance, WPBakery, Flatsome UX, and layout tools (`wp_get_page_layout`, `wp_list_layout_elements`, `wp_update_page_layout`)
- `safe-updates` — plugins, themes, and core
- `site-health-review` — Site Health, PHP errors, diagnostics

If no listed tool does the job, call `wp_list_disabled_tools` with a few words of the task before saying it cannot be done. `wp_cli_run` is not enabled on this site.

## How to edit

1. Find the live object first (`wp_search`, `wp_find_in_site`, `wp_list_posts`, `wp_list_pages`, `wp_get_post_full`). Do not invent IDs or slugs.
2. Read the current content, blocks, meta, and SEO before writing.
3. Create and update posts and pages as drafts unless mohammad asked to publish.
4. After a write, read the object back and say what changed, including the edit URL when the tool returns one.

Rank Math is on. Use `wp_ability_rank_math_*` for SEO meta, schema, sitemaps, redirections, and the site audit. WP Rocket is on. Use `wp_ability_wp_rocket_*` for cache status and purges. WooCommerce catalog tools are off; the only Woo tool present is `wp_ability_woocommerce_order_notes_query`.

## Confirm first

Ask before deleting or trashing content, changing users, publishing, editing menus, changing Rank Math sitewide settings, clearing the WP Rocket cache, or calling `wp_rest_write`.

When a tool fails on a valid request, call `wp_send_feedback` (`kind` `bug` or `missing_feature`, summary under 200 characters) before answering. Do not print the API token or copy it into files.
