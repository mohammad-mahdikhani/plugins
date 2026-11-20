"""Build Elementor documents that wrap the original PM HTML (editable HTML widget)."""
from __future__ import annotations

import json
import secrets

from .builder import LOGO_WEBP, req, BASE

HEADER_TEMPLATE_ID = 124
LOGO_WIDGET_CLASS = "pm-header-logo-editable"
LOGO_WIDGET_ID = "pmlogo01"
DEFAULT_LOGO_ID = 9


def _element_id() -> str:
    return secrets.token_hex(4)


def _container_settings() -> dict:
    return {
        "content_width": "full",
        "padding": {
            "unit": "px",
            "top": "0",
            "right": "0",
            "bottom": "0",
            "left": "0",
            "isLinked": True,
        },
        "flex_gap": {"unit": "px", "size": 0, "column": "0", "row": "0"},
    }


def _html_widget(html: str) -> dict:
    return {
        "id": _element_id(),
        "elType": "widget",
        "widgetType": "html",
        "isInner": False,
        "settings": {"html": html},
        "elements": [],
    }


def _container(html: str) -> dict:
    return {
        "id": _element_id(),
        "elType": "container",
        "isInner": False,
        "settings": _container_settings(),
        "elements": [_html_widget(html)],
    }


def html_widget_tree(html: str) -> list:
    """Single HTML widget (library sections)."""
    return [_container(html)]


def html_widget_tree_split(header_html: str, main_html: str, footer_html: str) -> list:
    """Three HTML widgets: header chrome, editable main, footer chrome."""
    return [
        _container(header_html),
        _container(main_html),
        _container(footer_html),
    ]


def set_elementor_html(post_id: int, html: str) -> None:
    tree = html_widget_tree(html)
    _push_elementor_page(post_id, tree)


def set_elementor_html_split(
    post_id: int,
    header_html: str,
    main_html: str,
    footer_html: str,
) -> None:
    tree = html_widget_tree_split(header_html, main_html, footer_html)
    _push_elementor_page(post_id, tree)


def _push_elementor_page(post_id: int, tree: list) -> None:
    req(
        "POST",
        f"{BASE}/pages/{post_id}",
        {
            "content": "",
            "status": "publish",
            "meta": {
                "_wp_page_template": "elementor_canvas",
                "_elementor_edit_mode": "builder",
                "_elementor_data": json.dumps(tree, ensure_ascii=False),
            },
        },
    )


def header_logo_widget(
    image_id: int = DEFAULT_LOGO_ID,
    image_url: str = LOGO_WEBP,
    alt: str = "پاکن منتال",
) -> dict:
    """Elementor image widget the header template uses as the editable logo."""
    return {
        "id": LOGO_WIDGET_ID,
        "elType": "widget",
        "widgetType": "image",
        "isInner": False,
        "settings": {
            "image": {
                "url": image_url,
                "id": image_id,
                "size": "",
                "alt": alt,
                "source": "library",
            },
            "image_size": "full",
            "align": "center",
            "caption_source": "custom",
            "caption": "تصویر لوگوی هدر — این تصویر را عوض کنید",
            "link_to": "none",
            "_css_classes": LOGO_WIDGET_CLASS,
            "_title": "تصویر لوگوی هدر",
        },
        "elements": [],
    }


def _walk(nodes: list):
    for node in nodes:
        if isinstance(node, dict):
            yield node
            yield from _walk(node.get("elements") or [])


def find_header_logo_widget(tree: list) -> dict | None:
    for node in _walk(tree):
        if node.get("widgetType") != "image":
            continue
        classes = str((node.get("settings") or {}).get("_css_classes") or "")
        if LOGO_WIDGET_CLASS in classes or node.get("id") == LOGO_WIDGET_ID:
            return node
    return None


def _mark_logo_html(html: str) -> str:
    note = "<!-- لوگوی هدر از ویجت «تصویر لوگوی هدر» در همین قالب عوض می‌شود -->"
    if note in html or "pm-header__logo" not in html:
        return html
    needle = '<picture class="pm-logo__picture">'
    if needle not in html:
        return html
    return html.replace(needle, note + needle, 1)


def with_header_logo_control(tree: list, existing: list | None = None) -> list:
    """Keep an editable logo image at the top of the header template."""
    if not tree or not isinstance(tree[0], dict):
        return tree
    elements = tree[0].get("elements")
    if not isinstance(elements, list):
        return tree
    current = find_header_logo_widget(tree) or (
        find_header_logo_widget(existing) if existing else None
    )
    widget = current or header_logo_widget()
    tree[0]["elements"] = [widget] + [
        node for node in elements if node is not widget and node.get("id") != widget.get("id")
    ]
    for node in tree[0]["elements"]:
        settings = node.get("settings") or {}
        html = settings.get("html")
        if isinstance(html, str):
            settings["html"] = _mark_logo_html(html)
            node["settings"] = settings
    return tree


def set_library_html(post_id: int, html: str) -> None:
    tree = html_widget_tree(html)
    if post_id == HEADER_TEMPLATE_ID:
        existing = _library_tree(post_id)
        tree = with_header_logo_control(tree, existing)
    _push_library(post_id, tree)


def _library_tree(post_id: int) -> list | None:
    post = req("GET", f"{BASE}/elementor_library/{post_id}?context=edit")
    raw = (post.get("meta") or {}).get("_elementor_data")
    if not isinstance(raw, str) or not raw:
        return None
    data = json.loads(raw)
    return data if isinstance(data, list) else None


def _push_library(post_id: int, tree: list) -> None:
    req(
        "POST",
        f"{BASE}/elementor_library/{post_id}",
        {
            "status": "publish",
            "meta": {
                "_elementor_edit_mode": "builder",
                "_elementor_data": json.dumps(tree, ensure_ascii=False),
            },
        },
    )


def upsert_header_logo_control(post_id: int = HEADER_TEMPLATE_ID) -> None:
    tree = _library_tree(post_id)
    if not tree:
        raise RuntimeError(f"elementor library {post_id} has no data")
    _push_library(post_id, with_header_logo_control(tree))
