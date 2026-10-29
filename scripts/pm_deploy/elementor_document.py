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
