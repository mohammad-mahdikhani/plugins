"""Page main content fragments."""
from __future__ import annotations

from urllib.parse import quote, unquote

from .builder import URLS, req, BASE, SITE
from . import wc_store as wc

_CAT_SVGS = {
    "board": (
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" '
        'stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="8" height="8" rx="1.5"/>'
        '<rect x="13" y="13" width="8" height="8" rx="1.5"/><circle cx="7" cy="7" r="1" fill="currentColor"/>'
        '<circle cx="17" cy="17" r="1" fill="currentColor"/></svg>'
    ),
    "puzzle": (
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" '
        'stroke-linecap="round" stroke-linejoin="round"><path d="M9 3h2a2 2 0 0 1 2 2v1h2a2 2 0 0 1 2 2v2h1a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-1v2a2 2 0 0 1-2 2h-2v1a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2v-1H5a2 2 0 0 1-2-2v-2H3a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h1V5a2 2 0 0 1 2-2h2V3z"/></svg>'
    ),
    "figure": (
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" '
        'stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="6" r="3"/>'
        '<path d="M7 21v-2a5 5 0 0 1 10 0v2"/><path d="M12 12v4"/></svg>'
    ),
    "card": (
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" '
        'stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="14" height="20" rx="2"/>'
        '<path d="M8 6h8M8 10h6"/><rect x="6" y="4" width="14" height="20" rx="2" opacity="0.35"/></svg>'
    ),
    "mental": (
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" '
        'stroke-linecap="round" stroke-linejoin="round"><path d="M9.5 4A5.5 5.5 0 0 0 4 9.5c0 2.2 1.2 4.1 3 5.2V18h5v-3.3c1.8-1.1 3-3 3-5.2A5.5 5.5 0 0 0 14.5 4 4.5 4.5 0 0 0 12 5a4.5 4.5 0 0 0-2.5-1z"/>'
        '<path d="M10 18h4"/></svg>'
    ),
    "accessory": (
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" '
        'stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>'
        '<path d="M3.3 7.2L12 12l8.7-4.8M12 22V12"/></svg>'
    ),
    "dice": (
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" '
        'stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="4"/>'
        '<circle cx="8" cy="8" r="1.2" fill="currentColor"/><circle cx="16" cy="16" r="1.2" fill="currentColor"/>'
        '<circle cx="12" cy="12" r="1.2" fill="currentColor"/></svg>'
    ),
    "chess": (
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" '
        'stroke-linecap="round" stroke-linejoin="round"><path d="M8 20h8M9 16h6l-1-4h-4z"/>'
        '<path d="M12 4v4M9 8h6"/><circle cx="12" cy="4" r="1.5"/></svg>'
    ),
    "cube": (
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" '
        'stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/>'
        '<path d="M12 12l8-4.5M12 12v9M12 12L4 7.5"/></svg>'
    ),
    "doll": (
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" '
        'stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="3"/>'
        '<path d="M6 20v-2a6 6 0 0 1 12 0v2"/></svg>'
    ),
}


def category_tile(label: str, icon_key: str, href: str | None = None) -> str:
    svg = _CAT_SVGS[icon_key]
    link = href or URLS["shop"]
    return (
        f'<a class="pm-cat" href="{link}">'
        f'<span class="pm-cat__icon" aria-hidden="true">{svg}</span>'
        f'<span class="pm-cat__label">{label}</span></a>'
    )


def _category_icon_key(name: str, slug: str) -> str:
    text = f"{name} {slug}"
    pairs = (
        ("پازل", "puzzle"),
        ("فیگور", "figure"),
        ("شطرنج", "chess"),
        ("نرد", "board"),
        ("تاس", "dice"),
        ("روبیک", "cube"),
        ("لگو", "cube"),
        ("عروسک", "doll"),
        ("جاکلید", "accessory"),
        ("فکری", "mental"),
        ("کارت", "card"),
    )
    for needle, key in pairs:
        if needle in text:
            return key
    return "accessory"


def _is_uncategorized(cat: dict) -> bool:
    slug = (cat.get("slug") or "").lower()
    name = cat.get("name") or ""
    if slug in {"uncategorized", "دسته-بندی-نشده"}:
        return True
    if "دسته‌بندی نشده" in name or "دسته بندی نشده" in name or "دسته-بندی-نشده" in name:
        return True
    return False


def home_categories_html() -> str:
    try:
        categories = req("GET", f"{SITE}/wp-json/wc/v3/products/categories?per_page=100&hide_empty=false")
        if not isinstance(categories, list):
            categories = []
    except Exception:
        categories = wc.fetch_categories()

    parents = [
        cat
        for cat in categories
        if int(cat.get("parent") or 0) == 0 and not _is_uncategorized(cat)
    ]
    parents.sort(key=lambda cat: ((cat.get("menu_order") or 0), cat.get("name") or ""))

    parts = ['<div class="pm-cats" data-pm-home-cats>']
    for cat in parents:
        name = cat.get("name") or ""
        slug = unquote(cat.get("slug") or "")
        href = cat.get("permalink") or ""
        if not href or href.rstrip("/") == URLS["shop"].rstrip("/"):
            href = f"{SITE}/product-category/{quote(slug)}/" if slug else URLS["shop"]
        parts.append(category_tile(name, _category_icon_key(name, slug), href))
    parts.append("</div>")
    return "\n".join(parts)


def home_product_rows_html() -> str:
    categories = wc.fetch_categories()
    trend = wc.fetch_products(per_page=8, orderby="date")
    if not trend:
        trend = wc.fetch_products(per_page=8, orderby="popularity")

    puzzle_id = wc.category_id(categories, "پازل")
    board_id = wc.category_id(categories, "بازی-فکری", "بازی فکری")
    figure_id = wc.category_id(categories, "اکشن-فیگور", "اکشن فیگور")

    puzzle = wc.fetch_products(per_page=8, category_id=puzzle_id) if puzzle_id else []
    board = wc.fetch_products(per_page=8, category_id=board_id) if board_id else []
    figure = wc.fetch_products(per_page=8, category_id=figure_id) if figure_id else []

    html = wc.carousel_row(
        title="جدیدترین محصولات",
        subtitle="همین حالا در فروشگاه پاکان منتال",
        row_id="trend",
        products=trend,
        more_url=URLS["shop"],
        alt=True,
        badge=True,
        placeholder_prefix="محصول",
        orderby="date",
    )
    if puzzle or puzzle_id:
        html += wc.carousel_row(
            title="پازل",
            subtitle="چالش تصویری برای همه سن‌ها",
            row_id="puzzle",
            products=puzzle,
            more_url=wc.category_permalink(categories, "پازل"),
            placeholder_prefix="پازل",
            category_id=puzzle_id,
        )
    if board or board_id:
        html += wc.carousel_row(
            title="بردگیم",
            subtitle="شب دورهم با بازی رومیزی",
            row_id="board",
            products=board,
            more_url=wc.category_permalink(categories, "بازی-فکری", "بازی فکری"),
            alt=True,
            placeholder_prefix="بردگیم",
            category_id=board_id,
        )
    if figure or figure_id:
        html += wc.carousel_row(
            title="اکشن فیگور",
            subtitle="کلکسیون و هدیه",
            row_id="figure",
            products=figure,
            more_url=wc.category_permalink(categories, "اکشن-فیگور", "اکشن فیگور"),
            placeholder_prefix="فیگور",
            category_id=figure_id,
        )
    return html


def shop_products_html(per_page: int = 12) -> str:
    products = wc.fetch_products(per_page=per_page, orderby="date")
    if not products:
        return product_card_grid(per_page)
    return "\n".join(wc.pm_shop_product(p) for p in products)


def product_card_grid(count: int = 12, link: str | None = None) -> str:
    link = link or URLS["product_demo"]
    items = []
    cats = ["بردگیم", "پازل", "فیگور", "کارتی", "بردگیم", "پازل"]
    for i in range(1, count + 1):
        cat = cats[i % len(cats)]
        items.append(
            f"""
<article class="pm-product">
  <a href="{link}" class="pm-product__link">
    <div class="pm-product__thumb" aria-hidden="true"></div>
    <h3 class="pm-product__title">محصول نمونه {i} — {cat}</h3>
    <p class="pm-product__price"><span>۱٬۲۵۰٬۰۰۰</span> <small>تومان</small></p>
  </a>
</article>"""
        )
    return "\n".join(items)


def home_main() -> str:
    categories_block = home_categories_html()
    rows = home_product_rows_html()

    return f"""
<section class="pm-rev" aria-label="اسلایدر اصلی">
  <div class="pm-rev__viewport">
    <article class="pm-rev__slide is-active" data-slide="0">
      <div class="pm-rev__bg pm-rev__bg--1" aria-hidden="true"></div>
      <div class="pm-rev__shade" aria-hidden="true"></div>
      <div class="pm-container pm-rev__inner">
        <div class="pm-rev__copy">
          <p class="pm-tags"><span>جدید</span><span>بردگیم</span><span>پازل</span></p>
          <h1>سرگرمی رومیزی برای هر سلیقه</h1>
          <p>بازی فکری، پازل، اکشن فیگور و بازی‌های منتالی — یک فروشگاه برای لحظه‌های دورهم.</p>
          <a class="pm-btn pm-btn--primary" href="{URLS["shop"]}">مشاهده فروشگاه</a>
        </div>
      </div>
    </article>
    <article class="pm-rev__slide" data-slide="1">
      <div class="pm-rev__bg pm-rev__bg--2" aria-hidden="true"></div>
      <div class="pm-rev__shade" aria-hidden="true"></div>
      <div class="pm-container pm-rev__inner">
        <div class="pm-rev__copy">
          <p class="pm-tags"><span>ترند</span><span>پازل</span></p>
          <h1>پازل‌های منتخب</h1>
          <p>از چالش‌های سبک تا مجموعه‌های حرفه‌ای.</p>
          <a class="pm-btn pm-btn--primary" href="{URLS["shop"]}">دسته پازل</a>
        </div>
      </div>
    </article>
    <article class="pm-rev__slide" data-slide="2">
      <div class="pm-rev__bg pm-rev__bg--3" aria-hidden="true"></div>
      <div class="pm-rev__shade" aria-hidden="true"></div>
      <div class="pm-container pm-rev__inner">
        <div class="pm-rev__copy">
          <p class="pm-tags"><span>اکشن فیگور</span></p>
          <h1>کلکسیون فیگور</h1>
          <p>برای کلکسیونرها و هدیهٔ خاص.</p>
          <a class="pm-btn pm-btn--primary" href="{URLS["shop"]}">مشاهده فیگورها</a>
        </div>
      </div>
    </article>
    <button type="button" class="pm-rev__arrow pm-rev__arrow--prev" data-pm-hero-prev aria-label="اسلاید قبلی">‹</button>
    <button type="button" class="pm-rev__arrow pm-rev__arrow--next" data-pm-hero-next aria-label="اسلاید بعدی">›</button>
    <div class="pm-rev__bullets" role="tablist" aria-label="اسلایدها">
      <button type="button" role="tab" aria-selected="true" data-pm-hero-dot="0"></button>
      <button type="button" role="tab" aria-selected="false" data-pm-hero-dot="1"></button>
      <button type="button" role="tab" aria-selected="false" data-pm-hero-dot="2"></button>
    </div>
  </div>
</section>
<section class="pm-section">
  <div class="pm-container">
    <header class="pm-section__head"><div><h2>دسته‌بندی فروشگاه</h2><p>مسیر سریع به هر نوع سرگرمی</p></div></header>
    {categories_block}
  </div>
</section>
{rows}
<section class="pm-section pm-section--alt">
  <div class="pm-container">
    <header class="pm-section__head">
      <div><h2>مجله پاکن منتال</h2><p>راهنما و مقالات</p></div>
      <a class="pm-more" href="{URLS["blog"]}">همه مطالب</a>
    </header>
    <div class="pm-blog">{blog_cards(3)}</div>
  </div>
</section>
<section class="pm-trust">
  <div class="pm-container pm-trust__grid">
    <div><strong>ارسال سریع</strong><span>به سراسر کشور</span></div>
    <div><strong>ضمانت اصالت</strong><span>کالای اورجینال</span></div>
    <div><strong>پشتیبانی</strong><span>پاسخگویی دوستانه</span></div>
  </div>
</section>
"""


def blog_cards(limit: int = 6) -> str:
    try:
        posts = req("GET", f"{BASE}/posts?per_page={limit}&status=publish")
    except Exception:
        posts = []
    if not posts:
        out = ""
        for i in range(1, limit + 1):
            out += f'<article class="pm-post pm-post--empty"><div class="pm-post__ph"></div><h3>مقاله نمونه {i}</h3><p>به‌زودی</p></article>'
        return out
    html = ""
    for p in posts:
        title = p["title"]["rendered"]
        link = p["link"]
        excerpt = p.get("excerpt", {}).get("rendered", "").replace("<p>", "").replace("</p>", "")[:120]
        html += f"""
<article class="pm-post">
  <a href="{link}">
    <div class="pm-post__ph" aria-hidden="true"></div>
    <h3>{title}</h3>
    <p>{excerpt}</p>
  </a>
</article>"""
    return html


def _filter_checks(name: str, options: list[tuple[str, str]]) -> str:
    items = []
    for value, label in options:
        items.append(
            f'<li><label class="pm-check"><input type="checkbox" name="{name}[]" value="{value}" />'
            f'<span class="pm-check__box" aria-hidden="true"></span><span>{label}</span></label></li>'
        )
    return f'<ul class="pm-check-list">{"".join(items)}</ul>'


def shop_filters_sidebar() -> str:
    return f"""
<form id="pm-shop-filters" class="pm-filters__form" action="{URLS["shop"]}" method="get">
  <div class="pm-filters__head">
    <h2>فیلتر محصولات</h2>
    <button type="button" class="pm-filters__close" data-pm-filter-close aria-label="بستن">×</button>
  </div>

  <fieldset class="pm-filter-block">
    <legend>قیمت (تومان)</legend>
    <p class="pm-filter-hint" data-pm-price-out>۰ – ۱۰٬۰۰۰٬۰۰۰ تومان</p>
    <div class="pm-price-inputs">
      <label>از <input type="number" id="pm-price-min" name="min_price" min="0" step="10000" value="0" inputmode="numeric" /></label>
      <label>تا <input type="number" id="pm-price-max" name="max_price" min="0" step="10000" value="10000000" inputmode="numeric" /></label>
    </div>
    <input type="range" id="pm-price-range" class="pm-range" min="0" max="10000000" step="100000" value="5000000" aria-label="حداکثر قیمت" />
  </fieldset>

  <fieldset class="pm-filter-block">
    <legend>برند</legend>
    {_filter_checks("brand", [
      ("pakan", "پاکن منتال"),
      ("hasbro", "Hasbro"),
      ("ravensburger", "Ravensburger"),
      ("fantasy-flight", "Fantasy Flight"),
      ("other", "سایر برندها"),
    ])}
  </fieldset>

  <fieldset class="pm-filter-block">
    <legend>نوع بازی</legend>
    {_filter_checks("game_type", [
      ("board", "بردگیم"),
      ("card", "کارتی"),
      ("puzzle", "پازل"),
      ("figure", "اکشن فیگور"),
      ("party", "مهمانی"),
      ("accessory", "لوازم جانبی"),
    ])}
  </fieldset>

  <fieldset class="pm-filter-block">
    <legend>نوع بازی فکری</legend>
    {_filter_checks("mental_type", [
      ("strategy", "استراتژی"),
      ("logic", "منطق و معما"),
      ("memory", "حافظه"),
      ("speed", "سرعت و واکنش"),
      ("coop", "همکاری"),
      ("deduction", "استنتاج"),
    ])}
  </fieldset>

  <fieldset class="pm-filter-block">
    <legend>تعداد بازیکن</legend>
    {_filter_checks("players", [
      ("1", "۱ نفر"),
      ("2", "۲ نفر"),
      ("3-4", "۳–۴ نفر"),
      ("5-6", "۵–۶ نفر"),
      ("7plus", "۷ نفر و بیشتر"),
    ])}
  </fieldset>

  <fieldset class="pm-filter-block">
    <legend>زمان هر بازی</legend>
    {_filter_checks("play_time", [
      ("lt30", "کمتر از ۳۰ دقیقه"),
      ("30-60", "۳۰ تا ۶۰ دقیقه"),
      ("60-90", "۶۰ تا ۹۰ دقیقه"),
      ("gt90", "بیش از ۹۰ دقیقه"),
    ])}
  </fieldset>

  <fieldset class="pm-filter-block">
    <legend>ست بازی</legend>
    {_filter_checks("game_set", [
      ("base", "بازی پایه"),
      ("expansion", "اکسپنشن / گسترش"),
      ("bundle", "بسته کامل"),
      ("standalone", "مستقل (نیاز به پایه ندارد)"),
    ])}
  </fieldset>

  <fieldset class="pm-filter-block">
    <legend>سن بازی</legend>
    {_filter_checks("age", [
      ("3", "۳+"),
      ("6", "۶+"),
      ("8", "۸+"),
      ("10", "۱۰+"),
      ("14", "۱۴+"),
      ("18", "۱۸+"),
    ])}
  </fieldset>

  <div class="pm-filters__actions">
    <button type="submit" class="pm-btn pm-btn--primary pm-btn--block">اعمال فیلتر</button>
    <button type="button" class="pm-btn pm-btn--ghost pm-btn--block" data-pm-filter-reset>پاک کردن</button>
  </div>
</form>
"""


def _die_flat_svg(pips: list[tuple[int, int]]) -> str:
    dots = "".join(
        f'<circle cx="{x}" cy="{y}" r="3.25" fill="#2a5f52"/>'
        for x, y in pips
    )
    return f"""<svg class="pm-not-found__die" viewBox="0 0 48 48" width="52" height="52" aria-hidden="true">
  <rect x="3.5" y="3.5" width="41" height="41" rx="11" fill="#ffffff" stroke="#1e4339" stroke-width="1.6"/>
  {dots}
</svg>"""


def _dice_404_row() -> str:
    four = [(16, 16), (32, 16), (16, 32), (32, 32)]
    return f"""
<div class="pm-not-found__code-row" aria-label="۴۰۴">
  <span class="pm-not-found__die-slot" style="--pm-rot:-10deg">{_die_flat_svg(four)}</span>
  <span class="pm-not-found__zero" aria-hidden="true">۰</span>
  <span class="pm-not-found__die-slot" style="--pm-rot:8deg">{_die_flat_svg(four)}</span>
</div>"""


def not_found_main() -> str:
    dice_row = _dice_404_row()
    return f"""
<section class="pm-not-found">
  <div class="pm-not-found__bg" aria-hidden="true"></div>
  <div class="pm-container pm-not-found__inner">
    {dice_row}
    <h1 class="pm-not-found__title">صفحه‌ای که دنبالش بودید اینجا نیست</h1>
    <p class="pm-not-found__lead">احتمالاً آدرس عوض شده یا اشتباه تایپ شده. از خانه یا فروشگاه بازی ادامه دهید.</p>
    <div class="pm-not-found__actions">
      <a class="pm-btn pm-btn--primary" href="{URLS["home"]}">بازگشت به خانه</a>
      <a class="pm-btn pm-btn--ghost" href="{URLS["shop"]}">فروشگاه پاکان منتال</a>
    </div>
  </div>
</section>
"""


def account_main() -> str:
    return """
<section class="pm-wc-account">
  <div class="pm-container">
    <header class="pm-wc-account__intro">
      <h1>حساب کاربری</h1>
      <p>سفارش‌ها، دانلودها، آدرس‌ها و اطلاعات ورود شما</p>
    </header>
    <div class="pm-wc-account__body">
      [woocommerce_my_account]
    </div>
  </div>
</section>
"""


def shop_main() -> str:
    return f"""
<style id="pm-hide-wc-shop">
body.woocommerce-shop .site-header,
body.woocommerce-shop .site-footer,
body.woocommerce-shop .woocommerce-products-header,
body.woocommerce-shop .woocommerce-result-count,
body.woocommerce-shop .woocommerce-ordering,
body.woocommerce-shop ul.products {{ display: none !important; }}
body.woocommerce-shop .pm-product-grid {{ display: grid !important; }}
</style>
<section class="pm-page-head">
  <div class="pm-container">
    <h1>فروشگاه</h1>
    <p>با فیلتر قیمت، برند، نوع بازی، بازیکن، زمان، ست و سن — محصول مناسب را سریع پیدا کنید.</p>
  </div>
</section>
<section class="pm-shop-category" aria-label="توضیحات دسته‌بندی">
  <div class="pm-container">
    <div class="pm-shop-category__content" data-pm-category-description>
      <h2 class="pm-shop-category__title">دسته‌بندی محصولات</h2>
      <div class="pm-shop-category__body">
        <p>این بخش برای متن معرفی دسته‌بندی است. در آرشیو ووکامرس، توضیحاتی که در «محصولات → دسته‌ها» وارد می‌کنید اینجا نمایش داده می‌شود.</p>
        <p>تا آن زمان می‌توانید همین متن را در ویرایشگر المنتور (HTML این صفحه) تغییر دهید.</p>
      </div>
    </div>
  </div>
</section>
<section class="pm-shop">
  <div class="pm-filters-backdrop" data-pm-filter-close tabindex="-1" aria-hidden="true"></div>
  <div class="pm-container pm-shop__layout">
    <aside class="pm-filters" aria-label="فیلترهای فروشگاه">
      {shop_filters_sidebar()}
    </aside>
    <div class="pm-shop__main">
      <div class="pm-shop__toolbar">
        <button type="button" class="pm-btn pm-btn--ghost" data-pm-filter-open>فیلترها</button>
        <label class="pm-sort">
          <span>مرتب‌سازی</span>
          <select class="pm-select" name="orderby" aria-label="مرتب‌سازی">
            <option value="date">جدیدترین</option>
            <option value="popularity">پرفروش‌ترین</option>
            <option value="price">ارزان‌ترین</option>
            <option value="price-desc">گران‌ترین</option>
          </select>
        </label>
      </div>
      <div class="pm-product-grid" data-pm-product-grid data-per-page="12" data-orderby="date">{shop_products_html(12)}</div>
    </div>
  </div>
</section>
"""


def cart_main() -> str:
    return """
<section class="pm-wc-cart">
  <div class="pm-container">
    <header class="pm-wc-cart__intro">
      <h1>سبد خرید</h1>
      <p>محصولات از ووکامرس — تعداد را تغییر دهید یا به پرداخت بروید</p>
    </header>
    <ol class="pm-checkout-steps" aria-label="مراحل خرید">
      <li class="is-current">سبد</li>
      <li>اطلاعات</li>
      <li>پرداخت</li>
    </ol>
    <div class="pm-wc-cart__body">
      [woocommerce_cart]
    </div>
  </div>
</section>
"""


def checkout_main() -> str:
    return """
<section class="pm-wc-checkout">
  <div class="pm-container">
    <header class="pm-wc-checkout__intro">
      <h1>تسویه حساب</h1>
      <p>آدرس، روش ارسال و درگاه پرداخت — همه از ووکامرس</p>
    </header>
    <ol class="pm-checkout-steps" aria-label="مراحل خرید">
      <li>سبد</li>
      <li class="is-current">اطلاعات</li>
      <li>پرداخت</li>
    </ol>
    <div class="pm-wc-checkout__body">
      [woocommerce_checkout]
    </div>
  </div>
</section>
"""


def blog_main() -> str:
    return f"""
<section class="pm-page-head">
  <div class="pm-container">
    <h1>مجله پاکن منتال</h1>
    <p>راهنمای خرید، معرفی بازی‌ها و نکات نگهداری کلکسیون.</p>
  </div>
</section>
<section class="pm-section">
  <div class="pm-container">
    <div class="pm-blog pm-blog--archive">{blog_cards(9)}</div>
  </div>
</section>
"""


def product_single_main() -> str:
    return f"""
<section class="pm-product-single">
  <div class="pm-container pm-product-single__grid">
    <div class="pm-product-gallery">
      <div class="pm-product-gallery__main" aria-label="تصویر محصول"></div>
      <div class="pm-product-gallery__thumbs">
        <button type="button" class="is-active" aria-label="تصویر ۱"></button>
        <button type="button" aria-label="تصویر ۲"></button>
        <button type="button" aria-label="تصویر ۳"></button>
      </div>
    </div>
    <div class="pm-product-summary">
      <p class="pm-breadcrumb"><a href="{URLS["shop"]}">فروشگاه</a> / بردگیم</p>
      <h1>بردگیم نمونه — شب دورهم</h1>
      <p class="pm-product-summary__price">۲٬۴۵۰٬۰۰۰ <small>تومان</small></p>
      <p class="pm-product-summary__desc">این یک صفحهٔ نمونهٔ تک‌محصول است. پس از نصب ووکامرس، قالب همین شکل برای محصولات واقعی اعمال می‌شود.</p>
      <ul class="pm-product-meta">
        <li><strong>رده سنی:</strong> ۱۰+</li>
        <li><strong>بازیکن:</strong> ۲–۶ نفر</li>
        <li><strong>زمان:</strong> ۴۵–۶۰ دقیقه</li>
      </ul>
      <div class="pm-qty-row">
        <label>تعداد</label>
        <input type="number" class="pm-qty" value="1" min="1" aria-label="تعداد" />
        <button type="button" class="pm-btn pm-btn--primary">افزودن به سبد</button>
      </div>
      <a class="pm-btn pm-btn--ghost" href="{URLS["cart"]}">مشاهده سبد خرید</a>
    </div>
  </div>
</section>
<section class="pm-section pm-section--alt">
  <div class="pm-container">
    <h2>محصولات مرتبط</h2>
    <div class="pm-product-grid pm-product-grid--compact">{product_card_grid(4)}</div>
  </div>
</section>
"""
