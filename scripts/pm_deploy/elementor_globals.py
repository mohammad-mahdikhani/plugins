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
"""


def sync_global_classes(client: ElementorMcp) -> None:
    """Optional: keep kit classes empty/minimal to avoid clashing with pm.css."""
    return


def sync_all(client: ElementorMcp | None = None) -> None:
    sync_kit_tokens()
    client = client or ElementorMcp()
    sync_global_classes(client)
