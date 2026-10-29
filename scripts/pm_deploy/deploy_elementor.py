#!/usr/bin/env python3
"""
Deploy Pakan Mental: original pm.css layout inside Elementor HTML widget (editable in editor).
"""
from __future__ import annotations

from urllib.parse import quote

from .builder import req, BASE, wrap_page, header, footer
from .elementor_client import ElementorMcp
from .elementor_document import set_elementor_html, set_library_html
from .elementor_globals import PAGE_CHROME_CSS, sync_all
from .elementor_layout import PAGE_IDS, NOT_FOUND_PAGE_SLUG, NOT_FOUND_PAGE_TITLE
from .pages_content import (
    home_main,
    shop_main,
    cart_main,
    blog_main,
    product_single_main,
    not_found_main,
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


def _ensure_page(title: str, slug: str) -> int:
    found = req("GET", f"{BASE}/pages?slug={slug}&per_page=5")
    for item in found:
        if item.get("slug") == slug:
            return int(item["id"])
    created = req(
        "POST",
        f"{BASE}/pages",
        {
            "title": title,
            "slug": slug,
            "status": "publish",
            "meta": {
                "_elementor_edit_mode": "builder",
                "_elementor_template_type": "wp-page",
            },
        },
    )
    return int(created["id"])


def _apply_page_settings(client: ElementorMcp, post_id: int) -> None:
    client.call(
        "elementor-update-page-settings",
        {
            "post_id": post_id,
            "settings": {
                "hide_title": "yes",
                "page_layout": "elementor_canvas",
                "custom_css": PAGE_CHROME_CSS,
                "custom_javascript": "",
            },
        },
    )
    client.call("elementor-publish-document", {"post_id": post_id})


def main() -> None:
    client = ElementorMcp()
    sync_all(client)

    header_section_id = _ensure_section(SECTION_HEADER_TITLE)
    footer_section_id = _ensure_section(SECTION_FOOTER_TITLE)
    set_library_html(
        header_section_id,
        f'<div class="pm-site" dir="rtl">{header("home")}</div>',
    )
    set_library_html(footer_section_id, f'<div class="pm-site" dir="rtl">{footer()}</div>')

    not_found_id = _ensure_page(NOT_FOUND_PAGE_TITLE, NOT_FOUND_PAGE_SLUG)
    PAGE_IDS["not_found"] = not_found_id

    pages = [
        (PAGE_IDS["home"], "home", home_main(), True, False),
        (PAGE_IDS["shop"], "shop", shop_main(), False, True),
        (PAGE_IDS["cart"], "shop", cart_main(), False, False),
        (PAGE_IDS["blog"], "blog", blog_main(), False, False),
        (PAGE_IDS["product"], "shop", product_single_main(), False, False),
        (not_found_id, "home", not_found_main(), False, False),
    ]

    for post_id, active, main_html, hero_js, shop_js in pages:
        html = wrap_page(
            active,
            main_html,
            include_hero_js=hero_js,
            include_shop_js=shop_js,
            elementor=True,
        )
        set_elementor_html(post_id, html)
        _apply_page_settings(client, post_id)
        print("elementor html page", post_id, "ok")

    try:
        client.call(
            "elementor-update-page-settings",
            {
                "post_id": 6,
                "settings": {"custom_css": PAGE_CHROME_CSS, "custom_javascript": ""},
            },
        )
    except Exception as exc:
        print("kit settings skipped", exc)

    req(
        "POST",
        f"{BASE}/settings",
        {
            "show_on_front": "page",
            "page_on_front": PAGE_IDS["home"],
            "hello_elementor_settings_page_title": "",
        },
    )
    print("sections header", header_section_id, "footer", footer_section_id)
    print("404 page id", not_found_id, "slug", NOT_FOUND_PAGE_SLUG)
    print("edit:", f"https://www.pakanmental.com/wp-admin/post.php?post={PAGE_IDS['home']}&action=elementor")


if __name__ == "__main__":
    main()
