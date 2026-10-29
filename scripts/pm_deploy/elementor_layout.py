"""Elementor V4 composition XML + settings for Pakan Mental templates."""
from __future__ import annotations

from .builder import URLS

LOGO_ID = 9

PAGE_IDS = {
    "home": 13,
    "shop": 15,
    "cart": 19,
    "blog": 20,
    "product": 21,
}


def _link(url: str) -> dict:
    return {"destination": url}


def _page_link(page_id: int, label: str) -> dict:
    return {"destination": {"id": page_id, "label": label}}


def header_xml(active: str) -> str:
    return """
<e-flexbox configuration-id="pm-header">
  <e-flexbox configuration-id="pm-header-bar">
    <e-flexbox configuration-id="pm-header-nav">
      <e-flexbox configuration-id="pm-nav-seg">
        <e-button configuration-id="pm-nav-home"></e-button>
        <e-button configuration-id="pm-nav-shop"></e-button>
        <e-button configuration-id="pm-nav-blog"></e-button>
      </e-flexbox>
    </e-flexbox>
    <e-image configuration-id="pm-logo"></e-image>
    <e-flexbox configuration-id="pm-header-tools">
      <e-button configuration-id="pm-cart-btn"></e-button>
    </e-flexbox>
  </e-flexbox>
</e-flexbox>
""".strip()


def header_config(active: str) -> tuple[dict, dict, dict]:
    nav_active = {
        "home": "pm-nav-home",
        "shop": "pm-nav-shop",
        "blog": "pm-nav-blog",
    }.get(active, "")

    element_config = {
        "pm-header": {"tag": "header"},
        "pm-header-bar": {},
        "pm-header-nav": {},
        "pm-nav-seg": {},
        "pm-nav-home": {
            "text": "خانه",
            "link": _page_link(PAGE_IDS["home"], "خانه"),
        },
        "pm-nav-shop": {
            "text": "فروشگاه",
            "link": _page_link(PAGE_IDS["shop"], "فروشگاه"),
        },
        "pm-nav-blog": {
            "text": "مجله",
            "link": _page_link(PAGE_IDS["blog"], "مجله"),
        },
        "pm-logo": {
            "image": {"src": {"id": LOGO_ID}, "size": "medium"},
            "link": _page_link(PAGE_IDS["home"], "پاکن منتال"),
        },
        "pm-header-tools": {},
        "pm-cart-btn": {
            "text": "سبد خرید",
            "link": _page_link(PAGE_IDS["cart"], "سبد خرید"),
        },
    }

    classes = {
        "pm-header": ["pm-header"],
        "pm-header-bar": ["PM Header Bar"],
        "pm-nav-seg": ["PM Nav Seg"],
        "pm-nav-home": ["PM Nav Link Active" if nav_active == "pm-nav-home" else "PM Nav Link"],
        "pm-nav-shop": ["PM Nav Link Active" if nav_active == "pm-nav-shop" else "PM Nav Link"],
        "pm-nav-blog": ["PM Nav Link Active" if nav_active == "pm-nav-blog" else "PM Nav Link"],
        "pm-cart-btn": ["PM Btn Ghost"],
    }

    style = {
        "pm-header": "width: 100%; flex-direction: column; padding: 0.75rem 0 0.35rem; align-items: stretch;",
        "pm-header-bar": "flex-direction: row; align-items: center; justify-content: space-between;",
        "pm-header-nav": "justify-content: flex-start; flex: 1; min-width: 0;",
        "pm-header-tools": "justify-content: flex-end; flex: 1; min-width: 0;",
        "pm-logo": "width: auto; max-width: 92px; flex: 0 0 auto;",
    }
    return element_config, style, classes


def footer_xml() -> str:
    return """
<e-flexbox configuration-id="pm-footer">
  <e-flexbox configuration-id="pm-footer-inner">
    <e-flexbox configuration-id="pm-footer-brand">
      <e-image configuration-id="pm-footer-logo"></e-image>
      <e-paragraph configuration-id="pm-footer-tagline"></e-paragraph>
      <e-heading configuration-id="pm-footer-name"></e-heading>
    </e-flexbox>
    <e-flexbox configuration-id="pm-footer-col-shop">
      <e-heading configuration-id="pm-footer-title-shop"></e-heading>
      <e-button configuration-id="pm-footer-link-shop"></e-button>
      <e-button configuration-id="pm-footer-link-cart"></e-button>
    </e-flexbox>
    <e-flexbox configuration-id="pm-footer-col-blog">
      <e-heading configuration-id="pm-footer-title-blog"></e-heading>
      <e-button configuration-id="pm-footer-link-blog"></e-button>
      <e-button configuration-id="pm-footer-link-home"></e-button>
    </e-flexbox>
  </e-flexbox>
  <e-paragraph configuration-id="pm-footer-copy"></e-paragraph>
</e-flexbox>
""".strip()


def footer_config() -> tuple[dict, dict, dict]:
    element_config = {
        "pm-footer": {"tag": "footer"},
        "pm-footer-inner": {},
        "pm-footer-brand": {},
        "pm-footer-logo": {
            "image": {"src": {"id": LOGO_ID}, "size": "medium"},
            "link": _page_link(PAGE_IDS["home"], "پاکن منتال"),
        },
        "pm-footer-tagline": {
            "paragraph": "فروشگاه بازی فکری، بردگیم، پازل و سرگرمی رومیزی.",
            "tag": "p",
        },
        "pm-footer-name": {"tag": "h3", "title": "پاکن منتال"},
        "pm-footer-col-shop": {},
        "pm-footer-title-shop": {"tag": "h4", "title": "فروشگاه"},
        "pm-footer-link-shop": {"text": "همه محصولات", "link": _page_link(PAGE_IDS["shop"], "فروشگاه")},
        "pm-footer-link-cart": {"text": "سبد خرید", "link": _page_link(PAGE_IDS["cart"], "سبد خرید")},
        "pm-footer-col-blog": {},
        "pm-footer-title-blog": {"tag": "h4", "title": "مجله"},
        "pm-footer-link-blog": {"text": "مقالات", "link": _page_link(PAGE_IDS["blog"], "مجله")},
        "pm-footer-link-home": {"text": "صفحه اصلی", "link": _page_link(PAGE_IDS["home"], "خانه")},
        "pm-footer-copy": {
            "paragraph": "© پاکن منتال — سرگرمی رومیزی برای هر سلیقه",
            "tag": "p",
        },
    }
    classes = {
        "pm-footer": ["PM Footer"],
        "pm-footer-link-shop": ["PM Footer Link"],
        "pm-footer-link-cart": ["PM Footer Link"],
        "pm-footer-link-blog": ["PM Footer Link"],
        "pm-footer-link-home": ["PM Footer Link"],
    }
    style = {
        "pm-footer": "flex-direction: column; align-items: stretch; gap: 1.5rem;",
        "pm-footer-inner": (
            "width: 100%; max-width: min(1200px, 92vw); margin-inline: auto; "
            "display: grid; grid-template-columns: 1.5fr 1fr 1fr; gap: 2rem; padding: 0 4vw;"
        ),
        "pm-footer-brand": "flex-direction: column; align-items: flex-start; gap: 0.5rem;",
        "pm-footer-col-shop": "flex-direction: column; align-items: flex-start; gap: 0.45rem;",
        "pm-footer-col-blog": "flex-direction: column; align-items: flex-start; gap: 0.45rem;",
        "pm-footer-title-shop": "color: #e5d4bc; font-size: 0.82rem;",
        "pm-footer-title-blog": "color: #e5d4bc; font-size: 0.82rem;",
        "pm-footer-tagline": "color: #faf7f2; opacity: 0.9;",
        "pm-footer-name": "color: #e5d4bc; font-size: 1rem;",
        "pm-footer-copy": "text-align: center; color: #e5d4bc; width: 100%;",
        "pm-footer-logo": "max-width: 72px;",
    }
    return element_config, style, classes


def _merge_configs(*parts: tuple[dict, dict, dict]) -> tuple[dict, dict, dict]:
    ec: dict = {}
    st: dict = {}
    cl: dict = {}
    for a, b, c in parts:
        ec.update(a)
        st.update(b)
        cl.update(c)
    return ec, st, cl


def home_main_xml() -> str:
    cats = "".join(
        f'<e-flexbox configuration-id="pm-cat-{i}"><e-paragraph configuration-id="pm-cat-label-{i}"></e-paragraph></e-flexbox>'
        for i in range(6)
    )
    labels = ["بردگیم", "پازل", "اکشن فیگور", "کارتی", "بازی منتالی", "لوازم جانبی"]
    return f"""
<e-flexbox configuration-id="pm-root">
{header_xml("home")}
<e-flexbox configuration-id="pm-hero">
  <e-flexbox configuration-id="pm-hero-inner">
    <e-heading configuration-id="pm-hero-title"></e-heading>
    <e-paragraph configuration-id="pm-hero-lead"></e-paragraph>
    <e-button configuration-id="pm-hero-cta"></e-button>
  </e-flexbox>
</e-flexbox>
<e-flexbox configuration-id="pm-cats-section">
  <e-heading configuration-id="pm-cats-title"></e-heading>
  <e-flexbox configuration-id="pm-cats-grid">
    {cats}
  </e-flexbox>
</e-flexbox>
<e-flexbox configuration-id="pm-row-trend">
  <e-heading configuration-id="pm-row-trend-title"></e-heading>
  <e-flexbox configuration-id="pm-row-trend-grid">
    <e-flexbox configuration-id="pm-card-1"><e-paragraph configuration-id="pm-card-1-t"></e-paragraph></e-flexbox>
    <e-flexbox configuration-id="pm-card-2"><e-paragraph configuration-id="pm-card-2-t"></e-paragraph></e-flexbox>
    <e-flexbox configuration-id="pm-card-3"><e-paragraph configuration-id="pm-card-3-t"></e-paragraph></e-flexbox>
    <e-flexbox configuration-id="pm-card-4"><e-paragraph configuration-id="pm-card-4-t"></e-paragraph></e-flexbox>
  </e-flexbox>
</e-flexbox>
{footer_xml()}
</e-flexbox>
""".strip()


def home_main_config() -> tuple[dict, dict, dict]:
    h = header_config("home")
    f = footer_config()
    ec, st, cl = _merge_configs(h, f)
    ec.update(
        {
            "pm-root": {},
            "pm-hero": {},
            "pm-hero-inner": {},
            "pm-hero-title": {
                "tag": "h1",
                "title": "سرگرمی رومیزی برای هر سلیقه",
            },
            "pm-hero-lead": {
                "paragraph": "بازی فکری، پازل، اکشن فیگور و بازی‌های منتالی — یک فروشگاه برای لحظه‌های دورهم.",
                "tag": "p",
            },
            "pm-hero-cta": {
                "text": "مشاهده فروشگاه",
                "link": _page_link(PAGE_IDS["shop"], "فروشگاه"),
            },
            "pm-cats-section": {},
            "pm-cats-title": {"tag": "h2", "title": "دسته‌بندی فروشگاه"},
            "pm-cats-grid": {},
            "pm-row-trend": {},
            "pm-row-trend-title": {"tag": "h2", "title": "پرفروش‌ها و ترند"},
            "pm-card-1-t": {"paragraph": "محصول نمونه ۱", "tag": "p"},
            "pm-card-2-t": {"paragraph": "محصول نمونه ۲", "tag": "p"},
            "pm-card-3-t": {"paragraph": "محصول نمونه ۳", "tag": "p"},
            "pm-card-4-t": {"paragraph": "محصول نمونه ۴", "tag": "p"},
        }
    )
    labels = ["بردگیم", "پازل", "اکشن فیگور", "کارتی", "بازی منتالی", "لوازم جانبی"]
    for i, label in enumerate(labels):
        ec[f"pm-cat-label-{i}"] = {"paragraph": label, "tag": "p", "link": _page_link(PAGE_IDS["shop"], "فروشگاه")}
        cl[f"pm-cat-{i}"] = ["PM Cat Tile"]
        st[f"pm-cat-{i}"] = "cursor: pointer;"
    cl.update(
        {
            "pm-root": [],
            "pm-hero": ["PM Hero"],
            "pm-hero-cta": ["PM Btn Primary"],
            "pm-cats-section": ["PM Section"],
            "pm-cats-grid": ["PM Grid 6"],
            "pm-row-trend": ["PM Section"],
            "pm-row-trend-grid": ["PM Grid 4"],
            "pm-card-1": ["PM Product Card"],
            "pm-card-2": ["PM Product Card"],
            "pm-card-3": ["PM Product Card"],
            "pm-card-4": ["PM Product Card"],
        }
    )
    st.update(
        {
            "pm-root": "width: 100%; flex-direction: column; align-items: stretch; gap: 0; padding: 0;",
            "pm-hero-inner": "width: min(1200px, 92vw); margin-inline: auto; flex-direction: column; align-items: flex-start; gap: 1rem;",
            "pm-cats-section": "flex-direction: column; gap: 1rem; align-items: stretch;",
            "pm-row-trend": "flex-direction: column; gap: 1rem; align-items: stretch;",
            "pm-hero-title": "color: #faf7f2; font-size: clamp(1.75rem, 4vw, 2.75rem);",
            "pm-hero-lead": "color: color-mix(in srgb, #faf7f2 88%, transparent); max-width: 42ch;",
        }
    )
    return ec, st, cl


def shop_main_xml() -> str:
    return f"""
<e-flexbox configuration-id="pm-root">
{header_xml("shop")}
<e-flexbox configuration-id="pm-shop-head">
  <e-heading configuration-id="pm-shop-title"></e-heading>
  <e-paragraph configuration-id="pm-shop-lead"></e-paragraph>
</e-flexbox>
<e-flexbox configuration-id="pm-shop-layout">
  <e-flexbox configuration-id="pm-shop-filters">
    <e-heading configuration-id="pm-filter-title"></e-heading>
    <e-accordion configuration-id="pm-filter-acc">
      <e-accordion-item configuration-id="pm-filter-price">
        <e-accordion-item-header configuration-id="pm-filter-price-h">
          <e-accordion-item-title configuration-id="pm-filter-price-t">
            <e-paragraph configuration-id="pm-filter-price-label"></e-paragraph>
          </e-accordion-item-title>
        </e-accordion-item-header>
        <e-accordion-item-content configuration-id="pm-filter-price-c">
          <e-paragraph configuration-id="pm-filter-price-body"></e-paragraph>
        </e-accordion-item-content>
      </e-accordion-item>
    </e-accordion>
  </e-flexbox>
  <e-flexbox configuration-id="pm-shop-products">
    <e-collection-loop configuration-id="pm-product-loop"></e-collection-loop>
    <e-flexbox configuration-id="pm-shop-grid">
      <e-flexbox configuration-id="pm-sp-1"><e-paragraph configuration-id="pm-sp-1-t"></e-paragraph></e-flexbox>
      <e-flexbox configuration-id="pm-sp-2"><e-paragraph configuration-id="pm-sp-2-t"></e-paragraph></e-flexbox>
      <e-flexbox configuration-id="pm-sp-3"><e-paragraph configuration-id="pm-sp-3-t"></e-paragraph></e-flexbox>
      <e-flexbox configuration-id="pm-sp-4"><e-paragraph configuration-id="pm-sp-4-t"></e-paragraph></e-flexbox>
    </e-flexbox>
  </e-flexbox>
</e-flexbox>
{footer_xml()}
</e-flexbox>
""".strip()


def shop_main_config() -> tuple[dict, dict, dict]:
    h = header_config("shop")
    f = footer_config()
    ec, st, cl = _merge_configs(h, f)
    ec.update(
        {
            "pm-shop-head": {},
            "pm-shop-title": {"tag": "h1", "title": "فروشگاه پاکن منتال"},
            "pm-shop-lead": {
                "paragraph": "بردگیم، پازل، فیگور و بازی‌های منتالی — فیلترها را در المنتور ویرایش کنید.",
                "tag": "p",
            },
            "pm-shop-layout": {},
            "pm-shop-filters": {},
            "pm-filter-title": {"tag": "h3", "title": "فیلتر محصولات"},
            "pm-filter-acc": {},
            "pm-filter-price-label": {"paragraph": "محدوده قیمت", "tag": "p"},
            "pm-filter-price-body": {
                "paragraph": "حداقل و حداکثر قیمت را پس از فعال‌سازی ووکامرس به ویجت فرم وصل کنید.",
                "tag": "p",
            },
            "pm-shop-products": {},
            "pm-product-loop": {},
            "pm-shop-grid": {},
            "pm-sp-1-t": {"paragraph": "آرشیو محصول — نمونه ۱", "tag": "p"},
            "pm-sp-2-t": {"paragraph": "آرشیو محصول — نمونه ۲", "tag": "p"},
            "pm-sp-3-t": {"paragraph": "آرشیو محصول — نمونه ۳", "tag": "p"},
            "pm-sp-4-t": {"paragraph": "آرشیو محصول — نمونه ۴", "tag": "p"},
        }
    )
    cl.update(
        {
            "pm-shop-head": ["PM Section"],
            "pm-shop-layout": ["PM Section"],
            "pm-shop-filters": ["PM Product Card"],
            "pm-shop-grid": ["PM Grid 4"],
            "pm-sp-1": ["PM Product Card"],
            "pm-sp-2": ["PM Product Card"],
            "pm-sp-3": ["PM Product Card"],
            "pm-sp-4": ["PM Product Card"],
        }
    )
    st.update(
        {
            "pm-root": "width:100%;flex-direction:column;gap:0;padding:0;",
            "pm-shop-head": "flex-direction:column;align-items:center;text-align:center;gap:0.5rem;",
            "pm-shop-layout": (
                "width:min(1200px,92vw);margin-inline:auto;display:grid;"
                "grid-template-columns:minmax(220px,280px) 1fr;gap:1.5rem;align-items:start;"
            ),
            "pm-shop-filters": "flex-direction:column;gap:0.75rem;padding:1rem;",
            "pm-shop-products": "flex-direction:column;gap:1rem;",
            "pm-shop-grid": "width:100%;",
        }
    )
    return ec, st, cl


def product_single_xml() -> str:
    return f"""
<e-flexbox configuration-id="pm-root">
{header_xml("shop")}
<e-flexbox configuration-id="pm-single">
  <e-flexbox configuration-id="pm-single-gallery">
    <e-image configuration-id="pm-single-image"></e-image>
  </e-flexbox>
  <e-flexbox configuration-id="pm-single-summary">
    <e-heading configuration-id="pm-single-title"></e-heading>
    <e-paragraph configuration-id="pm-single-price"></e-paragraph>
    <e-paragraph configuration-id="pm-single-desc"></e-paragraph>
    <e-button configuration-id="pm-single-atc"></e-button>
  </e-flexbox>
</e-flexbox>
{footer_xml()}
</e-flexbox>
""".strip()


def product_single_config() -> tuple[dict, dict, dict]:
    h = header_config("shop")
    f = footer_config()
    ec, st, cl = _merge_configs(h, f)
    ec.update(
        {
            "pm-single": {},
            "pm-single-gallery": {},
            "pm-single-image": {
                "image": {"src": {"id": LOGO_ID}, "size": "large"},
            },
            "pm-single-summary": {},
            "pm-single-title": {"tag": "h1", "title": "نمونه محصول — بردگیم"},
            "pm-single-price": {"paragraph": "۱٬۲۵۰٬۰۰۰ تومان", "tag": "p"},
            "pm-single-desc": {
                "paragraph": "این قالب تک‌محصول در المنتور قابل ویرایش است. پس از افزودن محصول ووکامرس، "
                "عنوان، قیمت و دکمه را به تگ‌های داینامیک وصل کنید.",
                "tag": "p",
            },
            "pm-single-atc": {
                "text": "افزودن به سبد خرید",
                "link": _page_link(PAGE_IDS["cart"], "سبد خرید"),
            },
        }
    )
    cl.update({"pm-single": ["PM Section"], "pm-single-atc": ["PM Btn Primary"], "pm-single-gallery": ["PM Product Card"]})
    st.update(
        {
            "pm-root": "width:100%;flex-direction:column;gap:0;",
            "pm-single": (
                "width:min(1200px,92vw);margin-inline:auto;display:grid;"
                "grid-template-columns:1fr 1fr;gap:2rem;padding:2rem 0;"
            ),
            "pm-single-summary": "flex-direction:column;align-items:flex-start;gap:0.75rem;",
            "pm-single-price": "font-size:1.25rem;font-weight:800;color:#2a5f52;",
        }
    )
    return ec, st, cl


def blog_main_xml() -> str:
    return f"""
<e-flexbox configuration-id="pm-root">
{header_xml("blog")}
<e-flexbox configuration-id="pm-blog-head">
  <e-heading configuration-id="pm-blog-title"></e-heading>
</e-flexbox>
<e-flexbox configuration-id="pm-blog-grid">
  <e-flexbox configuration-id="pm-post-1"><e-heading configuration-id="pm-post-1-t"></e-heading></e-flexbox>
  <e-flexbox configuration-id="pm-post-2"><e-heading configuration-id="pm-post-2-t"></e-heading></e-flexbox>
  <e-flexbox configuration-id="pm-post-3"><e-heading configuration-id="pm-post-3-t"></e-heading></e-flexbox>
</e-flexbox>
{footer_xml()}
</e-flexbox>
""".strip()


def blog_main_config() -> tuple[dict, dict, dict]:
    h = header_config("blog")
    f = footer_config()
    ec, st, cl = _merge_configs(h, f)
    ec.update(
        {
            "pm-blog-head": {},
            "pm-blog-title": {"tag": "h1", "title": "مجله پاکن منتال"},
            "pm-blog-grid": {},
            "pm-post-1-t": {"tag": "h3", "title": "مقاله نمونه ۱"},
            "pm-post-2-t": {"tag": "h3", "title": "مقاله نمونه ۲"},
            "pm-post-3-t": {"tag": "h3", "title": "مقاله نمونه ۳"},
        }
    )
    cl.update(
        {
            "pm-blog-head": ["PM Section"],
            "pm-blog-grid": ["PM Grid 4"],
            "pm-post-1": ["PM Product Card"],
            "pm-post-2": ["PM Product Card"],
            "pm-post-3": ["PM Product Card"],
        }
    )
    st.update(
        {
            "pm-root": "width:100%;flex-direction:column;",
            "pm-blog-head": "align-items:center;text-align:center;",
        }
    )
    return ec, st, cl


def cart_main_xml() -> str:
    return f"""
<e-flexbox configuration-id="pm-root">
{header_xml("shop")}
<e-flexbox configuration-id="pm-cart">
  <e-heading configuration-id="pm-cart-title"></e-heading>
  <e-paragraph configuration-id="pm-cart-lead"></e-paragraph>
</e-flexbox>
{footer_xml()}
</e-flexbox>
""".strip()


def cart_main_config() -> tuple[dict, dict, dict]:
    h = header_config("shop")
    f = footer_config()
    ec, st, cl = _merge_configs(h, f)
    ec.update(
        {
            "pm-cart": {},
            "pm-cart-title": {"tag": "h1", "title": "سبد خرید"},
            "pm-cart-lead": {
                "paragraph": "ویجت سبد ووکامرس را از پنل المنتور اینجا بکشید و رها کنید.",
                "tag": "p",
            },
        }
    )
    cl.update({"pm-cart": ["PM Section"]})
    st.update(
        {
            "pm-root": "width:100%;flex-direction:column;",
            "pm-cart": "align-items:center;text-align:center;gap:0.75rem;",
        }
    )
    return ec, st, cl
