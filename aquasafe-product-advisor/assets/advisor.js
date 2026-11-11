(function () {
  var LS = "as-advisor-v1";
  var boot = { support: "09373211344", other_kinds: [] };
  var answers = {};
  var step = 0;
  var steps = [];
  var last = null;

  function $(root, sel) {
    return root.querySelector(sel);
  }
  function el(html) {
    var d = document.createElement("div");
    d.innerHTML = html.trim();
    return d.firstChild;
  }
  function money(n) {
    if (!n) return "";
    return Number(n).toLocaleString("fa-IR") + " تومان";
  }
  function save() {
    try {
      localStorage.setItem(LS, JSON.stringify({ answers: answers, step: step }));
    } catch (e) {}
  }
  function load() {
    try {
      var x = JSON.parse(localStorage.getItem(LS) || "null");
      if (x && x.answers) {
        answers = x.answers;
        step = x.step || 0;
      }
    } catch (e) {}
  }
  function track(name, extra) {
    extra = extra || {};
    extra.event = name;
    extra.path = answers.path || "";
    extra.step = currentId();
    try {
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({ event: "as_advisor_" + name, as_advisor: extra });
    } catch (e) {}
    fetch((window.asAdvisor && asAdvisor.rest) + "event", {
      method: "POST",
      headers: { "Content-Type": "application/json", "X-WP-Nonce": (window.asAdvisor && asAdvisor.nonce) || "" },
      body: JSON.stringify(extra),
    }).catch(function () {});
  }
  function currentId() {
    return steps[step] ? steps[step].id : "start";
  }
  function buildSteps() {
    var p = answers.path;
    steps = [{ id: "path" }];
    if (p === "water") {
      steps = steps.concat([
        { id: "place", q: "کجا می‌خواهید از دستگاه استفاده کنید؟", opts: [
          ["home", "خانه"], ["office", "محل کار"], ["pro", "کافه، رستوران یا فضای حرفه‌ای"], ["unknown", "هنوز مشخص نیست"]
        ]},
        { id: "water_source", q: "منبع آب شما چیست؟", opts: [
          ["city", "آب شهری"], ["well", "چاه یا منبع خصوصی"], ["other", "منبع دیگر"], ["unknown", "نمی‌دانم"]
        ], note: "از روی شهر یا طعم آب به‌تنهایی نمی‌توان سلامت آب یا وجود آلاینده خاص را نتیجه گرفت. اگر آزمایش آب دارید، هنگام خرید به پشتیبانی بگویید." },
        { id: "need", q: "نیاز اصلی‌تان کدام است؟", opts: [
          ["taste", "بهبود طعم و بو"], ["sediment", "کاهش ذرات و گل‌ولای"], ["tds", "رسوب، املاح یا سختی"], ["general", "انتخاب عمومی / مطمئن نیستم"]
        ]},
        { id: "users", q: "تقریباً چند نفر از دستگاه استفاده می‌کنند؟", opts: [
          ["1-3", "۱ تا ۳ نفر"], ["4-6", "۴ تا ۶ نفر"], ["7plus", "بیش از ۶ نفر"], ["unknown", "نمی‌دانم"]
        ]},
        { id: "space", q: "فضای نصب چقدر محدود است؟", opts: [
          ["under_sink", "زیر سینک یا فضای معمولی"], ["tight", "خیلی محدود"], ["unknown", "فرقی ندارد / نمی‌دانم"]
        ]},
        { id: "budget", q: "بودجه تقریبی‌تان چقدر است؟", opts: [
          ["low", "تا حدود ۴۵۰ هزار تومان"], ["mid", "تا حدود ۸۰۰ هزار تومان"], ["high", "بالاتر از این بازه"], ["unknown", "هنوز مشخص نکرده‌ام"]
        ]}
      ]);
    } else if (p === "air") {
      steps = steps.concat([
        { id: "area", q: "فضا تقریباً چند متر مربع است؟", opts: [
          ["s", "تا حدود ۲۰ متر"], ["m", "حدود ۲۰ تا ۳۵ متر"], ["l", "بزرگ‌تر از ۳۵ متر"], ["unknown", "نمی‌دانم"]
        ], note: "تناسب دستگاه با فضا بر اساس مشخصات اعلام‌شده سازنده (مثل CADR) بررسی می‌شود، نه حدس." },
        { id: "room", q: "بیشتر در چه فضایی استفاده می‌شود؟", opts: [
          ["bedroom", "اتاق خواب"], ["living", "پذیرایی"], ["office", "محل کار"], ["unknown", "سایر / مشخص نیست"]
        ]},
        { id: "air_issue", q: "دغدغه اصلی‌تان چیست؟", opts: [
          ["dust", "گردوغبار"], ["pets", "موی حیوانات خانگی"], ["odor", "بو"], ["general", "استفاده عمومی"]
        ], note: "دستگاه تصفیه هوا درمان بیماری نیست و فقط آلاینده‌هایی را کاهش می‌دهد که سازنده تأیید کرده باشد." },
        { id: "noise", q: "صدای دستگاه چقدر برایتان مهم است؟", opts: [
          ["sleep", "برای خواب باید کم‌صدا باشد"], ["any", "محدودیت خاصی ندارم"]
        ]},
        { id: "budget", q: "بودجه تقریبی‌تان چقدر است؟", opts: [
          ["low", "تا حدود ۴۵۰ هزار تومان"], ["mid", "تا حدود ۸۰۰ هزار تومان"], ["high", "بالاتر از این بازه"], ["unknown", "هنوز مشخص نکرده‌ام"]
        ]},
        { id: "cost_priority", q: "کدام هزینه برایتان اولویت دارد؟", opts: [
          ["purchase", "قیمت خرید کمتر"], ["maintenance", "هزینه نگهداری کمتر"], ["unknown", "فرقی ندارد"]
        ]}
      ]);
    } else if (p === "filter") {
      steps = steps.concat([
        { id: "device_type", q: "فیلتر یا قطعه برای کدام دستگاه است؟", opts: [
          ["water", "تصفیه آب"], ["air", "تصفیه هوا"]
        ]},
        { id: "model_pick", q: "برند و مدل دستگاه را می‌دانید؟" },
        { id: "part_type", q: "نوع فیلتر یا قطعه را می‌دانید؟", opts: [
          ["", "نمی‌دانم / فرقی ندارد"], ["filter", "فیلتر"], ["pump", "پمپ"], ["tank", "مخزن"], ["cartridge", "کارتریج"], ["part", "قطعه دیگر"]
        ]},
        { id: "budget", q: "سقف بودجه؟", opts: [
          ["low", "تا حدود ۴۵۰ هزار تومان"], ["mid", "تا حدود ۸۰۰ هزار تومان"], ["high", "محدودیتی ندارم"], ["unknown", "هنوز مشخص نکرده‌ام"]
        ]}
      ]);
    } else if (p === "other") {
      var kinds = (boot.other_kinds || []).map(function (k) { return [k.id, k.label]; });
      if (!kinds.length) kinds = [["parts", "قطعات و تجهیزات"]];
      steps = steps.concat([
        { id: "other_kind", q: "دنبال کدام دسته هستید؟", opts: kinds },
        { id: "budget", q: "سقف بودجه؟", opts: [
          ["low", "تا حدود ۴۵۰ هزار تومان"], ["mid", "تا حدود ۸۰۰ هزار تومان"], ["high", "محدودیتی ندارم"], ["unknown", "هنوز مشخص نکرده‌ام"]
        ]}
      ]);
    }
  }
  function card(root) {
    var wrap = $(root, ".as-adv-card");
    if (!wrap) {
      wrap = el('<div class="as-adv-card"></div>');
      root.appendChild(wrap);
    }
    return wrap;
  }
  function progressHtml() {
    var n = Math.max(steps.length, 1);
    var h = '<div class="as-adv-progress" aria-hidden="true">';
    for (var i = 0; i < n; i++) h += "<b class=\"" + (i <= step ? "on" : "") + "\"></b>";
    return h + "</div>";
  }
  function render(root) {
    var modal = root.getAttribute("data-as-advisor") === "modal";
    var c = card(root);
    var close = modal ? '<button type="button" class="as-adv-close" data-close aria-label="بستن">×</button>' : "";
    if (!answers.path) {
      var qs = boot.questions || {};
      var title = qs.start_title || "محصول مناسب شما کدام است؟";
      var lead = qs.start_lead || "چند سؤال کوتاه جواب بده تا گزینه‌های مناسب نیازت را پیدا کنیم.";
      var paths = qs.paths || [
        { id: "water", label: "می‌خواهم دستگاه تصفیه آب بخرم." },
        { id: "air", label: "می‌خواهم دستگاه تصفیه هوا بخرم." },
        { id: "filter", label: "برای دستگاه فعلی‌ام فیلتر یا قطعه می‌خواهم." },
        { id: "other", label: "سایر محصولات و تجهیزات" }
      ];
      var pathBtns = paths.map(function (p) {
        return '<button type="button" class="as-adv-opt" data-set="path" data-val="' + p.id + '">' + p.label + "</button>";
      }).join("");
      c.innerHTML = close +
        '<p class="as-adv-kicker">دستیار انتخاب محصول</p><h2>' + title + "</h2>" +
        '<p class="as-adv-lead">' + lead + "</p>" + pathBtns;
      bind(root);
      return;
    }
    buildSteps();
    if (step >= steps.length) {
      recommend(root);
      return;
    }
    var s = steps[step];
    var html = close + progressHtml() + "<h2>" + s.q + "</h2>";
    if (s.note) html += '<p class="as-adv-muted">' + s.note + "</p>";
    if (s.id === "model_pick") {
      html += '<label class="as-adv-muted" for="as-model">جست‌وجوی برند یا مدل</label>' +
        '<input class="as-adv-search" id="as-model" autocomplete="off" placeholder="مثلاً AJ-3500">' +
        '<div class="as-adv-suggest" hidden></div>' +
        '<button type="button" class="as-adv-opt" data-unknown-model>مدل دستگاه را نمی‌دانم</button>' +
        '<p class="as-adv-muted">برچسب مدل معمولاً پشت یا کنار دستگاه، روی جعبه یا دفترچه است. اگر پیدا نشد با پشتیبانی تماس بگیرید.</p>';
    } else {
      (s.opts || []).forEach(function (o) {
        html += '<button type="button" class="as-adv-opt" data-set="' + s.id + '" data-val="' + o[0] + '">' + o[1] + "</button>";
      });
    }
    html += '<div class="as-adv-row"><button type="button" class="as-adv-btn ghost" data-back>بازگشت</button></div>';
    c.innerHTML = html;
    bind(root);
  }
  function setAns(k, v, root) {
    answers[k] = v;
    if (k === "path") {
      step = 1;
      buildSteps();
      track("start");
    } else {
      step += 1;
    }
    save();
    render(root);
  }
  function bind(root) {
    root.querySelectorAll("[data-set]").forEach(function (b) {
      b.addEventListener("click", function () { setAns(b.getAttribute("data-set"), b.getAttribute("data-val"), root); });
    });
    var back = $(root, "[data-back]");
    if (back) back.addEventListener("click", function () {
      if (step > 1) step -= 1;
      else { answers = {}; step = 0; }
      save();
      render(root);
    });
    var cl = $(root, "[data-close]");
    if (cl) cl.addEventListener("click", function () {
      track("dropoff");
      root.hidden = true;
    });
    var unk = $(root, "[data-unknown-model]");
    if (unk) unk.addEventListener("click", function () {
      answers.model_unknown = true;
      answers.device_id = 0;
      step += 1;
      save();
      render(root);
    });
    var inp = $(root, "#as-model");
    var box = $(root, ".as-adv-suggest");
    if (inp && box) {
      var t;
      inp.addEventListener("input", function () {
        clearTimeout(t);
        t = setTimeout(function () {
          var q = inp.value.trim();
          if (q.length < 2) { box.hidden = true; return; }
          fetch((window.asAdvisor && asAdvisor.rest) + "models?q=" + encodeURIComponent(q) + "&type=" + encodeURIComponent(answers.device_type || ""), {
            headers: { "X-WP-Nonce": (window.asAdvisor && asAdvisor.nonce) || "" }
          }).then(function (r) { return r.json(); }).then(function (rows) {
            box.innerHTML = "";
            (rows || []).forEach(function (m) {
              var btn = document.createElement("button");
              btn.type = "button";
              btn.textContent = m.name;
              btn.addEventListener("click", function () {
                answers.device_id = m.id;
                answers.model_unknown = false;
                step += 1;
                save();
                render(root);
              });
              box.appendChild(btn);
            });
            box.hidden = !rows || !rows.length;
          }).catch(function () {});
        }, 200);
      });
    }
  }
  function recommend(root) {
    var c = card(root);
    c.innerHTML = '<p class="as-adv-lead">در حال پیدا کردن گزینه‌های مناسب…</p>';
    fetch((window.asAdvisor && asAdvisor.rest) + "recommend", {
      method: "POST",
      headers: { "Content-Type": "application/json", "X-WP-Nonce": (window.asAdvisor && asAdvisor.nonce) || "" },
      body: JSON.stringify({ answers: answers })
    }).then(function (r) { return r.json(); }).then(function (data) {
      last = data;
      track(data.items && data.items.length ? "complete" : "no_result");
      if (data.compat_status === "unknown") track("compat_unknown");
      showResults(root, data);
    }).catch(function () {
      c.innerHTML = '<p class="as-adv-empty">دریافت پیشنهادها ممکن نشد. اتصال را بررسی کنید یا بعداً دوباره تلاش کنید.</p><button type="button" class="as-adv-btn ghost" data-back>بازگشت</button>';
      bind(root);
    });
  }
  function shareUrl() {
    var u = new URL(location.href);
    u.hash = "asadv=" + encodeURIComponent(JSON.stringify(answers));
    return u.toString();
  }
  function showResults(root, data) {
    var c = card(root);
    var html = '<p class="as-adv-kicker">نتیجه دستیار</p><h2>پیشنهادهای متناسب با پاسخ‌های شما</h2>';
    html += '<p class="as-adv-muted">' + (data.need_summary || "") + "</p>";
    (data.warnings || []).forEach(function (w) { html += '<div class="as-adv-warn">' + w + "</div>"; });
    if (!data.items || !data.items.length) {
      html += '<p class="as-adv-empty">' + (data.empty_reason || "گزینه‌ای پیدا نشد.") + "</p>";
      html += '<div class="as-adv-row"><button type="button" class="as-adv-btn ghost" data-edit>ویرایش پاسخ‌ها</button>';
      if (answers.budget && answers.budget !== "unknown") {
        html += '<button type="button" class="as-adv-btn" data-widen>افزایش بازه بودجه</button>';
      }
      html += "</div>";
      html += '<p class="as-adv-muted">پشتیبانی: <a href="tel:09373211344">09373211344</a></p>';
      c.innerHTML = html;
      bindResults(root);
      return;
    }
    data.items.forEach(function (it) {
      var p = it.product || it;
      html += '<article class="as-adv-result"><img alt="" src="' + (p.image || "") + '"><div>';
      html += "<h3>" + (p.name || "") + "</h3>";
      html += '<div>' + money(p.price) + (p.in_stock ? " — موجود" : " — ناموجود") + "</div>";
      html += "<ul>" + (it.reasons || []).map(function (r) { return "<li>" + r + "</li>"; }).join("") + "</ul>";
      if (it.limitation) html += '<p class="as-adv-muted">' + it.limitation + "</p>";
      if (p.specs && p.specs.length) html += '<p class="as-adv-muted">' + p.specs.join(" · ") + "</p>";
      if (it.yearly_cost && it.yearly_cost.amount) {
        html += '<p class="as-adv-muted">هزینه تقریبی نگهداری سالانه: ' + money(it.yearly_cost.amount) + " (" + it.yearly_cost.basis + ")</p>";
      }
      html += '<div class="as-adv-row">';
      if (p.permalink) html += '<a class="as-adv-btn ghost" data-view="' + p.id + '" href="' + p.permalink + '">مشاهده محصول</a>';
      if (p.add_to_cart && p.in_stock) html += '<a class="as-adv-btn" data-cart="' + p.id + '" href="' + p.add_to_cart + '">افزودن به سبد</a>';
      html += "</div></div></article>";
    });
    if (data.items.length > 1) {
      html += "<h3>مقایسه ساده</h3><table class=\"as-adv-compare\"><thead><tr><th></th>";
      data.items.forEach(function (it) { var p = it.product || it; html += "<th>" + (p.name || "") + "</th>"; });
      html += "</tr></thead><tbody>";
      ["price", "stock", "kind"].forEach(function (row, i) {
        var lab = ["قیمت", "موجودی", "نوع"][i];
        html += "<tr><th>" + lab + "</th>";
        data.items.forEach(function (it) {
          var p = it.product || it;
          var v = row === "price" ? money(p.price) : row === "stock" ? (p.in_stock ? "موجود" : "ناموجود") : (p.kind || "");
          html += "<td>" + v + "</td>";
        });
        html += "</tr>";
      });
      html += "</tbody></table>";
    }
    html += '<div class="as-adv-row"><button type="button" class="as-adv-btn ghost" data-edit>ویرایش پاسخ‌ها</button>';
    html += '<button type="button" class="as-adv-btn ghost" data-share>کپی لینک نتیجه</button></div>';
    html += '<p class="as-adv-muted">پشتیبانی: <a href="tel:09373211344">09373211344</a></p>';
    c.innerHTML = html;
    bindResults(root);
  }
  function bindResults(root) {
    var edit = $(root, "[data-edit]");
    if (edit) edit.addEventListener("click", function () {
      step = 1;
      save();
      render(root);
    });
    var widen = $(root, "[data-widen]");
    if (widen) widen.addEventListener("click", function () {
      answers.budget = "unknown";
      save();
      recommend(root);
    });
    var share = $(root, "[data-share]");
    if (share) share.addEventListener("click", function () {
      var u = shareUrl();
      if (navigator.clipboard) navigator.clipboard.writeText(u);
      share.textContent = "لینک کپی شد";
    });
    root.querySelectorAll("[data-view]").forEach(function (a) {
      a.addEventListener("click", function () { track("view_product", { product_id: a.getAttribute("data-view") }); });
    });
    root.querySelectorAll("[data-cart]").forEach(function (a) {
      a.addEventListener("click", function () { track("add_to_cart", { product_id: a.getAttribute("data-cart") }); });
    });
    var back = $(root, "[data-back]");
    if (back) back.addEventListener("click", function () { step = Math.max(steps.length - 1, 1); render(root); });
  }
  function hydrateFromHash() {
    if (!location.hash || location.hash.indexOf("asadv=") === -1) return;
    try {
      answers = JSON.parse(decodeURIComponent(location.hash.split("asadv=")[1]));
      step = 99;
    } catch (e) {}
  }
  function mount(root) {
    if (root.getAttribute("data-as-advisor") === "modal" && root.hidden) return;
    load();
    hydrateFromHash();
    render(root);
  }
  function bootAll() {
    var url = (window.asAdvisor && asAdvisor.rest) + "bootstrap";
    fetch(url, { headers: { "X-WP-Nonce": (window.asAdvisor && asAdvisor.nonce) || "" } })
      .then(function (r) { return r.json(); })
      .then(function (b) { boot = b || boot; })
      .catch(function () {})
      .then(function () {
        document.querySelectorAll("[data-as-advisor]").forEach(function (n) {
          if (n.getAttribute("data-as-advisor") === "embedded") mount(n);
        });
      });
    document.addEventListener("click", function (e) {
      var b = e.target.closest("[data-as-open]");
      if (!b) return;
      var host = b.parentNode;
      var modal = host.querySelector("[data-as-advisor=modal]");
      if (!modal) return;
      modal.hidden = false;
      mount(modal);
    });
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", bootAll);
  else bootAll();
})();
