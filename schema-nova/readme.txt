=== Schema Nova ===
Contributors: webakery
Tags: schema, json-ld, seo, rich results, search console
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

JSON-LD structured data for any WordPress theme, plus SERP-aware titles and metas driven by the analytics API you connect.

== Description ==

Schema Nova (هوش‌اسکیما) outputs a single connected `@graph` JSON-LD document that follows [Google Search Central structured data](https://developers.google.com/search/docs/appearance/structured-data/search-gallery) and the [Rich result report](https://support.google.com/webmasters/answer/7552505) types.

It does not depend on your theme. Markup is printed in `wp_head`.

= Schema types =

Organization (and OnlineStore / LocalBusiness / NewsMediaOrganization), WebSite + Sitelinks search box, WebPage, BreadcrumbList, Article / NewsArticle / BlogPosting, Product (WooCommerce + merchant fields), FAQPage, Event, JobPosting, Recipe, VideoObject (YouTube, Vimeo, Aparat), Course, SoftwareApplication, Dataset, Review, Service, ProfilePage, CollectionPage, SearchResultsPage, QAPage.

= Smart titles and metas =

Connect Google Search Console, GA4, or any Analytics-like REST API. Every few days the plugin:

1. Pulls queries, clicks, impressions, CTR and position.
2. Finds URLs whose CTR is below the expected CTR for that position.
3. Rewrites the title and meta only when the query still overlaps the page content (Google title-link guidelines: unique, concise, no stuffing).
4. Suggests by default, or auto-applies. Rolls back if CTR drops.

== Installation ==

1. Copy the `schema-nova` folder into `wp-content/plugins/`.
2. Activate **هوش‌اسکیما | Schema Nova**.
3. Fill Organization identity.
4. Connect an API under Data source.
5. Leave the optimizer in Suggest mode until you trust the proposals.

== Changelog ==

= 1.1 =
* Author set to webakery.ir.
* Cleaner WordPress install zip.

= 1.0.0 =
* First release.
