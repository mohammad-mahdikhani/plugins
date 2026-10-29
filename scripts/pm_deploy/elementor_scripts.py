"""Inline front-end bootstrap for Elementor pages (header, search, fonts)."""
from __future__ import annotations

import json

from .catalog import PRODUCT_CATALOG, SHOP, SITE


def build_boot_js() -> str:
    catalog = json.dumps(PRODUCT_CATALOG, ensure_ascii=False)
    return f"""
(function () {{
  "use strict";
  window.PM_PRODUCT_CATALOG = {catalog};
  window.PM_SHOP_URL = {json.dumps(SHOP)};
  window.PM_SITE_URL = {json.dumps(SITE)};

  var header =
    document.querySelector("header.pm-header") ||
    document.querySelector(".pm-header") ||
    document.querySelector("header.e-flexbox");
  if (!header) return;
  header.classList.add("pm-header");

  if (document.querySelector(".pm-hero")) {{
    document.documentElement.classList.add("pm-site--has-hero");
  }}
  document.documentElement.classList.add("pm-site");

  var tools = header.querySelector(".pm-header-tools") || header.querySelector('[class*="pm-header-tools"]');
  if (tools && !tools.querySelector(".pm-search")) {{
    tools.insertAdjacentHTML(
      "afterbegin",
      '<div class="pm-search pm-search--inline">' +
        '<label class="pm-search__label" for="pm-search-input">جستجو</label>' +
        '<span class="pm-search__icon" aria-hidden="true">' +
        '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
        '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/></svg></span>' +
        '<input id="pm-search-input" class="pm-search__input" type="search" name="q" placeholder="جستجوی محصول…" autocomplete="off" />' +
        '<div class="pm-search__results" hidden></div></div>'
    );
  }}

  var threshold = 28;
  var ticking = false;
  function onScroll() {{
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(function () {{
      var scrolled = window.scrollY > threshold;
      header.classList.toggle("is-scrolled", scrolled);
      document.documentElement.classList.toggle("pm-scrolled", scrolled);
      ticking = false;
    }});
  }}
  onScroll();
  window.addEventListener("scroll", onScroll, {{ passive: true }});

  var root = document.documentElement;
  var input = root.querySelector(".pm-search__input");
  var panel = root.querySelector(".pm-search__results");
  if (!input || !panel) return;

  var catalog = window.PM_PRODUCT_CATALOG || [];
  var shopUrl = window.PM_SHOP_URL || "/";
  var site = window.PM_SITE_URL || "";
  var timer;

  input.addEventListener("input", function () {{
    clearTimeout(timer);
    var q = input.value.trim();
    if (q.length < 2) {{
      panel.hidden = true;
      panel.innerHTML = "";
      return;
    }}
    timer = setTimeout(function () {{ runSearch(q); }}, 250);
  }});

  input.addEventListener("focus", function () {{
    if (input.value.trim().length >= 2) panel.hidden = false;
  }});

  document.addEventListener("click", function (e) {{
    var wrap = root.querySelector(".pm-search");
    if (wrap && !wrap.contains(e.target)) panel.hidden = true;
  }});

  function runSearch(q) {{
    var storeUrl =
      site + "/wp-json/wc/store/v1/products?search=" + encodeURIComponent(q) + "&per_page=8";
    fetch(storeUrl, {{ credentials: "same-origin" }})
      .then(function (r) {{ if (!r.ok) throw new Error("wc"); return r.json(); }})
      .then(function (items) {{
        if (items && items.length) {{
          render(
            items.map(function (p) {{
              return {{
                title: p.name || p.title,
                url: p.permalink || p.link,
                price: p.prices && p.prices.price ? formatPrice(p.prices.price) : "",
              }};
            }})
          );
        }} else render(filterLocal(q));
      }})
      .catch(function () {{
        fetch(site + "/wp-json/wp/v2/pages?search=" + encodeURIComponent(q) + "&per_page=5", {{
          credentials: "same-origin",
        }})
          .then(function (r) {{ return r.ok ? r.json() : []; }})
          .then(function (pages) {{
            var fromPages = (pages || []).map(function (p) {{
              return {{ title: p.title.rendered, url: p.link, price: "" }};
            }});
            var local = filterLocal(q);
            render(local.length ? local : fromPages);
          }})
          .catch(function () {{ render(filterLocal(q)); }});
      }});
  }}

  function filterLocal(q) {{
    var needle = q.toLowerCase();
    return catalog
      .filter(function (item) {{ return item.title.toLowerCase().indexOf(needle) !== -1; }})
      .slice(0, 8)
      .map(function (item) {{
        return {{ title: item.title, url: item.url, price: item.price || "" }};
      }});
  }}

  function formatPrice(raw) {{
    var n = parseInt(raw, 10);
    if (isNaN(n)) return raw;
    return n.toLocaleString("fa-IR") + " تومان";
  }}

  function render(items) {{
    if (!items.length) {{
      panel.innerHTML =
        '<p class="pm-search__empty">محصولی پیدا نشد.</p><a class="pm-search__all" href="' +
        shopUrl +
        '">مشاهده فروشگاه</a>';
      panel.hidden = false;
      return;
    }}
    var html = '<ul class="pm-search__list">';
    items.forEach(function (item) {{
      html +=
        '<li><a href="' +
        item.url +
        '"><span class="pm-search__title">' +
        item.title +
        "</span>" +
        (item.price ? '<span class="pm-search__price">' + item.price + "</span>" : "") +
        "</a></li>";
    }});
    html += '</ul><a class="pm-search__all" href="' + shopUrl + '">همه نتایج در فروشگاه</a>';
    panel.innerHTML = html;
    panel.hidden = false;
  }}
}})();
""".strip()
