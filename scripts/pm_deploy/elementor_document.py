"""Build Elementor documents that wrap the original PM HTML (editable HTML widget)."""
from __future__ import annotations

import json
import secrets

from .builder import req, BASE


def _element_id() -> str:
    return secrets.token_hex(4)


def html_widget_tree(html: str) -> list:
    """Elementor document: one container + legacy HTML widget (full design markup)."""
    root = _element_id()
    widget = _element_id()
    return [
        {
            "id": root,
            "elType": "container",
            "isInner": False,
            "settings": {"content_width": "full", "padding": {"unit": "px", "top": "0", "right": "0", "bottom": "0", "left": "0", "isLinked": True}},
            "elements": [
                {
                    "id": widget,
                    "elType": "widget",
                    "widgetType": "html",
                    "isInner": False,
                    "settings": {"html": html},
                    "elements": [],
                }
            ],
        }
    ]


def set_elementor_html(post_id: int, html: str) -> None:
    tree = html_widget_tree(html)
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
