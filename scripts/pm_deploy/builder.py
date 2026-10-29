"""Shared HTML shell for WordPress HTML blocks (Pakan Mental)."""
from __future__ import annotations

import json
import urllib.request

from .catalog import catalog_script

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
    "account": f"{SITE}/my-account/",
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
    with open("/workspace/pakanmental-home/assets/css/pm-brand-overrides.css", encoding="utf-8") as f:
        css += "\n" + f.read()
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
body.archive #content.site-main { max-width: none; padding: 0; margin: 0; background: #fafaf9 !important; }
body.single .pm-single-post-wrap,
body.blog .pm-blog-archive-fallback { margin: 0; }
.pm-site .pm-search__input,
.pm-site .pm-search__input:focus,
.pm-site .pm-search__input:focus-visible {
  outline: none !important;
  border: none !important;
  border-radius: 9999px !important;
  -webkit-tap-highlight-color: transparent;
}
.pm-site .pm-search__input::-moz-focus-inner { border: 0; }
.elementor-widget-html .pm-header,
.elementor-widget-html .pm-header__bar,
.elementor-widget-html .pm-header__nav,
.elementor-widget-html .pm-seg,
.elementor-widget-html .pm-mega { overflow: visible !important; }
"""
    return css


def load_site_js(include_hero: bool = False, include_shop: bool = False) -> str:
    parts = []
    with open("/workspace/pakanmental-home/assets/js/pm-search.js", encoding="utf-8") as f:
        parts.append(f.read())
    with open("/workspace/pakanmental-home/assets/js/pm-header.js", encoding="utf-8") as f:
        parts.append(f.read())
    with open("/workspace/pakanmental-home/assets/js/pm-mega-menu.js", encoding="utf-8") as f:
        parts.append(f.read())
    with open("/workspace/pakanmental-home/assets/js/pm-trust-badges.js", encoding="utf-8") as f:
        parts.append(f.read())
    with open("/workspace/pakanmental-home/assets/js/pm-mobile.js", encoding="utf-8") as f:
        parts.append(f.read())
    if include_hero:
        with open("/workspace/pakanmental-home/assets/js/home.js", encoding="utf-8") as f:
            parts.append(f.read())
    if include_shop:
        with open("/workspace/pakanmental-home/assets/js/pm-shop.js", encoding="utf-8") as f:
            parts.append(f.read())
    return "\n".join(parts)


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


def mobile_dock(active: str) -> str:
    def item(key: str, label: str, href: str, svg: str) -> str:
        cls = "pm-dock__item is-active" if active == key else "pm-dock__item"
        return f"""<a class="{cls}" href="{href}">
  <span class="pm-dock__icon-wrap">{svg}</span>
  <span class="pm-dock__label">{label}</span>
</a>"""

    ic_home = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true"><path d="M4 10.5L12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5z"/></svg>'
    ic_shop = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true"><path d="M4 10h16l-1.2 9H5.2L4 10z"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>'
    ic_cart = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true"><path d="M6 6h15l-1.5 9h-12z"/><circle cx="9" cy="20" r="1.2"/><circle cx="18" cy="20" r="1.2"/></svg>'
    ic_user = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M6 20v-1a6 6 0 0 1 12 0v1"/></svg>'

    return f"""
<nav class="pm-dock pm-mobile-only" aria-label="ناوبری پایین موبایل">
  {item("home", "خانه", URLS["home"], ic_home)}
  {item("shop", "فروشگاه", URLS["shop"], ic_shop)}
  {item("cart", "سبد خرید", URLS["cart"], ic_cart)}
  {item("account", "حساب کاربری", URLS["account"], ic_user)}
</nav>
"""


def header(active: str) -> str:
    def seg(key: str, label: str) -> str:
        cls = "pm-seg__link is-active" if active == key else "pm-seg__link"
        href = URLS.get(key, URLS["home"])
        return f'<a class="{cls}" href="{href}">{label}</a>'

    shop_active = " is-active" if active == "shop" else ""
    cart_svg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6h15l-1.5 9h-12z"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/><path d="M6 6L5 3H2"/></svg>'
    return f"""
<header class="pm-header">
  <div class="pm-container pm-header__wrap">
    <div class="pm-header__bar">
      <button type="button" class="pm-burger pm-mobile-only" data-pm-drawer-open aria-controls="pm-drawer" aria-expanded="false" aria-label="باز کردن منو">
        <span class="pm-burger__lines" aria-hidden="true"><i></i><i></i><i></i></span>
      </button>
      <nav class="pm-header__nav pm-desktop-only" aria-label="منوی اصلی">
        <div class="pm-seg" role="list">
          {seg("home", "خانه")}
          <div class="pm-mega pm-seg__mega" data-pm-mega data-shop-url="{URLS["shop"]}">
            <a class="pm-seg__link pm-mega__trigger{shop_active}" href="{URLS["shop"]}" aria-haspopup="dialog" aria-expanded="false" id="pm-mega-trigger">فروشگاه</a>
            <div class="pm-mega__panel" hidden role="dialog" aria-labelledby="pm-mega-trigger">
              <div class="pm-mega__inner">
                <div class="pm-mega__head">
                  <span class="pm-mega__head-title">دسته‌بندی فروشگاه</span>
                  <button type="button" class="pm-mega__close" data-pm-mega-close aria-label="بستن منو">×</button>
                </div>
                <div class="pm-mega__custom" data-pm-elementor-slot="mega-promo"></div>
                <div class="pm-mega__grid" data-pm-mega-grid>
                  <p class="pm-mega__loading">در حال بارگذاری دسته‌ها…</p>
                </div>
                <a class="pm-mega__all" href="{URLS["shop"]}">مشاهده همه محصولات</a>
              </div>
            </div>
          </div>
          {seg("blog", "مجله")}
        </div>
      </nav>
      <a class="pm-header__logo" href="{URLS["home"]}" aria-label="پاکن منتال">
        <span class="pm-header__logo-glow" aria-hidden="true"></span>
        {logo_picture()}
      </a>
      <a class="pm-header__icon-btn pm-cart pm-mobile-only pm-header__cart-mobile" href="{URLS["cart"]}" aria-label="سبد خرید">
        {cart_svg}
      </a>
      <div class="pm-header__tools pm-desktop-only">
        <div class="pm-search pm-search--inline">
          <label class="pm-search__label" for="pm-search-input">جستجو در محصولات</label>
          <span class="pm-search__icon" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/></svg>
          </span>
          <input id="pm-search-input" class="pm-search__input" type="search" name="q" placeholder="جستجوی محصول…" autocomplete="off" enterkeyhint="search" />
          <div class="pm-search__results" hidden></div>
        </div>
        <a class="pm-header__icon-btn pm-cart" href="{URLS["cart"]}" aria-label="سبد خرید">
          {cart_svg}
        </a>
      </div>
    </div>
  </div>
  <div id="pm-drawer" class="pm-drawer" data-pm-drawer hidden>
    <div class="pm-drawer__backdrop" data-pm-drawer-close tabindex="-1" aria-hidden="true"></div>
    <aside class="pm-drawer__panel" role="dialog" aria-label="منوی موبایل">
      <div class="pm-drawer__head">
        <span class="pm-drawer__title">منوی پاکان منتال</span>
        <button type="button" class="pm-drawer__close" data-pm-drawer-close aria-label="بستن">×</button>
      </div>
      <div class="pm-drawer__search">
        <div class="pm-search pm-search--inline">
          <label class="pm-search__label" for="pm-search-input-mobile">جستجو</label>
          <span class="pm-search__icon" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/></svg>
          </span>
          <input id="pm-search-input-mobile" class="pm-search__input" type="search" name="q" placeholder="جستجوی محصول…" autocomplete="off" />
          <div class="pm-search__results" hidden></div>
        </div>
      </div>
      <nav class="pm-drawer__links">
        {seg("home", "خانه")}
        <a class="pm-drawer__link{' is-active' if active == 'shop' else ''}" href="{URLS["shop"]}">فروشگاه</a>
        {seg("blog", "مجله")}
        <a class="pm-drawer__link" href="{URLS["cart"]}">سبد خرید</a>
        <a class="pm-drawer__link" href="{URLS["account"]}">حساب کاربری</a>
      </nav>
      <div class="pm-drawer__cats">
        <p class="pm-drawer__cats-title">دسته‌بندی‌ها</p>
        <div class="pm-mega__grid pm-drawer__mega-grid" data-pm-mega-grid>
          <p class="pm-mega__loading">در حال بارگذاری…</p>
        </div>
      </div>
    </aside>
  </div>
</header>
"""


def footer() -> str:
    return f"""
<footer class="pm-footer">
  <div class="pm-footer__glow" aria-hidden="true"></div>
  <div class="pm-container pm-footer__grid">
    <div class="pm-footer__brand">
      <a class="pm-footer__logo" href="{URLS["home"]}">{logo_picture(footer=True)}</a>
      <p class="pm-footer__tagline">فروشگاه بازی فکری، بردگیم، پازل و سرگرمی رومیزی.</p>
      <p class="pm-footer__brand-name">فروشگاه پاکان منتال</p>
    </div>
    <div class="pm-footer__col">
      <h2 class="pm-footer__title">فروشگاه</h2>
      <ul class="pm-footer__links">
        <li><a href="{URLS["shop"]}">همه محصولات</a></li>
        <li><a href="{URLS["cart"]}">سبد خرید</a></li>
      </ul>
    </div>
    <div class="pm-footer__col">
      <h2 class="pm-footer__title">مجله</h2>
      <ul class="pm-footer__links">
        <li><a href="{URLS["blog"]}">مقالات</a></li>
        <li><a href="{URLS["home"]}">صفحه اصلی</a></li>
      </ul>
    </div>
    <div class="pm-footer__col pm-footer__enamad">
      <h2 class="pm-footer__title">نماد اعتماد</h2>
      <div class="pm-footer__enamad-slot" id="pm-enamad" data-pm-enamad>
        <span class="pm-footer__enamad-placeholder">پس از فعال‌سازی افزونه، از منوی «پاکن منتال» در پیشخوان کد نماد را وارد کنید.</span>
      </div>
    </div>
  </div>
  <p class="pm-footer__copy">© فروشگاه پاکان منتال — سرگرمی رومیزی برای هر سلیقه</p>
</footer>
"""


def wrap_page(
    active: str,
    main_html: str,
    include_hero_js: bool = False,
    include_shop_js: bool = False,
    *,
    elementor: bool = False,
) -> str:
    css = load_css()
    js = load_site_js(include_hero=include_hero_js, include_shop=include_shop_js)
    inner = f"""<div class="pm-site{' pm-site--has-hero' if include_hero_js else ''}" dir="rtl">
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
<style id="pm-site-css">{css}</style>
{catalog_script()}
{header(active)}
<main id="pm-main">
{main_html}
</main>
{footer()}
{mobile_dock(active)}
<script id="pm-site-js">{js}</script>
</div>"""
    if elementor:
        return inner
    return f"""<!-- wp:html -->
{inner}
<!-- /wp:html -->"""


def wrap_page_elementor_widgets(
    active: str,
    main_html: str,
    include_hero_js: bool = False,
    include_shop_js: bool = False,
) -> tuple[str, str, str]:
    """
    Three Elementor HTML widgets (header / main / footer) so manual edits in Elementor
    stay isolated. Each chunk carries pm-site for typography tokens.
    """
    css = load_css()
    js = load_site_js(include_hero=include_hero_js, include_shop=include_shop_js)
    site_class = f"pm-site{' pm-site--has-hero' if include_hero_js else ''}"
    assets = f"""<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
<style id="pm-site-css">{css}</style>
{catalog_script()}"""
    widget_header = f"""<!-- PM Elementor: هدر + مگامenu — ویرایش بنر data-pm-elementor-slot="mega-promo" -->
<div class="{site_class} pm-elementor-chrome" dir="rtl">
{assets}
{header(active)}
</div>"""
    widget_main = f"""<!-- PM Elementor: محتوای اصلی صفحه — این ویجت را دستی ویرایش کنید -->
<div class="{site_class} pm-elementor-main" dir="rtl">
<main id="pm-main">
{main_html}
</main>
</div>"""
    widget_footer = f"""<!-- PM Elementor: فوتر + اسکریپت -->
<div class="{site_class} pm-elementor-chrome" dir="rtl">
{footer()}
{mobile_dock(active)}
<script id="pm-site-js">{js}</script>
</div>"""
    return widget_header, widget_main, widget_footer
