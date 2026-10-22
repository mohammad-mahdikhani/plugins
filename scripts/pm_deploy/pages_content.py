"""Page main content fragments."""
from __future__ import annotations

from .builder import URLS, req, BASE, SITE


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
      <a class="pm-cat" href="{URLS["shop"]}"><span class="pm-cat__label">بردگیم</span></a>
      <a class="pm-cat" href="{URLS["shop"]}"><span class="pm-cat__label">پازل</span></a>
      <a class="pm-cat" href="{URLS["shop"]}"><span class="pm-cat__label">اکشن فیگور</span></a>
      <a class="pm-cat" href="{URLS["shop"]}"><span class="pm-cat__label">کارتی</span></a>
      <a class="pm-cat" href="{URLS["shop"]}"><span class="pm-cat__label">بازی منتالی</span></a>
      <a class="pm-cat" href="{URLS["shop"]}"><span class="pm-cat__label">لوازم جانبی</span></a>
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


def shop_main() -> str:
    return f"""
<section class="pm-page-head">
  <div class="pm-container">
    <h1>فروشگاه</h1>
    <p>بردگیم، پازل، اکشن فیگور و سرگرمی رومیزی — محصولات به‌زودی اضافه می‌شوند.</p>
  </div>
</section>
<section class="pm-shop">
  <div class="pm-container pm-shop__layout">
    <aside class="pm-filters" aria-label="فیلترها">
      <h2>دسته‌بندی</h2>
      <ul class="pm-filter-list">
        <li><a href="{URLS["shop"]}" class="is-active">همه</a></li>
        <li><a href="{URLS["shop"]}">بردگیم</a></li>
        <li><a href="{URLS["shop"]}">پازل</a></li>
        <li><a href="{URLS["shop"]}">اکشن فیگور</a></li>
      </ul>
      <h2>مرتب‌سازی</h2>
      <select class="pm-select" aria-label="مرتب‌سازی">
        <option>جدیدترین</option>
        <option>پرفروش‌ترین</option>
        <option>ارزان‌ترین</option>
      </select>
    </aside>
    <div class="pm-shop__main">
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
    return f"""
<section class="pm-page-head">
  <div class="pm-container">
    <h1>سبد خرید</h1>
    <p>هنوز محصولی اضافه نکرده‌اید — پس از فعال‌سازی ووکامرس، سبد اینجا به‌روز می‌شود.</p>
  </div>
</section>
<section class="pm-cart-page">
  <div class="pm-container">
    <div class="pm-cart-empty">
      <div class="pm-cart-empty__icon" aria-hidden="true"></div>
      <h2>سبد خرید شما خالی است</h2>
      <p>از فروشگاه یک سرگرمی رومیزی برای خودتان یا هدیه انتخاب کنید.</p>
      <a class="pm-btn pm-btn--primary" href="{URLS["shop"]}">رفتن به فروشگاه</a>
    </div>
    <div class="pm-cart-table-wrap" hidden>
      <table class="pm-cart-table">
        <thead><tr><th>محصول</th><th>قیمت</th><th>تعداد</th><th>جمع</th></tr></thead>
        <tbody></tbody>
      </table>
      <div class="pm-cart-summary">
        <p><span>جمع جزء</span><strong>۰ تومان</strong></p>
        <a class="pm-btn pm-btn--primary" href="#">تسویه حساب</a>
      </div>
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
