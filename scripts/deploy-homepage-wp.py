#!/usr/bin/env python3
"""Deploy homepage HTML block to pakanmental.com via REST API."""
import json
import re
import urllib.request

AUTH = "Basic bWhtOkNaT1p3U2RRMU1PMmhmMzJwM0FSWXNEOA=="
BASE = "https://www.pakanmental.com/wp-json/wp/v2"
LOGO_WEBP = "https://www.pakanmental.com/wp-content/uploads/2026/10/logo-pakanmental.webp"
SHOP = "https://www.pakanmental.com/shop/"
BLOG = "https://www.pakanmental.com/%d8%b3%d9%84%d8%a7%d9%85-%d8%af%d9%86%db%8c%d8%a7/"


def req(method, url, data=None):
    body = None
    headers = {"Authorization": AUTH}
    if data is not None:
        body = json.dumps(data).encode("utf-8")
        headers["Content-Type"] = "application/json"
    r = urllib.request.Request(url, data=body, headers=headers, method=method)
    with urllib.request.urlopen(r, timeout=120) as res:
        return json.loads(res.read().decode())


with open("/workspace/pakanmental-home/assets/css/home.css", encoding="utf-8") as f:
    css = f.read()
css += """
/* Full-width home: hide Hello Elementor chrome */
body.page .site-header,
body.page .site-footer,
body.page .page-header { display: none !important; }
body.page #content.site-main { max-width: none; padding: 0; margin: 0; }
"""
with open("/workspace/pakanmental-home/assets/js/home.js", encoding="utf-8") as f:
    js = f.read()

with open("/workspace/design/wireframe/index.html", encoding="utf-8") as f:
    html = f.read()

body_inner = re.search(r"<body[^>]*>(.*)</body>", html, re.S).group(1)
body_inner = re.sub(r"<body[^>]*>|</body>", "", body_inner)
body_inner = body_inner.replace('class="pm-home"', "", 1)
body_inner = body_inner.replace('href="#"', f'href="{SHOP}"')
body_inner = body_inner.replace('href="#">مجله', f'href="{BLOG}">مجله')
body_inner = body_inner.replace('href="#">تماس', 'href="https://www.pakanmental.com/contact/">تماس')
body_inner = body_inner.replace('href="#" class="is-active"', f'href="https://www.pakanmental.com/" class="is-active"')
body_inner = re.sub(
    r"<picture>.*?</picture>",
    f'<picture><source type="image/webp" srcset="{LOGO_WEBP}" /><img src="{LOGO_WEBP}" alt="پاکن منتال" width="180" height="52" loading="eager" decoding="async" fetchpriority="high" /></picture>',
    body_inner,
    count=1,
)
body_inner = re.sub(
    r"<picture>.*?</picture>",
    f'<picture><source type="image/webp" srcset="{LOGO_WEBP}" /><img src="{LOGO_WEBP}" alt="" width="140" height="56" loading="lazy" decoding="async" /></picture>',
    body_inner,
    count=1,
)
body_inner = body_inner.replace('<script src="../../pakanmental-home/assets/js/home.js"></script>', "")
body_inner = body_inner.replace('<link rel="stylesheet" href="styles.css" />', "")

block = f"""<!-- wp:html -->
<div class="pm-home" dir="rtl">
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
<style id="pm-home-css">{css}</style>
{body_inner}
<script id="pm-home-js">{js}</script>
</div>
<!-- /wp:html -->"""

page = req(
    "POST",
    f"{BASE}/pages",
    {
        "title": "خانه",
        "status": "publish",
        "content": block,
        "template": "",
        "meta": {
            "_wp_page_template": "elementor_header_footer",
        },
    },
)
page_id = page["id"]
print("created_page", page_id, page.get("link"))

settings = req(
    "POST",
    f"{BASE}/settings",
    {
        "show_on_front": "page",
        "page_on_front": page_id,
        "hello_elementor_settings_header_footer": "",
        "hello_elementor_settings_page_title": "",
    },
)
print("settings", settings.get("show_on_front"), settings.get("page_on_front"))

# Trash draft elementor page 11 if exists
try:
    req("DELETE", f"{BASE}/pages/11?force=true")
    print("deleted_draft_11")
except Exception as e:
    print("skip_delete_11", e)
