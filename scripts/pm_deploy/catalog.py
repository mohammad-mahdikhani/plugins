"""Demo product catalog for AJAX search until WooCommerce is live."""
from __future__ import annotations

import json

SITE = "https://www.pakanmental.com"
SHOP = f"{SITE}/%d9%81%d8%b1%d9%88%d8%b4%da%af%d8%a7%d9%87/"
PRODUCT_DEMO = f"{SITE}/%d9%86%d9%85%d9%88%d9%86%d9%87-%d9%85%d8%ad%d8%b5%d9%88%d9%84/"

PRODUCT_CATALOG = [
    {"title": "بردگیم نمونه — شب دورهم", "url": PRODUCT_DEMO, "price": "۲٬۴۵۰٬۰۰۰ تومان"},
    {"title": "پازل منظره ۱۰۰۰ تکه", "url": PRODUCT_DEMO, "price": "۱٬۱۲۰٬۰۰۰ تومان"},
    {"title": "اکشن فیگور کلکسیونی", "url": PRODUCT_DEMO, "price": "۳٬۸۰۰٬۰۰۰ تومان"},
    {"title": "بازی کارتی مهمانی", "url": PRODUCT_DEMO, "price": "۸۹۰٬۰۰۰ تومان"},
    {"title": "بردگیم استراتژیک", "url": PRODUCT_DEMO, "price": "۲٬۱۰۰٬۰۰۰ تومان"},
    {"title": "پازل کودک ۵۰۰ تکه", "url": PRODUCT_DEMO, "price": "۶۵۰٬۰۰۰ تومان"},
    {"title": "بازی منتالی تمرکز", "url": PRODUCT_DEMO, "price": "۷۵۰٬۰۰۰ تومان"},
    {"title": "لوازم جانبی مهره", "url": SHOP, "price": "۲۵۰٬۰۰۰ تومان"},
]


def catalog_script() -> str:
    payload = json.dumps(PRODUCT_CATALOG, ensure_ascii=False)
    return (
        f'<script>window.PM_PRODUCT_CATALOG={payload};'
        f'window.PM_SHOP_URL="{SHOP}";'
        f'window.PM_SITE_URL="https://www.pakanmental.com";</script>'
    )
