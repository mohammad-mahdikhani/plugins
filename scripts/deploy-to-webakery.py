#!/usr/bin/env python3
"""Upload Schema Nova to webakery.ir via WordPress REST (Application Password).

Reads WEBAKERY_WP_USER and WEBAKERY_WP_APPLICATION_PASSWORD from the
environment. Never prints those values.
"""

from __future__ import annotations

import base64
import json
import os
import ssl
import sys
import urllib.error
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
ZIP_PATH = ROOT / "web-bakery" / "schema-nova-1.0.0.zip"
SITE = os.environ.get("WEBAKERY_URL", "https://webakery.ir").rstrip("/")
USER = os.environ.get("WEBAKERY_WP_USER", "").strip()
PASSWORD = os.environ.get("WEBAKERY_WP_APPLICATION_PASSWORD", "").strip()


def die(msg: str, code: int = 1) -> None:
    print(msg, file=sys.stderr)
    raise SystemExit(code)


def auth_header() -> str:
    token = base64.b64encode(f"{USER}:{PASSWORD}".encode("utf-8")).decode("ascii")
    return "Basic " + token


def request(method: str, url: str, *, data: bytes | None = None, headers: dict[str, str] | None = None, timeout: int = 60):
    hdrs = {"User-Agent": "SchemaNova-Deploy/1.0", "Authorization": auth_header()}
    if headers:
        hdrs.update(headers)
    req = urllib.request.Request(url, data=data, headers=hdrs, method=method)
    ctx = ssl.create_default_context()
    try:
        with urllib.request.urlopen(req, timeout=timeout, context=ctx) as res:
            raw = res.read()
            ctype = res.headers.get("Content-Type", "")
            if "json" in ctype and raw:
                return json.loads(raw.decode("utf-8"))
            return raw
    except urllib.error.HTTPError as err:
        body = err.read().decode("utf-8", "replace")[:800]
        die(f"HTTP {err.code} {url}: {body}")


def upload_zip() -> dict:
    if not ZIP_PATH.is_file():
        die(f"Missing zip: {ZIP_PATH}")
    payload = ZIP_PATH.read_bytes()
    return request(
        "POST",
        f"{SITE}/wp-json/wp/v2/media",
        data=payload,
        headers={
            "Content-Disposition": 'attachment; filename="schema-nova-1.0.0.zip"',
            "Content-Type": "application/zip",
        },
    )


PAGE_HTML = """
<div class="wb-schema-nova" dir="rtl">
  <h2>هوش‌اسکیما | Schema Nova</h2>
  <p>افزونه اسکیمای JSON-LD مطابق مستندات گوگل، برای هر قالبی. تایتل و متا را هم می‌تواند بر اساس اینتنت جستجو از API آنالیتیکس به‌روز کند.</p>
  <p><a class="elementor-button" href="{source_url}" download>دانلود schema-nova-1.0.0.zip</a></p>
  <ol>
    <li>افزونه‌ها → افزودن → بارگذاری افزونه → انتخاب همین ZIP</li>
    <li>فعال کنید؛ منوی هوش‌اسکیما در پیشخوان می‌آید</li>
    <li>هویت سازمان را پر کنید و در منبع داده API را وصل کنید</li>
  </ol>
</div>
"""


def upsert_page(source_url: str) -> dict:
    html = PAGE_HTML.format(source_url=source_url)
    existing = request("GET", f"{SITE}/wp-json/wp/v2/pages?slug=schema-nova")
    body = json.dumps(
        {
            "title": "دانلود هوش‌اسکیما",
            "slug": "schema-nova",
            "status": "publish",
            "content": html,
        }
    ).encode("utf-8")
    headers = {"Content-Type": "application/json"}
    if isinstance(existing, list) and existing:
        page_id = existing[0]["id"]
        return request("POST", f"{SITE}/wp-json/wp/v2/pages/{page_id}", data=body, headers=headers)
    return request("POST", f"{SITE}/wp-json/wp/v2/pages", data=body, headers=headers)


def maybe_product(source_url: str) -> dict | None:
    payload = {
        "name": "پلاگین هوش‌اسکیما | Schema Nova",
        "type": "simple",
        "regular_price": "0",
        "virtual": True,
        "downloadable": True,
        "catalog_visibility": "visible",
        "short_description": "اسکیمای JSON-LD کامل + تایتل هوشمند بر اساس سرپ.",
        "description": "دانلود رایگان افزونه هوش‌اسکیما برای وردپرس.",
        "downloads": [{"name": "schema-nova-1.0.0.zip", "file": source_url}],
        "download_limit": -1,
        "download_expiry": -1,
    }
    try:
        return request(
            "POST",
            f"{SITE}/wp-json/wc/v3/products",
            data=json.dumps(payload).encode("utf-8"),
            headers={"Content-Type": "application/json"},
        )
    except SystemExit:
        print("WooCommerce product API not available with this login; page was still published.", file=sys.stderr)
        return None


def main() -> None:
    if not USER or not PASSWORD:
        die(
            "Set WEBAKERY_WP_USER and WEBAKERY_WP_APPLICATION_PASSWORD "
            "(WordPress → Users → Profile → Application Passwords)."
        )
    media = upload_zip()
    source_url = media.get("source_url") or media.get("guid", {}).get("rendered")
    if not source_url:
        die("Upload succeeded but no file URL was returned.")
    page = upsert_page(source_url)
    product = maybe_product(source_url)
    print(json.dumps({
        "media_id": media.get("id"),
        "file": source_url,
        "page": page.get("link"),
        "product": (product or {}).get("permalink"),
    }, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
