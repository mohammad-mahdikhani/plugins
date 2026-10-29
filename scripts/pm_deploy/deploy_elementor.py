#!/usr/bin/env python3
"""Deploy Pakan Mental layouts as native Elementor documents (editable in the editor)."""
from __future__ import annotations

from urllib.parse import quote

from .builder import req, BASE
from .elementor_client import ElementorMcp
from .elementor_globals import PAGE_CHROME_CSS, sync_all
from .elementor_layout import (
    PAGE_IDS,
    blog_main_config,
    blog_main_xml,
    cart_main_config,
    cart_main_xml,
    footer_config,
    footer_xml,
    header_config,
    header_xml,
    home_main_config,
    home_main_xml,
    product_single_config,
    product_single_xml,
    shop_main_config,
    shop_main_xml,
)

SECTION_HEADER_TITLE = "PM Header Elementor"
SECTION_FOOTER_TITLE = "PM Footer Elementor"


def _ensure_section(title: str) -> int:
    q = quote(title)
    found = req("GET", f"{BASE}/elementor_library?search={q}&per_page=20")
    for item in found:
        if item.get("title", {}).get("rendered") == title:
            return int(item["id"])
    created = req(
        "POST",
        f"{BASE}/elementor_library",
        {
            "title": title,
            "status": "publish",
            "meta": {
                "_elementor_template_type": "section",
                "_elementor_edit_mode": "builder",
            },
        },
    )
    return int(created["id"])


def _prepare_wp_page(post_id: int) -> None:
    req(
        "POST",
        f"{BASE}/pages/{post_id}",
        {
            "content": "",
            "template": "elementor_canvas",
            "meta": {"_wp_page_template": "elementor_canvas"},
        },
    )


def _apply_page_settings(client: ElementorMcp, post_id: int) -> None:
    client.call(
        "elementor-update-page-settings",
        {
            "post_id": post_id,
            "settings": {
                "hide_title": "yes",
                "page_layout": "elementor_canvas",
                "custom_css": PAGE_CHROME_CSS,
            },
        },
    )


def _build_document(
    client: ElementorMcp,
    post_id: int,
    xml: str,
    element_config: dict,
    style: dict,
    classes: dict,
) -> None:
    client.call(
        "elementor-build-composition",
        {
            "post_id": post_id,
            "xml_structure": xml,
            "element_config": element_config,
            "style": style,
            "classes": classes,
            "parent_id": "document",
            "mode": "replace_children",
        },
    )
    client.call("elementor-publish-document", {"post_id": post_id})


def _build_section(client: ElementorMcp, post_id: int, xml: str, cfg: tuple[dict, dict, dict]) -> None:
    ec, st, cl = cfg
    _build_document(client, post_id, xml, ec, st, cl)


def main() -> None:
    client = ElementorMcp()
    sync_all(client)

    header_section_id = _ensure_section(SECTION_HEADER_TITLE)
    footer_section_id = _ensure_section(SECTION_FOOTER_TITLE)

    hx, hc = header_xml("home"), header_config("home")
    fx, fc = footer_xml(), footer_config()
    _build_section(client, header_section_id, hx, hc)
    _build_section(client, footer_section_id, fx, fc)

    pages = [
        (PAGE_IDS["home"], home_main_xml(), home_main_config()),
        (PAGE_IDS["shop"], shop_main_xml(), shop_main_config()),
        (PAGE_IDS["cart"], cart_main_xml(), cart_main_config()),
        (PAGE_IDS["blog"], blog_main_xml(), blog_main_config()),
        (PAGE_IDS["product"], product_single_xml(), product_single_config()),
    ]

    for post_id, xml, cfg in pages:
        _prepare_wp_page(post_id)
        ec, st, cl = cfg
        _build_document(client, post_id, xml, ec, st, cl)
        _apply_page_settings(client, post_id)
        print("elementor page", post_id, "ok")

    req(
        "POST",
        f"{BASE}/settings",
        {
            "show_on_front": "page",
            "page_on_front": PAGE_IDS["home"],
            "hello_elementor_settings_page_title": "",
        },
    )
    print("saved sections: header", header_section_id, "footer", footer_section_id)
    print("edit home:", f"https://www.pakanmental.com/wp-admin/post.php?post={PAGE_IDS['home']}&action=elementor")


if __name__ == "__main__":
    main()
