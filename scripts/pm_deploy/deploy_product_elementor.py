"""Deploy WooCommerce single products as Elementor canvas (PM shell)."""
from __future__ import annotations

import html
import json
import re

from .builder import req, BASE, wrap_page_elementor_widgets, URLS
from .elementor_client import ElementorMcp
from .elementor_document import html_widget_tree_split
from . import wc_store as wc

# Use shortcodes when plugin 1.1.8+ is on server; until then deploy bakes HTML from REST.
USE_PRODUCT_SHORTCODES = False


def _push_elementor_product(post_id: int, tree: list) -> None:
    data_json = json.dumps(tree, ensure_ascii=False)
    _clear_elementor_element_cache(post_id)
    req(
        "PUT",
        f"https://www.pakanmental.com/wp-json/wc/v3/products/{post_id}",
        {
            "meta_data": [
                {"key": "_wp_page_template", "value": "elementor_canvas"},
                {"key": "_elementor_edit_mode", "value": "builder"},
                {"key": "_elementor_template_type", "value": "wp-post"},
                {"key": "_elementor_data", "value": data_json},
            ],
        },
    )


def _clear_elementor_element_cache(post_id: int) -> None:
    try:
        product = req("GET", f"https://www.pakanmental.com/wp-json/wc/v3/products/{post_id}")
    except Exception:
        return
    cache_id = None
    for meta in product.get("meta_data") or []:
        if meta.get("key") == "_elementor_element_cache":
            cache_id = meta.get("id")
            break
    if not cache_id:
        return
    req(
        "PUT",
        f"https://www.pakanmental.com/wp-json/wc/v3/products/{post_id}",
        {
            "meta_data": [
                {"id": cache_id, "key": "_elementor_element_cache", "value": None},
            ],
        },
    )


def _apply_product_elementor_settings(client: ElementorMcp, post_id: int) -> None:
    try:
        client.call(
            "elementor-update-page-settings",
            {
                "post_id": post_id,
                "settings": {
                    "hide_title": "yes",
                    "page_layout": "elementor_canvas",
                },
            },
        )
        client.call("elementor-publish-document", {"post_id": post_id})
    except RuntimeError as exc:
        print("elementor MCP settings skipped (product CPT):", exc)


def fetch_product_store(product_id: int) -> dict:
    import urllib.request

    url = f"https://www.pakanmental.com/wp-json/wc/store/v1/products/{product_id}"
    req_obj = urllib.request.Request(
        url,
        headers={
            "Accept": "application/json",
            "User-Agent": "PakanMentalDeploy/1.0",
        },
    )
    with urllib.request.urlopen(req_obj, timeout=45) as res:
        return json.loads(res.read().decode())


def fetch_product_wc_v3(product_id: int) -> dict:
    return req("GET", f"https://www.pakanmental.com/wp-json/wc/v3/products/{product_id}")


def fetch_magazine_posts(limit: int = 3, product_v3: dict | None = None) -> list:
    post_ids: list[int] = []
    if product_v3:
        for meta in product_v3.get("meta_data") or []:
            if meta.get("key") == "pm_related_post_ids" and meta.get("value"):
                post_ids = [int(x) for x in re.split(r"[\s,]+", str(meta["value"])) if str(x).isdigit()]
                break
    if post_ids:
        posts = []
        for pid in post_ids[:limit]:
            try:
                posts.append(req("GET", f"{BASE}/posts/{pid}"))
            except Exception:
                continue
        return posts
    try:
        return req("GET", f"{BASE}/posts?per_page={limit}&status=publish&orderby=date&order=desc")
    except Exception:
        return []


def _lead_block(short_description: str) -> str:
    return ""


def _product_promises_html() -> str:
    truck = (
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" '
        'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        '<path d="M3 7h11v10H3zM14 10h4l3 3v4h-7z"/>'
        '<circle cx="7" cy="18" r="1.6"/><circle cx="18" cy="18" r="1.6"/></svg>'
    )
    tag = (
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" '
        'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        '<path d="M20 12 12 4H5v7l8 8z"/><circle cx="8.5" cy="8.5" r="1"/></svg>'
    )
    wrench = (
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" '
        'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        '<path d="M14.7 6.3a4.5 4.5 0 0 0-6.4 6.4L4 17l3 3 4.3-4.3a4.5 4.5 0 0 0 6.4-6.4L15 12z"/></svg>'
    )
    return f"""
<ul class="pm-product-promises">
  <li class="pm-product-promise">
    <span class="pm-product-promise__icon">{truck}</span>
    <span class="pm-product-promise__copy">
      <small>ارسال سریع</small>
      <strong>ارسال کالا در نیشابور</strong>
      <span>در کمترین زمان</span>
    </span>
  </li>
  <li class="pm-product-promise">
    <span class="pm-product-promise__icon">{tag}</span>
    <span class="pm-product-promise__copy">
      <small>خرید مطمئن</small>
      <strong>تضمین قیمت</strong>
      <span>بهترین نرخ فروشگاه</span>
    </span>
  </li>
  <li class="pm-product-promise">
    <span class="pm-product-promise__icon">{wrench}</span>
    <span class="pm-product-promise__copy">
      <small>پس از خرید</small>
      <strong>پشتیبانی کامل قطعات</strong>
      <span>همراه شما می‌مانیم</span>
    </span>
  </li>
</ul>
"""


def _meta_value(product_v3: dict, key: str) -> str:
    for meta in product_v3.get("meta_data") or []:
        if meta.get("key") == key and meta.get("value"):
            return str(meta.get("value")).strip()
    return ""


def _game_specs_html(product_v3: dict) -> str:
    type_map = {
        "strategic": "استراتژیک",
        "competitive": "رقابتی",
        "puzzle": "معمایی",
        "mental": "فکری",
    }
    items = [
        ("age", "سن", _meta_value(product_v3, "_pm_game_age")),
        ("players", "بازیکن", _meta_value(product_v3, "_pm_game_players")),
        ("time", "زمان ست", _meta_value(product_v3, "_pm_game_time")),
        ("type", "نوع بازی", type_map.get(_meta_value(product_v3, "_pm_game_type"), _meta_value(product_v3, "_pm_game_type"))),
    ]
    tiles = [i for i in items if i[2]]
    if not tiles:
        return ""
    html_parts = ['<ul class="pm-game-specs" aria-label="ویژگی بازی">']
    for key, label, value in tiles:
        html_parts.append(
            f'<li class="pm-game-spec pm-game-spec--{html.escape(key)}">'
            f'<span class="pm-game-spec__label">{html.escape(label)}</span>'
            f'<span class="pm-game-spec__value">{html.escape(value)}</span></li>'
        )
    html_parts.append("</ul>")
    return "\n".join(html_parts)


def _content_section(description: str) -> str:
    if not description or not description.strip():
        return ""
    return f"""
<section class="pm-product-content-section pm-section" aria-labelledby="pm-product-content-title">
  <div class="pm-container">
    <header class="pm-section__head">
      <div>
        <h2 id="pm-product-content-title">توضیحات محصول</h2>
        <p>جزئیات، مشخصات و نکات نگهداری</p>
      </div>
    </header>
    <article class="pm-product-content entry-content">
      {description.strip()}
    </article>
  </div>
</section>
"""


def _magazine_section(posts: list) -> str:
    if not posts:
        return ""
    cards = ""
    for p in posts:
        title = html.escape(p["title"]["rendered"])
        link = html.escape(p["link"])
        excerpt = p.get("excerpt", {}).get("rendered", "")
        excerpt = re.sub(r"<[^>]+>", "", excerpt)[:140]
        excerpt = html.escape(excerpt)
        cards += f"""
<article class="pm-post">
  <a href="{link}">
    <div class="pm-post__ph" aria-hidden="true"></div>
    <h3>{title}</h3>
    <p>{excerpt}</p>
  </a>
</article>"""
    return f"""
<section class="pm-product-magazine pm-section pm-section--alt" aria-labelledby="pm-product-magazine-title">
  <div class="pm-container">
    <header class="pm-section__head">
      <div>
        <h2 id="pm-product-magazine-title">از مجله پاکن منتال</h2>
        <p>راهنما و مطالب مرتبط</p>
      </div>
      <a class="pm-more" href="{URLS["blog"]}">همه مطالب</a>
    </header>
    <div class="pm-blog pm-blog--product">{cards}
    </div>
  </div>
</section>
"""


def product_elementor_main(product: dict, product_v3: dict, posts: list) -> str:
    name = product.get("name") or ""
    pid = int(product.get("id") or 0)
    images = product.get("images") or []
    img = images[0].get("src") if images else ""
    alt = images[0].get("alt") if images else name
    price = wc.price_html(product)
    cats = product.get("categories") or []
    crumb = f'<a href="{URLS["shop"]}">فروشگاه</a>'
    if cats:
        cat = cats[0]
        slug = cat.get("slug") or ""
        cat_name = cat.get("name") or ""
        cat_link = f'{URLS["shop"].rstrip("/")}/product-category/{slug}/' if slug else URLS["shop"]
        crumb += f' / <a href="{cat_link}">{cat_name}</a>'
    crumb += f" / {html.escape(name)}"

    gallery = (
        f'<img class="pm-product-gallery__main" src="{html.escape(img)}" alt="{html.escape(alt)}" width="800" height="800" loading="eager" />'
        if img
        else '<div class="pm-product-gallery__main" aria-hidden="true"></div>'
    )

    long_desc = product_v3.get("description") or ""
    specs = _game_specs_html(product_v3)

    if USE_PRODUCT_SHORTCODES:
        specs = "[pm_game_specs]"
        content = '[pm_product_content title="توضیحات محصول" subtitle="جزئیات، مشخصات و نکات نگهداری"]'
        magazine = '[pm_product_magazine limit="3"]'
    else:
        content = _content_section(long_desc)
        magazine = _magazine_section(posts)

    return f"""
<style id="pm-hide-wc-duplicate">
body.single-product.elementor-page header.site-header,
body.single-product.elementor-page footer.site-footer,
body.single-product.elementor-page .site-header,
body.single-product.elementor-page .site-footer:not(.pm-footer),
body.single-product.elementor-page .woocommerce-breadcrumb,
body.single-product.elementor-page .woocommerce-product-gallery,
body.single-product.elementor-page .summary.entry-summary,
body.single-product.elementor-page .woocommerce-tabs ul.tabs,
body.single-product.elementor-page .related.products,
body.single-product.elementor-page .upsells.products {{ display: none !important; }}
body.single-product.elementor-page #primary.content-area,
body.single-product.elementor-page .site-main,
body.single-product.elementor-page .woocommerce-tabs,
body.single-product.elementor-page .woocommerce-Tabs-panel,
body.single-product.elementor-page div.product {{
  float: none !important; width: 100% !important; max-width: none !important;
  margin: 0 !important; padding: 0 !important; border: none !important; background: transparent !important;
}}
body.single-product.elementor-page .woocommerce-Tabs-panel {{ display: block !important; }}
</style>
<section class="pm-product-single">
  <div class="pm-container pm-product-single__grid">
    <div class="pm-product-gallery">
      {gallery}
    </div>
    <div class="pm-product-summary">
      <p class="pm-breadcrumb">{crumb}</p>
      <h1>{html.escape(name)}</h1>
      <p class="pm-product-summary__price">{price}</p>
      {specs}
      <style id="pm-atc-minimal">
.pm-product-summary form.cart.pm-add-to-cart{{display:flex!important;flex-direction:row!important;flex-wrap:wrap;align-items:center!important;justify-content:center!important;gap:.7rem}}
.pm-product-summary .pm-qty-row{{display:flex!important;flex-direction:row!important;flex-wrap:wrap;align-items:center!important;justify-content:center!important;grid-template-columns:none!important;gap:.7rem;width:100%}}
.pm-qty-stepper{{display:inline-flex;direction:ltr;align-items:center;justify-content:space-between;min-width:11.25rem;height:48px;padding:0 .3rem;border:1.5px solid color-mix(in srgb, var(--pm-mint-500, #4aa88e) 48%, var(--pm-teal-700, #2a5f52));border-radius:999px;background:#fff}}
.pm-qty-stepper__btn{{width:36px;height:36px;border:0;border-radius:50%;background:transparent;color:var(--pm-teal-700,#2a5f52);font-size:1.2rem;font-weight:600;cursor:pointer;line-height:1}}
.pm-qty-stepper__btn:hover{{background:var(--pm-mint-50,#e8f6f1);color:var(--pm-mint-600,#3d8f78)}}
.pm-product-summary .pm-qty-stepper .pm-qty{{width:2.4rem!important;border:0!important;box-shadow:none!important;background:transparent;text-align:center;font-weight:700;-moz-appearance:textfield;appearance:textfield}}
.pm-product-summary .pm-qty-stepper .pm-qty::-webkit-outer-spin-button,.pm-product-summary .pm-qty-stepper .pm-qty::-webkit-inner-spin-button{{-webkit-appearance:none;margin:0}}
.pm-product-summary .single_add_to_cart_button{{width:auto!important;min-width:11.5rem;height:48px;margin:0;padding:0 1.5rem;border-radius:999px;box-shadow:none}}
      </style>
      <form class="cart pm-add-to-cart" method="post" action="">
        <div class="pm-qty-row">
          <div class="pm-qty-stepper" role="group" aria-label="تعداد">
            <button type="button" class="pm-qty-stepper__btn" data-pm-qty-step="1" aria-label="افزایش تعداد">+</button>
            <input type="number" class="qty pm-qty" name="quantity" value="1" min="1" step="1" inputmode="numeric" aria-label="تعداد" />
            <button type="button" class="pm-qty-stepper__btn" data-pm-qty-step="-1" aria-label="کاهش تعداد">−</button>
          </div>
          <button type="submit" name="add-to-cart" value="{pid}" class="pm-btn pm-btn--primary single_add_to_cart_button">افزودن به سبد خرید</button>
        </div>
        <input type="hidden" name="add-to-cart" value="{pid}" />
      </form>
      <script>
      (function(){{
        document.addEventListener("click", function(e){{
          var btn = e.target.closest("[data-pm-qty-step]");
          if (!btn) return;
          e.preventDefault();
          var wrap = btn.closest(".pm-qty-stepper");
          var input = wrap && wrap.querySelector("input.qty");
          if (!input) return;
          var min = parseInt(input.min || "1", 10) || 1;
          var next = (parseInt(input.value, 10) || 1) + (parseInt(btn.getAttribute("data-pm-qty-step"), 10) || 0);
          if (next < min) next = min;
          input.value = String(next);
        }});
      }})();
      </script>
      {_product_promises_html()}
    </div>
  </div>
</section>
{content}
{magazine}
<section class="pm-section pm-section--alt">
  <div class="pm-container">
    <h2>محصولات مرتبط</h2>
    [related_products limit="4" columns="4"]
  </div>
</section>
"""


def deploy_product(post_id: int) -> None:
    product = fetch_product_store(post_id)
    product_v3 = fetch_product_wc_v3(post_id)
    posts = fetch_magazine_posts(3, product_v3)

    main = product_elementor_main(product, product_v3, posts)
    wh, wm, wf = wrap_page_elementor_widgets("shop", main, include_hero_js=False, include_shop_js=False)
    hide = '<style id="pm-hide-wc-duplicate">body.single-product.elementor-page header.site-header,body.single-product.elementor-page .site-header,body.single-product.elementor-page .woocommerce-product-gallery,body.single-product.elementor-page .summary.entry-summary,body.single-product.elementor-page .woocommerce-tabs ul.tabs,body.single-product.elementor-page .related.products{display:none!important}body.single-product.elementor-page .woocommerce-Tabs-panel{display:block!important;padding:0!important;border:0!important}</style>'
    wh = hide + wh
    wm = hide + wm
    tree = html_widget_tree_split(wh, wm, wf)
    _push_elementor_product(post_id, tree)
    client = ElementorMcp()
    _apply_product_elementor_settings(client, post_id)
    try:
        client.call("elementor-publish-document", {"post_id": post_id})
    except RuntimeError:
        pass
    _clear_elementor_element_cache(post_id)
    print("product elementor canvas", post_id, "ok")


def main() -> None:
    deploy_product(347)


if __name__ == "__main__":
    main()
