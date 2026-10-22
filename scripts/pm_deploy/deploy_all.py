#!/usr/bin/env python3
"""Deploy / update all Pakan Mental template pages on live WordPress."""
from __future__ import annotations

from .builder import wrap_page, req, BASE, URLS
from .sync_elementor import sync as sync_elementor
from .pages_content import home_main, shop_main, cart_main, blog_main, product_single_main


def upsert_page(slug: str, title: str, content: str) -> int:
    pages = req("GET", f"{BASE}/pages?slug={slug}&per_page=1")
    if pages:
        pid = pages[0]["id"]
        req("POST", f"{BASE}/pages/{pid}", {"title": title, "content": content, "status": "publish"})
        print("updated", slug, pid)
        return pid
    page = req("POST", f"{BASE}/pages", {"title": title, "slug": slug, "content": content, "status": "publish"})
    print("created", slug, page["id"])
    return page["id"]


def wrap_blog_posts():
    from .builder import URLS

    posts = req("GET", f"{BASE}/posts?per_page=20&status=publish")
    for p in posts:
        pid = p["id"]
        full = req("GET", f"{BASE}/posts/{pid}?context=edit")
        title = full["title"]["raw"]
        body = full["content"]["raw"]
        if "<!-- wp:html -->" in body and "pm-rev__viewport" in body:
            continue
        main = f"""
<section class="pm-page-head"><div class="pm-container"><h1>{title}</h1></div></section>
<section class="pm-section"><div class="pm-container pm-article">{body}</div></section>
<p class="pm-container" style="margin-bottom:2rem"><a class="pm-more" href="{URLS['blog']}">← بازگشت به مجله</a></p>
"""
        req("POST", f"{BASE}/posts/{pid}", {"content": wrap_page("blog", main)})
        print("wrapped post", pid)


def main():
    home_id = upsert_page("%d8%ae%d8%a7%d9%86%d9%87", "خانه", wrap_page("home", home_main(), include_hero_js=True))
    upsert_page("%d9%81%d8%b1%d9%88%d8%b4%da%af%d8%a7%d9%87", "فروشگاه", wrap_page("shop", shop_main()))
    upsert_page("%d8%b3%d8%a8%d8%af-%d8%ae%d8%b1%db%8c%d8%af", "سبد خرید", wrap_page("cart", cart_main()))
    blog_id = upsert_page("%d9%85%d8%ac%d9%84%d9%87", "مجله", wrap_page("blog", blog_main()))
    upsert_page("%d9%86%d9%85%d9%88%d9%86%d9%87-%d9%85%d8%ad%d8%b5%d9%88%d9%84", "نمونه محصول", wrap_page("shop", product_single_main()))

    req(
        "POST",
        f"{BASE}/settings",
        {
            "show_on_front": "page",
            "page_on_front": home_id,
            "hello_elementor_settings_page_title": "",
        },
    )
    wrap_blog_posts()
    try:
        sync_elementor()
    except Exception as exc:
        print("elementor sync skipped", exc)
    print("reading settings OK", URLS["home"])
    print("shop", URLS["shop"], "cart", URLS["cart"], "blog", URLS["blog"], "product", URLS["product_demo"])


if __name__ == "__main__":
    main()
