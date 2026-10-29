"""Page main content fragments."""
from __future__ import annotations

from .builder import URLS, req, BASE, SITE

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
}


def category_tile(label: str, icon_key: str) -> str:
    svg = _CAT_SVGS[icon_key]
    return (
        f'<a class="pm-cat" href="{URLS["shop"]}">'
        f'<span class="pm-cat__icon" aria-hidden="true">{svg}</span>'
        f'<span class="pm-cat__label">{label}</span></a>'
    )


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
    rows = [
        ("پرفروش‌ها و ترند", "trend", True),
        ("پازل", "puzzle", False),
        ("بردگیم", "board", False),
        ("اکشن فیگور", "figure", False),
    ]
    carousels = ""
    for title, rid, alt in rows:
        cards = ""
        for i in range(1, 7):
            badge = '<span class="pm-card__badge">ترند</span>' if alt and i <= 2 else ""
            cards += f"""
<article class="pm-card pm-card--empty">
  <a href="{URLS["product_demo"]}"><div class="pm-card__ph"></div>
  <h3 class="pm-card__title">{title} {i}</h3>
  <p class="pm-card__price">— <small>تومان</small></p></a>{badge}
</article>"""
        section_alt = " pm-section--alt" if alt else ""
        carousels += f"""
<section class="pm-section{section_alt}">
  <div class="pm-container">
    <header class="pm-section__head">
      <div><h2>{title}</h2><p>پس از افزودن محصول در ووکامرس، این ردیف خودکار پر می‌شود.</p></div>
      <a class="pm-more" href="{URLS["shop"]}">مشاهده همه</a>
    </header>
    <div class="pm-carousel">
      <div class="pm-track" id="track-{rid}">{cards}</div>
    </div>
  </div>
</section>"""

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
    <div class="pm-cats">
      {category_tile("بردگیم", "board")}
      {category_tile("پازل", "puzzle")}
      {category_tile("اکشن فیگور", "figure")}
      {category_tile("کارتی", "card")}
      {category_tile("بازی منتالی", "mental")}
      {category_tile("لوازم جانبی", "accessory")}
    </div>
  </div>
</section>
{carousels}
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


def _dice_face_svg(dice_id: str, pips: list[tuple[float, float]], rotate: float, dx: float, dy: float) -> str:
    """Single isometric die for 404 art (viewBox unit coords)."""
    dots = "".join(
        f'<circle cx="{x}" cy="{y}" r="4.2" fill="#faf7f2"/>' for x, y in pips
    )
    return f"""
  <g class="pm-dice-404__die" transform="translate({dx} {dy}) rotate({rotate} 50 50)">
    <ellipse cx="50" cy="92" rx="34" ry="6" fill="#1e4339" opacity="0.14"/>
    <path d="M50 18 L88 36 L88 74 L50 92 L12 74 L12 36 Z" fill="url(#{dice_id}-top)" stroke="#2a5f52" stroke-width="1.6"/>
    <path d="M88 36 L102 44 L102 82 L88 74 Z" fill="url(#{dice_id}-side)" stroke="#1e4339" stroke-width="1.2"/>
    <path d="M12 36 L50 18 L88 36 L50 54 Z" fill="url(#{dice_id}-face)" stroke="#1e4339" stroke-width="1.2"/>
    {dots}
  </g>"""


def _dice_404_svg() -> str:
    die_a = _dice_face_svg("dA", [(50, 54)], -14, 8, 18)
    die_b = _dice_face_svg(
        "dB",
        [(38, 48), (62, 48), (38, 62), (62, 62)],
        6,
        72,
        8,
    )
    die_c = _dice_face_svg(
        "dC",
        [(38, 46), (62, 46), (50, 54), (38, 62), (62, 62), (50, 70)],
        18,
        132,
        22,
    )
    return f"""
<svg class="pm-not-found__dice-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 220 130" role="img" aria-label="سه تاس بازی">
  <defs>
    <linearGradient id="dA-top" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#faf7f2"/><stop offset="100%" stop-color="#e8f6f1"/></linearGradient>
    <linearGradient id="dA-side" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" stop-color="#4aa88e"/><stop offset="100%" stop-color="#2a5f52"/></linearGradient>
    <linearGradient id="dA-face" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#3d8f78"/><stop offset="100%" stop-color="#1e4339"/></linearGradient>
    <linearGradient id="dB-top" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#fff"/><stop offset="100%" stop-color="#e8f6f1"/></linearGradient>
    <linearGradient id="dB-side" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" stop-color="#5bb89e"/><stop offset="100%" stop-color="#357a68"/></linearGradient>
    <linearGradient id="dB-face" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#4aa88e"/><stop offset="100%" stop-color="#1e4339"/></linearGradient>
    <linearGradient id="dC-top" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#faf7f2"/><stop offset="100%" stop-color="#dceede"/></linearGradient>
    <linearGradient id="dC-side" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" stop-color="#3d8f78"/><stop offset="100%" stop-color="#1e4339"/></linearGradient>
    <linearGradient id="dC-face" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#2a5f52"/><stop offset="100%" stop-color="#163830"/></linearGradient>
  </defs>
  {die_a}
  {die_b}
  {die_c}
</svg>
"""


def not_found_main() -> str:
    dice = _dice_404_svg()
    return f"""
<section class="pm-not-found">
  <div class="pm-container">
    <div class="pm-not-found__hero">
      <p class="pm-not-found__code" aria-hidden="true">404</p>
      <div class="pm-not-found__art">{dice}</div>
    </div>
    <p class="pm-not-found__kicker">اوه! تاس بد آورد</p>
    <h1>این صفحه پیدا نشد</h1>
    <p>شاید لینک قدیمی باشد یا آدرس را اشتباه زده‌اید. یک بار دیگر تاس بیندازید — یا از فروشگاه یک بازی تازه انتخاب کنید.</p>
    <div class="pm-not-found__actions">
      <a class="pm-btn pm-btn--primary" href="{URLS["home"]}">بازگشت به خانه</a>
      <a class="pm-btn pm-btn--ghost" href="{URLS["shop"]}">رفتن به فروشگاه</a>
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
      <div class="pm-product-grid">{product_card_grid(12)}</div>
      <nav class="pm-pagination" aria-label="صفحه‌بندی">
        <span class="pm-pagination__item is-active">۱</span>
        <span class="pm-pagination__item">۲</span>
        <span class="pm-pagination__item">۳</span>
      </nav>
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
      <p>محصولات انتخاب‌شده — به‌روزرسانی بدون بارگذاری مجدد صفحه</p>
    </header>
    <div class="pm-wc-cart__body">
      [woocommerce_cart]
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
