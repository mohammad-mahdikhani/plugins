"""Elementor kit: global colors, classes, and page chrome CSS."""
from __future__ import annotations

from .elementor_client import ElementorMcp
from .sync_elementor import sync as sync_kit_tokens

PAGE_CHROME_CSS = """
body .site-header,
body .site-footer,
body .page-header { display: none !important; }
body #content.site-main,
body .site-main { max-width: none; padding: 0; margin: 0; }
body { background: #fafaf9; font-family: "Vazirmatn", system-ui, sans-serif; }
"""

GLOBAL_CLASSES: list[tuple[str, str]] = [
    (
        "pm-header",
        "position: sticky; top: 0; z-index: 100; width: 100%; padding: 0.75rem 0 0.35rem; "
        "background: linear-gradient(180deg, color-mix(in srgb, #fafaf9 90%, transparent), transparent);",
    ),
    (
        "PM Header Bar",
        "display: flex; align-items: center; justify-content: space-between; gap: 1rem; width: 100%; "
        "max-width: min(1200px, 92vw); margin-inline: auto; padding: 0.55rem 1rem; border-radius: 32px; "
        "background: color-mix(in srgb, #faf7f2 85%, #fafaf9); backdrop-filter: blur(14px); "
        "box-shadow: 0 14px 40px color-mix(in srgb, #1e4339 8%, transparent);",
    ),
    (
        "PM Nav Seg",
        "display: inline-flex; gap: 0.15rem; padding: 0.2rem; border-radius: 999px; "
        "background: color-mix(in srgb, #e8f6f1 65%, #fafaf9);",
    ),
    (
        "PM Nav Link",
        "padding: 0.42rem 0.85rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600; "
        "color: #2a5f52; background: transparent;",
    ),
    (
        "PM Nav Link Active",
        "padding: 0.42rem 0.85rem; border-radius: 999px; font-size: 0.8rem; font-weight: 700; "
        "color: #faf7f2; background: linear-gradient(135deg, #4aa88e, #2a5f52); "
        "box-shadow: 0 6px 28px color-mix(in srgb, #1e4339 5%, transparent);",
    ),
    (
        "PM Btn Primary",
        "background: linear-gradient(135deg, #4aa88e, #2a5f52); color: #faf7f2; border-radius: 999px; "
        "padding: 0.65rem 1.35rem; font-weight: 700; font-size: 0.9rem;",
    ),
    (
        "PM Btn Ghost",
        "background: color-mix(in srgb, #faf7f2 70%, #fafaf9); color: #2a5f52; border-radius: 999px; "
        "padding: 0.55rem 1rem; font-weight: 600;",
    ),
    (
        "PM Section",
        "width: 100%; max-width: min(1200px, 92vw); margin-inline: auto; padding: 2.5rem 0;",
    ),
    (
        "PM Hero",
        "width: 100vw; margin-inline: calc(50% - 50vw); padding: clamp(4rem, 11vw, 6rem) 0 3rem; "
        "background: linear-gradient(135deg, #1e4339 0%, #4aa88e 55%, #e8f6f1 100%); color: #faf7f2;",
    ),
    (
        "PM Footer",
        "width: 100vw; margin-inline: calc(50% - 50vw); margin-top: 2rem; padding: 3rem 0 1.25rem; "
        "color: #faf7f2; background: linear-gradient(165deg, #1e4339, #2a5f52 55%, #1e4339);",
    ),
    (
        "PM Footer Link",
        "color: #faf7f2; font-size: 0.88rem; font-weight: 500; opacity: 0.95;",
    ),
    (
        "PM Cat Tile",
        "display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; "
        "min-height: 118px; padding: 1rem 0.5rem; border-radius: 32px; "
        "background: linear-gradient(165deg, #faf7f2, #e8f6f1); "
        "box-shadow: 0 6px 28px color-mix(in srgb, #1e4339 5%, transparent); font-weight: 700; color: #1e4339;",
    ),
    (
        "PM Grid 6",
        "display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 0.65rem; width: 100%;",
    ),
    (
        "PM Grid 4",
        "display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.85rem; width: 100%;",
    ),
    (
        "PM Product Card",
        "padding: 0.85rem; border-radius: 22px; background: #faf7f2; "
        "box-shadow: 0 6px 28px color-mix(in srgb, #1e4339 5%, transparent);",
    ),
]


def sync_global_classes(client: ElementorMcp) -> None:
    ops = [{"action": "create", "label": label, "css": css} for label, css in GLOBAL_CLASSES]
    result = client.call("elementor-manage-classes", {"operations": ops})
    results = result.get("results") or []
    if any(r.get("status") == "ok" for r in results):
        return
    updates = [{"action": "update", "label": label, "css": css} for label, css in GLOBAL_CLASSES]
    client.call("elementor-manage-classes", {"operations": updates[:50]})


def sync_all(client: ElementorMcp | None = None) -> None:
    sync_kit_tokens()
    client = client or ElementorMcp()
    sync_global_classes(client)
