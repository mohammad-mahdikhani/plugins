"""Elementor kit: global colors and minimal page chrome (PM styles live in HTML widget)."""
from __future__ import annotations

from .elementor_client import ElementorMcp
from .sync_elementor import sync as sync_kit_tokens

PAGE_CHROME_CSS = """
body .site-header,
body .site-footer,
body .page-header { display: none !important; }
body #content.site-main,
body .site-main { max-width: none; padding: 0; margin: 0; }
body.single-product .site-header,
body.single-product .site-footer,
body.single-product .page-header { display: none !important; }
body.single-product .site-main { max-width: none; padding: 0; }
body.single-product .product {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  max-width: min(1200px, 92vw);
  margin: 1.5rem auto 2rem;
  padding: 0 1rem;
  align-items: start;
}
body.single-product .product .woocommerce-product-gallery { margin: 0; }
body.single-product .product .summary { float: none !important; width: auto !important; }
body.single-product .product_title { font-family: Vazirmatn, sans-serif; font-size: clamp(1.25rem, 2.5vw, 1.75rem); }
body.single-product .price { font-size: 1.35rem; font-weight: 800; }
@media (max-width: 768px) {
  body.single-product .product { grid-template-columns: 1fr; }
}
.elementor-html-widget,
.elementor-widget-html,
.elementor-widget-wrap,
.elementor-element,
.e-con,
.e-con-inner { overflow: visible !important; }
.elementor-html-widget { width: 100%; }
.pm-elementor-chrome,
.pm-elementor-main { width: 100%; }
:root {
  --pm-brand-green: #4aa88e;
  --e-global-color-primary: #4aa88e;
  --e-global-color-accent: #4aa88e;
  --woocommerce: #4aa88e;
  --wp--preset--color--primary: #4aa88e;
}
body.post-type-archive-product {
  font-family: Vazirmatn, Tahoma, sans-serif;
  background: #fafaf9;
  color: #1e4339;
}
body.post-type-archive-product .site-header {
  background: #faf7f2;
  box-shadow: 0 6px 28px rgba(30,67,57,0.06);
  padding: 0.6rem 1rem;
}
body.post-type-archive-product .site-header .custom-logo {
  max-height: 64px;
  width: auto;
}
body.post-type-archive-product .site-description { display: none !important; }
body.post-type-archive-product .site-main,
body.post-type-archive-product #content.site-main {
  max-width: min(1200px, 92vw);
  margin: 0 auto;
  padding: 1.25rem 0 3rem;
  float: none;
}
body.post-type-archive-product .woocommerce-breadcrumb { display: none; }
body.post-type-archive-product .woocommerce-products-header__title {
  font-size: clamp(1.6rem, 3vw, 2.2rem);
  font-weight: 800;
  color: #1e4339;
  text-align: right;
}
body.post-type-archive-product .woocommerce-result-count,
body.post-type-archive-product .woocommerce-ordering {
  margin: 0.5rem 0 1rem;
}
body.post-type-archive-product ul.products {
  display: grid !important;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 0.9rem;
  list-style: none;
  margin: 0;
  padding: 0;
  float: none !important;
  width: 100% !important;
}
body.post-type-archive-product ul.products li.product {
  width: auto !important;
  float: none !important;
  margin: 0 !important;
  background: #faf7f2;
  border-radius: 22px;
  padding: 0.65rem;
  box-shadow: 0 6px 28px rgba(30,67,57,0.05);
}
body.post-type-archive-product ul.products li.product img {
  width: 100%;
  aspect-ratio: 1;
  object-fit: cover;
  border-radius: 12px;
}
body.post-type-archive-product ul.products li.product .woocommerce-loop-product__title,
body.post-type-archive-product ul.products li.product h2 {
  color: #1e4339 !important;
  font-size: 0.86rem !important;
  font-weight: 600;
  line-height: 1.4;
}
body.post-type-archive-product ul.products li.product a.button,
body.post-type-archive-product ul.products li.product .button {
  background: #4aa88e !important;
  color: #faf7f2 !important;
  border: 0 !important;
  border-radius: 999px !important;
  width: 100%;
  text-align: center;
}
@media (max-width: 640px) {
  body.post-type-archive-product ul.products {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
"""


def sync_global_classes(client: ElementorMcp) -> None:
    """Optional: keep kit classes empty/minimal to avoid clashing with pm.css."""
    return


def sync_all(client: ElementorMcp | None = None) -> None:
    sync_kit_tokens()
    client = client or ElementorMcp()
    sync_global_classes(client)
