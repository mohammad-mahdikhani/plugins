"""Shared HTML shell for WordPress HTML blocks (Pakan Mental)."""
from __future__ import annotations

import json
import urllib.request

AUTH = "Basic bWhtOkNaT1p3U2RRMU1PMmhmMzJwM0FSWXNEOA=="
BASE = "https://www.pakanmental.com/wp-json/wp/v2"
SITE = "https://www.pakanmental.com"
LOGO_WEBP = f"{SITE}/wp-content/uploads/2026/10/logo-pakanmental.webp"

URLS = {
    "home": f"{SITE}/",
    "shop": f"{SITE}/%d9%81%d8%b1%d9%88%d8%b4%da%af%d8%a7%d9%87/",
    "cart": f"{SITE}/%d8%b3%d8%a8%d8%af-%d8%ae%d8%b1%db%8c%d8%af/",
    "blog": f"{SITE}/%d9%85%d8%ac%d9%84%d9%87/",
    "product_demo": f"{SITE}/%d9%86%d9%85%d9%88%d9%86%d9%87-%d9%85%d8%ad%d8%b5%d9%88%d9%84/",
}


def req(method: str, url: str, data=None):
    body = None
    headers = {"Authorization": AUTH}
    if data is not None:
        body = json.dumps(data).encode("utf-8")
        headers["Content-Type"] = "application/json"
    r = urllib.request.Request(url, data=body, headers=headers, method=method)
    with urllib.request.urlopen(r, timeout=120) as res:
        return json.loads(res.read().decode())


def load_css() -> str:
    with open("/workspace/pakanmental-home/assets/css/pm.css", encoding="utf-8") as f:
        css = f.read()
    css += """
body.page .site-header,
body.page .site-footer,
body.page .page-header,
body.single .site-header,
body.single .site-footer,
body.single .page-header,
body.blog .site-header,
body.blog .site-footer,
body.archive .site-header,
body.archive .site-footer { display: none !important; }
body.page #content.site-main,
body.single #content.site-main,
body.blog #content.site-main,
body.archive #content.site-main { max-width: none; padding: 0; margin: 0; }
body.single .pm-single-post-wrap,
body.blog .pm-blog-archive-fallback { margin: 0; }
"""
    return css


def load_home_js() -> str:
    with open("/workspace/pakanmental-home/assets/js/home.js", encoding="utf-8") as f:
        return f.read()


def logo_picture(footer: bool = False) -> str:
    w, h = (160, 64) if footer else (220, 72)
    load = "lazy" if footer else "eager"
    fetch = "" if footer else ' fetchpriority="high"'
    alt = "" if footer else ' alt="پاکن منتال"'
    return (
        f'<picture class="pm-logo__picture">'
        f'<source type="image/webp" srcset="{LOGO_WEBP}" />'
        f'<img class="pm-logo__img" src="{LOGO_WEBP}" width="{w}" height="{h}" loading="{load}" decoding="async"{fetch}{alt} />'
        f"</picture>"
    )


def header(active: str) -> str:
    def nav_item(key: str, label: str) -> str:
        cls = ' class="is-active"' if active == key else ""
        return f'<a href="{URLS[key]}"{cls}>{label}</a>'

    return f"""
<header class="pm-header">
  <div class="pm-container pm-header__inner">
    <nav class="pm-nav pm-nav--start" aria-label="منوی اصلی">
      {nav_item("home", "خانه")}
      {nav_item("shop", "فروشگاه")}
    </nav>
    <a class="pm-logo" href="{URLS["home"]}" aria-label="پاکن منتال">
      {logo_picture()}
    </a>
    <div class="pm-header__end">
      <nav class="pm-nav pm-nav--end">
        {nav_item("blog", "مجله")}
        <a href="{SITE}/contact/">تماس</a>
      </nav>
      <a class="pm-cart" href="{URLS["cart"]}" aria-label="سبد خرید">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6h15l-1.5 9h-12z"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/><path d="M6 6L5 3H2"/></svg>
        <span class="pm-cart__count">۰</span>
      </a>
    </div>
    <details class="pm-menu-mobile">
      <summary>منو</summary>
      <div class="pm-menu-mobile__panel">
        <a href="{URLS["shop"]}">فروشگاه</a>
        <a href="{URLS["blog"]}">مجله</a>
        <a href="{URLS["cart"]}">سبد خرید</a>
      </div>
    </details>
  </div>
</header>
"""


def footer() -> str:
    return f"""
<footer class="pm-footer">
  <div class="pm-container pm-footer__grid">
    <div class="pm-footer__brand">
      <a href="{URLS["home"]}">{logo_picture(footer=True)}</a>
      <p>فروشگاه بازی فکری، بردگیم، پازل و سرگرمی رومیزی — پاکن منتال.</p>
    </div>
    <div>
      <h2 class="pm-footer__title">فروشگاه</h2>
      <ul>
        <li><a href="{URLS["shop"]}">همه محصولات</a></li>
        <li><a href="{URLS["cart"]}">سبد خرید</a></li>
      </ul>
    </div>
    <div>
      <h2 class="pm-footer__title">مجله</h2>
      <ul>
        <li><a href="{URLS["blog"]}">مقالات</a></li>
      </ul>
    </div>
  </div>
  <p class="pm-footer__copy">© پاکن منتال</p>
</footer>
"""


def wrap_page(active: str, main_html: str, extra_js: str = "") -> str:
    css = load_css()
    js_block = f'<script id="pm-home-js">{extra_js}</script>' if extra_js else ""
    return f"""<!-- wp:html -->
<div class="pm-site pm-home" dir="rtl">
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
<style id="pm-site-css">{css}</style>
{header(active)}
<main id="pm-main">
{main_html}
</main>
{footer()}
{js_block}
</div>
<!-- /wp:html -->"""
