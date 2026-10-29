"""Elementor kit: global colors, classes, and page chrome CSS."""
from __future__ import annotations

from .elementor_client import ElementorMcp
from .sync_elementor import sync as sync_kit_tokens

PAGE_CHROME_CSS = """
@import url("https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap");

body,
.elementor-widget-container,
.elementor-button {
  font-family: "Vazirmatn", system-ui, sans-serif !important;
}

body .site-header,
body .site-footer,
body .page-header { display: none !important; }
body #content.site-main,
body .site-main { max-width: none; padding: 0; margin: 0; }
body { background: #fafaf9; }

/* —— Header: hero merge + blur scroll —— */
.pm-header {
  position: sticky;
  top: 0;
  z-index: 200;
  width: 100%;
  padding: 0.85rem 0 0.45rem;
  background: transparent;
  transition: padding 0.45s ease, background 0.45s ease, backdrop-filter 0.45s ease, box-shadow 0.45s ease;
}

html.pm-site--has-hero .pm-header:not(.is-scrolled) {
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  backdrop-filter: blur(10px);
}

html.pm-site--has-hero .pm-header.is-scrolled {
  position: fixed;
  padding: 0.35rem 0 0.25rem;
  background: color-mix(in srgb, #fafaf9 78%, transparent);
  backdrop-filter: blur(20px);
  box-shadow: 0 10px 40px color-mix(in srgb, #1e4339 6%, transparent);
}

.pm-header.is-scrolled {
  padding: 0.35rem 0 0.25rem;
  background: color-mix(in srgb, #fafaf9 82%, transparent);
  backdrop-filter: blur(18px);
}

.pm-header-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem 1rem;
  width: 100%;
  max-width: min(1200px, 92vw);
  margin-inline: auto;
  padding: 0.6rem 1rem;
  border-radius: 32px;
  background: color-mix(in srgb, #faf7f2 85%, #fafaf9);
  backdrop-filter: blur(14px);
  box-shadow: 0 14px 40px color-mix(in srgb, #1e4339 8%, transparent);
  transition: padding 0.45s ease, background 0.45s ease, backdrop-filter 0.45s ease, box-shadow 0.45s ease, transform 0.45s ease;
}

html.pm-site--has-hero:not(.pm-scrolled) .pm-header-bar {
  background: color-mix(in srgb, #faf7f2 12%, transparent);
  backdrop-filter: blur(12px);
  box-shadow: 0 16px 48px color-mix(in srgb, #1e4339 12%, transparent);
}

.pm-header.is-scrolled .pm-header-bar {
  padding: 0.4rem 0.85rem;
  background: color-mix(in srgb, #faf7f2 70%, transparent);
  backdrop-filter: blur(22px) saturate(1.12);
  transform: scale(0.985);
}

.pm-header-tools {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.5rem;
  flex: 1;
  min-width: 0;
}

html.pm-site--has-hero:not(.pm-scrolled) .pm-nav-seg .elementor-button {
  background: color-mix(in srgb, #faf7f2 12%, transparent) !important;
  color: #faf7f2 !important;
}

html.pm-site--has-hero:not(.pm-scrolled) .pm-nav-seg .elementor-button:hover {
  background: color-mix(in srgb, #faf7f2 22%, transparent) !important;
}

.pm-hero {
  padding-top: clamp(5rem, 12vw, 7rem) !important;
}

/* —— Ajax search —— */
.pm-search {
  position: relative;
  flex: 1;
  min-width: 0;
  max-width: 280px;
}

.pm-search__icon {
  position: absolute;
  right: 0.85rem;
  top: 50%;
  transform: translateY(-50%);
  color: #2a5f52;
  opacity: 0.55;
  pointer-events: none;
}

.pm-search__label {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
}

.pm-search__input {
  width: 100%;
  padding: 0.62rem 2.45rem 0.62rem 1.05rem;
  border-radius: 999px;
  border: none;
  background: color-mix(in srgb, #faf7f2 55%, #fafaf9);
  color: #1e4339;
  font-family: inherit;
  font-size: 0.82rem;
  box-shadow: 0 6px 22px color-mix(in srgb, #1e4339 7%, transparent),
    inset 0 0 0 1px color-mix(in srgb, #4aa88e 14%, transparent);
  transition: box-shadow 0.25s ease, background 0.25s ease;
}

html.pm-site--has-hero:not(.pm-scrolled) .pm-search__input {
  background: color-mix(in srgb, #faf7f2 18%, transparent);
  color: #faf7f2;
  box-shadow: 0 8px 28px color-mix(in srgb, #1e4339 15%, transparent),
    inset 0 0 0 1px color-mix(in srgb, #faf7f2 25%, transparent);
}

html.pm-site--has-hero:not(.pm-scrolled) .pm-search__input::placeholder {
  color: color-mix(in srgb, #faf7f2 65%, transparent);
}

html.pm-site--has-hero:not(.pm-scrolled) .pm-search__icon {
  color: #faf7f2;
  opacity: 0.75;
}

.pm-search__input:focus {
  outline: none;
  background: color-mix(in srgb, #faf7f2 75%, #fafaf9);
  box-shadow: 0 8px 28px color-mix(in srgb, #4aa88e 18%, transparent),
    inset 0 0 0 1px color-mix(in srgb, #4aa88e 28%, transparent);
}

.pm-search__results {
  position: absolute;
  right: 0;
  left: 0;
  top: calc(100% + 0.35rem);
  z-index: 300;
  padding: 0.45rem;
  border-radius: 22px;
  background: color-mix(in srgb, #faf7f2 92%, #fafaf9);
  backdrop-filter: blur(16px);
  box-shadow: 0 18px 48px color-mix(in srgb, #1e4339 12%, transparent);
}

.pm-search__list {
  margin: 0;
  padding: 0;
  list-style: none;
}

.pm-search__list a {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.55rem 0.65rem;
  border-radius: 14px;
  color: #1e4339;
  font-size: 0.85rem;
}

.pm-search__list a:hover {
  background: #e8f6f1;
}

.pm-search__price {
  color: #2a5f52;
  font-weight: 700;
  white-space: nowrap;
}

.pm-search__empty {
  margin: 0.35rem 0.65rem;
  font-size: 0.85rem;
  color: #2a5f52;
}

.pm-search__all {
  display: block;
  margin: 0.35rem 0.65rem 0.15rem;
  font-size: 0.8rem;
  font-weight: 700;
  color: #4aa88e;
}

@media (max-width: 900px) {
  .pm-search { max-width: 180px; }
  .pm-header-bar { padding: 0.5rem 0.65rem; gap: 0.45rem; }
}
"""

GLOBAL_CLASSES: list[tuple[str, str]] = [
    (
        "pm-header",
        "position: sticky; top: 0; z-index: 200; width: 100%; padding: 0.75rem 0 0.35rem; background: transparent;",
    ),
    (
        "pm-header-bar",
        "display: flex; align-items: center; justify-content: space-between; gap: 1rem; width: 100%; "
        "max-width: min(1200px, 92vw); margin-inline: auto; padding: 0.55rem 1rem; border-radius: 32px; "
        "background: color-mix(in srgb, #faf7f2 85%, #fafaf9); backdrop-filter: blur(14px); "
        "box-shadow: 0 14px 40px color-mix(in srgb, #1e4339 8%, transparent);",
    ),
    (
        "pm-header-tools",
        "display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem; flex: 1; min-width: 0;",
    ),
    (
        "pm-nav-seg",
        "display: inline-flex; gap: 0.15rem; padding: 0.2rem; border-radius: 999px; "
        "background: color-mix(in srgb, #e8f6f1 65%, #fafaf9);",
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
    (
        "PM Header Bar",
        "display: flex; align-items: center; justify-content: space-between; gap: 1rem; width: 100%; "
        "max-width: min(1200px, 92vw); margin-inline: auto; padding: 0.55rem 1rem; border-radius: 32px; "
        "background: color-mix(in srgb, #faf7f2 85%, #fafaf9); backdrop-filter: blur(14px); "
        "box-shadow: 0 14px 40px color-mix(in srgb, #1e4339 8%, transparent);",
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
