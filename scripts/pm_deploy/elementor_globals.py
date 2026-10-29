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
.elementor-html-widget { width: 100%; }
"""


def sync_global_classes(client: ElementorMcp) -> None:
    """Optional: keep kit classes empty/minimal to avoid clashing with pm.css."""
    return


def sync_all(client: ElementorMcp | None = None) -> None:
    sync_kit_tokens()
    client = client or ElementorMcp()
    sync_global_classes(client)
