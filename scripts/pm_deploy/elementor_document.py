"""Build Elementor documents that wrap the original PM HTML (editable HTML widget)."""
from __future__ import annotations

import json
import secrets

from .builder import req, BASE


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


HOME_PAGE_ID = 13
SLIDER_WIDGET_IDS = ("pmsld01", "pmsld02", "pmsld03")


def slider_image_widget(index: int) -> dict:
    """Empty Elementor image the homepage template uses as one slide background."""
    label = f"تصویر اسلاید {index}"
    return {
        "id": SLIDER_WIDGET_IDS[index - 1],
        "elType": "widget",
        "widgetType": "image",
        "isInner": False,
        "settings": {
            "image": {"url": "", "id": "", "alt": "", "source": "library"},
            "image_size": "full",
            "align": "center",
            "caption_source": "custom",
            "caption": f"{label} — پس‌زمینه اسلایدر صفحه اصلی",
            "link_to": "none",
            "_css_classes": f"pm-slider-image pm-slider-image--{index}",
            "_title": label,
        },
        "elements": [],
    }


def _walk(nodes: list):
    for node in nodes:
        if isinstance(node, dict):
            yield node
            yield from _walk(node.get("elements") or [])


def find_slider_widgets(tree: list | None) -> dict[int, dict]:
    found: dict[int, dict] = {}
    if not tree:
        return found
    for node in _walk(tree):
        if node.get("widgetType") != "image":
            continue
        classes = str((node.get("settings") or {}).get("_css_classes") or "")
        for index in (1, 2, 3):
            if f"pm-slider-image--{index}" in classes or node.get("id") == SLIDER_WIDGET_IDS[index - 1]:
                found[index] = node
    return found


def _slider_container(tree: list) -> dict | None:
    for node in _walk(tree):
        if node.get("elType") != "container":
            continue
        for child in node.get("elements") or []:
            html = (child.get("settings") or {}).get("html")
            if isinstance(html, str) and "pm-rev__bg--1" in html:
                return node
    return None


def with_slider_image_controls(tree: list, existing: list | None = None) -> list:
    """Keep three editable slide images above the homepage slider HTML."""
    container = _slider_container(tree)
    if container is None:
        return tree
    saved = find_slider_widgets(existing)
    saved.update(find_slider_widgets(tree))
    widgets = [saved.get(index) or slider_image_widget(index) for index in (1, 2, 3)]
    widget_ids = {widget.get("id") for widget in widgets}
    rest = []
    for child in container.get("elements") or []:
        classes = str((child.get("settings") or {}).get("_css_classes") or "")
        if child.get("id") in widget_ids or "pm-slider-image--" in classes:
            continue
        rest.append(child)
    container["elements"] = widgets + rest
    return tree


def _page_tree(post_id: int) -> list | None:
    post = req("GET", f"{BASE}/pages/{post_id}?context=edit")
    raw = (post.get("meta") or {}).get("_elementor_data")
    if not isinstance(raw, str) or not raw:
        return None
    data = json.loads(raw)
    return data if isinstance(data, list) else None


def upsert_slider_image_controls(post_id: int = HOME_PAGE_ID) -> None:
    tree = _page_tree(post_id)
    if not tree:
        raise RuntimeError(f"page {post_id} has no elementor data")
    req(
        "POST",
        f"{BASE}/pages/{post_id}",
        {
            "meta": {
                "_elementor_edit_mode": "builder",
                "_elementor_data": json.dumps(with_slider_image_controls(tree), ensure_ascii=False),
            },
        },
    )


def set_elementor_html_split(
    post_id: int,
    header_html: str,
    main_html: str,
    footer_html: str,
) -> None:
    tree = html_widget_tree_split(header_html, main_html, footer_html)
    if post_id == HOME_PAGE_ID:
        tree = with_slider_image_controls(tree, _page_tree(post_id))
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


def set_library_html(post_id: int, html: str) -> None:
    tree = html_widget_tree(html)
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
