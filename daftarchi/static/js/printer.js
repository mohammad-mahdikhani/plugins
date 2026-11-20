/* Visual, per-browser printer setup. Measurements are millimetres, not screen pixels. */
(() => {
  "use strict";
  const KEY = "daftarchi-printer-v1";
  const DEFAULT = { width: 80, height: 0, margin: 3, font: 12 };
  const PRESETS = { "58": { width: 58, height: 0 }, "80": { width: 80, height: 0 }, a5: { width: 148, height: 210 }, a4: { width: 210, height: 297 } };
  const digits = value => String(value ?? "").replace(/[۰-۹]/g, d => "۰۱۲۳۴۵۶۷۸۹".indexOf(d)).replace(/[٠-٩]/g, d => "٠١٢٣٤٥٦٧٨٩".indexOf(d));
  function normalize(value) {
    const finite = (key, min, max) => {
      const parsed = Number(digits(value?.[key] ?? DEFAULT[key]));
      return Math.min(max, Math.max(min, Number.isFinite(parsed) ? Math.round(parsed) : DEFAULT[key]));
    };
    const width = finite("width", 40, 216);
    return { width, height: Number(digits(value?.height ?? 0)) === 0 ? 0 : finite("height", 50, 500),
      margin: Math.min(finite("margin", 0, 15), Math.floor((width - 24) / 2)), font: finite("font", 9, 20) };
  }
  function load() {
    try { return normalize(JSON.parse(localStorage.getItem(KEY) || "null")); } catch { return { ...DEFAULT }; }
  }
  let saved = load(), editor = null, draft = null, source = null, restoreFocus = null, activePrint = null;
  const fa = value => new Intl.NumberFormat("fa-IR").format(value);
  function pageStyle(config, measuredHeight) {
    const p = normalize(config);
    const height = p.height || Math.max(25, Math.ceil(measuredHeight * 10) / 10 + p.margin * 2 + 1);
    return `@page { size: ${p.width}mm ${height}mm; margin: ${p.margin}mm; }
      @media print {
        html, body { margin:0!important; padding:0!important; background:#fff!important; height:auto!important; min-height:0!important; }
        body > * { display:none!important; }
        body > #daftarchi-print-root { display:block!important; position:static!important; visibility:visible!important;
          width:${p.width - 2 * p.margin}mm!important; max-width:none!important; margin:0!important; padding:0!important; }
        #daftarchi-print-root, #daftarchi-print-root * { visibility:visible!important; }
        #daftarchi-print-root button, #daftarchi-print-root .no-print { display:none!important; }
      }`;
  }
  function sample() {
    const el = document.createElement("div");
    el.className = "invoice thermal";
    el.innerHTML = `<div class="invoice-sheet"><header class="invoice-head"><div class="invoice-brand"><strong>فروشگاه شما</strong></div><div class="invoice-meta"><b>فاکتور نمونه</b><span>۱۴۰۵/۰۸/۲۸</span></div></header>
      <p>نمونهٔ تنظیم ابعاد چاپ</p><table><thead><tr><th>کالا</th><th>مبلغ</th></tr></thead><tbody><tr><td>کالای نمونه × ۲</td><td>۲۰۰٬۰۰۰</td></tr><tr><td>کالایی با نام بلند برای بررسی جا شدن متن در عرض کاغذ × ۱</td><td>۱۵۰٬۰۰۰</td></tr></tbody></table>
      <p class="invoice-total"><span>قابل پرداخت</span><span>۳۵۰٬۰۰۰ تومان</span></p><p class="invoice-thanks">با تشکر از خرید شما</p><p class="printer-calibration">خط زیر دقیقاً ۲۰ میلی‌متر است</p><div class="printer-calibration-line"></div></div>`;
    return el;
  }
  function receipt(original) {
    const clone = (original || sample()).cloneNode(true);
    clone.removeAttribute("id");
    clone.querySelectorAll("[id]").forEach(el => el.removeAttribute("id"));
    clone.querySelectorAll("button, .no-print").forEach(el => el.remove());
    clone.classList.add("printer-receipt");
    return clone;
  }
  function cleanup() {
    document.getElementById("daftarchi-print-root")?.remove();
    document.getElementById("daftarchi-page-style")?.remove();
    activePrint = null;
  }
  function prepare(config = saved, original = document.getElementById("print-area")) {
    document.getElementById("daftarchi-print-root")?.remove();
    const p = normalize(config), root = document.createElement("div");
    root.id = "daftarchi-print-root";
    root.className = "printer-measuring";
    root.style.width = `${p.width - p.margin * 2}mm`;
    root.style.setProperty("--printer-font", `${p.font}px`);
    root.append(receipt(original));
    document.body.append(root);
    const height = root.getBoundingClientRect().height * 25.4 / 96;
    root.classList.remove("printer-measuring");
    let style = document.getElementById("daftarchi-page-style");
    if (!style) { style = document.createElement("style"); style.id = "daftarchi-page-style"; document.head.append(style); }
    style.textContent = pageStyle(p, height);
    return { ...p, measuredHeight: height };
  }
  function print(config = saved, original = document.getElementById("print-area")) {
    if (activePrint) return Promise.resolve();
    activePrint = { config: normalize(config), original };
    const fontsReady = document.fonts?.ready || Promise.resolve();
    return Promise.all([fontsReady, waitImages(original)]).then(async () => {
      if (!activePrint) return;
      prepare(activePrint.config, activePrint.original);
      const root = document.getElementById("daftarchi-print-root");
      await waitImages(root);
      if (!activePrint || !root?.isConnected) return;
      root.classList.add("printer-measuring");
      const height = root.getBoundingClientRect().height * 25.4 / 96;
      document.getElementById("daftarchi-page-style").textContent = pageStyle(activePrint.config, height);
      root.classList.remove("printer-measuring");
      activePrint.prepared = true;
      window.print();
    }).catch(error => { cleanup(); throw error; });
  }
  function waitImages(element) {
    return Promise.all([...(element?.querySelectorAll("img") || [])].map(img => new Promise((resolve, reject) => {
      let timer;
      const finish = () => {
        clearTimeout(timer); img.removeEventListener("load", finish); img.removeEventListener("error", finish);
        if (img.naturalWidth > 0) resolve();
        else reject(new Error("لوگوی رسید بارگذاری نشد. آدرس لوگو یا اتصال را بررسی و دوباره چاپ کنید."));
      };
      if (img.complete) { finish(); return; }
      img.addEventListener("load", finish, { once: true }); img.addEventListener("error", finish, { once: true });
      timer = setTimeout(finish, 10000);
    })));
  }
  function close() {
    editor?.remove(); editor = null; draft = null; source = null;
    restoreFocus?.focus(); restoreFocus = null;
  }
  function update() {
    if (!editor) return;
    draft = normalize(draft);
    for (const key of ["width", "height", "margin", "font"]) {
      editor.querySelectorAll(`[data-printer-field="${key}"]`).forEach(el => { el.value = key === "height" && !draft.height ? 100 : draft[key]; el.disabled = key === "height" && !draft.height; });
      const value = editor.querySelector(`[data-printer-value="${key}"]`);
      if (value) value.textContent = key === "height" && !draft.height ? "خودکار" : `${fa(draft[key])}${key === "font" ? "" : " میلی‌متر"}`;
    }
    editor.querySelector("[data-printer-auto]").checked = !draft.height;
    editor.querySelectorAll("[data-printer-preset]").forEach(button => {
      const p = PRESETS[button.dataset.printerPreset];
      button.setAttribute("aria-pressed", String(p.width === draft.width && p.height === draft.height));
    });
    const paper = editor.querySelector(".printer-paper");
    paper.style.width = `${draft.width}mm`;
    paper.style.height = draft.height ? `${draft.height}mm` : "auto";
    paper.style.padding = `${draft.margin}mm`;
    paper.style.setProperty("--printer-font", `${draft.font}px`);
    paper.style.transform = "none";
    const viewport = editor.querySelector(".printer-preview-viewport");
    const scale = Math.min(1, (viewport.clientWidth - 54) / paper.offsetWidth);
    const frame = editor.querySelector(".printer-paper-frame");
    frame.style.width = `${paper.offsetWidth * scale}px`;
    frame.style.height = `${paper.offsetHeight * scale}px`;
    paper.style.transform = `scale(${scale})`;
    paper.dataset.scale = scale;
    editor.querySelector(".printer-width-label").textContent = `${fa(draft.width)} میلی‌متر`;
    editor.querySelector(".printer-height-label").textContent = draft.height ? `${fa(draft.height)} میلی‌متر` : "ارتفاع خودکار";
    editor.querySelector(".printer-overflow").hidden = !draft.height || paper.scrollHeight <= paper.clientHeight + 1;
    editor.querySelector('[data-printer-drag="height"]').hidden = !draft.height;
  }
  function control(key, label, min, max) {
    return `<div class="printer-control"><label for="printer-${key}">${label} <output data-printer-value="${key}"></output></label><div><input id="printer-${key}" type="range" min="${min}" max="${max}" step="1" data-printer-field="${key}"><input type="number" min="${min}" max="${max}" step="1" inputmode="numeric" aria-label="${label} به عدد" data-printer-field="${key}"></div></div>`;
  }
  function open(original = document.getElementById("print-area")) {
    if (editor) return;
    restoreFocus = document.activeElement; source = original; draft = { ...saved };
    editor = document.createElement("div"); editor.id = "printer-editor"; editor.className = "printer-overlay";
    editor.innerHTML = `<section class="printer-dialog" role="dialog" aria-modal="true" aria-labelledby="printer-title" tabindex="-1"><header><div><h2 id="printer-title">تنظیم تصویری چاپ</h2><p>اندازه‌ها را تغییر بده و نتیجه را همان لحظه ببین.</p></div><button type="button" data-printer-action="close" aria-label="بستن تنظیمات چاپ">×</button></header>
      <div class="printer-layout"><div class="printer-controls"><div class="printer-presets">${Object.entries(PRESETS).map(([key, p]) => `<button type="button" data-printer-preset="${key}" aria-pressed="false"><span class="printer-paper-icon" style="width:${p.width === 58 ? 18 : p.width === 80 ? 24 : 30}px"></span><strong>${key === "a4" ? "A4" : key === "a5" ? "A5" : fa(p.width) + " میلی‌متر"}</strong></button>`).join("")}</div>
      ${control("width", "عرض کاغذ", 40, 216)}<label class="printer-auto"><input type="checkbox" data-printer-auto>ارتفاع خودکار برای کاغذ رولی</label>${control("height", "ارتفاع هر صفحه", 50, 500)}${control("margin", "حاشیه از هر طرف", 0, 15)}${control("font", "اندازهٔ نوشته", 9, 20)}
      <p class="printer-note">عرض کاغذ را با دستگیرهٔ کنار پیش‌نمایش هم می‌توانی تغییر بدهی. تنظیمات برای همین مرورگر ذخیره می‌شوند.</p><p class="printer-note">در پنجرهٔ چاپ، کاغذ متناسب با پرینتر، مقیاس ۱۰۰٪ و سرصفحه و پاصفحهٔ خاموش را انتخاب کن.</p></div>
      <div class="printer-preview"><p class="printer-width-label"></p><div class="printer-preview-viewport"><div class="printer-paper-frame"><div class="printer-paper"></div><button type="button" class="printer-resize-width" data-printer-drag="width" aria-label="تغییر عرض کاغذ با کشیدن یا کلیدهای جهت">↔</button><button type="button" class="printer-resize-height" data-printer-drag="height" aria-label="تغییر ارتفاع کاغذ با کشیدن یا کلیدهای جهت">↕</button></div></div><p class="printer-height-label"></p><p class="printer-overflow" hidden>فاکتور از یک صفحه بلندتر است و در چند صفحه چاپ می‌شود.</p><p class="printer-note">پیش‌نمایش کوچک‌شده است؛ اندازهٔ واقعی چاپ همان عدد میلی‌متری است.</p></div></div>
      <footer><span class="printer-feedback" role="status"></span><button type="button" data-printer-action="reset">بازنشانی</button><button type="button" data-printer-action="test">چاپ آزمایشی</button><button type="button" class="printer-save" data-printer-action="save">ذخیره و بستن</button></footer></section>`;
    editor.querySelector(".printer-paper").append(receipt(original));
    document.body.append(editor); update(); editor.querySelector('[data-printer-action="close"]').focus();
    editor.addEventListener("input", event => {
      const key = event.target.dataset.printerField;
      if (!key || event.target.value === "") return;
      draft[key] = Number(digits(event.target.value)); update();
    });
    editor.addEventListener("change", event => {
      if (event.target.hasAttribute("data-printer-auto")) { draft.height = event.target.checked ? 0 : 150; update(); }
    });
    editor.addEventListener("click", event => {
      const preset = event.target.closest("[data-printer-preset]");
      if (preset) { Object.assign(draft, PRESETS[preset.dataset.printerPreset]); update(); return; }
      const action = event.target.closest("[data-printer-action]")?.dataset.printerAction;
      if (action === "close" || event.target === editor) close();
      if (action === "reset") { draft = { ...DEFAULT }; update(); }
      if (action === "test") print(draft, source).catch(error => { if (editor) editor.querySelector(".printer-feedback").textContent = error.message; });
      if (action === "save") {
        saved = normalize(draft);
        try { localStorage.setItem(KEY, JSON.stringify(saved)); close(); }
        catch { editor.querySelector(".printer-feedback").textContent = "تنظیمات فعلاً اعمال شد؛ مرورگر اجازهٔ ذخیرهٔ دائمی نمی‌دهد."; }
      }
    });
    editor.addEventListener("keydown", event => {
      if (event.key === "Escape") { event.stopPropagation(); close(); return; }
      const key = event.target.dataset.printerDrag;
      if (key && ["ArrowLeft", "ArrowRight", "ArrowUp", "ArrowDown"].includes(event.key)) {
        event.preventDefault(); draft[key] += ["ArrowRight", "ArrowDown"].includes(event.key) ? 1 : -1; update();
      }
      if (event.key === "Tab") {
        const focusable = [...editor.querySelectorAll("button, input")].filter(el => !el.disabled && !el.hidden);
        if (event.shiftKey && document.activeElement === focusable[0]) { event.preventDefault(); focusable.at(-1)?.focus(); }
        else if (!event.shiftKey && document.activeElement === focusable.at(-1)) { event.preventDefault(); focusable[0]?.focus(); }
      }
    });
    editor.addEventListener("pointerdown", event => {
      const handle = event.target.closest("[data-printer-drag]"); if (!handle) return;
      event.preventDefault(); handle.setPointerCapture(event.pointerId);
      const key = handle.dataset.printerDrag, start = draft[key], coord = key === "width" ? event.clientX : event.clientY;
      const scale = Number(editor.querySelector(".printer-paper").dataset.scale) || 1;
      const move = next => { draft[key] = start + ((key === "width" ? next.clientX : next.clientY) - coord) * 25.4 / (96 * scale); update(); };
      const end = () => { handle.removeEventListener("pointermove", move); handle.removeEventListener("pointerup", end); handle.removeEventListener("pointercancel", end); };
      handle.addEventListener("pointermove", move); handle.addEventListener("pointerup", end); handle.addEventListener("pointercancel", end);
    });
  }
  window.addEventListener("beforeprint", () => {
    const original = activePrint ? activePrint.original : document.getElementById("print-area");
    if (!activePrint && original) prepare(saved, original);
  });
  window.addEventListener("afterprint", cleanup);
  window.addEventListener("resize", () => { if (editor) update(); });
  window.DaftarchiPrinter = { open, print, getSettings: () => ({ ...saved }), normalize, pageStyle };
})();
