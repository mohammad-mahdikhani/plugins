(function () {
  var siteRoot = document.querySelector(".pm-site") || document.documentElement;
  var catalog = window.PM_PRODUCT_CATALOG || [];
  var shopUrl = window.PM_SHOP_URL || "/";
  var site = window.PM_SITE_URL || "";

  siteRoot.querySelectorAll(".pm-search").forEach(function (wrap) {
    initSearch(wrap);
  });

  function initSearch(wrap) {
    var input = wrap.querySelector(".pm-search__input");
    var panel = wrap.querySelector(".pm-search__results");
    if (!input || !panel) return;
    var timer;

    input.addEventListener("input", function () {
      clearTimeout(timer);
      var q = input.value.trim();
      if (q.length < 2) {
        panel.hidden = true;
        panel.innerHTML = "";
        return;
      }
      timer = setTimeout(function () {
        runSearch(q, panel);
      }, 250);
    });

    input.addEventListener("focus", function () {
      if (input.value.trim().length >= 2) {
        panel.hidden = false;
      }
    });

    document.addEventListener("click", function (e) {
      if (!wrap.contains(e.target)) {
        panel.hidden = true;
      }
    });
  }

  function runSearch(q, panel) {
    var storeUrl =
      site +
      "/wp-json/wc/store/v1/products?search=" +
      encodeURIComponent(q) +
      "&per_page=8";

    fetch(storeUrl, { credentials: "same-origin" })
      .then(function (r) {
        if (!r.ok) throw new Error("wc");
        return r.json();
      })
      .then(function (items) {
        if (items && items.length) {
          render(
            panel,
            items.map(function (p) {
              return {
                title: p.name || p.title,
                url: p.permalink || p.link,
                price: p.prices && p.prices.price ? formatPrice(p.prices.price) : "",
              };
            })
          );
        } else {
          render(panel, filterLocal(q));
        }
      })
      .catch(function () {
        fetch(
          site +
            "/wp-json/wp/v2/pages?search=" +
            encodeURIComponent(q) +
            "&per_page=5",
          { credentials: "same-origin" }
        )
          .then(function (r) {
            return r.ok ? r.json() : [];
          })
          .then(function (pages) {
            var fromPages = (pages || []).map(function (p) {
              return { title: p.title.rendered, url: p.link, price: "" };
            });
            var local = filterLocal(q);
            render(panel, local.length ? local : fromPages);
          })
          .catch(function () {
            render(panel, filterLocal(q));
          });
      });
  }

  function filterLocal(q) {
    var needle = q.toLowerCase();
    return catalog
      .filter(function (item) {
        return item.title.toLowerCase().indexOf(needle) !== -1;
      })
      .slice(0, 8)
      .map(function (item) {
        return { title: item.title, url: item.url, price: item.price || "" };
      });
  }

  function formatPrice(raw) {
    var n = parseInt(raw, 10);
    if (isNaN(n)) return raw;
    return n.toLocaleString("fa-IR") + " تومان";
  }

  function render(panel, items) {
    if (!items.length) {
      panel.innerHTML =
        '<p class="pm-search__empty">محصولی پیدا نشد.</p><a class="pm-search__all" href="' +
        shopUrl +
        '">مشاهده فروشگاه</a>';
      panel.hidden = false;
      return;
    }
    var html = '<ul class="pm-search__list">';
    items.forEach(function (item) {
      html +=
        '<li><a href="' +
        item.url +
        '"><span class="pm-search__title">' +
        item.title +
        "</span>" +
        (item.price ? '<span class="pm-search__price">' + item.price + "</span>" : "") +
        "</a></li>";
    });
    html +=
      '</ul><a class="pm-search__all" href="' +
      shopUrl +
      '">همه نتایج در فروشگاه</a>';
    panel.innerHTML = html;
    panel.hidden = false;
  }
})();
