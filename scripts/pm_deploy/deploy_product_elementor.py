"""Deploy WooCommerce single products as Elementor canvas (PM shell)."""
from __future__ import annotations

import json

from .builder import req, BASE, wrap_page_elementor_widgets, URLS
from .elementor_client import ElementorMcp
from .elementor_document import html_widget_tree_split
from . import wc_store as wc


def _push_elementor_product(post_id: int, tree: list) -> None:
    data_json = json.dumps(tree, ensure_ascii=False)
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


def product_elementor_main(product: dict) -> str:
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
    crumb += f" / {name}"

    gallery = (
        f'<img class="pm-product-gallery__main" src="{img}" alt="{alt}" width="800" height="800" loading="eager" />'
        if img
        else '<div class="pm-product-gallery__main" aria-hidden="true"></div>'
    )

    desc = product.get("description") or product.get("short_description") or ""
    if desc and "<style" in desc:
        desc = ""
    if desc:
        desc_block = f'<div class="pm-product-summary__desc">{desc}</div>'
    else:
        desc_block = ""

    return f"""
<section class="pm-product-single">
  <div class="pm-container pm-product-single__grid">
    <div class="pm-product-gallery">
      {gallery}
    </div>
    <div class="pm-product-summary">
      <p class="pm-breadcrumb">{crumb}</p>
      <h1>{name}</h1>
      <p class="pm-product-summary__price">{price}</p>
      {desc_block}
      <div class="pm-qty-row">
        [add_to_cart id="{pid}" show_price="false" style=""]
      </div>
      <a class="pm-btn pm-btn--ghost" href="{URLS["cart"]}">مشاهده سبد خرید</a>
    </div>
  </div>
</section>
<section class="pm-section pm-section--alt">
  <div class="pm-container">
    <h2>محصولات مرتبط</h2>
    [related_products limit="4" columns="4"]
  </div>
</section>
"""


def fetch_product(product_id: int) -> dict:
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


def deploy_product(post_id: int) -> None:
    product = fetch_product(post_id)

    main = product_elementor_main(product)
    wh, wm, wf = wrap_page_elementor_widgets("shop", main, include_hero_js=False, include_shop_js=False)
    tree = html_widget_tree_split(wh, wm, wf)
    _push_elementor_product(post_id, tree)
    client = ElementorMcp()
    _apply_product_elementor_settings(client, post_id)
    print("product elementor canvas", post_id, "ok")


def main() -> None:
    deploy_product(347)


if __name__ == "__main__":
    main()
