"""Elementor kit: global colors and minimal page chrome (PM styles live in HTML widget)."""
from __future__ import annotations

from .elementor_client import ElementorMcp
from .sync_elementor import sync as sync_kit_tokens

PAGE_CHROME_CSS = """
body .site-header,
body .site-footer,
body .page-header { display: none !important; }
body #content.site-main,
body .site-main { max-width: none; padding: 0; margin: 0; }
body.single-product .site-header,
body.single-product .site-footer,
body.single-product .page-header { display: none !important; }
body.single-product .site-main { max-width: none; padding: 0; }
body.single-product .product {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  max-width: min(1200px, 92vw);
  margin: 1.5rem auto 2rem;
  padding: 0 1rem;
  align-items: start;
}
body.single-product .product .woocommerce-product-gallery { margin: 0; }
body.single-product .product .summary { float: none !important; width: auto !important; }
body.single-product .product_title { font-family: Vazirmatn, sans-serif; font-size: clamp(1.25rem, 2.5vw, 1.75rem); }
body.single-product .price { font-size: 1.35rem; font-weight: 800; }
@media (max-width: 768px) {
  body.single-product .product { grid-template-columns: 1fr; }
}
.elementor-html-widget,
.elementor-widget-html,
.elementor-widget-wrap,
.elementor-element,
.e-con,
.e-con-inner { overflow: visible !important; }
.elementor-html-widget { width: 100%; }
.pm-elementor-chrome,
.pm-elementor-main { width: 100%; }
:root {
  --pm-brand-green: #4aa88e;
  --e-global-color-primary: #4aa88e;
  --e-global-color-accent: #4aa88e;
  --woocommerce: #4aa88e;
  --wp--preset--color--primary: #4aa88e;
}
body.post-type-archive-product {
  font-family: Vazirmatn, Tahoma, sans-serif;
  background: #fafaf9;
  color: #1e4339;
}
body.post-type-archive-product .site-header {
  background: #faf7f2;
  box-shadow: 0 6px 28px rgba(30,67,57,0.06);
  padding: 0.6rem 1rem;
}
body.post-type-archive-product .site-header .custom-logo {
  max-height: 64px;
  width: auto;
}
body.post-type-archive-product .site-description { display: none !important; }
body.post-type-archive-product .site-main,
body.post-type-archive-product #content.site-main {
  max-width: min(1200px, 92vw);
  margin: 0 auto;
  padding: 1.25rem 0 3rem;
  float: none;
}
body.post-type-archive-product .woocommerce-breadcrumb { display: none; }
body.post-type-archive-product .woocommerce-products-header__title {
  font-size: clamp(1.6rem, 3vw, 2.2rem);
  font-weight: 800;
  color: #1e4339;
  text-align: right;
}
body.post-type-archive-product .woocommerce-result-count,
body.post-type-archive-product .woocommerce-ordering {
  margin: 0.5rem 0 1rem;
}
body.post-type-archive-product ul.products {
  display: grid !important;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 0.9rem;
  list-style: none;
  margin: 0;
  padding: 0;
  float: none !important;
  width: 100% !important;
}
body.post-type-archive-product ul.products li.product {
  width: auto !important;
  float: none !important;
  margin: 0 !important;
  background: #faf7f2;
  border-radius: 22px;
  padding: 0.65rem;
  box-shadow: 0 6px 28px rgba(30,67,57,0.05);
}
body.post-type-archive-product ul.products li.product img {
  width: 100%;
  aspect-ratio: 1;
  object-fit: cover;
  border-radius: 12px;
}
body.post-type-archive-product ul.products li.product .woocommerce-loop-product__title,
body.post-type-archive-product ul.products li.product h2 {
  color: #1e4339 !important;
  font-size: 0.86rem !important;
  font-weight: 600;
  line-height: 1.4;
}
body.post-type-archive-product ul.products li.product a.button,
body.post-type-archive-product ul.products li.product .button {
  background: #4aa88e !important;
  color: #faf7f2 !important;
  border: 0 !important;
  border-radius: 999px !important;
  width: 100%;
  text-align: center;
}
@media (max-width: 640px) {
  body.post-type-archive-product ul.products {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
html body.theme-hello-elementor.single-product .site-header,
html body.theme-hello-elementor.single-product header#site-header,
html body.theme-hello-elementor.single-product .dynamic-header,
html body.theme-hello-elementor.tax-product_cat .site-header,
html body.theme-hello-elementor.tax-product_cat header#site-header,
html body.theme-hello-elementor.tax-product_tag .site-header,
html body.theme-hello-elementor.single-product .site-footer,
html body.theme-hello-elementor.tax-product_cat .site-footer {
  display: none !important;
}
"""


PAGE_CHROME_JS = r"""
(function () {
  var path = (location.pathname || "").replace(/\/+$/, "");
  if (path === "/shop") {
    location.replace("https://www.pakanmental.com/%d9%81%d8%b1%d9%88%d8%b4%da%af%d9%87/");
    return;
  }
  var body = document.body;
  if (!body) return;
  var isProduct = body.classList.contains("single-product");
  var isCat = body.classList.contains("tax-product_cat") || body.classList.contains("tax-product_tag");
  if (!isProduct && !isCat) return;

  function addCss() {
    if (document.getElementById("pm-kit-wc-css")) return;
    var st = document.createElement("style");
    st.id = "pm-kit-wc-css";
    st.textContent = "html body.single-product header#site-header,html body.single-product .site-header,html body.single-product .dynamic-header,html body.tax-product_cat header#site-header,html body.tax-product_cat .site-header,html body.tax-product_tag .site-header,html body.single-product footer.site-footer,html body.tax-product_cat footer.site-footer{display:none!important}body.single-product,body.tax-product_cat{font-family:Vazirmatn,Tahoma,sans-serif;background:#fafaf9;color:#1e4339}body.single-product div.product{display:grid!important;grid-template-columns:1fr 1fr;gap:2rem;max-width:min(1200px,92vw);margin:1.5rem auto 2rem;float:none!important;width:auto!important}body.single-product .woocommerce-product-gallery,body.single-product .summary{float:none!important;width:auto!important;max-width:none!important}body.single-product .product_title{font-size:clamp(1.25rem,2.5vw,1.75rem);font-weight:800}body.single-product .price{font-size:1.35rem;font-weight:800;color:#1e4339}@media(max-width:768px){body.single-product div.product{grid-template-columns:1fr}}";
    document.head.appendChild(st);
  }
  function addLink(id, href) {
    if (document.getElementById(id)) return;
    var l = document.createElement("link");
    l.id = id;
    l.rel = "stylesheet";
    l.href = href;
    document.head.appendChild(l);
  }
  function addScript(id, src) {
    if (document.getElementById(id)) return;
    var s = document.createElement("script");
    s.id = id;
    s.src = src;
    s.defer = true;
    document.body.appendChild(s);
  }
  function restyleAttrs() {
    var table = document.querySelector(".woocommerce-product-attributes, table.shop_attributes");
    var summary = document.querySelector(".summary.entry-summary, .product .summary");
    if (!table || !summary || summary.querySelector(".pm-game-specs")) return;
    var ul = document.createElement("ul");
    ul.className = "pm-game-specs";
    ul.setAttribute("aria-label", "ویژگی‌های محصول");
    table.querySelectorAll("tr").forEach(function (tr) {
      var th = tr.querySelector("th");
      var td = tr.querySelector("td");
      if (!th || !td) return;
      var li = document.createElement("li");
      li.className = "pm-game-spec";
      li.innerHTML = '<span class="pm-game-spec__label"></span><span class="pm-game-spec__value"></span>';
      li.querySelector(".pm-game-spec__label").textContent = th.textContent.trim();
      li.querySelector(".pm-game-spec__value").textContent = td.textContent.trim();
      ul.appendChild(li);
    });
    if (!ul.children.length) return;
    var price = summary.querySelector("p.price, .price");
    if (price && price.parentNode === summary) {
      price.insertAdjacentElement("afterend", ul);
    } else {
      var form = summary.querySelector("form.cart");
      summary.insertBefore(ul, form || summary.firstChild);
    }
    var wrap = table.closest(".woocommerce-Tabs-panel, .woocommerce-tabs") || table;
    wrap.style.display = "none";
  }
  function injectChrome() {
    if (document.querySelector(".pm-header")) return Promise.resolve();
    return fetch("/", { credentials: "same-origin" })
      .then(function (r) { return r.text(); })
      .then(function (html) {
        var box = document.createElement("div");
        box.innerHTML = html;
        var header = box.querySelector(".pm-header");
        var cart = box.querySelector("#pm-mini-cart");
        var footer = box.querySelector(".pm-footer");
        var dock = box.querySelector(".pm-dock");
        if (header) {
          document.body.insertBefore(header, document.body.firstChild);
          if (cart) header.insertAdjacentElement("afterend", cart);
        }
        if (footer) document.body.appendChild(footer);
        if (dock) document.body.appendChild(dock);
        document.body.classList.add("pm-site", "pm-wc-shell");
      })
      .catch(function () {});
  }
  addCss();
  addLink("pm-vazir-font", "https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap");
  addLink("pm-site-css", "/wp-content/plugins/pakanmental-home/assets/css/pm.css?ver=kit-wc");
  addLink("pm-brand-css", "/wp-content/plugins/pakanmental-home/assets/css/pm-brand-overrides.css?ver=kit-wc");
  function boot() {
    injectChrome().then(function () {
      restyleAttrs();
      ["pm-header", "pm-mega-menu", "pm-mobile", "pm-search"].forEach(function (name) {
        addScript("pmjs-" + name, "/wp-content/plugins/pakanmental-home/assets/js/" + name + ".js?ver=kit-wc");
      });
    });
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", boot);
  else boot();
})();
"""


def sync_global_classes(client: ElementorMcp) -> None:
    """Optional: keep kit classes empty/minimal to avoid clashing with pm.css."""
    return


def apply_kit_chrome(client: ElementorMcp | None = None) -> None:
    client = client or ElementorMcp()
    client.call(
        "elementor-update-page-settings",
        {
            "post_id": 6,
            "settings": {
                "custom_css": PAGE_CHROME_CSS,
                "custom_javascript": PAGE_CHROME_JS,
            },
        },
    )
    try:
        client.call("elementor-publish-document", {"post_id": 6})
    except RuntimeError:
        pass


def sync_all(client: ElementorMcp | None = None) -> None:
    sync_kit_tokens()
    client = client or ElementorMcp()
    sync_global_classes(client)
    apply_kit_chrome(client)
