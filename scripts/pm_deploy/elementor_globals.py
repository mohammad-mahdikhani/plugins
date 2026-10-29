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
