"""WooCommerce Store API helpers for deploy-time product HTML."""
from __future__ import annotations

import html
import json
import urllib.parse
import urllib.request
from typing import Any

from .builder import SITE, URLS

_STORE = f"{SITE}/wp-json/wc/store/v1"


def _get(path: str, params: dict[str, Any] | None = None) -> Any:
    qs = ""
    if params:
        qs = "?" + urllib.parse.urlencode({k: v for k, v in params.items() if v is not None})
    url = f"{_STORE}{path}{qs}"
    req = urllib.request.Request(
        url,
        headers={
            "Accept": "application/json",
            "User-Agent": "PakanMentalDeploy/1.0 (+https://www.pakanmental.com)",
        },
    )
    with urllib.request.urlopen(req, timeout=45) as res:
        return json.loads(res.read().decode())


def fetch_products(
    *,
    per_page: int = 8,
    category_id: int | None = None,
    orderby: str = "date",
) -> list[dict]:
    params: dict[str, Any] = {
        "per_page": per_page,
        "orderby": orderby,
        "order": "desc",
    }
    if category_id:
        params["category"] = category_id
    try:
        data = _get("/products", params)
        return data if isinstance(data, list) else []
    except Exception:
        return []


def fetch_categories() -> list[dict]:
    try:
        data = _get("/products/categories", {"per_page": 100})
        return data if isinstance(data, list) else []
    except Exception:
        return []


def category_permalink(categories: list[dict], *slug_or_name: str) -> str:
    for hint in slug_or_name:
        if not hint:
            continue
        for cat in categories:
            if cat.get("slug") == hint or cat.get("name") == hint:
                return cat.get("permalink") or URLS["shop"]
    return URLS["shop"]


def category_id(categories: list[dict], *slug_or_name: str) -> int | None:
    for hint in slug_or_name:
        if not hint:
            continue
        for cat in categories:
            if cat.get("slug") == hint or cat.get("name") == hint:
                return int(cat["id"])
    return None


def price_html(product: dict) -> str:
    raw = product.get("price_html") or ""
    if raw:
        return raw
    prices = product.get("prices") or {}
    amount = prices.get("price") or "0"
    if not amount or amount == "0":
        return "— <small>تومان</small>"
    try:
        value = int(amount)
        formatted = f"{value:,}".replace(",", "٬")
    except (TypeError, ValueError):
        formatted = amount
    prefix = prices.get("currency_prefix") or ""
    suffix = prices.get("currency_suffix") or ""
    return f"{prefix}{formatted}{suffix}"


def pm_card(product: dict, *, badge: bool = False) -> str:
    name = html.escape(product.get("name") or "")
    link = html.escape(product.get("permalink") or URLS["shop"])
    images = product.get("images") or []
    if images:
        src = html.escape(images[0].get("thumbnail") or images[0].get("src") or "")
        alt = html.escape(images[0].get("alt") or name)
        media = f'<img class="pm-card__img" src="{src}" alt="{alt}" loading="lazy" width="300" height="300" />'
    else:
        media = '<div class="pm-card__ph" aria-hidden="true"></div>'
    badge_html = '<span class="pm-card__badge">ترند</span>' if badge else ""
    return f"""
<article class="pm-card">
  <a href="{link}" class="pm-card__link">
    {media}
    <h3 class="pm-card__title">{name}</h3>
    <p class="pm-card__price">{price_html(product)}</p>
  </a>{badge_html}
</article>"""


def pm_shop_product(product: dict) -> str:
    name = html.escape(product.get("name") or "")
    link = html.escape(product.get("permalink") or URLS["shop"])
    images = product.get("images") or []
    if images:
        src = html.escape(images[0].get("thumbnail") or images[0].get("src") or "")
        alt = html.escape(images[0].get("alt") or name)
        thumb = f'<img class="pm-product__thumb" src="{src}" alt="{alt}" loading="lazy" width="300" height="300" />'
    else:
        thumb = '<div class="pm-product__thumb" aria-hidden="true"></div>'
    return f"""
<article class="pm-product">
  <a href="{link}" class="pm-product__link">
    {thumb}
    <h3 class="pm-product__title">{name}</h3>
    <p class="pm-product__price">{price_html(product)}</p>
  </a>
</article>"""


def carousel_row(
    *,
    title: str,
    subtitle: str,
    row_id: str,
    products: list[dict],
    more_url: str | None = None,
    alt: bool = False,
    badge: bool = False,
    placeholder_prefix: str = "",
    category_id: int | None = None,
    orderby: str = "date",
) -> str:
    more = html.escape(more_url or URLS["shop"])
    cards = ""
    if products:
        for i, product in enumerate(products):
            cards += pm_card(product, badge=badge and i < 2)
    else:
        prefix = placeholder_prefix or title
        for i in range(1, 7):
            cards += f"""
<article class="pm-card pm-card--empty">
  <div class="pm-card__ph" aria-hidden="true"></div>
  <h3 class="pm-card__title">{html.escape(prefix)} {i}</h3>
  <p class="pm-card__price">— <small>تومان</small></p>
</article>"""
    section_alt = " pm-section--alt" if alt else ""
    track_id = f"track-{row_id}"
    cat_attr = ""
    if category_id:
        cat_attr = f' data-category="{int(category_id)}"'
    badge_attr = ' data-badge="1"' if badge else ""
    return f"""
<section class="pm-section{section_alt}">
  <div class="pm-container">
    <header class="pm-section__head">
      <div><h2>{html.escape(title)}</h2><p>{html.escape(subtitle)}</p></div>
      <a class="pm-more" href="{more}">مشاهده همه</a>
    </header>
    <div class="pm-carousel">
      <button type="button" class="pm-carousel__btn pm-carousel__btn--prev" data-pm-scroll="{track_id}" data-pm-dir="prev" aria-label="قبلی">‹</button>
      <div class="pm-track" id="{track_id}" data-pm-product-row data-orderby="{html.escape(orderby)}" data-per-page="8"{cat_attr}{badge_attr} tabindex="0">{cards}
      </div>
      <button type="button" class="pm-carousel__btn pm-carousel__btn--next" data-pm-scroll="{track_id}" data-pm-dir="next" aria-label="بعدی">›</button>
    </div>
  </div>
</section>"""
