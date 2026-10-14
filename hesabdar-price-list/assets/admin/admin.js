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

  async function uploadFile(file) {
    toast(HPL.i18n.uploading);
    const fd = new FormData();
    fd.append("file", file);
    const data = await post("hpl_upload", fd);
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
    setStep(2);
  }

  async function preview() {
    toast(HPL.i18n.previewing);
    const data = await post("hpl_preview", {
      token: state.token,
      sheet: String(state.sheet),
      mapping: JSON.stringify(state.mapping),
      update_regular: $("#hpl-update-regular").checked ? "1" : "0",
      update_sale: $("#hpl-update-sale").checked ? "1" : "0",
      update_stock: $("#hpl-update-stock").checked ? "1" : "0",
    });
    const stats = data.stats || {};
    $("#hpl-stats").innerHTML = [
      ["کل ردیف‌ها", stats.total],
      ["تطبیق‌شده", stats.matched],
      ["قابل تغییر", stats.changed],
      ["بدون تغییر", stats.unchanged],
      ["پیدا نشد", stats.missing],
      ["نامعتبر", stats.invalid],
    ]
      .map(([k, v]) => '<div class="hpl-stat"><b>' + fmt(v) + "</b><span>" + k + "</span></div>")
      .join("");

    const note = $("#hpl-match-note");
    if (note) {
      const missing = Number(stats.missing || 0);
      note.textContent = missing
        ? "ردیف‌های پیدا نشده معمولاً به‌خاطر متفاوت بودن کد فایل با SKU فروشگاه است. فیلتر «پیدا نشده» را بزنید."
        : "همه ردیف‌های فایل با محصولات فروشگاه تطبیق داده شد.";
    }
    const tbody = $("#hpl-preview tbody");
    tbody.innerHTML = (data.items || [])
      .map((item) => {
        const shop =
          item.status === "missing"
            ? escapeHtml(item.name || "") +
              (item.hint
                ? '<div class="hpl-hint-cell">نزدیک‌ترین در فروشگاه: ' + escapeHtml(item.hint) + "</div>"
                : "")
            : escapeHtml(item.product_name || item.name);
        return (
          '<tr class="is-' +
          escapeHtml(item.status) +
          '" data-status="' +
          escapeHtml(item.status) +
          '"><td>' +
          statusLabel(item.status) +
          "</td><td>" +
          escapeHtml(item.sku) +
          "</td><td>" +
          shop +
          "</td><td>" +
          (item.status === "missing" ? "—" : matchLabel(item.matched_by)) +
          "</td><td>" +
          escapeHtml(item.old_regular) +
          "</td><td><b>" +
          escapeHtml(item.new_regular) +
          "</b></td></tr>"
        );
      })
      .join("");
    setStep(3);
  }

  async function applyAll() {
    setStep(4);
    let offset = 0;
    let updated = 0;
    const bar = $("#hpl-bar");
    const text = $("#hpl-progress-text");
    while (true) {
      const data = await post("hpl_apply", { token: state.token, offset: String(offset) });
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
        const filter = btn.getAttribute("data-filter");
        $$("#hpl-preview tbody tr").forEach((tr) => {
          tr.style.display = filter === "all" || tr.getAttribute("data-status") === filter ? "" : "none";
        });
      });
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
    bindWizard();
    bindHistory();
  });
})();
