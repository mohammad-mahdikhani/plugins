(function () {
  const $ = (sel, root) => (root || document).querySelector(sel);
  const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));

  const state = {
    token: "",
    sheets: [],
    mapping: {},
    labels: {},
    sheet: 0,
  };

  function toast(msg) {
    const el = $("#hpl-toast");
    if (!el) return;
    el.textContent = msg;
    el.hidden = false;
    setTimeout(() => {
      el.hidden = true;
    }, 3500);
  }

  function setStep(n) {
    $$(".hpl-steps li").forEach((li) => {
      const s = Number(li.getAttribute("data-step"));
      li.classList.toggle("is-active", s === n);
      li.classList.toggle("is-done", s < n);
    });
    [1, 2, 3, 4].forEach((s) => {
      const card = $("#hpl-step-" + s);
      if (card) card.classList.toggle("is-hidden", s !== n);
    });
  }

  function fmt(n) {
    return Number(n || 0).toLocaleString("fa-IR");
  }

  function statusLabel(status) {
    return (
      {
        changed: "تغییر می‌کند",
        unchanged: "بدون تغییر",
        missing: "پیدا نشد",
        invalid: "نامعتبر",
        skipped: "رد شد",
      }[status] || status
    );
  }

  function matchLabel(method) {
    return (
      {
        sku: "کد کالا",
        product_id: "شناسه",
        barcode: "بارکد",
        code: "کد/بارکد",
        name: "نام کالا",
      }[method] || "—"
    );
  }

  async function post(action, data) {
    const body = data instanceof FormData ? data : new FormData();
    if (!(data instanceof FormData) && data) {
      Object.keys(data).forEach((k) => body.append(k, data[k]));
    }
    body.append("action", action);
    body.append("nonce", HPL.nonce);
    const res = await fetch(HPL.ajax, { method: "POST", body, credentials: "same-origin" });
    const json = await res.json();
    if (!json || !json.success) {
      const msg = json && json.data && json.data.message ? json.data.message : HPL.i18n.error;
      throw new Error(msg);
    }
    return json.data;
  }

  function fillMap() {
    const sheet = state.sheets[state.sheet];
    if (!sheet) return;
    state.mapping = { ...sheet.mapping };
    const box = $("#hpl-map");
    box.innerHTML = "";
    Object.keys(state.labels).forEach((field) => {
      const label = document.createElement("label");
      const select = document.createElement("select");
      select.dataset.field = field;
      const none = document.createElement("option");
      none.value = "-1";
      none.textContent = "— انتخاب نشده —";
      select.appendChild(none);
      sheet.headers.forEach((h, i) => {
        const opt = document.createElement("option");
        opt.value = String(i);
        opt.textContent = h || "ستون " + (i + 1);
        if (Number(state.mapping[field]) === i) opt.selected = true;
        select.appendChild(opt);
      });
      select.addEventListener("change", () => {
        state.mapping[field] = Number(select.value);
      });
      label.innerHTML = "<span>" + state.labels[field] + "</span>";
      label.appendChild(select);
      box.appendChild(label);
    });

    const thead = $("#hpl-sample thead");
    const tbody = $("#hpl-sample tbody");
    thead.innerHTML = "<tr>" + sheet.headers.map((h) => "<th>" + escapeHtml(h) + "</th>").join("") + "</tr>";
    tbody.innerHTML = (sheet.sample || [])
      .map((row) => "<tr>" + row.map((c) => "<td>" + escapeHtml(c) + "</td>").join("") + "</tr>")
      .join("");
  }

  function escapeHtml(s) {
    return String(s == null ? "" : s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function acceptParsed(data) {
    state.token = data.token;
    state.sheets = data.sheets || [];
    state.labels = data.labels || {};
    state.sheet = 0;
    const select = $("#hpl-sheet");
    select.innerHTML = state.sheets
      .map((s, i) => '<option value="' + i + '">' + escapeHtml(s.name) + " (" + s.row_count + " ردیف)</option>")
      .join("");
    $("#hpl-file-meta").textContent = data.filename + " — " + String(data.type).toUpperCase();
    const warn = $("#hpl-warning");
    if (data.warning) {
      warn.hidden = false;
      warn.textContent = data.warning;
    } else {
      warn.hidden = true;
    }
    fillMap();
    if (String(data.type) === "telegram") {
      const stock = $("#hpl-update-stock");
      if (stock) stock.checked = false;
    }
    setStep(2);
  }

  async function uploadFile(file) {
    toast(HPL.i18n.uploading);
    const fd = new FormData();
    fd.append("file", file);
    const data = await post("hpl_upload", fd);
    acceptParsed(data);
  }

  function kindLabel(kind) {
    return (
      {
        document: "فایل",
        public_file: "فایل کانال",
        text: "متن قیمت",
      }[kind] || kind
    );
  }

  function formatWhen(ts) {
    if (!ts) return "";
    try {
      return new Date(Number(ts) * 1000).toLocaleString("fa-IR");
    } catch (e) {
      return "";
    }
  }

  function renderTelegramPosts(posts) {
    const body = $("#hpl-telegram-list");
    const wrap = $("#hpl-telegram-list-wrap");
    if (!body || !wrap) return;
    const list = posts || [];
    if (!list.length) {
      wrap.hidden = true;
      body.innerHTML = "";
      return;
    }
    wrap.hidden = false;
    body.innerHTML = list
      .map((p) => {
        const title = p.filename || p.preview || "پست تلگرام";
        return (
          "<tr><td>" +
          escapeHtml(title) +
          "<div class='hpl-cand'>" +
          escapeHtml(formatWhen(p.date)) +
          "</div></td><td>" +
          escapeHtml(kindLabel(p.kind)) +
          "</td><td>" +
          (p.rows_guess ? fmt(p.rows_guess) : "—") +
          "</td><td><button type='button' class='hpl-btn hpl-btn-primary hpl-tg-open' data-id='" +
          escapeHtml(p.id) +
          "'>خواندن</button></td></tr>"
        );
      })
      .join("");
    $$(".hpl-tg-open", body).forEach((btn) => {
      btn.onclick = () =>
        post("hpl_telegram_open", { id: btn.getAttribute("data-id") })
          .then(acceptParsed)
          .catch((err) => toast(err.message));
    });
  }

  function bindTelegram() {
    const box = $("#hpl-telegram");
    if (!box) return;
    const help = $("#hpl-telegram-help");
    if (!HPL.telegram) {
      if (help) {
        help.innerHTML =
          'برای خواندن کانال، توکن ربات و نام کانال را در <a href="' +
          escapeHtml(HPL.settings || "admin.php?page=hesabdar-price-list-settings") +
          '">تنظیمات لیست قیمت</a> وارد کنید.';
      }
    } else if (help) {
      help.textContent =
        "اگر دکمه کانال خطا داد، کل پست را از تلگرام کپی کنید و بچسبانید. سرور بعضی هاست‌ها به t.me وصل نمی‌شود.";
    }
    renderTelegramPosts(HPL.posts || []);
    const latest = $("#hpl-telegram-latest");
    if (latest) {
      latest.addEventListener("click", () => {
        if (!HPL.telegram) {
          toast("ابتدا کانال تلگرام را در تنظیمات وارد کنید.");
          return;
        }
        toast(HPL.i18n.telegram || "در حال خواندن از تلگرام…");
        post("hpl_telegram_open", { id: "" })
          .then(acceptParsed)
          .catch((err) => toast(err.message));
      });
    }
    const pasteBtn = $("#hpl-telegram-paste-btn");
    if (pasteBtn) {
      pasteBtn.addEventListener("click", () => {
        const text = ($("#hpl-telegram-paste") && $("#hpl-telegram-paste").value) || "";
        if (!text.trim()) {
          toast("ابتدا متن پست را از تلگرام کپی و اینجا بچسبانید.");
          return;
        }
        toast("در حال خواندن نام و قیمت از متن پست…");
        post("hpl_telegram_paste", { text: text })
          .then(acceptParsed)
          .catch((err) => toast(err.message));
      });
    }
    const refresh = $("#hpl-telegram-refresh");
    if (refresh) {
      refresh.addEventListener("click", () => {
        if (!HPL.telegram) {
          toast("ابتدا کانال تلگرام را در تنظیمات وارد کنید.");
          return;
        }
        toast("در حال دریافت پست‌های کانال…");
        post("hpl_telegram_fetch", {})
          .then((data) => {
            HPL.posts = data.posts || [];
            renderTelegramPosts(HPL.posts);
            toast((HPL.posts.length || 0) + " پست پیدا شد");
          })
          .catch((err) => toast(err.message));
      });
    }
  }

  function renderScreen(data) {
    state.screen = data;
    const stats = data.stats || {};
    $("#hpl-stats").innerHTML = [
      ["ردیف فایل", stats.file_rows],
      ["محصول سایت", stats.site_products],
      ["در سایت", stats.on_site],
      ["نیاز به تأیید", stats.review],
      ["در سایت نیست", stats.not_on_site],
      ["آماده اعمال", data.ready],
    ]
      .map(([k, v]) => '<div class="hpl-stat"><b>' + fmt(v) + "</b><span>" + k + "</span></div>")
      .join("");
    const note = $("#hpl-match-note");
    if (note) {
      note.textContent =
        "در سایت: قیمت و موجودی به‌روز می‌شود. نیاز به تأیید: پیشنهاد موتور را بپذیرید. در سایت نیست: غایب‌ها را ست کنید یا رد کنید.";
    }

    const onBody = $("#hpl-body-on_site");
    if (onBody) {
      onBody.innerHTML = (data.on_site || [])
        .map((item) => {
          return (
            "<tr class='is-" +
            escapeHtml(item.status) +
            "'><td>" +
            escapeHtml(item.sku) +
            "</td><td>" +
            escapeHtml(item.product_name || item.name) +
            "<div class='hpl-cand'>" +
            matchLabel(item.matched_by) +
            "</div></td><td>" +
            escapeHtml(item.old_regular) +
            "</td><td><b>" +
            escapeHtml(item.new_regular) +
            "</b></td><td>" +
            escapeHtml(item.old_stock) +
            "</td><td><b>" +
            escapeHtml(item.new_stock) +
            "</b></td></tr>"
          );
        })
        .join("");
    }

    const reviewBody = $("#hpl-body-review");
    if (reviewBody) {
      reviewBody.innerHTML = (data.review || [])
        .map((item) => {
          const top = (item.candidates || [])[0];
          const cand = top
            ? escapeHtml(top.name) + " (" + escapeHtml(top.sku || "بدون کد") + ")"
            : "—";
          return (
            "<tr data-index='" +
            item.row_index +
            "'><td>" +
            escapeHtml(item.sku || item.name) +
            "<div class='hpl-cand'>" +
            escapeHtml(item.name || "") +
            "</div></td><td>" +
            cand +
            "</td><td class='hpl-score'>" +
            Math.round((item.confidence || 0) * 100) +
            "٪</td><td>" +
            (top
              ? "<button type='button' class='hpl-btn hpl-btn-primary hpl-accept' data-index='" +
                item.row_index +
                "' data-id='" +
                escapeHtml(top.id) +
                "'>تأیید اتصال</button> "
              : "") +
            "<button type='button' class='hpl-btn hpl-skip' data-index='" +
            item.row_index +
            "'>رد</button></td></tr>"
          );
        })
        .join("");
    }

    const missBody = $("#hpl-body-not_on_site");
    if (missBody) {
      missBody.innerHTML = (data.not_on_site || [])
        .map((item) => {
          return (
            "<tr><td>" +
            escapeHtml(item.sku) +
            "</td><td>" +
            escapeHtml(item.name) +
            (item.hint ? "<div class='hpl-cand'>نزدیک‌ترین: " + escapeHtml(item.hint) + "</div>" : "") +
            "</td><td>" +
            escapeHtml(item.new_regular) +
            "</td><td>" +
            escapeHtml(item.new_stock) +
            "</td><td><button type='button' class='hpl-btn hpl-btn-primary hpl-create' data-index='" +
            item.row_index +
            "'>ست کردن در سایت</button> <button type='button' class='hpl-btn hpl-skip' data-index='" +
            item.row_index +
            "'>نادیده</button></td></tr>"
          );
        })
        .join("");
    }

    const nif = $("#hpl-body-not_in_file");
    if (nif) {
      nif.innerHTML = (data.not_in_file || [])
        .map((p) => {
          return (
            "<tr><td>" +
            escapeHtml(p.id) +
            "</td><td>" +
            escapeHtml(p.name) +
            "</td><td>" +
            escapeHtml(p.sku) +
            "</td><td>" +
            escapeHtml(p.regular_price) +
            "</td><td>" +
            escapeHtml(p.stock) +
            "</td></tr>"
          );
        })
        .join("");
    }
    bindScreenButtons();
    setStep(3);
  }

  async function preview() {
    toast("در حال غربالگری همه ردیف‌های فایل با همه محصولات سایت…");
    const data = await post("hpl_preview", {
      token: state.token,
      sheet: String(state.sheet),
      mapping: JSON.stringify(state.mapping),
      update_regular: $("#hpl-update-regular").checked ? "1" : "0",
      update_sale: $("#hpl-update-sale").checked ? "1" : "0",
      update_stock: $("#hpl-update-stock").checked ? "1" : "0",
    });
    renderScreen(data);
  }

  async function screenDo(doAction, index, productId) {
    const payload = { token: state.token, do: doAction };
    if (index != null) payload.index = String(index);
    if (productId) payload.product_id = String(productId);
    const data = await post("hpl_screen_action", payload);
    renderScreen(data);
  }

  function bindScreenButtons() {
    $$(".hpl-accept").forEach((btn) => {
      btn.onclick = () =>
        screenDo("accept", btn.getAttribute("data-index"), btn.getAttribute("data-id")).catch((err) =>
          toast(err.message)
        );
    });
    $$(".hpl-create").forEach((btn) => {
      btn.onclick = () => screenDo("create", btn.getAttribute("data-index")).catch((err) => toast(err.message));
    });
    $$(".hpl-skip").forEach((btn) => {
      btn.onclick = () => screenDo("skip", btn.getAttribute("data-index")).catch((err) => toast(err.message));
    });
  }

  async function applyAll() {
    setStep(4);
    let offset = 0;
    let updated = 0;
    const bar = $("#hpl-bar");
    const text = $("#hpl-progress-text");
    const publish = $("#hpl-publish-new") && $("#hpl-publish-new").checked ? "1" : "0";
    while (true) {
      const data = await post("hpl_apply", {
        token: state.token,
        offset: String(offset),
        publish_new: publish,
      });
      offset = data.offset;
      updated += Number(data.updated || 0);
      const pct = data.total ? Math.round((offset / data.total) * 100) : 100;
      bar.style.width = pct + "%";
      text.textContent = "اعمال شده: " + fmt(offset) + " از " + fmt(data.total);
      if (data.done) {
        const changed = data.stats && data.stats.changed ? data.stats.changed : updated;
        text.innerHTML =
          "<strong>" +
          HPL.i18n.done +
          "</strong><br>تعداد قیمت تغییر یافته: " +
          fmt(changed) +
          '<br><a class="hpl-btn hpl-btn-primary" href="admin.php?page=hesabdar-price-list-history">مشاهده تاریخچه و بازگشت</a>';
        toast(HPL.i18n.done);
        break;
      }
    }
  }

  function bindUpload() {
    const drop = $("#hpl-drop");
    const input = $("#hpl-file");
    if (!drop || !input) return;
    $("#hpl-browse").addEventListener("click", () => input.click());
    drop.addEventListener("click", (e) => {
      if (e.target.id !== "hpl-browse") input.click();
    });
    input.addEventListener("change", () => {
      if (input.files && input.files[0]) uploadFile(input.files[0]).catch((err) => toast(err.message));
    });
    ["dragenter", "dragover"].forEach((ev) => {
      drop.addEventListener(ev, (e) => {
        e.preventDefault();
        drop.classList.add("is-drag");
      });
    });
    ["dragleave", "drop"].forEach((ev) => {
      drop.addEventListener(ev, (e) => {
        e.preventDefault();
        drop.classList.remove("is-drag");
      });
    });
    drop.addEventListener("drop", (e) => {
      const file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
      if (file) uploadFile(file).catch((err) => toast(err.message));
    });
  }

  function bindWizard() {
    const sheet = $("#hpl-sheet");
    if (sheet) {
      sheet.addEventListener("change", () => {
        state.sheet = Number(sheet.value);
        fillMap();
      });
    }
    const previewBtn = $("#hpl-do-preview");
    if (previewBtn) previewBtn.addEventListener("click", () => preview().catch((err) => toast(err.message)));
    const applyBtn = $("#hpl-do-apply");
    if (applyBtn) applyBtn.addEventListener("click", () => applyAll().catch((err) => toast(err.message)));
    const back1 = $("#hpl-back-1");
    if (back1) back1.addEventListener("click", () => setStep(1));
    const back2 = $("#hpl-back-2");
    if (back2) back2.addEventListener("click", () => setStep(2));
    $$("#hpl-filters .hpl-btn").forEach((btn) => {
      btn.addEventListener("click", () => {
        $$("#hpl-filters .hpl-btn").forEach((b) => b.classList.remove("is-on"));
        btn.classList.add("is-on");
        const panel = btn.getAttribute("data-panel");
        $$(".hpl-screen-panels .hpl-table-wrap").forEach((el) => el.classList.add("is-hidden"));
        const target = $("#hpl-panel-" + panel);
        if (target) target.classList.remove("is-hidden");
      });
    });
    const acceptAll = $("#hpl-accept-all");
    if (acceptAll)
      acceptAll.addEventListener("click", () => screenDo("accept_all").catch((err) => toast(err.message)));
    const createAll = $("#hpl-create-all");
    if (createAll)
      createAll.addEventListener("click", () => {
        if (!confirm("همه کالاهای غایب به‌عنوان محصول جدید در سایت ست شوند؟")) return;
        screenDo("create_all").catch((err) => toast(err.message));
      });
  }

  function bindHistory() {
    $$(".hpl-rollback").forEach((btn) => {
      btn.addEventListener("click", async () => {
        if (!confirm("قیمت‌ها به نسخه قبل از این لیست برگردد؟")) return;
        try {
          const data = await post("hpl_rollback", { import_id: btn.getAttribute("data-id") });
          toast("بازگشت انجام شد: " + fmt(data.restored) + " محصول");
          location.reload();
        } catch (err) {
          toast(err.message);
        }
      });
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    if (typeof HPL === "undefined") return;
    bindUpload();
    bindTelegram();
    bindWizard();
    bindHistory();
  });
})();
