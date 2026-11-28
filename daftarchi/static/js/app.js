const PRIMARY = [
  ["pos", "فروش"],
  ["dashboard", "پیشخوان", "stock"],
  ["products", "کالا", "stock"],
  ["register", "صندوق"],
];
const MORE = [
  ["hub", "پیشخوان مرکزی", "admin"],
  ["inventory", "انبار", "stock"],
  ["checks", "چک", "admin"],
  ["accounts", "حسابداری", "admin"],
  ["invoices", "فاکتورها"],
  ["siteorders", "سفارش‌های سایت"],
  ["calendar", "تقویم"],
  ["customers", "مشتریان"],
  ["licenses", "لایسنس", "admin"],
  ["sms", "پیامک", "admin"],
  ["settings", "تنظیمات", "admin"],
  ["updates", "به‌روزرسانی", "admin"],
];
const NAV = [...PRIMARY, ...MORE];
const TITLES = Object.fromEntries(NAV.map(([id, title]) => [id, title]));
const PAY = { cash: "نقد", card: "کارت", gateway: "درگاه وب‌بیکری", credit: "نسیه" };
const SERVER_STORAGE_KEY = "daftarchi_server_url";
function normalizeServerUrl(raw) {
  let text = String(raw || "").trim();
  if (!text) return "";
  if (!/^https?:\/\//i.test(text)) text = `http://${text}`;
  try {
    const url = new URL(text);
    if (!url.hostname) return "";
    return `${url.protocol}//${url.host}`;
  } catch {
    return "";
  }
}
function savedServerUrl() {
  return normalizeServerUrl(localStorage.getItem(SERVER_STORAGE_KEY) || "");
}
function currentOrigin() {
  return normalizeServerUrl(`${location.protocol}//${location.host}`);
}
function redirectToSavedServer() {
  const target = savedServerUrl();
  if (!target || currentOrigin() === target) return false;
  const tail = `${location.pathname || "/"}${location.search || ""}${location.hash || ""}`;
  window.location.replace(`${target}${tail.startsWith("/") ? tail : `/${tail}`}`);
  return true;
}
function apiUrl(path) {
  const base = savedServerUrl();
  const route = String(path || "");
  if (!base || currentOrigin() === base) return route;
  return `${base}${route.startsWith("/") ? route : `/${route}`}`;
}
function allowCredit() {
  return state.shop?.allow_credit !== false;
}
const THEMES = [["light", "روشن"], ["dark", "تیره"], ["sand", "کرم"], ["forest", "سبز"]];
function themeId(value) {
  const text = String(value || state.settings?.ui_theme || state.shop?.theme || state.bootstrap?.theme || "").trim();
  return THEMES.some((row) => row[0] === text) ? text : "light";
}
function applyTheme(value) {
  const id = themeId(value);
  document.documentElement.dataset.theme = id;
  const color = { light: "#16315f", dark: "#101722", sand: "#8a5a2b", forest: "#1f6b3a" }[id];
  document.querySelector('meta[name="theme-color"]')?.setAttribute("content", color);
  try { localStorage.setItem("daftarchi-theme", id); } catch { /* ignore */ }
  return id;
}
function payQrBlock(url) {
  if (!url) return "";
  const src = `https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=6&data=${encodeURIComponent(url)}`;
  return `<div class="pay-qr-wrap"><img class="pay-qr" src="${src}" alt="QR پرداخت"><p class="hint">برای پرداخت با موبایل اسکن کنید</p></div>`;
}
function payUrlField(url, targetId) {
  if (!url) return "";
  return `<div class="field"><label>لینک پرداخت</label><input class="ltr" id="${targetId}" readonly value="${esc(url)}"><button type="button" class="btn" data-act="copy-text" data-target="${targetId}">کپی لینک</button></div>`;
}
function ean13Svg(code) {
  const digits = String(code || "").replace(/\D/g, "");
  if (digits.length !== 13) return "";
  const L = ["0001101", "0011001", "0010011", "0111101", "0100011", "0110001", "0101111", "0111011", "0110111", "0001011"];
  const G = ["0100111", "0110011", "0011011", "0100001", "0011101", "0111001", "0000101", "0010001", "0001001", "0010111"];
  const R = ["1110010", "1100110", "1101100", "1000010", "1011100", "1001110", "1010000", "1000100", "1001000", "1110100"];
  const parity = ["LLLLLL", "LLGLGG", "LLGGLG", "LLGGGL", "LGLLGG", "LGGLLG", "LGGGLL", "LGLGLG", "LGLGGL", "LGGLGL"];
  const left = parity[Number(digits[0])] || "LLLLLL";
  let bits = "101";
  for (let i = 0; i < 6; i += 1) bits += (left[i] === "L" ? L : G)[Number(digits[i + 1])];
  bits += "01010";
  for (let i = 7; i < 13; i += 1) bits += R[Number(digits[i])];
  bits += "101";
  const bars = [...bits].map((bit, i) => (bit === "1" ? `<rect x="${i}" y="0" width="1" height="48" fill="#000"/>` : "")).join("");
  return `<div class="invoice-barcode"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${bits.length} 48" preserveAspectRatio="none" aria-label="${esc(digits)}" fill="#000">${bars}</svg><span class="ltr">${esc(digits)}</span></div>`;
}
function gatewayPanel(sale) {
  const target = `pay-url-${sale.id}`;
  const url = sale.pay_url || "";
  return `${payQrBlock(url)}${payUrlField(url, target)}<p class="hint">صفحه درگاه در مرورگر باز شده است. لینک را برای مشتری بفرستید یا QR را اسکن کنید.</p>${url ? `<p><a href="${esc(url)}" target="_blank" rel="noopener">باز کردن درگاه</a></p>` : ""}`;
}
const LICENSE_STATUS = { paid: "پرداخت‌شده", pending: "در انتظار پرداخت", canceled: "لغو" };
const TYPES = { asset: "دارایی", liability: "بدهی", equity: "سرمایه", income: "درآمد", expense: "هزینه", person: "شخص" };
const KINDS = { sale: "فروش", purchase: "خرید", adjust: "تعدیل", price: "تغییر قیمت" };
const PAGE_SIZE = 60;

const state = {
  user: null,
  mustChange: false,
  initialPassword: "",
  passwordPrompted: false,
  bootstrap: null,
  authView: "login",
  billingPeriod: "3m",
  proPackId: "pro50",
  recovery: null,
  pendingEmail: "",
  verifyNote: "",
  authError: "",
  authDraft: null,
  backups: null,
  update: null,
  page: "dashboard",
  busy: false,
  modal: null,
  chart: "week",
  reportMetric: "total",
  productMetric: "total",
  focusBarcode: false,
  products: [],
  priceHistory: [],
  archivedProducts: [],
  showArchivedProducts: false,
  siteTerms: {},
  siteOrders: null,
  siteOrderPage: 1,
  siteOrderSearch: "",
  siteOrderStatus: "any",
  receiptMode: readReceiptMode(),
  sendMoadian: false,
  creditKind: "credit",
  checkDetails: null,
  productQuery: "",
  productSort: "name",
  posLimit: PAGE_SIZE,
  productLimit: PAGE_SIZE,
  inventoryLimit: PAGE_SIZE,
  inventoryQuery: "",
  invoiceQuery: "",
  invoiceDate: "",
  invoices: [],
  calendar: null,
  calYear: 0,
  calMonth: 0,
  today: null,
  cart: [],
  discountType: "amount",
  discountValue: "",
  buyerFirst: "",
  buyerLast: "",
  buyerPhone: "",
  posQuery: "",
  pending: [],
  dashboard: null,
  sales: [],
  registerInfo: null,
  inventory: null,
  accounts: null,
  journal: [],
  accountId: "",
  customers: [],
  licenses: null,
  access: null,
  plan: "12m",
  more: false,
  sms: [],
  smsCatalog: [],
  shopMode: "",
  tourDone: false,
  tourOn: false,
  tourStep: 0,
  centralHub: false,
  settings: null,
  users: [],
  importResult: null,
  dismissed: null,
  importFile: null,
  support: false,
  updating: false,
  justUpdated: false,
  supportTab: "chat",
  supportLog: [],
  lastError: "",
  scanHit: null,
  desktopIcon: false,
  installedApp: window.matchMedia("(display-mode: standalone)").matches || window.navigator.standalone === true,
};

function readReceiptMode() {
  try { const value = localStorage.getItem("daftarchi-receipt-mode"); return ["receipt", "invoice", "none"].includes(value) ? value : "receipt"; }
  catch { return "receipt"; }
}
function isSoldOut(product) {
  if (product.price_per_gram > 0) return false;
  return product.type === "variable" ? !(product.variations || []).some(item => item.in_stock) : Number(product.stock) <= 0;
}
function stockBadge(product) {
  if (product.price_per_gram > 0) return `<span class="stock-badge stock-calc">محاسبهٔ گرمی</span>`;
  const out = isSoldOut(product);
  const count = product.type === "variable" && (product.variations || []).some(item => !item.tracked && item.in_stock)
    ? "موجود" : `موجودی ${num(product.stock)}`;
  return `<span class="stock-badge ${out ? "stock-out" : Number(product.stock) < 2 ? "stock-low" : "stock-ready"}">${out ? "ناموجود · موجودی صفر" : count}</span>`;
}
const SITE_STATUS = { pending: "در انتظار پرداخت", processing: "در حال انجام", "on-hold": "در انتظار بررسی", completed: "تکمیل‌شده", cancelled: "لغوشده", refunded: "مستردشده", failed: "ناموفق", trash: "زباله‌دان" };
function siteAmount(amount, currency) {
  const label = { IRT: "تومان", IRR: "ریال", USD: "دلار", EUR: "یورو" }[currency] || currency;
  return `${numberFormat.format(Number(amount) || 0)} ${esc(label || "")}`;
}
function siteOrdersHtml() {
  const data = state.siteOrders || {}, methods = data.methods || [];
  return `<div class="stack"><section class="card"><h2 class="section-title">سفارش‌های ووکامرس</h2>
    <p class="hint">سفارش‌ها، وضعیت و روش پرداخت از سایت خوانده می‌شوند. اسنپ‌پی و ترب‌پی از درگاه فعال همان سایت انجام می‌شوند.</p>
    <form data-act="search-site-orders" class="row-actions"><input type="search" name="search" aria-label="جستجوی سفارش سایت" placeholder="شماره، نام یا اطلاعات سفارش" value="${esc(state.siteOrderSearch)}"><select name="status" aria-label="وضعیت سفارش"><option value="any">همه وضعیت‌ها</option>${Object.entries(SITE_STATUS).map(([key, label]) => `<option value="${key}" ${state.siteOrderStatus === key ? "selected" : ""}>${label}</option>`).join("")}</select><button class="btn primary" type="submit">جستجو / تازه‌سازی</button></form>
    <div class="site-methods"><strong>روش‌های فعال سایت:</strong> ${methods.map(item => `<span class="pill">${esc(item.title || item.id)}</span>`).join(" ") || "روشی دریافت نشده است"}</div>${data.methods_error ? `<p class="hint">${esc(data.methods_error)}</p>` : ""}
    </section><section class="card stack">${(data.orders || []).map(order => `<article class="site-order"><div class="row-actions"><strong>سفارش ${esc(order.number)}</strong><span class="pill">${esc(SITE_STATUS[order.status] || order.status)}</span><strong class="num">${siteAmount(order.total, order.currency)}</strong></div><p>${esc(order.customer || "مشتری مهمان")} <span class="ltr">${esc(order.phone)}</span></p><p><strong>${(order.items || []).map((item) => esc(item.name)).filter(Boolean).join("، ") || "—"}</strong></p><p class="hint">${esc(order.date.replace("T", " "))} · روش پرداخت: <strong>${esc(order.payment_title || "ثبت نشده")}</strong></p><details><summary>کالاهای سفارش</summary><table><tbody>${order.items.map(item => `<tr><td>${esc(item.name)} × ${num(item.qty)}</td><td class="num">${siteAmount(item.total, order.currency)}</td></tr>`).join("")}</tbody></table>${order.payment_url && safeImg(order.payment_url) ? `<a class="btn" href="${esc(order.payment_url)}" target="_blank" rel="noopener">ادامه پرداخت در سایت</a>` : ""}</details></article>`).join("") || `<p class="empty">سفارشی با این جستجو و وضعیت پیدا نشد.</p>`}
    <div class="row-actions"><button type="button" class="btn" data-act="site-orders-page" data-page="${state.siteOrderPage - 1}" ${state.siteOrderPage <= 1 ? "disabled" : ""}>قبلی</button><span>صفحه ${num(state.siteOrderPage)}</span><button type="button" class="btn" data-act="site-orders-page" data-page="${state.siteOrderPage + 1}" ${data.has_more ? "" : "disabled"}>بعدی</button></div><p class="hint">برای دیدن سفارش‌های تازه، «تازه‌سازی» را بزنید. این فهرست سند فروش حضوری جدید ثبت نمی‌کند.</p></section></div>`;
}
function termField(kind) {
  const product = state.modal?.product || {}, draft = state.modal?.termDraft?.[kind];
  const selected = draft || product[kind] || [], cache = state.siteTerms?.[kind] || {};
  const terms = [...new Map([...selected, ...(cache.terms || [])].map(item => [Number(item.id), item])).values()];
  const title = kind === "brands" ? "برند" : "دسته‌بندی";
  return `<div class="field" data-term-box="${kind}"><label for="terms-${kind}">${title} سایت (اختیاری)</label><select id="terms-${kind}" data-site-terms="${kind}" multiple size="4">${terms.map(item => `<option value="${item.id}" ${selected.some(term => Number(term.id) === Number(item.id)) ? "selected" : ""}>${esc(item.name)}</option>`).join("")}</select><p class="hint">می‌توانید هیچ موردی انتخاب نکنید. در ویندوز با Ctrl چند مورد انتخاب یا حذف می‌شود.</p><div class="row-actions"><button type="button" class="btn" data-act="load-site-terms" data-kind="${kind}">${cache.has_more ? "دریافت ادامه فهرست" : "دریافت از سایت"}</button><input id="new-term-${kind}" aria-label="نام ${title} جدید" placeholder="${title} جدید"><button type="button" class="btn" data-act="create-site-term" data-kind="${kind}">افزودن به سایت</button></div>${cache.error ? `<p class="hint">${esc(cache.error)}</p>` : ""}<span data-term-status role="status"></span></div>`;
}
async function loadSiteTerms(kind) {
  const box = document.querySelector(`[data-term-box="${kind}"]`);
  if (!box || box.dataset.busy === "1") return;
  box.dataset.busy = "1";
  box.querySelector("[data-term-status]").textContent = "در حال دریافت از سایت…";
  const modal = state.modal, previous = state.siteTerms[kind] || {}, page = previous.has_more ? previous.page + 1 : 1;
  try {
    const result = await api(`/api/site/terms/${kind}?page=${page}`);
    state.siteTerms[kind] = { ...result, terms: page > 1 ? [...previous.terms, ...result.terms] : result.terms };
  } catch (err) { state.siteTerms[kind] = { ...previous, error: `${kind === "brands" ? "برند در این سایت قابل دریافت نیست؛ پشتیبانی برند در ووکامرس و مجوز کلید را بررسی کنید. " : ""}${err.message}` }; }
  if (state.modal === modal && box.isConnected) box.outerHTML = termField(kind);
}
const $app = () => document.getElementById("app");

function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (ch) => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;",
  }[ch]));
}
const numberFormat = new Intl.NumberFormat("fa-IR");
function money(value) {
  return `${numberFormat.format(Math.round(Number(value) || 0))} تومان`;
}
function num(value) {
  return numberFormat.format(Number(value) || 0);
}
function asInt(value) {
  const clean = String(value ?? "")
    .replace(/[۰-۹]/g, (d) => "۰۱۲۳۴۵۶۷۸۹".indexOf(d))
    .replace(/[٠-٩]/g, (d) => "٠١٢٣٤٥٦٧٨٩".indexOf(d))
    .replace(/[^\d-]/g, "");
  const parsed = Number(clean);
  return Number.isFinite(parsed) ? Math.round(parsed) : 0;
}
function safeImg(src) {
  if (!src) return "";
  if (src.startsWith("/uploads/") || src.startsWith("https://") || src.startsWith("http://")) return src;
  return "";
}
function thumb(src, alt, small) {
  const safe = safeImg(src);
  if (!safe) return `<span class="ph ${small ? "sm" : ""}"></span>`;
  return `<img class="${small ? "thumb sm" : ""}" src="${esc(safe)}" alt="${esc(alt || "")}" loading="lazy" decoding="async">`;
}
function remedyFor(message) {
  const text = String(message || "");
  if (text.includes("قیمت هر گرم عطر تغییر")) return "قلم عطر را از سبد حذف کنید و با نرخ تازه دوباره وزن یا مبلغ را محاسبه کنید.";
  if (text.includes("قطع شد")) return "دفترچی را ببندید و دوباره باز کنید. اگر فروش ثبت شده، همان فاکتور را دوباره نزنید.";
  if (text.includes("ستون نام") || text.includes("نام کالا")) return "ردیف اول اکسل باید نام کالا و قیمت فروش باشد. شناسه اگر نیست، همان نام کالا کافی است.";
  if (text.includes("موجودی کالای متغیر")) return "در انبار، از لیست همان «نوع» متغیر را انتخاب کنید (مثلاً رنگ قرمز)، بعد موجودی را بزنید.";
  if (text.includes("ووکامرس") || text.includes("Consumer") || text.includes("کلید مصرف")) return "در تنظیمات، کلید ووکامرس را با ck_ بچسبانید و دسترسی را خواندن و نوشتن بگذارید.";
  if (text.includes("صندوق")) return "از زبانه صندوق، صندوق را باز کنید و فروش را دوباره بزنید.";
  if (text.includes("وارد نشده")) return "یک بار خارج شوید و دوباره وارد شوید.";
  if (text.includes("توکن") || text.includes("تلگرام") || text.includes("شناسه چت")) return "از چت بفرستید.";
  return "اگر دوباره پیش آمد، از چت بفرستید.";
}
function markUpdated() {
  try {
    sessionStorage.setItem("daftarchi-updated", "1");
    sessionStorage.setItem("daftarchi-show-reset", "1");
  } catch { /* private window */ }
  state.justUpdated = true;
  toast("آپدیت شدی", { ok: true });
}
function showUpdated() {
  const params = new URLSearchParams(location.search);
  if (params.get("updated") === "1") {
    params.delete("updated");
    const next = params.toString();
    history.replaceState(null, "", location.pathname + (next ? `?${next}` : "") + location.hash);
    toast("آپدیت شدی", { ok: true });
    return;
  }
  let flag = "";
  let reset = "";
  try {
    flag = sessionStorage.getItem("daftarchi-updated") || "";
    reset = sessionStorage.getItem("daftarchi-show-reset") || "";
    if (flag) sessionStorage.removeItem("daftarchi-updated");
  } catch { flag = ""; }
  state.justUpdated = reset === "1";
  if (flag) toast("آپدیت شدی", { ok: true });
}
function reopenAfterUpdate() {
  markUpdated();
  setTimeout(() => {
    window.close();
    setTimeout(() => location.replace(location.pathname + location.hash), 2200);
  }, 500);
}
function resetProgramButton() {
  if (!state.justUpdated) return "";
  return `<button type="button" class="btn primary" data-act="restart-app">ریست برنامه</button>`;
}
function compressCanvas(canvas, done) {
  let quality = 0.72;
  let data = canvas.toDataURL("image/jpeg", quality);
  while (data.length > 650000 && quality > 0.35) {
    quality -= 0.12;
    data = canvas.toDataURL("image/jpeg", quality);
  }
  done(data);
}
function loadCanvas() {
  if (window.html2canvas) return Promise.resolve(window.html2canvas);
  const current = document.querySelector('script[src*="/static/js/app.js"]')?.getAttribute("src") || "";
  const version = (current.match(/[?&]v=([^&]+)/) || [])[1] || "";
  return new Promise((resolve, reject) => {
    const script = document.createElement("script");
    script.src = `/static/js/html2canvas.min.js${version ? `?v=${version}` : ""}`;
    script.onload = () => (window.html2canvas ? resolve(window.html2canvas) : reject(new Error("اسکرین صفحه ساخته نشد")));
    script.onerror = () => reject(new Error("اسکرین صفحه ساخته نشد"));
    document.head.appendChild(script);
  });
}
async function captureApp() {
  const html2canvas = await loadCanvas();
  const node = document.getElementById("app") || document.body;
  const canvas = await html2canvas(node, {
    backgroundColor: "#f4f6fb",
    scale: 1,
    useCORS: true,
    logging: false,
    x: window.scrollX,
    y: window.scrollY,
    width: window.innerWidth,
    height: window.innerHeight,
    windowWidth: document.documentElement.clientWidth,
    windowHeight: window.innerHeight,
  });
  const data = await new Promise((resolve) => compressCanvas(canvas, resolve));
  if (!String(data).startsWith("data:image/") || data.length > 700000) throw new Error("اسکرین صفحه ساخته نشد");
  return data;
}
function withVip(title, vip) {
  return `${esc(title || "")}${vip ? " VIP" : ""}`;
}
function toast(message, options) {
  let el = document.getElementById("toast");
  if (!el) {
    el = document.createElement("div");
    el.id = "toast";
    document.body.appendChild(el);
  }
  const problem = Boolean(options && options.error);
  if (problem) state.lastError = String(message || "");
  el.classList.toggle("ok", Boolean(options && options.ok) && !problem);
  el.innerHTML = problem
    ? `<p>${esc(message)}</p><p class="toast-remedy">${esc(remedyFor(message))}</p>`
    : esc(message);
  el.classList.add("show");
  clearTimeout(toast.timer);
  toast.timer = setTimeout(() => el.classList.remove("show"), problem ? 12000 : 8000);
}
function hideToast() {
  const el = document.getElementById("toast");
  if (el) el.classList.remove("show");
  clearTimeout(toast.timer);
}
async function api(path, { method = "GET", body, form } = {}) {
  const cross = savedServerUrl() && currentOrigin() !== savedServerUrl();
  const opts = { method, credentials: cross ? "include" : "same-origin" };
  if (form) opts.body = form;
  else if (body !== undefined) {
    opts.headers = { "Content-Type": "application/json" };
    opts.body = JSON.stringify(body);
  }
  let res;
  try {
    res = await fetch(apiUrl(path), opts);
  } catch {
    throw new Error("ارتباط با دفترچی قطع شد. اگر برنامه تازه نصب شده، آن را ببندید و دوباره باز کنید.");
  }
  const raw = await res.text();
  let data = {};
  if (raw) {
    try { data = JSON.parse(raw); }
    catch { data = { error: raw.replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").trim().slice(0, 180) }; }
  }
  if (!data || typeof data !== "object" || Array.isArray(data)) data = {};
  if (!res.ok) {
    const message = data.error || (res.status === 401 ? "وارد نشده‌اید" : `درخواست انجام نشد (${res.status})`);
    const err = new Error(message);
    err.status = res.status;
    err.data = data;
    if (res.status === 401 && !String(path).includes("/api/auth/login") && !String(path).includes("/api/panel/update")) {
      state.user = null;
      render();
    }
    throw err;
  }
  return data;
}
async function loadUpdate() {
  try { state.update = await api("/api/update"); }
  catch { state.update = null; }
}
function isAdmin() { return state.user?.role === "admin"; }
function isStock() { return isAdmin() || state.user?.role === "stock"; }
function canSee(level) {
  if (!level) return true;
  if (level === "stock") return isStock();
  if (level === "admin") return isAdmin();
  return false;
}
function allowed(page) {
  const item = NAV.find((row) => row[0] === page);
  if (!item) return false;
  if (!canSee(item[2])) return false;
  return modeAllows(page);
}
async function guard(fn) {
  if (state.busy) return;
  state.busy = true;
  try { await fn(); }
  catch (err) {
    const message = err.message || "درخواست انجام نشد";
    if (!state.user) {
      state.authError = message;
      render();
    }
    toast(message, { error: true });
  }
  finally { state.busy = false; }
}

const loaders = {
  async dashboard() {
    const updatePromise = loadUpdate();
    const [summary, sales, registerInfo] = await Promise.all([
      api("/api/reports/summary"), api("/api/sales?limit=8"), api("/api/register"),
    ]);
    state.dashboard = summary;
    state.sales = sales.sales;
    state.registerInfo = registerInfo;
    updatePromise.then(() => { if (state.page === "dashboard") paintChrome(); });
  },
  async siteorders() {
    const query = new URLSearchParams({ page: state.siteOrderPage, search: state.siteOrderSearch, status: state.siteOrderStatus });
    const [orders, methods] = await Promise.allSettled([api(`/api/site/orders?${query}`), api("/api/site/payment-methods")]);
    if (orders.status === "rejected") throw orders.reason;
    state.siteOrders = { ...orders.value, methods: methods.status === "fulfilled" ? methods.value.methods : [], methods_error: methods.status === "rejected" ? methods.reason.message : "" };
  },
  async checks() {
    const [checks, bank] = await Promise.all([api("/api/checks"), api("/api/bank/sms")]);
    state.checks = checks;
    state.checkAlerts = checks.alerts || [];
    state.bankMessages = bank.messages || [];
  },
  async pos() {
    const requests = [
      api("/api/products"), api("/api/sales?status=pending&limit=20"), api("/api/register"),
    ];
    if (allowCredit()) requests.push(api("/api/checks/alerts"));
    const [products, pending, registerInfo, alerts] = await Promise.all(requests);
    state.products = stampProducts(products.products);
    state.posLimit = PAGE_SIZE;
    state.pending = pending.sales;
    state.registerInfo = registerInfo;
    state.checkAlerts = allowCredit() ? (alerts?.alerts || []) : [];
    syncCartStock();
  },
  async products() {
    const [active, archived, history] = await Promise.all([
      api("/api/products"),
      state.showArchivedProducts && isAdmin() ? api("/api/products?archived=1") : null,
      isAdmin() ? api("/api/products/price-history") : null,
    ]);
    state.products = stampProducts(active.products);
    if (archived) state.archivedProducts = stampProducts(archived.products);
    state.priceHistory = history?.changes || [];
    state.productLimit = PAGE_SIZE;
  },
  async inventory() {
    state.inventory = await api("/api/inventory");
    state.inventory.products = stampProducts(state.inventory.products);
    state.inventoryLimit = PAGE_SIZE;
  },
  async accounts() {
    const [accounts, journal] = await Promise.all([
      api("/api/accounts"),
      api("/api/journal" + (state.accountId ? `?account_id=${state.accountId}` : "")),
    ]);
    state.accounts = accounts;
    state.journal = journal.entries;
  },
  async register() {
    state.registerInfo = await api("/api/register");
  },
  async licenses() {
    state.licenses = await api("/api/licenses");
    if (state.licenses?.access) state.access = state.licenses.access;
  },
  async customers() {
    state.customers = (await api("/api/customers")).customers;
  },
  async sms() {
    const [wallet, people] = await Promise.all([api("/api/sms"), api("/api/customers")]);
    state.smsWallet = wallet;
    state.sms = wallet.messages;
    state.customers = people.customers;
  },
  async settings() {
    const updatePromise = loadUpdate();
    const catalogPromise = api("/api/sms/catalog").catch(() => ({ patterns: [] }));
    const [settings, users, catalog, backups] = await Promise.all([
      api("/api/settings"),
      api("/api/users"),
      catalogPromise,
      api("/api/backups").catch(() => ({ backups: [], auto: true, keep: 14 })),
    ]);
    state.settings = settings.settings;
    state.users = users.users;
    state.smsCatalog = catalog.patterns || [];
    state.backups = backups;
    updatePromise.then(() => { if (state.page === "settings") paintChrome(); });
  },
  async updates() {
    state.panelUpdate = await api("/api/panel/update");
  },
  async invoices() {
    const [sales, products, pro] = await Promise.all([
      api("/api/sales?limit=80"),
      api("/api/products"),
      isAdmin() ? api("/api/pro") : Promise.resolve(null),
    ]);
    state.invoices = sales.sales;
    state.products = stampProducts(products.products);
    state.pro = pro;
  },
  async calendar() {
    const today = state.today || {};
    const year = state.calYear || today.year || "";
    const month = state.calMonth || today.month || "";
    const query = year && month ? `?year=${year}&month=${month}` : "";
    const data = await api(`/api/calendar${query}`);
    state.calendar = data;
    state.today = data.today || state.today;
    state.calYear = data.year;
    state.calMonth = data.month;
  },
  async hub() {
    state.hub = await api("/api/hub");
  },
};

async function navigate(page) {
  if (!allowed(page)) page = homePage();
  if (!allowed(page)) page = NAV.map((row) => row[0]).find((id) => allowed(id)) || "pos";
  state.page = page;
  state.more = false;
  state.modal = null;
  if (location.hash !== `#${page}`) location.hash = page;
  await loaders[page]();
  render();
}

function cartSubtotal() {
  return state.cart.reduce((sum, line) => sum + line.price * line.qty, 0);
}
function cartDiscount() {
  const subtotal = cartSubtotal();
  if (state.discountType === "percent") {
    const pct = Math.max(0, Math.min(100, Math.round(asInt(state.discountValue))));
    return Math.floor((subtotal * pct + 50) / 100);
  }
  return Math.min(subtotal, Math.max(0, asInt(state.discountValue)));
}
function syncPos() {
  const read = (id, key) => {
    const el = document.getElementById(id);
    if (el) state[key] = el.value;
  };
  read("discount", "discountValue");
  read("discount-type", "discountType");
  read("buyer-first", "buyerFirst");
  read("buyer-last", "buyerLast");
  read("buyer-phone", "buyerPhone");
  read("pos-query", "posQuery");
}
function variationOf(product, variationId) {
  return (product?.variations || []).find((item) => item.id === Number(variationId)) || null;
}
function sellableStock(product, variation) {
  if (variation) {
    return { tracked: variation.tracked !== false, stock: variation.tracked === false ? 0 : Number(variation.stock || 0), inStock: !!variation.in_stock };
  }
  return { tracked: true, stock: Number(product?.stock || 0), inStock: Number(product?.stock || 0) > 0 };
}
function syncCartStock() {
  state.cart = state.cart.map((line) => {
    const product = state.products.find((item) => item.id === line.id);
    if (!product) return null;
    if (line.perfume) return { ...line, image: product.image, tracked: false };
    const variation = line.variationId ? variationOf(product, line.variationId) : null;
    if (line.variationId && !variation) return null;
    const limits = sellableStock(product, variation);
    if (!limits.inStock) return null;
    const name = variation ? `${product.name} — ${variation.label}` : product.name;
    const price = variation ? variation.price : product.price;
    const stock = limits.tracked ? limits.stock : line.qty;
    return { ...line, name, price, stock, tracked: limits.tracked, qty: limits.tracked ? Math.min(line.qty, stock) : line.qty };
  }).filter(Boolean);
}
function addProduct(product, variation) {
  if (!product) return false;
  if (product.price_per_gram > 0) { state.modal = { type: "perfume", product, perfumeBasis: "weight" }; render(); window.DaftarchiPerfume.refresh(state.modal); return false; }
  if (product.type === "variable" && !variation) {
    if (!product.variations?.length) { toast("این کالای متغیر هنوز نوعی ندارد. دوباره از ووکامرس دریافت کنید."); return false; }
    state.modal = { type: "variations", product };
    render();
    return false;
  }
  const limits = sellableStock(product, variation);
  if (limits.tracked && limits.stock <= 0) { toast("این کالا موجودی ندارد"); return false; }
  if (!limits.tracked && !limits.inStock) { toast("این متغیر ناموجود است"); return false; }
  const variationId = variation ? variation.id : 0;
  const line = state.cart.find((item) => item.id === product.id && (item.variationId || 0) === variationId);
  const cap = limits.tracked ? limits.stock : 1000000;
  if ((line?.qty || 0) + 1 > cap) { toast("موجودی کافی نیست"); return false; }
  if (line) line.qty += 1;
  else state.cart.push({
    id: product.id,
    variationId,
    name: variation ? `${product.name} — ${variation.label}` : product.name,
    price: variation ? variation.price : product.price,
    qty: 1,
    stock: limits.tracked ? limits.stock : 1000000,
    tracked: limits.tracked,
    image: (variation && variation.image) || product.image,
  });
  state.modal = null;
  state.focusBarcode = true;
  render();
  return true;
}
function matchCode(product, code) {
  if (product.barcode === code || product.sku === code) return { product, variation: null, parent: true };
  const variation = (product.variations || []).find((item) => item.barcode === code || item.sku === code);
  return variation ? { product, variation, parent: false } : null;
}
async function openScanHit(data) {
  if (data.kind === "sale") {
    await openSaleEdit(data.sale.id);
    if (state.modal?.type === "sale-edit" || state.modal?.type === "perfume-reissue") {
      toast("فاکتور باز شد");
      return true;
    }
    state.modal = { type: "receipt", sale: data.sale, items: data.items || [] };
    render();
    return true;
  }
  if (data.kind === "purchase") {
    state.modal = { type: "purchase-receipt", purchase: data.purchase };
    render();
    return true;
  }
  return false;
}
async function openDocumentFromBarcode(code) {
  const trimmed = String(code || "").trim();
  if (!trimmed) return;
  const data = await api(`/api/scan?code=${encodeURIComponent(trimmed)}`);
  if (await openScanHit(data)) return;
  toast("این بارکد فاکتور نیست", { error: true });
}
async function addByBarcode(code) {
  const trimmed = code.trim();
  if (!trimmed) return;
  const data = await api(`/api/scan?code=${encodeURIComponent(trimmed)}`);
  if (await openScanHit(data)) return;
  const product = data.product;
  if (!product) {
    toast("فاکتور یا کالایی با این بارکد نیست", { error: true });
    return;
  }
  if (!state.products.some((item) => item.id === product.id)) state.products = [...state.products, product];
  else state.products = state.products.map((item) => item.id === product.id ? product : item);
  const variation = data.variation_id
    ? (product.variations || []).find((item) => item.id === data.variation_id) || null
    : null;
  state.scanHit = {
    name: variation ? `${product.name} — ${variation.label}` : product.name,
    price: data.price,
    stock: data.stock,
  };
  const added = addProduct(product, variation);
  if (!added) render();
  const input = document.getElementById("barcode");
  if (input) input.value = "";
}

function updateBanner() {
  return "";
}
function supportHtml() {
  const log = (state.supportLog || []).filter((item) => item.kind !== "bug");
  const bubbles = log.length
    ? log.map((item) => `<article class="bubble"><p>${esc(item.body)}</p></article>`).join("")
    : `<p class="empty">پیامی نیست.</p>`;
  const sheet = state.support ? `<section class="support-sheet chat-sheet" data-act="stop"><div class="chat-log">${bubbles}</div><form data-act="send-support"><input type="hidden" name="kind" value="chat"><div class="chat-compose"><textarea name="body" required rows="2" placeholder="پیام" autocomplete="off"></textarea><button class="btn primary" type="submit">ارسال</button></div></form></section>` : "";
  return `${sheet}<button type="button" class="dock" data-act="toggle-support">${state.support ? "بستن" : "چت"}</button>`;
}
function authProblem() {
  if (!state.authError) return "";
  return `<p class="form-error">${esc(state.authError)}</p>`;
}
function authBackLink() {
  return `<p class="auth-links"><button type="button" class="text-link" data-act="show-login">بازگشت به ورود</button></p>`;
}
function prepareLoginBody(data) {
  let ident = String(data.ident || "").trim();
  const password = String(data.password || "");
  if (ident.toLowerCase() === "daftarchi") {
    throw new Error("شماره ۰۹۱۲۰۰۰۰۰۰۰ را در فیلد موبایل بگذارید؛ daftarchi فقط رمز است، نه شماره.");
  }
  if (!ident && password.toLowerCase() === "daftarchi") ident = "09120000000";
  return { ident, password };
}
function authServerBlock() {
  const saved = savedServerUrl();
  return `<section class="card auth-update-card auth-server-card">
    <h2 class="section-title">سرور مرکزی (LAN)</h2>
    <p class="hint">یک PC دفترچی را همیشه روشن بگذارید (همان SQLite). بقیه فقط مرورگر — بدون کپی پوشه data. عطر اشرافی و بقیه فروشگاه‌ها همین آدرس را می‌زنند.</p>
    ${saved ? `<p>اتصال ذخیره‌شده: <strong class="ltr">${esc(saved)}</strong></p>` : ""}
    <div class="field"><label>آدرس سرور</label><input class="ltr" name="server_url" inputmode="url" autocomplete="off" placeholder="http://192.168.1.10:8080" value="${esc(saved)}"></div>
    <div class="auth-actions">
      <button type="button" class="btn primary" data-act="connect-server">اتصال به سرور</button>
      <button type="button" class="btn quiet" data-act="clear-server">همین نصب (محلی)</button>
    </div>
  </section>`;
}
function authPanelUpdateBlock() {
  const row = state.panelUpdate || {
    current: state.bootstrap?.version || state.update?.current || "",
    reachable: false,
    update: false,
    latest: "",
    error: "",
  };
  const status = row.update
    ? `<p>نسخه <strong class="ltr">${esc(row.latest)}</strong> آماده است.</p>`
    : `<p>${row.reachable ? "همین نسخه آخر است." : esc(row.error || "ارتباط با سرور به‌روزرسانی برقرار نشد")}</p>`;
  return `<section class="card auth-update-card">
    <h2 class="section-title">به‌روزرسانی بدون ورود</h2>
    <p class="hint">روی همین نصب است؛ پوشه data دست نمی‌خورد.</p>
    ${row.current ? `<p>نسخه نصب‌شده: <strong class="ltr">${esc(row.current)}</strong></p>` : ""}
    ${status}
    <div class="auth-actions">
      <button type="button" class="btn" data-act="check-panel-update" ${state.updating ? "disabled" : ""}>بررسی آپدیت</button>
      <button type="button" class="btn primary" data-act="self-update" ${!row.update || state.updating ? "disabled" : ""}>${state.updating ? "در حال آپدیت..." : "آپدیت"}</button>
    </div>
  </section>`;
}
function authHtml() {
  const draft = state.authDraft || {};
  if (state.authView === "verify") {
    return `<div class="auth"><form class="card auth-card" data-act="verify">
      <div class="brand"><div class="mark">${logo()}</div><div><strong>تأیید جیمیل</strong><span>دفترچی</span></div></div>
      <p class="hint">${esc(state.verifyNote || "کد شش‌رقمی به جیمیل فرستاده شد. صندوق ورودی و هرزنامه را ببینید.")}</p>
      ${authProblem()}
      <div class="field"><label>جیمیل</label><input class="ltr" name="ident" value="${esc(state.pendingEmail || draft.ident || "")}" required></div>
      <div class="field"><label>کد ۶ رقمی</label><input class="ltr" name="code" inputmode="numeric" autocomplete="one-time-code" required></div>
      <div class="auth-actions">
        <button class="btn primary block" type="submit">تأیید حساب</button>
        <button class="btn quiet block" type="button" data-act="resend-verify">ارسال دوباره کد</button>
      </div>
      ${authBackLink()}
    </form></div>`;
  }
  if (state.authView === "phone-verify" || state.authView === "phone-enter") {
    const entering = state.authView === "phone-enter";
    return `<div class="auth"><form class="card auth-card" data-act="${entering ? "phone-enter" : "phone-verify"}">
      <div class="brand"><div class="mark">${logo()}</div><div><strong>${entering ? "ورود با کد" : "تأیید شماره"}</strong><span>دفترچی</span></div></div>
      <p class="hint">${esc(state.phoneNote || "کد شش‌رقمی از ملی‌پیامک وب‌بیکری به همین شماره می‌رود.")}</p>
      ${authProblem()}
      ${state.phoneCode ? `<p><strong class="num">${esc(state.phoneCode)}</strong></p>` : ""}
      <div class="field"><label>موبایل</label><input class="ltr" name="phone" required value="${esc(state.pendingPhone || draft.phone || "")}"></div>
      <div class="field"><label>کد ۶ رقمی</label><input class="ltr" name="code" inputmode="numeric" autocomplete="one-time-code" required value="${esc(state.phoneCode || "")}"></div>
      <div class="auth-actions">
        <button class="btn primary block" type="submit">${entering ? "ورود" : "تأیید شماره"}</button>
        <button class="btn quiet block" type="button" data-act="${entering ? "resend-phone-login" : "resend-phone"}">ارسال دوباره کد</button>
      </div>
      ${authBackLink()}
    </form></div>`;
  }
  if (state.authView === "phone-login") {
    return `<div class="auth"><form class="card auth-card" data-act="phone-login">
      <div class="brand"><div class="mark">${logo()}</div><div><strong>ورود با کد</strong><span>شماره ثبت‌شده</span></div></div>
      <p class="hint">فقط شماره‌ای که قبلاً ثبت و تأیید شده کد ورود می‌گیرد.</p>
      ${authProblem()}
      <div class="field"><label>موبایل</label><input class="ltr" name="phone" required placeholder="09..." value="${esc(draft.phone || draft.ident || "")}"></div>
      <div class="auth-actions">
        <button class="btn primary block" type="submit">دریافت کد</button>
      </div>
      ${authBackLink()}
    </form></div>`;
  }
  if (state.authView === "register") {
    return `<div class="auth"><form class="card auth-card" data-act="register">
      <div class="brand"><div class="mark">${logo()}</div><div><strong>ثبت‌نام</strong><span>با موبایل یا جیمیل</span></div></div>
      <p class="hint">موبایل یا جیمیل را بنویسید و یک رمز بگذارید. موبایل تا با کد ملی‌پیامک وب‌بیکری تأیید نشود وارد نمی‌شوید.</p>
      ${authProblem()}
      <div class="field"><label>موبایل</label><input class="ltr" name="phone" placeholder="09..." value="${esc(draft.phone || "")}"></div>
      <div class="field"><label>جیمیل</label><input class="ltr" name="email" type="email" placeholder="name@gmail.com" value="${esc(draft.email || "")}"></div>
      <div class="field"><label>رمز</label><input type="password" name="password" minlength="6" required value="${esc(draft.password || "")}"></div>
      <div class="auth-actions">
        <button class="btn primary block" type="submit">ثبت‌نام</button>
      </div>
      ${authBackLink()}
    </form></div>`;
  }
  if (state.authView === "reset") {
    const recovery = state.recovery || {};
    return `<div class="auth"><form class="card auth-card" data-act="reset">
      <div class="brand"><div class="mark">${logo()}</div><div><strong>بازیابی رمز</strong><span>دفترچی</span></div></div>
      ${authProblem()}
      <p class="hint">${recovery.delivery === "internal" ? "سامانه بیرونی فعال نیست؛ کد داخل خود سیستم ساخته شد." : "کد ارسال شد."} ${esc(recovery.detail || "")}</p>
      ${recovery.code ? `<p><strong class="num">${esc(recovery.code)}</strong></p>` : ""}
      <input type="hidden" name="ident" value="${esc(recovery.ident || "")}">
      <div class="field"><label>کد ۶ رقمی</label><input class="ltr" name="code" value="${esc(recovery.code || "")}" required></div>
      <div class="field"><label>رمز جدید</label><input type="password" name="password" minlength="6" required></div>
      <div class="auth-actions">
        <button class="btn primary block" type="submit">ثبت رمز جدید</button>
      </div>
      ${authBackLink()}
    </form></div>`;
  }
  if (state.authView === "forgot") {
    return `<div class="auth"><form class="card auth-card" data-act="forgot">
      <div class="brand"><div class="mark">${logo()}</div><div><strong>فراموشی رمز</strong><span>با جیمیل یا موبایل</span></div></div>
      ${authProblem()}
      <p class="hint">اگر جیمیل این حساب ثبت شده باشد، همان رمز فعلی به آن جیمیل می‌رود.</p>
      <div class="field"><label>موبایل یا جیمیل</label><input name="ident" required value="${esc(draft.ident || "")}"></div>
      <div class="auth-actions">
        <button class="btn primary block" type="submit">ارسال رمز</button>
      </div>
      ${authBackLink()}
    </form></div>`;
  }
  const ownerPhone = state.bootstrap?.owner_login_phone;
  const loginHint = ownerPhone
    ? `ورود مدیریت: موبایل <span class="ltr">${esc(ownerPhone)}</span> (رمز در فیلد پایین)`
    : `ورود اول مدیریت: موبایل <span class="ltr">09120000000</span> · رمز <span class="ltr">daftarchi</span> (رمز در فیلد پایین، نه موبایل)`;
  const identPh = ownerPhone ? `${ownerPhone} — موبایل` : "09120000000 — نه رمز";
  return `<div class="auth"><form class="card auth-card" data-act="login">
    <div class="brand"><div class="mark">${logo()}</div><div><strong>دفترچی</strong><span>${esc(state.bootstrap?.today?.label || "صندوق و حسابداری فروشگاه")}</span></div></div>
  <p class="hint">${loginHint}</p>
    ${authProblem()}
    <div class="field"><label>موبایل یا جیمیل</label><input class="ltr" name="ident" autocomplete="username" required value="${esc(draft.ident || "")}" placeholder="${esc(identPh)}"></div>
    <div class="field"><label>رمز</label><input type="password" name="password" autocomplete="current-password" required value="${esc(draft.password || "")}" placeholder="مثلاً daftarchi"></div>
    <div class="auth-actions">
      <button class="btn primary block" type="submit">ورود</button>
      <button class="btn quiet block" type="button" data-act="show-register">ثبت‌نام</button>
    </div>
    <p class="auth-links"><button type="button" class="text-link" data-act="show-forgot">رمز را فراموش کرده‌ام</button></p>
    ${updateBanner()}
    <p class="hint">نسخه ${esc(state.bootstrap?.version || state.update?.current || "")}</p>
  </form>${authPanelUpdateBlock()}${authServerBlock()}</div>`;
}
function showDesktopIcon() {
  return !state.installedApp;
}
function desktopIconButton(block) {
  if (!showDesktopIcon()) return "";
  if (mobileBrowser()) return mobileInstallButton(block);
  return `<button type="button" class="btn ${block ? "block" : ""}" data-act="desktop-icon">آیکون دفترچی روی دسکتاپ</button>`;
}
function appleMobileBrowser() {
  return /iPad|iPhone|iPod/i.test(navigator.userAgent) || (navigator.platform === "MacIntel" && navigator.maxTouchPoints > 1);
}
function mobileBrowser() {
  return appleMobileBrowser() || /Android/i.test(navigator.userAgent);
}
function mobileInstallButton(block = false) {
  if (state.installedApp) return "";
  return `<button type="button" class="btn install-app ${block ? "block" : ""}" data-act="install-app">نصب دفترچی</button>`;
}
async function installMobileApp() {
  const prompt = window.__desk?.event;
  if (prompt) {
    window.__desk.event = null;
    await prompt.prompt();
    if ((await prompt.userChoice).outcome === "accepted") {
      state.installedApp = true;
      render();
    }
    return;
  }
  if (!window.isSecureContext) {
    toast("برای نصب روی گوشی، دفترچی را با آدرس امن HTTPS باز کنید.");
    return;
  }
  toast(appleMobileBrowser()
    ? "در Safari، از منوی اشتراک‌گذاری «افزودن به صفحهٔ اصلی» را بزنید."
    : "در منوی مرورگر، «نصب برنامه» یا «افزودن به صفحهٔ اصلی» را بزنید.");
}
function shortcutFile() {
  const url = `${location.origin}/`;
  const ua = navigator.userAgent || "";
  if (/Windows/i.test(ua)) {
    return { name: "Daftarchi.url", type: "application/internet-shortcut", body: `[InternetShortcut]\r\nURL=${url}\r\n` };
  }
  if (/Mac OS X|Macintosh/i.test(ua)) {
    const body = `<?xml version="1.0" encoding="UTF-8"?>\n<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">\n<plist version="1.0"><dict><key>URL</key><string>${url}</string></dict></plist>\n`;
    return { name: "Daftarchi.webloc", type: "application/xml", body };
  }
  return {
    name: "Daftarchi.desktop",
    type: "application/x-desktop",
    body: `[Desktop Entry]\nType=Application\nName=دفترچی\nComment=صندوق فروش دفترچی\nExec=xdg-open ${url}\nTerminal=false\nCategories=Office;\n`,
  };
}
function downloadShortcut() {
  const file = shortcutFile();
  const blob = new Blob([file.body], { type: file.type });
  const link = document.createElement("a");
  link.href = URL.createObjectURL(blob);
  link.download = file.name;
  document.body.appendChild(link);
  link.click();
  link.remove();
  setTimeout(() => URL.revokeObjectURL(link.href), 1500);
}
async function createDesktopIcon() {
  try {
    const made = await api("/api/desktop-shortcut", { method: "POST" });
    if (made.ok) {
      toast(made.detail || "آیکون دفترچی روی دسکتاپ قرار گرفت.");
      return;
    }
  } catch {
    /* نسخه وب میانبر را دانلود می‌کند */
  }
  const held = window.__desk && window.__desk.event;
  if (held) {
    window.__desk.event = null;
    try {
      await held.prompt();
      const choice = await held.userChoice;
      if (choice && choice.outcome === "accepted") {
        state.installedApp = true;
        toast("آیکون دفترچی اضافه شد. از دسکتاپ یا منوی شروع بازش کنید.");
        render();
        return;
      }
      toast("آیکون ساخته نشد. هر وقت خواستید دوباره بزنید.");
      return;
    } catch {
      /* مرورگر دیالوگ نصب را نداد؛ فایل میانبر می‌سازیم */
    }
  }
  downloadShortcut();
  toast("فایل آیکون دانلود شد. آن را روی دسکتاپ بگذارید و باز کنید.");
}
function logo() {
  return `<svg width="32" height="32" viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="8" fill="#16315f"/><path d="M8.5 7.2h11.2L23.6 11v13.6a1.8 1.8 0 0 1-1.8 1.8H8.5a1.8 1.8 0 0 1-1.8-1.8V9a1.8 1.8 0 0 1 1.8-1.8z" fill="#f8f3e8"/><path d="M19.6 7.2l.1 3.6h3.8" fill="#e8dcc6"/><rect x="10.2" y="13" width="6.4" height="1.5" rx=".7" fill="#1b3a70"/><rect x="10.2" y="16.1" width="7.4" height="1.5" rx=".7" fill="#1b3a70"/><rect x="10.2" y="19.2" width="4.6" height="1.5" rx=".7" fill="#1b3a70"/><circle cx="19.4" cy="21.2" r="2.15" fill="none" stroke="#c99a3f" stroke-width="1.35"/></svg>`;
}
function shopBrand() {
  return {
    name: state.shop?.name || state.settings?.shop_name || "دفترچی",
    logo: state.shop?.logo || state.settings?.shop_logo || "",
  };
}
function invoiceSeal(big) {
  const logoUrl = safeImg(shopBrand().logo);
  if (logoUrl) return `<img class="seal ${big ? "big" : ""}" src="${esc(logoUrl)}" alt="لوگوی فروشگاه" loading="eager" decoding="sync">`;
  return `<span class="seal ${big ? "big" : ""}">${logo()}</span>`;
}
function invoiceSeals(count) {
  return Array.from({ length: count }, () => invoiceSeal(false)).join("");
}
function receiptHtml(modal) {
  const sale = modal.sale;
  const shop = shopBrand();
  const compact = (modal.layout || state.receiptMode) !== "invoice";
  const moadianLabel = { skipped: "شناسه کالا ندارد", failed: "ارسال نشد", sent: "در صف سامانه", pending: "در حال بررسی", success: "تأیید شد" }[sale.moadian_status] || "";
  const code = sale.invoice_code || `D${sale.id}`;
  const rows = (modal.items || []).filter((item) => Number(item.qty) > 0).map((item) => `<tr><td>${esc(item.name)} × ${num(item.qty)}${item.rate_per_gram ? `<small class="receipt-rate">هر گرم ${money(item.rate_per_gram)}</small>` : ""}</td><td class="num">${money(item.line_total)}</td></tr>`).join("");
  return `<div id="print-area" class="invoice thermal ${compact ? "receipt-compact" : "receipt-full"}">
    <div class="invoice-sheet">
      <header class="invoice-head">
        <div class="invoice-brand">${invoiceSeal(true)}<div><strong>${esc(shop.name)}</strong></div></div>
        <div class="invoice-meta"><b class="ltr">${esc(code)}</b>${compact ? "" : `<span>فاکتور ${num(sale.id)}</span>`}<strong class="receipt-date">${esc(sale.at_jalali || "")}</strong></div>
      </header>
      ${!compact && (sale.customer_name || sale.customer_phone) ? `<p class="invoice-buyer">${esc(sale.customer_name || "")} <span class="ltr">${esc(sale.customer_phone || "")}</span></p>` : ""}
      <table><thead><tr><th>کالا</th><th>مبلغ</th></tr></thead><tbody>${rows}</tbody></table>
      ${sale.discount ? `<p class="invoice-line"><span>تخفیف</span><span class="num">${money(sale.discount)}</span></p>` : ""}
      <p class="invoice-total"><span>قابل پرداخت</span><span class="num">${money(sale.payable)}</span></p>
      ${compact ? "" : `<p class="invoice-pay">${sale.credit_kind === "check" ? "چک · در انتظار وصول" : PAY[sale.payment_method] || ""}</p>`}
      ${sale.moadian_taxid ? `<p class="invoice-tax ltr">${esc(sale.moadian_taxid)}</p>` : ""}
      ${ean13Svg(sale.barcode) || `<p class="invoice-barcode"><span class="ltr">${esc(sale.barcode || code)}</span></p>`}
      ${compact ? "" : `<p class="invoice-thanks">با تشکر از خرید شما</p>`}
    </div>
  </div>
  ${modal.sms?.detail ? `<p class="hint">${esc(modal.sms.detail)}</p>` : ""}
  ${moadianLabel ? `<p class="hint">سامانه مودیان: ${esc(moadianLabel)}</p>` : ""}
  ${sale.moadian_error ? `<p class="hint">${esc(sale.moadian_error)}</p>` : ""}
  <div class="row-actions"><button type="button" class="btn" data-act="receipt-layout" data-layout="receipt" aria-pressed="${compact}">رسید کوتاه</button><button type="button" class="btn" data-act="receipt-layout" data-layout="invoice" aria-pressed="${!compact}">فاکتور کامل</button><button type="button" class="btn primary" data-act="print" title="یک نسخه، به اندازه کالاهای همین فروش">چاپ یک نسخه</button><button type="button" class="btn" data-act="printer-settings">تنظیم ابعاد چاپ</button><button type="button" class="btn quiet" data-act="clear-print-memory">پاک کردن حافظه چاپ</button>${sale.moadian_status === "success" ? "" : `<button type="button" class="btn" data-act="moadian-send" data-id="${sale.id}">ارسال به سامانه مودیان</button>`}${sale.moadian_reference ? `<button type="button" class="btn" data-act="moadian-inquiry" data-id="${sale.id}">استعلام مودیان</button>` : ""}</div>`;
}
function homePage() {
  if (state.centralHub && isAdmin()) return "hub";
  if (state.shopMode === "online") return "settings";
  if (state.shopMode === "offline" || state.shopMode === "hybrid") return "pos";
  return "dashboard";
}
function navItems() {
  if (!state.centralHub || !isAdmin()) return NAV;
  const rest = NAV.filter((row) => row[0] !== "hub");
  return [["hub", "فروشگاه‌ها", "admin"], ...rest];
}
function modeAllows(page) {
  if (page === "hub") return isAdmin();
  if (page === "licenses") return true;
  if (page === "siteorders") return state.shopMode !== "offline" && state.user?.role !== "stock";
  if (page === "checks" && !allowCredit()) return false;
  const allow = {
    offline: ["pos", "dashboard", "products", "register", "inventory", "checks", "invoices", "calendar", "customers", "sms", "settings", "updates"],
    hybrid: ["pos", "dashboard", "products", "register", "inventory", "checks", "invoices", "calendar", "customers", "sms", "settings", "updates"],
    online: ["products", "sms", "settings", "updates"],
  }[state.shopMode];
  if (!allow) return true;
  return allow.includes(page);
}
function visibleNav(list) {
  return list.filter((item) => {
    if (!canSee(item[2])) return false;
    if (item[0] === "licenses" && state.shopMode && state.access?.active) return false;
    return modeAllows(item[0]);
  });
}
const TOURS = {
  offline: [
    { page: "pos", tour: "barcode", title: "فروش حضوری", body: "بارکد را بگیرید یا روی کالا بزنید تا به سبد بیاید." },
    { page: "pos", tour: "buyer", title: "خریدار", body: "نام، فامیل و موبایل اختیاری است. اگر بنویسید در مشتریان حضوری می‌ماند." },
    { page: "pos", tour: "pay", title: "ثبت و چاپ", body: "کالا را که انتخاب کردید، کارت‌خوان را بزنید تا مبلغ روی دستگاه بیاید. مشتری کارت می‌کشد و رمز می‌زند. بعد از تأیید روی همین صفحه، اگر فاکتور می‌خواهید صدور فاکتور را بزنید." },
    { page: "register", tour: "nav-register", title: "صندوق", body: "قبل از اولین فروش، صندوق را باز کنید. آخر شیفت همین‌جا بسته می‌شود." },
    { page: "settings", tour: "pos-card", title: "کارت‌خوان", body: "شرکت دستگاه، آدرس و شماره پایانه را بنویسید و آزمایش اتصال را بزنید." },
  ],
  hybrid: [
    { page: "pos", tour: "pay", title: "فروش حضوری", body: "کارت‌خوان مبلغ را به دستگاه می‌فرستد. فاکتور فقط بعد از تأیید پرداخت صادر می‌شود." },
    { page: "products", tour: "nav-products", title: "کالاها", body: "کالا را اینجا بسازید یا از ووکامرس بگیرید تا صندوق و سایت یکی باشند." },
    { page: "settings", tour: "woo", title: "اتصال سایت", body: "آدرس سایت و کلید REST ووکامرس را ذخیره کنید. همگام‌سازی قیمت و موجودی روشن بماند." },
  ],
  online: [
    { page: "settings", tour: "woo-picture", title: "کلید را از کجا بگیرید", body: "در وردپرس همین مسیر را بروید: ووکامرس، پیکربندی، پیشرفته، REST API، افزودن کلید. دسترسی را خواندن و نوشتن بگذارید." },
    { page: "settings", tour: "woo-url", title: "آدرس سایت", body: "آدرس همان فروشگاه را با https بنویسید. مثال: https://shop.ir" },
    { page: "settings", tour: "woo-key", title: "Consumer key", body: "کلید مصرف‌کننده را بچسبانید. با ck_ شروع می‌شود." },
    { page: "settings", tour: "woo-secret", title: "Consumer secret", body: "رمز مصرف‌کننده را بچسبانید. با cs_ شروع می‌شود و فقط یک بار در وردپرس نشان داده می‌شود." },
    { page: "settings", tour: "woo-test", title: "آزمایش اتصال", body: "اول ذخیره کنید، بعد آزمایش ووکامرس را بزنید. اگر جواب داد، از زبانه کالا دریافت از ووکامرس را بزنید." },
  ],
};
function tourSteps() {
  return TOURS[state.shopMode] || [];
}
function readSetup(setup) {
  state.shopMode = setup?.mode || "";
  state.tourDone = setup?.tour === "1";
  state.tourOn = Boolean(state.shopMode) && !state.tourDone;
  state.tourStep = 0;
}
function wooFigure() {
  const steps = [
    "پیشخوان وردپرس",
    "منوی ووکامرس",
    "پیکربندی",
    "زبانه پیشرفته",
    "REST API",
    "افزودن کلید، خواندن و نوشتن",
  ];
  return `<div class="woo-figure" data-tour="woo-picture">
    <ol class="woo-steps">${steps.map((label, index) => `<li><b>${num(index + 1)}</b><span>${label}</span></li>`).join("")}</ol>
    <p class="hint">Consumer key با ck_ و Consumer secret با cs_ شروع می‌شود.</p>
  </div>`;
}
function modePickerHtml() {
  return `<div class="tour-shade"></div>
    <div class="tour-card" data-act="stop">
      <h2>این فروشگاه چطور کار می‌کند؟</h2>
      <p>یکی را انتخاب کنید. بعد فقط همان کارها، مرحله‌به‌مرحله، روشن می‌ماند و بقیه صفحه تار می‌شود.</p>
      <div class="mode-grid">
        <button type="button" class="btn mode-pick" data-act="pick-mode" data-mode="offline"><strong>فروشگاه حضوری</strong><span>فروش، فاکتور، کارت‌خوان و پرینتر. بدون سایت.</span></button>
        <button type="button" class="btn mode-pick" data-act="pick-mode" data-mode="hybrid"><strong>حضوری و آنلاین</strong><span>صندوق ساده، به‌علاوه اتصال به ووکامرس.</span></button>
        <button type="button" class="btn mode-pick" data-act="pick-mode" data-mode="online"><strong>فقط آنلاین</strong><span>فقط اتصال REST API ووکامرس، با راهنمای تصویری.</span></button>
      </div>
      <p class="row-actions"><button type="button" class="btn quiet" data-act="skip-mode-setup">رد شدن — شروع با فروشگاه حضوری</button></p>
    </div>`;
}
function holeShade(rect) {
  const pad = 8;
  const top = Math.max(0, Math.round(rect.top - pad));
  const left = Math.max(0, Math.round(rect.left - pad));
  const right = Math.min(window.innerWidth, Math.round(rect.right + pad));
  const bottom = Math.min(window.innerHeight, Math.round(rect.bottom + pad));
  const pane = "position:fixed;z-index:80;background:rgba(8,18,42,.72);backdrop-filter:blur(4px)";
  return `<div style="${pane};top:0;left:0;right:0;height:${top}px"></div>
    <div style="${pane};top:${top}px;left:0;width:${left}px;height:${bottom - top}px"></div>
    <div style="${pane};top:${top}px;left:${right}px;right:0;height:${bottom - top}px"></div>
    <div style="${pane};top:${bottom}px;left:0;right:0;bottom:0"></div>
    <div style="position:fixed;z-index:81;pointer-events:none;top:${top}px;left:${left}px;width:${Math.max(0, right - left)}px;height:${Math.max(0, bottom - top)}px;border-radius:14px;box-shadow:0 0 0 3px #f4f7fb"></div>`;
}
function paintTour() {
  const host = document.getElementById("tour-host");
  if (!host) return;
  if (!state.user || state.modal) {
    host.innerHTML = "";
    return;
  }
  if (!state.shopMode) {
    host.innerHTML = modePickerHtml();
    return;
  }
  if (!state.tourOn) {
    host.innerHTML = "";
    return;
  }
  const steps = tourSteps();
  const step = steps[state.tourStep];
  if (!step) {
    host.innerHTML = "";
    return;
  }
  const target = step.tour ? document.querySelector(`[data-tour="${step.tour}"]`) : null;
  if (target) target.scrollIntoView({ block: "center", inline: "nearest" });
  const rect = target?.getBoundingClientRect();
  const seen = rect && rect.width > 0 && rect.height > 0;
  const shade = seen ? holeShade(rect) : `<div class="tour-shade"></div>`;
  const last = state.tourStep >= steps.length - 1;
  const high = seen && rect.bottom > window.innerHeight * 0.62;
  host.innerHTML = `${shade}<div class="tour-card${high ? " top" : ""}" data-act="stop">
    <p class="hint">مرحله ${num(state.tourStep + 1)} از ${num(steps.length)}</p>
    <h2>${esc(step.title)}</h2>
    <p>${esc(step.body)}</p>
    <div class="row-actions">
      <button type="button" class="btn" data-act="tour-skip">رد شدن</button>
      <button type="button" class="btn" data-act="tour-back" ${state.tourStep ? "" : "disabled"}>قبلی</button>
      <button type="button" class="btn primary" data-act="tour-next">${last ? "تمام" : "بعدی"}</button>
    </div>
  </div>`;
}
async function skipModeSetup() {
  const saved = await api("/api/settings", { method: "PUT", body: { shop_mode: "offline", tour_done: "1" } });
  state.settings = saved.settings || state.settings;
  state.shopMode = "offline";
  state.tourDone = true;
  state.tourOn = false;
  state.tourStep = 0;
  await navigate(homePage());
}
async function chooseMode(mode) {
  if (!["offline", "hybrid", "online"].includes(mode)) return;
  const saved = await api("/api/settings", { method: "PUT", body: { shop_mode: mode, tour_done: "0" } });
  state.settings = saved.settings || state.settings;
  state.shopMode = mode;
  state.tourDone = false;
  state.tourOn = true;
  state.tourStep = 0;
  await navigate(tourSteps()[0]?.page || homePage());
}
async function finishTour() {
  state.tourOn = false;
  state.tourDone = true;
  try {
    await api("/api/settings", { method: "PUT", body: { tour_done: "1" } });
  } catch (err) {
    toast(err.message || "آموزش همین‌جا بسته شد", { error: true });
  }
  render();
}
async function goTour(delta) {
  const steps = tourSteps();
  const next = state.tourStep + delta;
  if (next < 0) return;
  if (next >= steps.length) {
    await finishTour();
    return;
  }
  state.tourStep = next;
  const page = steps[next].page;
  if (page && page !== state.page) await navigate(page);
  else render();
}
async function replayTour() {
  state.tourDone = false;
  state.tourOn = true;
  state.tourStep = 0;
  try {
    await api("/api/settings", { method: "PUT", body: { tour_done: "0" } });
  } catch (err) {
    toast(err.message || "آموزش دوباره باز شد", { error: true });
  }
  await navigate(tourSteps()[0]?.page || homePage());
}
function tabButton([id, title]) {
  return `<button type="button" class="tab" data-act="nav" data-page="${id}" data-tour="nav-${id}">${title}</button>`;
}
function brandMark() {
  const url = shopBrand().logo;
  if (url) return `<img src="${esc(url)}" alt="">`;
  return logo();
}
function webShell() {
  return `<div class="webapp" data-user="${state.user.id}" data-mode="${state.shopMode}">
    <header class="apphead">
      <div class="appbar">
        <div class="brand"><div class="mark">${brandMark()}</div><strong>دفترچی</strong></div>
        <div class="top-actions" id="app-status"></div>
      </div>
      <nav class="tabbar" aria-label="بخش‌ها">${visibleNav(navItems()).map(tabButton).join("")}</nav>
    </header>
    <div id="banner-host"></div>
    <main class="stage" id="view"></main>
    <div id="support-host"></div>
    <div id="modal-host"></div>
    <div id="tour-host"></div>
  </div>`;
}
function paintChrome() {
  const mark = document.querySelector(".appbar .mark");
  if (mark) mark.innerHTML = brandMark();
  document.querySelectorAll(".tab[data-page]").forEach((btn) => {
    const selected = btn.dataset.page === state.page;
    btn.classList.toggle("active", selected);
    if (selected) btn.setAttribute("aria-current", "page");
    else btn.removeAttribute("aria-current");
  });
  const open = state.registerInfo?.open;
  const pill = state.registerInfo
    ? `<span class="pill ${open ? "" : "warn"}">${open ? "صندوق باز" : "صندوق بسته"}</span>`
    : "";
  const access = state.access;
  const accessPill = access
    ? (access.active
      ? `<span class="pill">${access.vip ? "لایسنس VIP" : access.mode === "paid" ? "لایسنس فعال" : `${num(access.days_left)} روز رایگان`}</span>`
      : `<button type="button" class="pill warn" data-act="nav" data-page="licenses">مهلت رایگان تمام شد</button>`)
    : "";
  const status = document.getElementById("app-status");
  const todayPill = state.today?.label ? `<span class="pill date-pill">${esc(state.today.label)}</span>` : "";
  const updatePill = isAdmin() && state.update?.update ? `<button type="button" class="pill update" data-act="nav" data-page="updates">نسخه ${esc(state.update.version)} آماده</button>` : "";
  if (status) status.innerHTML = `${todayPill}${accessPill}${pill}${updatePill}${mobileBrowser() ? mobileInstallButton() : ""}<button type="button" class="btn" data-act="logout">خروج</button>`;
  const banner = document.getElementById("banner-host");
  if (banner) {
    banner.innerHTML = [
      state.mustChange && state.initialPassword ? `<div class="banner">رمز اولیه <bdi>${esc(state.initialPassword)}</bdi> <button type="button" class="btn" data-act="password-modal">تغییر رمز</button></div>` : "",
      updateBanner(),
    ].join("");
  }
  const modal = document.getElementById("modal-host");
  if (modal) modal.innerHTML = modalHtml();
  const support = document.getElementById("support-host");
  if (support) support.innerHTML = state.tourOn || !state.shopMode ? "" : supportHtml();
  paintTour();
}

function bars(summary) {
  const max = Math.max(1, ...summary.points.map((point) => point.total));
  return `<div class="bars">${summary.points.map((point) => {
    const height = Math.round((point.total / max) * 100);
    const label = summary.range === "day" && Number(point.label) % 3 !== 0
      ? ""
      : (typeof point.label === "number" ? num(point.label) : esc(point.label));
    return `<div class="bar" title="${esc(money(point.total))}"><i style="height:${height}%"></i><span>${label}</span></div>`;
  }).join("")}</div>`;
}
function canEditSale(sale) {
  return state.user && state.user.role !== "stock" && (!sale || (sale.status === "paid" && sale.payment_method !== "gateway"));
}
function saleProductLabel(sale) {
  return esc(sale.item_names || "—");
}
function salesTable(rows, actions) {
  if (!rows.length) return `<p class="empty">هنوز فروشی ثبت نشده.</p>`;
  return `<table><thead><tr><th>فاکتور</th><th>کالا</th><th>زمان</th><th>خریدار</th><th>روش</th><th>مبلغ</th>${actions ? "<th></th>" : ""}</tr></thead><tbody>
    ${rows.map((sale) => `<tr><td class="num">${num(sale.id)}</td><td>${saleProductLabel(sale)}</td><td>${esc(sale.at_jalali)}</td><td>${esc(sale.customer_name || "—")}</td><td>${PAY[sale.payment_method] || ""}</td><td class="num">${money(sale.payable)}</td>${actions && canEditSale(sale) ? `<td><button type="button" class="btn" data-act="edit-sale" data-id="${sale.id}">اصلاح</button></td>` : (actions ? "<td></td>" : "")}</tr>`).join("")}
  </tbody></table>`;
}
function invoiceReadHtml(invoice) {
  const lines = (invoice.lines || []).map((line, index) => {
    const fit = line.price_fit === "cost"
      ? "فی عکس با قیمت خرید کالا در برنامه و سایت یکی است."
      : line.price_fit === "sale"
        ? "فی عکس به قیمت فروش نزدیک است؛ برای ثبت خرید، قیمت خرید را چک کنید."
        : (line.product_id ? (line.price_note || "نام یا بارکد با کالای برنامه و سایت یکی شد.") : "در کالاهای برنامه و سایت پیدا نشد؛ به‌صورت کالای تازه ثبت می‌شود.");
    const prices = [line.shop_cost ? `خرید ${money(line.shop_cost)}` : "", line.shop_price ? `فروش ${money(line.shop_price)}` : ""].filter(Boolean).join(" · ");
    return `<div class="line"><div class="field"><label>کالا</label><input name="name-${index}" value="${esc(line.name)}"></div>
    <div class="grid-2"><div class="field"><label>تعداد</label><input name="qty-${index}" inputmode="numeric" value="${esc(line.qty)}"></div>
    <div class="field"><label>فی، تومان</label><input name="price-${index}" inputmode="numeric" value="${esc(line.unit_price)}"></div></div>
    <div class="field"><label>کالای موجود</label><select name="product-${index}"><option value="">کالای تازه</option>${stockChoices(state.products || []).replace(`value="${line.product_id}"`, `value="${line.product_id}" selected`)}</select></div>
    <p class="hint">${line.shop_name ? `${esc(line.shop_name)} · ` : ""}${prices ? `${prices} · ` : ""}${esc(fit)}</p></div>`;
  }).join("");
  const usageLine = invoice.usage?.free
    ? "این فاکتور سهمیه رایگان امروز این حساب بود. فاکتور بعدی از توکن Pro کم می‌شود."
    : `این بار ${num(invoice.usage?.tokens || invoice.tokens || 0)} توکن از پرو کم شد. ${num(invoice.usage?.remaining || 0)} توکن مانده. VIP از این موجودی جداست.`;
  return `<form data-act="apply-invoice"><h2 class="section-title">فاکتور خوانده‌شده</h2>
    <p class="hint">${esc(invoice.seller || "فروشنده نامشخص")}${invoice.invoice_no ? ` · ${esc(invoice.invoice_no)}` : ""}${invoice.date ? ` · ${esc(invoice.date)}` : ""}</p>
    <p class="hint">${usageLine}</p>
    <input type="hidden" name="seller" value="${esc(invoice.seller || "")}">
    <input type="hidden" name="count" value="${(invoice.lines || []).length}">
    ${lines}
    <p>قابل پرداخت روی عکس <strong class="num">${money(invoice.payable || 0)}</strong></p>
    <button class="btn primary" type="submit">ثبت خرید و افزودن موجودی</button></form>`;
}
function filteredInvoices() {
  const query = state.invoiceQuery.trim();
  const day = String(state.invoiceDate || "").trim();
  return (state.invoices || []).filter((sale) => {
    const hay = `${sale.id} ${sale.invoice_code || ""} ${sale.customer_name || ""} ${sale.customer_phone || ""} ${sale.item_names || ""}`;
    if (query && !hay.includes(query)) return false;
    if (day && !(sale.at_jalali || "").startsWith(day)) return false;
    return true;
  });
}
function jalaliDateField(name, value, required) {
  return `<div class="jalali-date-wrap"><input class="jalali-date" name="${esc(name)}" value="${esc(value || "")}" placeholder="۱۴۰۴/۰۹/۲۰" ${required ? "required" : ""} autocomplete="off"><button type="button" class="btn" data-act="open-datepicker" data-name="${esc(name)}">تقویم</button></div>`;
}
function closeJalaliPicker() {
  document.getElementById("cal-pop")?.remove();
}
function pickerGrid(data, selected) {
  const head = (data.weekdays || []).map((name) => `<span class="cal-head">${esc(name)}</span>`).join("");
  const days = (data.weeks || []).flat().map((cell) => {
    const cls = ["cal-day", cell.in_month ? "" : "out", cell.today ? "today" : "", cell.date === selected ? "selected" : ""].filter(Boolean).join(" ");
    return `<button type="button" class="${cls}" data-act="pick-day" data-date="${esc(cell.date)}">${num(cell.day)}</button>`;
  }).join("");
  return `<div class="cal-weekdays">${head}</div><div class="cal-grid">${days}</div>`;
}
async function fillJalaliPicker(pop, year, month, input) {
  const data = await api(`/api/calendar?year=${year}&month=${month}`);
  state.today = data.today || state.today;
  pop.dataset.year = String(data.year);
  pop.dataset.month = String(data.month);
  pop.querySelector(".cal-pop-title").textContent = data.label;
  pop.querySelector(".cal-pop-body").innerHTML = pickerGrid(data, input.value);
}
async function openJalaliPicker(input) {
  closeJalaliPicker();
  if (!input) return;
  const wrap = input.closest(".jalali-date-wrap") || input.parentElement;
  const pop = document.createElement("div");
  pop.id = "cal-pop";
  pop.className = "cal-pop";
  pop.innerHTML = `<div class="cal-nav"><button type="button" class="btn" data-act="picker-month" data-delta="-1">ماه قبل</button><strong class="cal-pop-title"></strong><button type="button" class="btn" data-act="picker-month" data-delta="1">ماه بعد</button></div><div class="cal-pop-body"></div>`;
  wrap.appendChild(pop);
  const today = state.today || {};
  const parts = String(input.value || "").split("/");
  const year = Number(parts[0]) || today.year;
  const month = Number(parts[1]) || today.month;
  try {
    await fillJalaliPicker(pop, year, month, input);
  } catch (err) {
    closeJalaliPicker();
    toast(err.message || "تقویم باز نشد", { error: true });
  }
}
function invoicesHtml() {
  const rows = filteredInvoices();
  const pro = state.pro;
  const reader = isAdmin() ? `<section class="card"><h2 class="section-title">خواندن عکس فاکتور</h2>
    <p class="hint">عکس فاکتور چاپی یا دست‌نویس با گپ‌جی‌پی‌تی وب‌بیکری خوانده می‌شود. هر حساب روزی یک فاکتور رایگان دارد. قلم‌ها با نام، بارکد و قیمت خرید/فروش کالاهای همین برنامه و سایت تطبیق داده می‌شوند. فاکتور بعدی از توکن Pro است؛ VIP این بخش را باز نمی‌کند.</p>
    <p>توکن مانده: <strong class="num">${num(pro?.tokens || 0)}</strong>${pro?.invoice_free ? " · سهمیه رایگان امروز باقی است" : ""}</p>
    <div class="row-actions">
      <label class="btn primary">انتخاب عکس<input id="invoice-photo" type="file" accept="image/*" hidden></label>
      <button type="button" class="btn" data-act="nav" data-page="licenses">خرید توکن Pro</button>
    </div>
  </section>` : "";
  return `<div class="stack">${reader}<section class="card"><h2 class="section-title">فاکتورهای ثبت‌شده</h2>
    <p class="hint">اگر کالا، تعداد، قیمت، تخفیف، خریدار یا روش پرداخت اشتباه ثبت شده، اصلاح را بزنید. اگر یک قلم اشتباه خریده شده، همان قلم را حذف کنید. اگر کل سفارش اشتباه است، داخل اصلاح «حذف فاکتور» را بزنید تا موجودی و حساب به قبل از فروش برگردد.</p>
    <div class="field"><label>بارکدخوان فاکتور</label><input id="invoice-barcode" data-barcode="invoice" class="ltr" autocomplete="off" placeholder="بارکد پایین رسید را بگیرید تا همان فاکتور باز شود"></div>
    <div class="grid-2">
      <input id="invoice-query" value="${esc(state.invoiceQuery)}" placeholder="شماره فاکتور، نام کالا، خریدار یا موبایل">
      <div class="field"><label>تاریخ شمسی</label><div class="jalali-date-wrap"><input class="jalali-date" id="invoice-date" value="${esc(state.invoiceDate)}" placeholder="همه روزها" autocomplete="off"><button type="button" class="btn" data-act="open-datepicker" data-target="invoice-date">تقویم</button></div></div>
    </div>
    <div id="invoice-table">${salesTable(rows, true)}</div></section></div>`;
}
function editTotals() {
  const modal = state.modal;
  const subtotal = (modal?.lines || []).reduce((sum, line) => sum + asInt(line.unit_price) * asInt(line.qty), 0);
  const discount = Math.min(subtotal, Math.max(0, asInt(modal?.discount)));
  return { subtotal, discount, payable: subtotal - discount };
}
function readSaleEdit() {
  const modal = state.modal;
  if (!modal || modal.type !== "sale-edit") return;
  modal.lines.forEach((line, index) => {
    const qty = document.querySelector(`[name="edit-qty-${index}"]`);
    const price = document.querySelector(`[name="edit-price-${index}"]`);
    if (qty) line.qty = Math.max(1, asInt(qty.value) || 1);
    if (price) line.unit_price = Math.max(0, asInt(price.value));
  });
  const take = (id, key) => {
    const el = document.getElementById(id);
    if (el) modal[key] = el.value;
  };
  take("edit-discount", "discount");
  take("edit-method", "method");
  take("edit-first", "first");
  take("edit-last", "last");
  take("edit-phone", "phone");
  take("edit-pos", "posRef");
}
function saleEditHtml() {
  const modal = state.modal;
  const totals = editTotals();
  const lines = modal.lines.map((line, index) => `<div class="line"><div><strong>${esc(line.name)}</strong></div>
    <div class="grid-2"><div class="field"><label>تعداد</label><input name="edit-qty-${index}" inputmode="numeric" value="${esc(line.qty)}"></div>
    <div class="field"><label>فی</label><input name="edit-price-${index}" inputmode="numeric" value="${esc(line.unit_price)}"></div></div>
    <button type="button" class="btn danger" data-act="edit-sale-remove" data-index="${index}">حذف این قلم</button></div>`).join("");
  return `<form data-act="save-sale"><h2 class="section-title">اصلاح فاکتور ${num(modal.sale.id)}</h2>
    <p class="hint">شماره فاکتور همان می‌ماند. اگر یک کالا اشتباه است، همان قلم را حذف کنید و اصلاح را ذخیره کنید. اگر کل سفارش اشتباه ثبت شده، حذف فاکتور موجودی و حساب را به قبل از این فروش برمی‌گرداند. فاکتور مودیان از اینجا بسته است.</p>
    ${lines || `<p class="empty">حداقل یک کالا لازم است.</p>`}
    <div class="field"><label>افزودن کالا</label><div class="row-actions"><select id="edit-add">${stockChoices(state.products || [])}</select><button type="button" class="btn" data-act="edit-sale-add">اضافه</button></div></div>
    <div class="grid-2">
      <div class="field"><label>تخفیف، تومان</label><input id="edit-discount" inputmode="numeric" value="${esc(modal.discount || "")}"></div>
      <div class="field"><label>روش پرداخت</label><select id="edit-method"><option value="cash" ${modal.method === "cash" ? "selected" : ""}>نقد</option><option value="card" ${modal.method === "card" ? "selected" : ""}>کارت</option>${allowCredit() ? `<option value="credit" ${modal.method === "credit" ? "selected" : ""}>نسیه</option>` : ""}</select></div>
    </div>
    <div class="grid-2">
      <div class="field"><label>نام</label><input id="edit-first" value="${esc(modal.first || "")}"></div>
      <div class="field"><label>نام خانوادگی</label><input id="edit-last" value="${esc(modal.last || "")}"></div>
    </div>
    <div class="field"><label>موبایل</label><input id="edit-phone" class="ltr" value="${esc(modal.phone || "")}" inputmode="tel" placeholder="${allowCredit() ? "برای نسیه لازم است" : "اختیاری"}"></div>
    <div class="field"><label>پیگیری کارت</label><input id="edit-pos" class="ltr" value="${esc(modal.posRef || "")}"></div>
    <p>جمع <strong class="num" id="edit-sub">${money(totals.subtotal)}</strong></p>
    <p>قابل پرداخت <strong class="num" id="edit-due">${money(totals.payable)}</strong></p>
    <div class="row-actions">
      <button class="btn primary" type="submit">ذخیره اصلاح</button>
      <button type="button" class="btn danger" data-act="delete-sale" data-id="${modal.sale.id}">حذف فاکتور و برگشت کالا</button>
    </div></form>`;
}
function reportTrend(current, previous, unit = "تومان") {
  const difference = current - previous;
  if (!previous) return `<span class="report-trend neutral">${current ? "دورهٔ قبل: صفر" : "بدون تغییر"}</span>`;
  const percent = Math.round(Math.abs(difference) * 1000 / Math.abs(previous)) / 10;
  return `<span class="report-trend ${difference > 0 ? "up" : difference < 0 ? "down" : "neutral"}">${difference > 0 ? "↑" : difference < 0 ? "↓" : "="} ${num(percent)}٪ ${difference > 0 ? "بیشتر" : difference < 0 ? "کمتر" : "تغییر"} <small>(${num(Math.abs(difference))} ${unit})</small></span>`;
}
function reportChart(points, series, amount = true) {
  const max = Math.max(1, ...points.flatMap(point => series.map(item => Number(point[item.key]) || 0)));
  const format = value => amount ? money(value) : `${num(value)} فاکتور`;
  return `<div class="report-legend">${series.map(item => `<span><i style="background:${item.color}"></i>${esc(item.label)}</span>`).join("")}</div>
    <div class="report-chart-scroll"><div class="report-chart" style="--report-columns:${points.length}">${points.map(point => {
      const description = `${point.label}؛ ${series.map(item => `${item.label}: ${format(point[item.key] || 0)}`).join("؛ ")}`;
      return `<div class="report-column" tabindex="0" aria-label="${esc(description)}"><div class="report-bar-group">${series.map(item => `<span class="report-bar" style="height:${Math.max(0, Number(point[item.key]) || 0) * 100 / max}%;background:${item.color}"></span>`).join("")}</div><small>${esc(point.label)}</small><div class="report-tooltip">${esc(description)}</div></div>`;
    }).join("")}</div></div>${points.some(point => series.some(item => point[item.key])) ? "" : `<p class="empty">در این بازه داده‌ای ثبت نشده است.</p>`}`;
}
function reportProducts(summary) {
  const quantity = state.productMetric === "qty";
  const rows = quantity ? summary.top_quantity || [] : summary.top || [];
  const max = Math.max(1, ...rows.map(row => quantity ? row.qty : row.total));
  return `<section class="card stack"><div class="report-title"><h2 class="section-title">محصولات پرفروش</h2><div class="seg"><button type="button" class="${quantity ? "" : "on"}" data-act="product-report-metric" data-metric="total">مبلغ</button><button type="button" class="${quantity ? "on" : ""}" data-act="product-report-metric" data-metric="qty">تعداد</button></div></div>
    ${rows.map((row, index) => `<div class="report-product"><div><span class="report-rank">${num(index + 1)}</span><strong>${esc(row.name)}</strong><span class="num">${quantity ? `${num(row.qty)} عدد` : money(row.total)}</span></div><div class="report-product-track"><i style="width:${(quantity ? row.qty : row.total) * 100 / max}%"></i></div><small>${num(row.qty)} عدد · دورهٔ قبل: ${quantity ? `${num(row.previous_qty || 0)} عدد` : money(row.previous_total || 0)}</small>${reportTrend(quantity ? row.qty : row.total, quantity ? row.previous_qty || 0 : row.previous_total || 0, quantity ? "عدد" : "تومان")}</div>`).join("") || `<p class="empty">هنوز محصولی در این بازه فروخته نشده است.</p>`}
    <p class="hint">مبلغ هر محصول پس از سهم تخفیف فاکتور محاسبه می‌شود؛ نوع‌های یک محصول با هم جمع می‌شوند.</p></section>`;
}
function dashboardHtml() {
  const data = state.dashboard;
  if (!data) return "";
  const summary = data[state.chart], previous = summary.previous || {};
  const labels = { day: "امروز", week: "این هفته", month: "این ماه" };
  const previousLabel = { day: "دیروز", week: "هفتهٔ قبل", month: "ماه قبل" }[state.chart];
  const cards = ["day", "week", "month"].map(key => `<button type="button" class="stat ${state.chart === key ? "active" : ""}" data-act="chart" data-range="${key}" aria-pressed="${state.chart === key}"><small>${labels[key]}</small><strong class="num">${money(data[key].total)}</strong><span>${num(data[key].count)} فاکتور</span></button>`).join("");
  const metrics = [["total", "فروش پس از تخفیف", true], ["count", "فاکتور پرداخت‌شده", false], ["average", "میانگین هر فاکتور", true], ["units", "تعداد کالای فروخته‌شده", false]];
  const metricCards = metrics.map(([key, title, amount]) => `<article class="report-stat"><small>${title}</small><strong>${amount ? money(summary[key] || 0) : num(summary[key] || 0)}</strong>${reportTrend(summary[key] || 0, previous[key] || 0, amount ? "تومان" : key === "count" ? "فاکتور" : "عدد")}<small>${previousLabel}: ${amount ? money(previous[key] || 0) : num(previous[key] || 0)}</small></article>`).join("");
  const count = state.reportMetric === "count";
  const series = [{ key: count ? "count" : "total", label: labels[state.chart], color: "var(--accent)" }, { key: count ? "previous_count" : "previous", label: previousLabel, color: "#a7b8d2" }];
  const hours = (summary.hours || []).map(point => ({ ...point, label: `${num(point.hour)}–${num(point.hour + 1)}` }));
  const peaks = [...(summary.hours || [])].filter(hour => hour.count).sort((a, b) => count ? b.count - a.count || a.hour - b.hour : b.total - a.total || a.hour - b.hour).slice(0, 3);
  const flow = summary.cashflow, oldFlow = previous.cashflow || {};
  const cashflow = flow ? `<section class="card stack"><h2 class="section-title">گردش وجوه ثبت‌شده</h2><div class="report-stats">${[["inflow", "ورودی وجوه"], ["outflow", "خروجی وجوه"], ["net", "خالص گردش وجوه"]].map(([key, title]) => `<article class="report-stat"><small>${title}</small><strong>${money(flow[key])}</strong>${reportTrend(flow[key], oldFlow[key] || 0)}<small>${previousLabel}: ${money(oldFlow[key] || 0)}</small></article>`).join("")}</div>${reportChart(summary.points, [{ key: "inflow", label: "ورودی", color: "#278367" }, { key: "outflow", label: "خروجی", color: "#cf7056" }])}<p class="hint">براساس اسناد صندوق، بانک و درگاه؛ انتقال بین این حساب‌ها دوباره شمرده نمی‌شود. فروش نسیه در فروش می‌آید و هنگام وصول در گردش وجوه دیده می‌شود. خالص گردش وجوه، سود نیست.</p></section>` : "";
  return `<div class="stack"><div class="stats">${cards}</div><div class="report-heading"><div><h1 class="section-title">گزارش ${esc(summary.label)}</h1><p class="hint">${esc(summary.comparison_note || "")} · تا ${esc(summary.as_of || "")}</p></div><button type="button" class="btn" data-act="refresh-reports">تازه‌سازی گزارش</button></div><div class="report-stats">${metricCards}</div>
    <section class="card stack"><div class="report-title"><h2 class="section-title">مقایسهٔ فروش با ${previousLabel}</h2><div class="seg"><button type="button" class="${count ? "" : "on"}" data-act="report-metric" data-metric="total">مبلغ فروش</button><button type="button" class="${count ? "on" : ""}" data-act="report-metric" data-metric="count">تعداد فاکتور</button></div></div>${reportChart(summary.points, series, !count)}</section>
    <div class="report-grid">${reportProducts(summary)}<section class="card stack"><h2 class="section-title">روش‌های پرداخت</h2>${(summary.payments || []).map(payment => `<div class="report-payment"><div><strong>${esc(PAY[payment.method] || payment.method)}</strong><span>${num(payment.count)} فاکتور</span><b>${money(payment.total)}</b></div><progress max="${Math.max(1, summary.total)}" value="${payment.total}"></progress></div>`).join("") || `<p class="empty">پرداختی ثبت نشده است.</p>`}<p class="hint">فقط فاکتورهای پرداخت‌شده؛ فاکتور لغوشده یا منتظر تأیید در نمودارها محاسبه نمی‌شود.</p></section></div>
    <section class="card stack"><h2 class="section-title">ساعت‌های پرفروش به وقت ایران</h2><div class="report-peaks">${peaks.map(point => `<span><strong>${num(point.hour)} تا ${num(point.hour + 1)}</strong>${money(point.total)} · ${num(point.count)} فاکتور</span>`).join("") || `<span class="hint">هنوز ساعت پرفروشی ثبت نشده است.</span>`}</div>${reportChart(hours, series, !count)}<p class="hint">ساعت‌ها در کل بازه جمع می‌شوند؛ رتبه‌بندی براساس ${count ? "تعداد فاکتور" : "مبلغ فروش"} است. برای دیدن عددها روی ستون بمانید یا با کلید Tab انتخابش کنید.</p></section>${cashflow}
    <section class="card"><h2 class="section-title">آخرین سفارشات</h2>${salesTable(state.sales, true)}</section></div>`;
}
function calendarHtml() {
  const data = state.calendar;
  if (!data) return "";
  const head = (data.weekdays || []).map((name) => `<span class="cal-head">${esc(name)}</span>`).join("");
  const days = (data.weeks || []).flat().map((cell) => {
    const cls = ["cal-day", cell.in_month ? "" : "out", cell.today ? "today" : "", cell.selected ? "selected" : ""].filter(Boolean).join(" ");
    const sales = cell.sales ? `<small>${num(cell.sales)} فروش</small>` : "";
    const checks = allowCredit() && cell.checks ? `<small class="warn-text">${num(cell.checks)} چک</small>` : "";
    const amount = cell.amount ? `<small class="num">${money(cell.amount)}</small>` : "";
    return `<button type="button" class="${cls}" data-act="cal-day" data-year="${cell.year}" data-month="${cell.month}" data-day="${cell.day}" ${cell.in_month ? "" : "disabled"}>
      <strong>${num(cell.day)}</strong>${sales}${amount}${checks}
    </button>`;
  }).join("");
  const sales = data.sales?.length
    ? salesTable(data.sales.map((row) => ({ ...row, customer_name: row.customer_name || "—" })), false)
    : `<p class="empty">در ${esc(data.selected)} فروشی نیست.</p>`;
  const checks = (data.checks || []).map((row) => `<li>${esc(row.number)} — ${esc(row.person)} — ${money(row.amount)} — ${row.kind === "out" ? "صادره" : "وارده"}</li>`).join("")
    || `<p class="empty">سررسید چکی در این روز نیست.</p>`;
  return `<div class="stack">
    <section class="card">
      <div class="cal-nav">
        <button type="button" class="btn" data-act="cal-month" data-delta="-1">ماه قبل</button>
        <h2 class="section-title">${esc(data.label)}</h2>
        <button type="button" class="btn" data-act="cal-month" data-delta="1">ماه بعد</button>
        <button type="button" class="btn" data-act="cal-today">امروز</button>
      </div>
      <p class="hint">تقویم دفترچی شمسی است. شنبه اول هفته است. روز را بزنید تا فروش${allowCredit() ? " و چک" : ""} همان روز بیاید.</p>
      <div class="cal-weekdays">${head}</div>
      <div class="cal-grid month">${days}</div>
    </section>
    <section class="card">
      <h2 class="section-title">فروش ${esc(data.selected)} — ${money(data.total || 0)}، ${num(data.count || 0)} فاکتور</h2>
      ${sales}
    </section>
    ${allowCredit() ? `<section class="card">
      <h2 class="section-title">چک‌های همین روز</h2>
      ${data.checks?.length ? `<ul>${checks}</ul>` : checks}
    </section>` : ""}
  </div>`;
}

function productSearchText(value) {
  return String(value ?? "").normalize("NFKC").toLowerCase()
    .replace(/[يى]/g, "ی").replace(/ك/g, "ک")
    .replace(/[\u064b-\u065f\u0670]/g, "")
    .replace(/[\u200c-\u200f\u0640]/g, "")
    .replace(/[۰-۹]/g, digit => "۰۱۲۳۴۵۶۷۸۹".indexOf(digit))
    .replace(/[٠-٩]/g, digit => "٠١٢٣٤٥٦٧٨٩".indexOf(digit))
    .replace(/\s+/g, " ").trim();
}
function productSearchTerms(query) {
  return productSearchText(query).split(" ").filter(Boolean);
}
function productMatches(product, terms) {
  const hay = product.hay || productHaystack(product);
  return terms.every(term => hay.includes(term));
}
function productHaystack(product) {
  const extra = (product.variations || []).map((item) => `${item.label} ${item.sku} ${item.barcode}`).join(" ");
  return productSearchText(`${product.name || ""} ${product.barcode || ""} ${product.sku || ""} ${product.tax_code || ""} ${extra}`);
}
function stampProducts(rows) {
  rows.forEach((product) => { product.hay = productHaystack(product); });
  return rows;
}
function moreControl(act, total, limit) {
  if (total <= limit) return "";
  const next = Math.min(PAGE_SIZE, total - limit);
  return `<p class="more-rows"><button type="button" class="btn" data-act="${act}">نمایش ${num(next)} تای بعدی · ${num(limit)} از ${num(total)}</button></p>`;
}
function variationLines(product) {
  if (product.type !== "variable" || !product.variations?.length) return "";
  return `<div class="var-list">${product.variations.map((item) => `<div class="var-line"><span>${esc(item.label)}</span><span class="num">${item.tracked ? `موجودی ${num(item.stock)}` : (item.in_stock ? "موجود" : "ناموجود")}</span><span class="num">${money(item.price)}</span></div>`).join("")}</div>`;
}
function stockChoices(products) {
  return products.map((product) => {
    if (product.type === "variable" && product.variations?.length) {
      return product.variations.map((item) => `<option value="${product.id}:${item.id}">${esc(product.name)} — ${esc(item.label)}</option>`).join("");
    }
    return `<option value="${product.id}">${esc(product.name)}</option>`;
  }).join("");
}
function posTiles() {
  const terms = productSearchTerms(state.posQuery);
  const rows = terms.length ? state.products.filter((product) => productMatches(product, terms)) : state.products;
  if (!rows.length) return `<p class="empty">کالایی پیدا نشد.</p>`;
  const shown = rows.slice(0, state.posLimit);
  return `<div class="product-grid">${shown.map((product) => {
    const variable = product.type === "variable" && product.variations?.length;
    const sellable = product.price_per_gram > 0 || (variable ? product.variations.some((item) => item.in_stock) : product.stock > 0);
    const price = product.price_per_gram > 0 ? `هر گرم ${money(product.price_per_gram)}` : variable ? `از ${money(product.price)}` : money(product.price);
    const stock = variable ? `${num(product.variations.length)} نوع` : `موجودی ${num(product.stock)}`;
    return `<button type="button" class="tile" data-act="add-cart" data-id="${product.id}" ${sellable ? "" : "disabled"}>
    ${thumb(product.image, product.name)}
    <span class="pname">${esc(product.name)}</span>
    <span class="num">${price}</span>
    ${stockBadge(product)}
  </button>`;
  }).join("")}</div>${moreControl("more-pos", rows.length, state.posLimit)}`;
}
function posHtml() {
  const subtotal = cartSubtotal();
  const discount = cartDiscount();
  const payable = subtotal - discount;
  const closed = state.registerInfo && !state.registerInfo.open;
  const lines = state.cart.length ? state.cart.map((line) => `<div class="line"><div><strong>${esc(line.name)}</strong><div class="muted num">${money(line.price)}${line.perfume ? ` · هر گرم ${money(line.perfume.rate)}` : ` · موجودی ${num(line.stock)}`}</div></div>
    <div><div class="qty"><button type="button" data-act="dec" data-id="${line.id}" data-variation="${line.variationId || 0}" data-perfume-key="${line.perfumeKey || ""}">−</button><span class="num">${num(line.qty)}</span><button type="button" data-act="inc" data-id="${line.id}" data-variation="${line.variationId || 0}" data-perfume-key="${line.perfumeKey || ""}">+</button></div>
    <button type="button" class="btn danger" data-act="remove-line" data-id="${line.id}" data-variation="${line.variationId || 0}" data-perfume-key="${line.perfumeKey || ""}">حذف</button></div></div>`).join("") : `<p class="empty">سبد خالی است. بارکد را بخوانید یا کالا را انتخاب کنید.</p>`;
  const pending = state.pending.length ? `<div><h3 class="section-title">پرداخت‌های باز</h3>${state.pending.map((sale) => `<div class="line"><span>فاکتور ${num(sale.id)} · ${money(sale.payable)}</span><button type="button" class="btn" data-act="open-gateway" data-id="${sale.id}">ادامه</button></div>`).join("")}</div>` : "";
  return `<div class="pos">
    <section class="card cart">
      ${closed ? `<div class="banner">صندوق بسته است. <button type="button" class="btn" data-act="nav" data-page="register">باز کردن صندوق</button></div>` : ""}
      ${checkAlertBanner()}
      <div class="field" data-tour="barcode"><label>بارکدخوان</label><input id="barcode" data-barcode="1" class="ltr" autocomplete="off" placeholder="بارکد کالا یا بارکد پایین فاکتور را بگیرید"></div>
      ${state.scanHit ? notice(`scan:${state.scanHit.name}:${state.scanHit.stock}`, `همین بارکد: <strong>${esc(state.scanHit.name)}</strong> · قیمت ${money(state.scanHit.price)} · موجودی ${num(state.scanHit.stock)}`) : ""}
      ${lines}
      <div class="grid-2">
        <div class="field"><label>تخفیف</label><input id="discount" value="${esc(state.discountValue)}" inputmode="numeric"></div>
        <div class="field"><label>نوع</label><select id="discount-type"><option value="amount" ${state.discountType === "amount" ? "selected" : ""}>مبلغ</option><option value="percent" ${state.discountType === "percent" ? "selected" : ""}>درصد</option></select></div>
      </div>
      <div data-tour="buyer">
      <div class="grid-2">
        <div class="field"><label>نام</label><input id="buyer-first" value="${esc(state.buyerFirst)}" autocomplete="off"></div>
        <div class="field"><label>نام خانوادگی</label><input id="buyer-last" value="${esc(state.buyerLast)}" autocomplete="off"></div>
      </div>
      <div class="field"><label>شماره موبایل</label><input id="buyer-phone" class="ltr" value="${esc(state.buyerPhone)}" inputmode="tel" autocomplete="off" placeholder="09..."></div>
      <p class="hint">${state.shopMode === "offline" ? "نام، نام خانوادگی و موبایل اختیاری است. اگر بنویسید در مشتریان حضوری ذخیره می‌شود." : `نام، نام خانوادگی و موبایل اختیاری است. اگر بنویسید، در مشتریان حضوری برنامه و سایت برای باشگاه مشتریان ذخیره می‌شود.${allowCredit() ? " برای نسیه موبایل لازم است." : ""}`}</p>
      </div>
      <p>جمع <strong class="num" id="pay-sub">${money(subtotal)}</strong></p>
      <p>تخفیف <strong class="num" id="pay-disc">${money(discount)}</strong></p>
      <p>قابل پرداخت <strong class="num" id="pay-due">${money(payable)}</strong></p>
      <div class="checkout-options"><label for="receipt-mode">بعد از پرداخت</label><select id="receipt-mode"><option value="receipt" ${state.receiptMode === "receipt" ? "selected" : ""}>رسید کوتاه</option><option value="invoice" ${state.receiptMode === "invoice" ? "selected" : ""}>فاکتور کامل</option><option value="none" ${state.receiptMode === "none" ? "selected" : ""}>عدم نمایش رسید</option></select><p class="hint">فاکتور در سوابق ذخیره می‌شود و بعداً قابل چاپ است.</p>
      ${state.shop?.moadian_enabled || state.settings?.moadian_enabled === "1" ? `<label class="check"><input type="checkbox" id="send-moadian" ${state.sendMoadian ? "checked" : ""}>این فاکتور به سامانهٔ مؤدیان ارسال شود (اختیاری)</label>` : `<p class="hint">ارسال به مؤدیان خاموش است؛ فعال‌سازی در تنظیمات مدیر اختیاری است.</p>`}</div>
      <div class="pay-row" data-tour="pay">
        <button type="button" class="btn primary" data-act="pay" data-method="cash">نقد</button>
        <button type="button" class="btn" data-act="pay" data-method="card">کارت‌خوان</button>
        ${allowCredit() ? `<button type="button" class="btn" data-act="pay" data-method="credit">نسیه / چک</button>` : ""}
        ${state.shop?.site_connected ? `<button type="button" class="btn" data-act="nav" data-page="siteorders">پرداخت‌های سایت / اسنپ‌پی / ترب‌پی</button>` : ""}
      </div>
      ${pending}
    </section>
    <section class="stack pos-catalog">
      <input id="pos-query" value="${esc(state.posQuery)}" placeholder="جستجوی نام، کد یا بارکد">
      <div id="pos-grid">${posTiles()}</div>
    </section>
  </div>`;
}

const productNameOrder = new Intl.Collator("fa", { numeric: true });
const sortedProductLists = new WeakMap();
const productSortOptions = {
  name: "الفبایی",
  newest: "جدیدترین",
  cheapest: "ارزان‌ترین",
  expensive: "گران‌ترین",
};

function sortedProducts(products, sort) {
  const cached = sortedProductLists.get(products);
  if (cached?.sort === sort) return cached.rows;
  const dates = sort === "newest"
    ? new Map(products.map((product) => [product.id, Date.parse(product.created_at) || 0]))
    : null;
  const rows = [...products].sort((first, second) => {
    let order = 0;
    if (dates) {
      order = dates.get(second.id) - dates.get(first.id) || second.id - first.id;
    } else if (sort === "cheapest") {
      order = first.price - second.price;
    } else if (sort === "expensive") {
      order = second.price - first.price;
    }
    return order || productNameOrder.compare(first.name, second.name) || first.id - second.id;
  });
  sortedProductLists.set(products, { sort, rows });
  return rows;
}

function firstTermName(product, kind) {
  return String((product?.[kind] || [])[0]?.name || "");
}
function uniqueTerms(kind) {
  const seen = new Map();
  for (const product of state.products || []) {
    for (const term of product[kind] || []) {
      const name = String(term.name || "").trim();
      if (!name) continue;
      const key = `${term.id || ""}:${name}`;
      if (!seen.has(key)) seen.set(key, { id: term.id || "", name });
    }
  }
  return [...seen.values()].sort((a, b) => a.name.localeCompare(b.name, "fa"));
}
function termNameOptions(kind) {
  return uniqueTerms(kind).map((row) => `<option value="${esc(row.name)}">`).join("");
}
function bulkTermOptions(kind) {
  return uniqueTerms(kind).map((row) => `<option value="${esc(String(row.id || row.name))}" data-name="${esc(row.name)}">${esc(row.name)}</option>`).join("");
}
function bulkPriceHtml() {
  const categories = bulkTermOptions("categories");
  const rows = state.priceHistory || [];
  const history = rows.length
    ? rows.map((row) => `<li class="price-history-row ${row.direction === "decrease" ? "decrease" : "increase"}"><time datetime="${esc(row.at || "")}" dir="ltr">${esc(row.at_jalali || "")}</time><strong>${esc(row.title || "")}</strong><span>${esc(row.detail || "")}</span></li>`).join("")
    : `<li class="muted">هنوز تغییر گروهی ثبت نشده.</li>`;
  return `<section class="card"><h2 class="section-title">تغییر قیمت گروهی</h2>
    <p class="hint">قیمت فروش کالاهای یک دسته یا یک برند را با درصد یا مبلغ ثابت، افزایش یا کاهش بدهید. اول روی کالا دسته و برند بگذارید.</p>
    <form data-act="bulk-price">
      <div class="grid-2">
        <div class="field"><label>بر اساس</label><select name="scope"><option value="category">دسته‌بندی</option><option value="brand">برند</option></select></div>
        <div class="field"><label>دسته یا برند</label><select name="term" required><option value="">انتخاب کنید</option>${categories}</select></div>
      </div>
      <div class="grid-2">
        <div class="field"><label>نوع تغییر</label><select name="direction"><option value="increase">افزایش</option><option value="decrease">کاهش</option></select></div>
        <div class="field"><label>روش</label><select name="mode"><option value="percent">درصد</option><option value="amount">مبلغ ثابت، تومان</option></select></div>
      </div>
      <div class="field"><label>مقدار</label><input name="value" inputmode="numeric" required autocomplete="off" placeholder="مثلاً ۱۰"></div>
      <button class="btn primary" type="submit">اعمال روی قیمت فروش</button>
    </form>
    <h3 class="section-title">سابقه تغییر قیمت</h3>
    <ul class="price-history">${history}</ul>
  </section>`;
}
function productRowActions(product, archived) {
  if (!isAdmin()) return "";
  if (archived) return `${product.woo_id || ["online", "hybrid"].includes(state.shopMode) ? `<button type="button" class="btn" data-act="draft-archived-product" data-id="${product.id}">پیش‌نویس در سایت</button>` : ""}<button type="button" class="btn danger" data-act="delete-archived-product" data-id="${product.id}">حذف</button>`;
  return `<button type="button" class="btn" data-act="edit-product" data-id="${product.id}">ویرایش قیمت و موجودی</button><button type="button" class="btn danger" data-act="archive-product" data-id="${product.id}">بایگانی</button>`;
}
function productRows() {
  const terms = productSearchTerms(state.productQuery);
  const archived = state.showArchivedProducts && isAdmin();
  const products = archived ? state.archivedProducts : state.products;
  const sorted = sortedProducts(products, state.productSort);
  const rows = terms.length ? sorted.filter((product) => productMatches(product, terms)) : sorted;
  if (!rows.length) return `<p class="empty">کالایی در این فهرست نیست.</p>`;
  const shown = rows.slice(0, state.productLimit);
  return `<table><thead><tr><th></th><th>نام</th><th>بارکد</th><th>مودیان</th><th>قیمت فروش</th><th>موجودی</th><th></th></tr></thead><tbody>
    ${shown.map((product) => `<tr><td>${thumb(product.image, product.name, true)}</td><td>${esc(product.name)}${product.price_per_gram > 0 ? `<div class="hint">هر گرم ${money(product.price_per_gram)}</div>` : ""}${product.woo_id ? ` <span class="pill">ووکامرس</span>` : ""}${product.type === "variable" ? ` <span class="pill">متغیر</span>` : ""}${variationLines(product)}</td><td class="ltr">${esc(product.barcode || "—")}</td><td class="ltr">${esc(product.tax_code || "—")}</td><td class="num">${product.type === "variable" ? `از ${money(product.price)}` : money(product.price)}</td><td>${stockBadge(product)}</td><td class="row-actions">${productRowActions(product, archived)}</td></tr>`).join("")}
  </tbody></table>${moreControl("more-products", rows.length, state.productLimit)}`;
}
function dismissedNotices() {
  if (state.dismissed) return state.dismissed;
  try {
    state.dismissed = JSON.parse(sessionStorage.getItem("daftarchi-dismiss") || "{}");
  } catch (error) {
    state.dismissed = {};
  }
  return state.dismissed;
}
function notice(id, inner) {
  if (!inner || dismissedNotices()[id]) return "";
  return `<div class="banner notice"><div class="notice-text">${inner}</div><button type="button" class="notice-x" data-act="dismiss" data-id="${esc(id)}" aria-label="بستن">×</button></div>`;
}
function checkAlertBanner() {
  if (!allowCredit()) return "";
  const alerts = state.checkAlerts || [];
  if (!alerts.length) return "";
  const id = `checks:${alerts.map((row) => row.id || row.number).join(",")}`;
  return notice(id, `سررسید چک: ${alerts.slice(0, 4).map((row) => `${esc(row.number)} تا ${esc(row.due_jalali)}`).join("، ")}`);
}
function productsHtml() {
  const imported = state.importResult;
  const banner = imported ? notice("import", `از اکسل ${num(imported.created)} کالا ثبت و ${num(imported.updated)} کالا به‌روز شد.
    ${imported.ids?.length ? `<button type="button" class="btn" data-act="woo-push-ids">ارسال همین‌ها به ووکامرس</button>` : ""}
    ${imported.errors?.length ? `<div>${imported.errors.map((row) => `<div>ردیف ${num(row.row)}: ${esc(row.error)}</div>`).join("")}</div>` : ""}`) : "";
  const woo = state.shopMode !== "offline";
  return `<div class="stack"><section class="card product-search"><label for="product-query">جست‌وجوی کالا با نام، بارکد یا کد</label><input type="search" id="product-query" value="${esc(state.productQuery)}" autocomplete="off" placeholder="نام کالا را اینجا بنویسید"><p class="hint">نتیجه با نوشتن نام نمایش داده می‌شود. برای تغییر قیمت و موجودی، دکمهٔ «ویرایش قیمت و موجودی» کنار کالا را بزنید.</p></section>${banner}
    ${isAdmin() ? `<section class="card"><h2 class="section-title">افزودن با بارکد</h2>
      <div class="field"><label>بارکدخوان</label><input id="add-barcode" data-barcode="add" class="ltr" autocomplete="off" placeholder="بارکد را بگیرید"></div>
      <p class="hint">بارکد را بگیرید. بعد نام، قیمت خرید، قیمت فروش و تعداد را بنویسید. اگر ووکامرس وصل باشد، کالا در سایت هم ساخته می‌شود.</p>
    </section>${bulkPriceHtml()}` : ""}
    <section class="card"><div class="row-actions">
      ${isAdmin() ? `<button type="button" class="btn primary" data-act="new-product">کالای جدید</button>` : ""}
      <a class="btn" href="/api/products/template">نمونه اکسل</a>
      <button type="button" class="btn" data-act="open-import">ورود با اکسل</button>
      ${woo ? `<button type="button" class="btn" data-act="woo-push">ارسال همه به ووکامرس</button><button type="button" class="btn" data-act="woo-pull">دریافت از ووکامرس</button>` : ""}
    </div><p class="hint">${woo ? "ستون‌های اکسل: نام کالا، SKU، موجودی، قیمت فروش." : "برای فروش حضوری همین‌جا کالا بسازید یا از اکسل بیاورید."}</p></section>
    <section class="card stack">
      <div class="row-actions">
        <label>مرتب‌سازی <select id="product-sort">${Object.entries(productSortOptions).map(([value, label]) => `<option value="${value}" ${state.productSort === value ? "selected" : ""}>${label}</option>`).join("")}</select></label>
        ${isAdmin() ? `<button type="button" class="btn" data-act="toggle-product-archive" aria-pressed="${state.showArchivedProducts}">بایگانی کالاها</button>` : ""}
      </div>
      ${state.showArchivedProducts && isAdmin() ? `<h2 class="section-title">کالاهای بایگانی‌شده</h2><p class="hint">کالاهای متصل، هنگام بایگانی در سایت پیش‌نویس می‌شوند. برای بایگانی‌های قدیمی، «پیش‌نویس در سایت» را بزنید. حذف، کالا را از این فهرست برمی‌دارد و در سایت به زباله‌دان می‌فرستد؛ سوابق فاکتورها باقی می‌مانند.</p>` : ""}
      <div id="product-table">${productRows()}</div>
    </section>
  </div>`;
}

function inventoryChoices() {
  const products = state.inventory?.products || [];
  const terms = productSearchTerms(state.inventoryQuery);
  return terms.length ? products.filter(product => productMatches(product, terms)) : products;
}
function inventoryFiltered() {
  return inventoryChoices();
}
function inventoryTable() {
  const rows = inventoryFiltered();
  const shown = rows.slice(0, state.inventoryLimit);
  const body = shown.map((product) => `<tr><td>${thumb(product.image, product.name, true)}</td><td>${esc(product.name)}${product.type === "variable" ? ` <span class="pill">متغیر</span>` : ""}${variationLines(product)}</td><td class="num">${money(product.price)}</td><td class="num">${money(product.cost)}</td><td>${stockBadge(product)}</td></tr>`).join("") || `<tr><td colspan="5">کالایی نیست.</td></tr>`;
  return `<table><thead><tr><th></th><th>کالا</th><th>قیمت</th><th>خرید</th><th>موجودی</th></tr></thead><tbody>${body}</tbody></table>${moreControl("more-inventory", rows.length, state.inventoryLimit)}`;
}
function inventoryHtml() {
  const data = state.inventory;
  if (!data) return "";
  const options = stockChoices(inventoryChoices().slice(0, PAGE_SIZE));
  const pay = data.pay_accounts.map((account) => `<option value="${esc(account.code)}">${esc(account.name)}</option>`).join("");
  return `<div class="stack"><section class="card"><h2 class="section-title">موجودی کالاها</h2><input id="inventory-query" value="${esc(state.inventoryQuery)}" placeholder="جستجوی کالا برای دیدن موجودی"><div id="inventory-box">${inventoryTable()}</div>
    ${isAdmin() ? `<details><summary>تعدیل موجودی</summary><form data-act="adjust"><div class="field"><label>کالا</label><select name="product_id">${options}</select><p class="hint">اگر کالا در فهرست نیست، نامش را بالا بنویسید.</p></div><div class="field"><label>موجودی جدید</label><input name="stock" inputmode="numeric" required></div><div class="field"><label>شرح</label><input name="note"></div><button class="btn primary" type="submit">ثبت</button></form></details>` : `<p class="hint">این حساب موجودی را می‌بیند. تغییر موجودی با مدیر است.</p>`}
    ${isAdmin() ? `<details><summary>رسید خرید</summary><form data-act="purchase"><div class="grid-2"><div class="field"><label>کالا</label><select name="product_id">${options}</select></div><div class="field"><label>تعداد</label><input name="qty" inputmode="numeric" required></div></div><div class="grid-2"><div class="field"><label>فی خرید</label><input name="unit_cost" inputmode="numeric" required></div><div class="field"><label>پرداخت از</label><select name="pay_code">${pay}</select></div></div><p class="hint">اگر فی خرید وارد شود، میانگین قیمت خرید عوض می‌شود و سند حسابداری می‌خورد. بعد از ثبت، بارکد روی رسید چاپ می‌شود.</p><button class="btn primary" type="submit">ثبت خرید</button></form></details>` : ""}
  </section>
  <section class="card"><h2 class="section-title">گردش اخیر</h2><table><thead><tr><th>زمان</th><th>کالا</th><th>نوع</th><th>از</th><th>به</th><th>شرح</th></tr></thead><tbody>
    ${data.logs.map((row) => `<tr><td>${esc(row.at_jalali)}</td><td>${esc(row.product_name)}</td><td>${KINDS[row.kind] || esc(row.kind)}</td><td class="num">${num(row.old_value)}</td><td class="num">${num(row.new_value)}</td><td>${esc(row.note || "")}</td></tr>`).join("") || `<tr><td colspan="6">گردشی نیست.</td></tr>`}
  </tbody></table></section></div>`;
}

function checksHtml() {
  if (!allowCredit()) return `<div class="stack"><section class="card"><p class="empty">در این نسخه چک و نسیه نیست.</p></section></div>`;
  const data = state.checks;
  if (!data) return "";
  const rows = (data.checks || []).map((row) => `<tr><td class="ltr">${esc(row.number)}</td><td>${esc(row.person)}</td><td class="num">${money(row.amount)}</td><td>${esc(row.due_jalali)}</td><td>${row.kind === "out" ? "صادره" : "وارده"}</td><td>${row.status === "collected" ? "وصول شد" : "باز"}</td><td>${row.status === "pending" ? `<button type="button" class="btn" data-act="collect-check" data-id="${row.id}">وصول و برداشت از صندوق</button>` : ""}</td></tr>`).join("") || `<tr><td colspan="7">چکی ثبت نشده.</td></tr>`;
  const messages = (state.bankMessages || []).map((row) => `<tr><td>${esc(row.at_jalali)}</td><td class="num">${money(row.amount)}</td><td>${esc(row.raw)}</td></tr>`).join("") || `<tr><td colspan="3">پیامکی ثبت نشده.</td></tr>`;
  return `<div class="stack">${checkAlertBanner()}
    <section class="card"><h2 class="section-title">چک</h2>
      <form data-act="save-check"><div class="grid-2"><div class="field"><label>شماره چک</label><input name="number" required></div><div class="field"><label>طرف</label><input name="person" required></div></div>
      <div class="grid-2"><div class="field"><label>مبلغ</label><input name="amount" inputmode="numeric" required></div><div class="field"><label>سررسید شمسی</label>${jalaliDateField("due_on", state.today?.date || "", true)}</div></div>
      <div class="field"><label>نوع</label><select name="kind"><option value="in">وارده؛ بعد از وصول از صندوق به بانک می‌رود</option><option value="out">صادره؛ بعد از وصول از صندوق کم می‌شود</option></select></div>
      <button class="btn primary" type="submit">ثبت چک</button></form>
      <table><thead><tr><th>شماره</th><th>طرف</th><th>مبلغ</th><th>سررسید</th><th>نوع</th><th>وضعیت</th><th></th></tr></thead><tbody>${rows}</tbody></table>
    </section>
    <section class="card"><h2 class="section-title">پیامک بانکی</h2><p class="hint">متن پیامک واریز را اینجا بگذارید. مبلغ به تومان در حساب بانک ثبت می‌شود و پیامک تکراری دوباره نمی‌خورد.</p>
      <form data-act="save-bank-sms"><div class="field"><label>متن پیامک</label><textarea name="text" required></textarea></div><button class="btn primary" type="submit">ثبت واریزی</button></form>
      <table><thead><tr><th>زمان</th><th>مبلغ</th><th>متن</th></tr></thead><tbody>${messages}</tbody></table>
    </section>
  </div>`;
}

function accountsHtml() {
  const data = state.accounts;
  if (!data) return "";
  const options = data.accounts.map((account) => `<option value="${account.id}">${esc(account.code)} ${esc(account.name)}</option>`).join("");
  return `<div class="stack"><section class="card"><p>جمع بدهکار ${money(data.debit_total)} · جمع بستانکار ${money(data.credit_total)} · ${data.balanced ? "تراز است" : "تراز نیست"}</p>
    <table><thead><tr><th>کد</th><th>حساب</th><th>نوع</th><th>مانده</th><th>سمت</th></tr></thead><tbody>
      ${data.accounts.map((account) => `<tr class="clickable ${String(state.accountId) === String(account.id) ? "selected" : ""}" data-act="filter-account" data-id="${account.id}"><td class="ltr">${esc(account.code)}</td><td>${esc(account.name)}</td><td>${TYPES[account.type] || ""}</td><td class="num">${money(account.amount)}</td><td>${esc(account.side)}</td></tr>`).join("")}
    </tbody></table>
    <details open><summary>سند دستی</summary><form data-act="journal"><div class="field"><label>شرح</label><input name="memo" required></div><div class="grid-2"><div class="field"><label>حساب</label><select name="account_id">${options}</select></div><div class="field"><label>حساب مقابل</label><select name="counter_id">${options}</select></div></div><div class="grid-2"><div class="field"><label>مبلغ</label><input name="amount" inputmode="numeric" required></div><div class="field"><label>این حساب</label><select name="side"><option value="debit">بدهکار شود</option><option value="credit">بستانکار شود</option></select></div></div><button class="btn primary" type="submit">ثبت سند</button></form></details>
    <details><summary>حساب جدید</summary><form data-act="create-account"><div class="field"><label>نام</label><input name="name" required></div><div class="grid-2"><div class="field"><label>نوع</label><select name="type"><option value="person">شخص</option><option value="asset">دارایی</option><option value="liability">بدهی</option><option value="equity">سرمایه</option><option value="income">درآمد</option><option value="expense">هزینه</option></select></div><div class="field"><label>مانده اول</label><input name="opening" inputmode="numeric" value="0"></div></div><div class="field"><label>سمت مانده اول</label><select name="opening_side"><option value="debit">بدهکار</option><option value="credit">بستانکار</option></select></div><button class="btn primary" type="submit">ساخت حساب</button></form></details>
  </section>
  <section class="card"><div class="row-actions"><h2 class="section-title">اسناد</h2><button type="button" class="btn" data-act="filter-account" data-id="">همه</button></div>
    ${state.journal.map((entry) => `<article><strong>${esc(entry.memo || "سند")}</strong> <span class="muted">${esc(entry.at_jalali)}</span><table>${entry.lines.map((line) => `<tr><td>${esc(line.code)} ${esc(line.name)}</td><td class="num">${line.debit ? money(line.debit) : ""}</td><td class="num">${line.credit ? money(line.credit) : ""}</td></tr>`).join("")}</table></article>`).join("") || `<p class="empty">سندی نیست.</p>`}
  </section></div>`;
}

function figuresHtml(figures) {
  const rows = [
    ["موجودی اول", figures.opening], ["فروش نقد", figures.cash_sales], ["فروش کارت", figures.card_sales],
    ["فروش درگاه", figures.gateway_sales], ["فروش نسیه", figures.credit_sales],
    ["دریافت طلب", figures.cash_receipts], ["خرید نقدی", figures.cash_purchases], ["مورد انتظار", figures.expected_cash],
  ];
  return `<table>${rows.map(([label, value]) => `<tr><th>${label}</th><td class="num">${money(value)}</td></tr>`).join("")}</table>`;
}
function registerHtml() {
  const info = state.registerInfo;
  if (!info) return "";
  const session = info.session;
  const history = info.history || [];
  const body = session ? `<section class="card"><h2 class="section-title">نوبت ${num(session.id)} از ${esc(session.opened_jalali)}</h2>${figuresHtml(session.figures)}
      <form data-act="close-register"><div class="field"><label>پول شمارش‌شده در کشو</label><input name="counted_cash" inputmode="numeric" value="${session.figures.expected_cash}"></div>
      <button class="btn primary" type="submit">بستن صندوق</button></form>
      <p class="hint">اختلاف شمارش با موجودی مورد انتظار در حساب کسر و اضافه ثبت می‌شود و برای مدیر پیامک می‌رود اگر در تنظیمات روشن باشد.</p></section>`
    : `<section class="card"><form data-act="open-register"><div class="field"><label>موجودی اول کشو</label><input name="opening_cash" inputmode="numeric" value="${info.suggested_opening}"></div>
      <p class="hint">پیشنهاد از مانده دفتری صندوق است. اگر عدد را عوض کنید، اختلاف به‌عنوان آورده یا برداشت سرمایه ثبت می‌شود.</p>
      <button class="btn primary" type="submit">باز کردن صندوق</button></form></section>`;
  const past = `<section class="card"><h2 class="section-title">نوبت‌های قبل</h2>${history.length ? `<table><thead><tr><th>شروع</th><th>پایان</th><th>مورد انتظار</th><th>شمارش</th><th>اختلاف</th></tr></thead><tbody>
    ${history.map((row) => `<tr><td>${esc(row.opened_jalali)}</td><td>${esc(row.closed_jalali)}</td><td class="num">${money(row.figures.expected_cash)}</td><td class="num">${money(row.counted_cash)}</td><td class="num">${money(row.figures.difference)}</td></tr>`).join("")}
  </tbody></table>` : `<p class="empty">هنوز صندوقی بسته نشده.</p>`}</section>`;
  return `<div class="stack">${body}${past}</div>`;
}

function accessCard(access) {
  if (!access) return "";
  if (access.mode === "paid" && access.active) {
    return `<section class="card access-card"><span>لایسنس این دفترچی</span><h2>${withVip(access.title, access.vip)}</h2><p class="days">${num(access.days_left)} روز مانده</p><p class="hint">تا ${esc(access.ends_jalali)}</p></section>`;
  }
  if (access.active) {
    return `<section class="card access-card"><span>استفاده رایگان</span><h2>۷ روز رایگان فعال است</h2><p class="days">${num(access.days_left)} روز مانده</p><p class="hint">تا ${esc(access.ends_jalali)}. هر وقت خواستید روی یک طرح بزنید.</p></section>`;
  }
  return `<section class="card access-card"><span>مهلت رایگان</span><h2>۷ روز رایگان تمام شده</h2><p class="hint">با «ادامه روزانه» روز به روز تمدید کنید، یا یکی از نسخه‌های ماهانه تا پنجاه‌ساله بخرید. صفحه زیبال باز می‌شود.</p></section>`;
}
function shopFeatureList(items) {
  return `<ul class="shop-plan-features">${items.map((item) => `<li>${esc(item)}</li>`).join("")}</ul>`;
}
function periodOff(monthly, price, months) {
  const full = Number(monthly || 0) * Number(months || 0);
  if (!full || !price || price >= full) return 0;
  return Math.round((1 - price / full) * 100);
}
function licensesHtml() {
  const data = state.licenses;
  if (!data) return "";
  const echoPeriods = [
    ["1m", "ماهانه", 1],
    ["3m", "سه‌ماهه", 3],
    ["6m", "شش‌ماهه", 6],
    ["12m", "سالانه", 12],
  ];
  const period = echoPeriods.some(([id]) => id === state.billingPeriod) ? state.billingPeriod : "3m";
  const byId = Object.fromEntries((data.plans || []).map((plan) => [plan.id, plan]));
  const pro = data.pro || { tokens: 0, packs: [] };
  const packList = pro.packs || [];
  const packs = Object.fromEntries(packList.map((pack) => [pack.id, pack]));
  const monthly = byId["1m"]?.price || 0;
  const daily = byId["1d"];
  const echo = byId[period];
  const plus = byId["50y"];
  const packId = packList.some((pack) => pack.id === state.proPackId) ? state.proPackId : (packList[1]?.id || packList[0]?.id || "pro50");
  const proPack = packs[packId];
  const echoMonths = echoPeriods.find(([id]) => id === period)?.[2] || 3;
  const echoOff = periodOff(monthly, echo?.price, echoMonths);
  const periods = echoPeriods.map(([id, label, months]) => [id, label, id === "1m" ? 0 : periodOff(monthly, byId[id]?.price, months)]);
  const dailyCard = daily ? `<article class="shop-plan daily-plan">
    <h3>ادامه روزانه</h3>
    <p class="shop-plan-kicker">بعد از ۷ روز رایگان یا تمدید کوتاه</p>
    ${daily.discount_percent ? `<del class="num">${money(daily.list_price)}</del>` : ""}
    <p class="shop-plan-price"><strong class="num">${money(daily.price)}</strong><span>برای یک روز دیگر</span></p>
    <button type="button" class="btn primary block" data-act="pick-plan" data-plan="1d">پرداخت روزانه</button>
    ${shopFeatureList([
      "همان امکانات اکو تا پایان همان روز",
      "چند بار پشت‌سرهم = چند روز اضافه",
      "برای امتحان کوتاه بعد از مهلت رایگان",
      "طرح‌های ماهانه و بالاتر زیر همین صفحه‌اند",
    ])}
  </article>` : "";
  const echoCard = `<article class="shop-plan">
    <h3>اکو</h3>
    <p class="shop-plan-kicker">فروشگاه حضوری</p>
    ${echo?.discount_percent ? `<del class="num">${money(echo.list_price)}</del>` : ""}
    <p class="shop-plan-price"><strong class="num">${money(echo?.price || 0)}</strong><span>${esc(echo?.title || "")}</span></p>
    ${echoOff ? `<p class="shop-plan-off">${num(echoOff)}٪ نسبت به ماهانه</p>` : ""}
    <button type="button" class="btn block" data-act="pick-plan" data-plan="${esc(echo?.id || period)}">خرید بسته اکو</button>
    ${shopFeatureList([
      "صندوق، فاکتور، انبار و حسابداری",
      "کارت‌خوان و چاپ رسید",
      "بکاپ روزانه روی همین رایانه",
      "روزی یک فاکتور رایگان با گپ‌جی‌پی‌تی",
      "پیامک با شارژ جدا از وب‌بیکری",
    ])}
  </article>`;
  const plusCard = `<article class="shop-plan featured">
    <span class="shop-plan-badge">بسته پیشنهادی</span>
    <h3>پلاس</h3>
    <p class="shop-plan-kicker">حضوری، سایت و شخصی‌سازی</p>
    ${plus?.discount_percent ? `<del class="num">${money(plus.list_price)}</del>` : ""}
    <p class="shop-plan-price"><strong class="num">${money(plus?.price || 0)}</strong><span>${esc(plus?.title || "پنجاه‌ساله")}</span></p>
    ${plus?.remaining != null ? `<span class="pill">باقی ${num(plus.remaining)} از ${num(plus.cap)}</span>` : ""}
    <button type="button" class="btn primary block" data-act="pick-plan" data-plan="50y" ${plus?.remaining === 0 ? "disabled" : ""}>خرید بسته پلاس</button>
    ${shopFeatureList([
      "همه امکانات اکو تا پنجاه سال",
      "شخصی‌سازی اپ متناسب با فروشگاه",
      "اتصال ووکامرس و همگام موجودی و قیمت سایت",
      "سفارش‌های سایت داخل برنامه",
      "ظرفیت محدود طرح پنجاه‌ساله",
    ])}
  </article>`;
  const proCard = `<article class="shop-plan">
    <h3>پرو</h3>
    <p class="shop-plan-kicker">خواندن عکس فاکتور</p>
    <p class="shop-plan-price"><strong class="num">${money(proPack?.price || 0)}</strong><span>${num(proPack?.tokens || 0)} توکن</span></p>
    <div class="seg shop-versions">${packList.map((pack) => `<button type="button" class="${pack.id === packId ? "on" : ""}" data-act="pick-pro-pack" data-pack="${pack.id}">${num(pack.tokens)}</button>`).join("")}</div>
    <p class="hint">مانده ${num(pro.tokens || 0)}${pro.invoice_free ? " · سهمیه رایگان امروز باقی است" : ""}</p>
    <button type="button" class="btn block" data-act="buy-pro" data-pack="${esc(proPack?.id || packId)}">خرید بسته پرو</button>
    ${shopFeatureList([
      "خواندن عکس فاکتور چاپی و دست‌نویس",
      "تطبیق فی خرید و فروش با کالاهای برنامه و سایت",
      "توکن جدا از لایسنس فروشگاه",
      "VIP این بخش را باز نمی‌کند",
      "مدل گپ‌جی‌پی‌تی روی وب‌بیکری",
    ])}
  </article>`;
  return `<div class="stack">${accessCard(data.access)}${data.campaign?.active || data.campaign?.upcoming ? `<section class="card campaign-note"><h2 class="section-title">۵۰٪ تخفیف ویژهٔ ۵ آذر ۱۴۰۵</h2><p>${data.campaign.active ? "تخفیف امروز روی مبلغ پرداختی همهٔ طرح‌ها اعمال شده است." : "فقط در روز ۵ آذر، قیمت همهٔ طرح‌ها نصف می‌شود."} از ۰۰:۰۰ تا پایان روز به وقت ایران.</p></section>` : ""}
    <section class="shop-store">
      <h2 class="shop-store-title">خرید و ارتقا بسته</h2>
      <div class="seg shop-periods">${periods.map(([id, label, off]) => `<button type="button" class="${period === id ? "on" : ""}" data-act="pick-period" data-period="${id}">${label}${off ? `<small>${num(off)}٪ تخفیف</small>` : ""}</button>`).join("")}</div>
      <div class="shop-plans">${dailyCard}${echoCard}${plusCard}${proCard}</div>
      <p class="hint">ادامه روزانه ۱۵۰ هزار تومان برای هر روز است. نسخه‌های لایسنس فروشگاه: ماهانه، سه‌ماهه، شش‌ماهه، یک‌ساله و پنجاه‌ساله. بسته پرو جداست. پرداخت در زیبال وب‌بیکری است.</p>
    </section>
      <details><summary>قیمت‌ها و ظرفیت پنجاه‌ساله</summary><form data-act="save-prices"><div class="grid-2">
        ${[["price_1d", "ادامه روزانه"], ["price_1m", "یک ماهه"], ["price_3m", "سه ماهه"], ["price_6m", "شش ماهه"], ["price_12m", "یک ساله"], ["price_50y", "پنجاه ساله"], ["cap_50y", "ظرفیت پنجاه ساله"], ["price_pro10", "پرو ۱۰ هزار توکن"], ["price_pro50", "پرو ۵۰ هزار توکن"], ["price_pro200", "پرو ۲۰۰ هزار توکن"]].map(([key, label]) => {
          const plan = data.plans.find((item) => item.id === { price_1d: "1d", price_1m: "1m", price_3m: "3m", price_6m: "6m", price_12m: "12m", price_50y: "50y" }[key]);
          const pack = (data.pro?.packs || []).find((item) => item.id === { price_pro10: "pro10", price_pro50: "pro50", price_pro200: "pro200" }[key]);
          const value = key === "cap_50y" ? (data.plans.find((item) => item.id === "50y")?.cap ?? 50) : (plan?.list_price ?? plan?.price ?? pack?.price ?? "");
          return `<div class="field"><label>${label}</label><input name="${key}" value="${value}"></div>`;
        }).join("")}
      </div><button class="btn" type="submit">ذخیره قیمت‌ها</button></form></details>
    ${pendingLicenses(data)}
    <section class="card"><h2 class="section-title">لایسنس‌های فروخته‌شده</h2><table><thead><tr><th>طرح</th><th>خریدار</th><th>تماس</th><th>مبلغ</th><th>وضعیت</th><th>هاست</th><th>تا</th></tr></thead><tbody>
      ${data.licenses.map((row) => `<tr><td>${withVip(row.title, row.vip)}</td><td>${esc(row.buyer_name)}</td><td class="ltr">${esc(row.phone)}</td><td class="num">${money(row.payable)}</td><td>${esc(LICENSE_STATUS[row.status] || row.status)}</td><td>${row.host_saved ? "ذخیره شد" : "منتظر هاست"}</td><td>${esc(row.ends_jalali)}</td></tr>`).join("") || `<tr><td colspan="7">هنوز لایسنسی فروخته نشده.</td></tr>`}
    </tbody></table></section></div>`;
}

function pendingLicenses(data) {
  const rows = (data.licenses || []).filter((row) => row.status === "pending");
  if (!rows.length) return "";
  return `<section class="card"><h2 class="section-title">پرداخت‌های باز وب‌بیکری</h2>${rows.map((row) => `<div class="line"><span>${withVip(row.title, row.vip)} · ${esc(row.buyer_name)} · ${money(row.payable)}</span><button type="button" class="btn" data-act="open-license" data-id="${row.id}">ادامه</button></div>`).join("")}</section>`;
}

function customersHtml() {
  const debtors = allowCredit() ? state.customers.filter((row) => row.balance > 0 && row.account_id) : [];
  const walk = state.customers.filter((row) => row.walk_in);
  const table = (rows) => rows.map((row) => `<tr><td>${esc(row.name)}</td><td class="ltr">${esc(row.phone || "—")}</td><td class="num">${num(row.purchases)}</td><td class="num">${money(row.amount)}</td><td>${esc(row.side)}</td></tr>`).join("") || `<tr><td colspan="5">موردی نیست.</td></tr>`;
  return `<div class="stack"><section class="card">${debtors.length && isAdmin() ? `<form data-act="receipt"><div class="grid-2"><div class="field"><label>مشتری بدهکار</label><select name="account_id">${debtors.map((row) => `<option value="${row.account_id}">${esc(row.name)} — ${money(row.balance)}</option>`).join("")}</select></div><div class="field"><label>مبلغ</label><input name="amount" inputmode="numeric" required></div></div><div class="field"><label>روش</label><select name="payment_method"><option value="cash">نقد</option><option value="card">کارت</option></select></div><button class="btn primary" type="submit">دریافت طلب</button></form>` : `<p class="empty">مانده نسیه‌ای برای دریافت نیست.</p>`}</section>
    <section class="card"><h2 class="section-title">مشتریان حضوری</h2><p class="hint">برای باشگاه مشتریان. همین فهرست، اگر کلید ووکامرس باشد، در سایت هم ذخیره می‌شود.</p><table><thead><tr><th>نام</th><th>تماس</th><th>خرید</th><th>مانده</th><th>سمت</th></tr></thead><tbody>
      ${table(walk)}
    </tbody></table></section>
    <section class="card"><h2 class="section-title">همه مشتریان</h2><table><thead><tr><th>نام</th><th>تماس</th><th>خرید</th><th>مانده</th><th>سمت</th></tr></thead><tbody>
      ${table(state.customers)}
    </tbody></table></section></div>`;
}

function smsInvoiceHtml(topup) {
  const shop = topup.shop || state.settings?.shop_name || "";
  return `<div id="print-area" class="invoice thermal">
    <div class="invoice-sheet">
      <header class="invoice-head">
        <div class="invoice-brand"><span class="seal big">${logo()}</span><div><strong>دفترچی</strong><div>شارژ پنل پیامک</div></div></div>
        <div class="invoice-meta"><b class="ltr">${esc(topup.invoice_code || "")}</b><span>فاکتور شارژ</span><span>${esc(topup.paid_jalali || topup.at_jalali || "")}</span></div>
      </header>
      <p class="invoice-buyer">${esc(topup.buyer_name || "")} <span class="ltr">${esc(topup.buyer_phone || "")}</span>${shop ? ` · ${esc(shop)}` : ""}</p>
      <table><thead><tr><th>شرح</th><th>مبلغ</th></tr></thead><tbody>
        <tr><td>شارژ پنل پیامک</td><td class="num">${money(topup.credit)}</td></tr>
        <tr><td>کارمزد ۳ درصد</td><td class="num">${money(topup.fee)}</td></tr>
      </tbody></table>
      <p class="invoice-total"><span>پرداخت‌شده</span><span class="num">${money(topup.payable)}</span></p>
      <p class="invoice-pay">زیبال${topup.paid_ref ? ` · <span class="ltr">${esc(topup.paid_ref)}</span>` : ""}</p>
      <p class="invoice-thanks">این فاکتور برای مدیر فروشگاه صادر شد</p>
    </div>
  </div>
  <div class="row-actions"><button type="button" class="btn primary" data-act="print" title="یک نسخه، به اندازه کالاهای همین فروش">چاپ یک نسخه</button><button type="button" class="btn" data-act="printer-settings">تنظیم ابعاد چاپ</button><button type="button" class="btn quiet" data-act="clear-print-memory">پاک کردن حافظه چاپ</button></div>`;
}
function purchaseReceiptHtml(modal) {
  const row = modal.purchase || {};
  const shop = shopBrand();
  const code = row.invoice_code || `P${row.id || ""}`;
  return `<div id="print-area" class="invoice thermal">
    <div class="invoice-sheet">
      <header class="invoice-head">
        <div class="invoice-brand">${invoiceSeal(true)}<div><strong>${esc(shop.name)}</strong><div>رسید خرید</div></div></div>
        <div class="invoice-meta"><b class="ltr">${esc(code)}</b><span>خرید ${num(row.id)}</span><strong class="receipt-date">${esc(row.at_jalali || "")}</strong></div>
      </header>
      <table><thead><tr><th>کالا</th><th>مبلغ</th></tr></thead><tbody>
        <tr><td>${esc(row.product_name || "")} × ${num(row.qty)}</td><td class="num">${money(row.total)}</td></tr>
      </tbody></table>
      <p class="invoice-total"><span>مبلغ خرید</span><span class="num">${money(row.total)}</span></p>
      ${ean13Svg(row.barcode) || `<p class="invoice-barcode"><span class="ltr">${esc(row.barcode || code)}</span></p>`}
      <p class="invoice-thanks">بارکد را برای باز کردن همین رسید اسکن کنید</p>
    </div>
  </div>
  <div class="row-actions"><button type="button" class="btn primary" data-act="print" title="یک نسخه، به اندازه کالاهای همین فروش">چاپ یک نسخه</button><button type="button" class="btn" data-act="printer-settings">تنظیم ابعاد چاپ</button><button type="button" class="btn quiet" data-act="clear-print-memory">پاک کردن حافظه چاپ</button></div>`;
}
function smsHtml() {
  const wallet = state.smsWallet || {};
  const packs = [100000, 250000, 500000, 1000000];
  const buyers = (state.customers || []).filter((row) => /^09\d{9}$/.test(String(row.phone || "")));
  const pending = (wallet.topups || []).filter((row) => row.status === "pending" && row.pay_url);
  const paid = (wallet.topups || []).filter((row) => row.status === "paid");
  const feeLine = (amount) => {
    const fee = Math.floor(amount * 3 / 100);
    return `پرداخت ${money(amount + fee)}`;
  };
  return `<div class="stack">
    <section class="card">
      <h2 class="section-title">شارژ پنل پیامک</h2>
      <p class="hint">ارسال و شارژ از پنل ملی‌پیامک وب‌بیکری است. فروشگاه کلید ملی‌پیامک نمی‌گیرد.</p>
      <p>موجودی: <strong class="num">${money(wallet.balance || 0)}</strong></p>
      <div class="row-actions">${packs.map((amount) => `<button type="button" class="btn primary" data-act="sms-topup" data-amount="${amount}">${feeLine(amount)}</button>`).join("")}</div>
      <form data-act="sms-topup-custom"><div class="field"><label>مبلغ دیگر، تومان</label><input name="amount" inputmode="numeric" required placeholder="مثلاً ۱۵۰۰۰۰"></div><button class="btn primary" type="submit">پرداخت همین مبلغ</button></form>
      ${pending.map((row) => `<p><a href="${esc(row.pay_url)}" target="_blank" rel="noopener">ادامه پرداخت ${money(row.payable)}</a> <button type="button" class="btn" data-act="confirm-sms" data-id="${row.id}">استعلام تأیید</button></p>`).join("")}
    </section>
    <section class="card"><h2 class="section-title">فاکتورهای شارژ</h2><table><thead><tr><th>فاکتور</th><th>مدیر</th><th>شارژ</th><th>پرداخت</th><th></th></tr></thead><tbody>
      ${paid.map((row) => `<tr><td class="ltr">${esc(row.invoice_code || "")}</td><td>${esc(row.buyer_name || "")} <span class="ltr">${esc(row.buyer_phone || "")}</span></td><td class="num">${money(row.credit)}</td><td class="num">${money(row.payable)}</td><td><button type="button" class="btn" data-act="open-sms-invoice" data-id="${row.id}">چاپ فاکتور</button></td></tr>`).join("") || `<tr><td colspan="5">هنوز فاکتور شارژی نیست.</td></tr>`}
    </tbody></table></section>
    <section class="card">
      <h2 class="section-title">پیامک به مشتریان فروشگاه</h2>
      <div class="field"><label>متن</label><textarea id="sms-body" rows="3" required placeholder="متن پیام برای مشتریان"></textarea></div>
      ${buyers.length ? buyers.map((row) => `<label class="check"><input type="checkbox" name="sms-customer" value="${row.id}">${esc(row.name)} <span class="ltr">${esc(row.phone)}</span></label>`).join("") : `<p class="empty">هنوز مشتری با موبایل ثبت نشده است.</p>`}
      <button type="button" class="btn primary" data-act="send-customer-sms">ارسال به انتخاب‌شده‌ها</button>
    </section>
    <section class="card"><form data-act="send-sms"><div class="field"><label>یک شماره</label><input class="ltr" name="recipient" required placeholder="09..."></div><div class="field"><label>متن</label><textarea name="body" required></textarea></div><button class="btn primary" type="submit">ارسال</button><p class="hint">متن آزاد و الگوها از خط ملی‌پیامک وب‌بیکری می‌روند. فروشگاه کلید جدا نمی‌گیرد.</p></form></section>
    <section class="card"><table><thead><tr><th>زمان</th><th>گیرنده</th><th>متن</th><th>وضعیت</th></tr></thead><tbody>
      ${state.sms.map((row) => `<tr><td>${esc(row.at_jalali)}</td><td class="ltr">${esc(row.recipient)}</td><td>${esc(row.body)}</td><td>${esc(row.status)} ${esc(row.detail || "")}</td></tr>`).join("") || `<tr><td colspan="4">پیامی نیست.</td></tr>`}
    </tbody></table></section></div>`;
}

function smsVarChips(field) {
  const vars = [
    ["mobile", "شماره موبایل مشتری"],
    ["phone", "شماره تلفن مشتری"],
    ["email", "ایمیل مشتری"],
    ["status", "وضعیت سفارش"],
    ["all_items", "محصولات سفارش"],
    ["all_items_full", "محصولات سفارش با نام کامل متغیر"],
    ["all_items_qty", "محصولات سفارش بهمراه تعداد"],
    ["item_count", "تعداد محصولات سفارش"],
    ["price", "مبلغ سفارش"],
    ["order_id", "شماره سفارش"],
    ["order_number", "شماره سفارش اصلی"],
    ["transaction_id", "شماره تراکنش"],
    ["order_date", "تاریخ سفارش"],
    ["customer_note", "توضیحات مشتری"],
    ["payment_method", "روش پرداخت"],
    ["shipping_method", "روش ارسال"],
    ["payment_link", "لینک پرداخت"],
    ["sh_first_name", "نام مشتری"],
    ["sh_last_name", "نام خانوادگی مشتری"],
    ["b_company", "نام شرکت"],
    ["b_country", "کشور"],
    ["b_state", "ایالت/استان"],
    ["b_city", "شهر"],
    ["b_address_1", "آدرس 1"],
    ["b_address_2", "آدرس 2"],
    ["b_postcode", "کد پستی"],
    ["sh_first_name", "نام مشتری (حمل و نقل)"],
    ["sh_last_name", "نام خانوادگی مشتری (حمل و نقل)"],
    ["sh_company", "نام شرکت (حمل و نقل)"],
    ["sh_country", "کشور (حمل و نقل)"],
    ["sh_state", "ایالت/استان (حمل و نقل)"],
    ["sh_city", "شهر (حمل و نقل)"],
    ["sh_address_1", "آدرس 1 (حمل و نقل)"],
    ["sh_address_2", "آدرس 2 (حمل و نقل)"],
    ["sh_postcode", "کد پستی (حمل و نقل)"],
  ];
  return `<div class="var-chips">${vars.map(([token, label]) => `<button type="button" class="chip" data-act="insert-sms-var" data-token="${token}" data-field="${field}">${esc(label)}</button>`).join("")}</div>`;
}
function patternPicker(role, label) {
  const catalog = state.smsCatalog || [];
  const current = state.settings[`sms_pattern_${role}`] || "";
  const choice = state.settings[`sms_choice_${role}`] || "";
  if (!catalog.length) {
    return `<div class="field"><label>${label}</label><textarea name="sms_pattern_${role}" rows="3">${esc(current)}</textarea>${smsVarChips(`sms_pattern_${role}`)}</div>`;
  }
  const options = catalog.map((item) => `<label class="check"><input type="radio" name="sms_choice_${role}" value="${esc(item.id)}" ${choice === item.id ? "checked" : ""}><span><strong>${esc(item.title)}</strong><br>${esc(item.text)}${item.pattern ? `<br><span class="ltr">${esc(item.pattern)}</span>` : ""}</span></label>`).join("");
  return `<div class="field"><label>${label}</label>${options}<label class="check"><input type="radio" name="sms_choice_${role}" value="" ${choice ? "" : "checked"}>متن خود فروشگاه</label></div><div class="field"><textarea name="sms_pattern_${role}" rows="3">${esc(current)}</textarea>${smsVarChips(`sms_pattern_${role}`)}</div>`;
}
function options(pairs, current) {
  return pairs.map(([value, label]) => `<option value="${value}" ${value === current ? "selected" : ""}>${label}</option>`).join("");
}
function field(key, label, type = "text", placeholder = "", tour = "") {
  const hold = placeholder ? ` placeholder="${esc(placeholder)}"` : "";
  const mark = tour ? ` data-tour="${tour}"` : "";
  return `<div class="field"${mark}><label>${label}</label><input name="${key}" type="${type}" value="${esc(state.settings[key] || "")}"${hold} ${type === "text" && /url|host|phone|sender|key|user|merchant|callback|chat/.test(key) ? `class="ltr"` : ""}></div>`;
}
const ROLE_LABEL = { admin: "مدیر", cashier: "صندوق", stock: "انبار" };
function hubUnifiedLink() {
  const target = savedServerUrl() || currentOrigin();
  return `${target.replace(/\/+$/, "")}/`;
}
function hubHtml() {
  const hub = state.hub || {};
  const shops = hub.shops || [];
  const users = hub.users || [];
  const current = String(hub.site_id || "");
  const link = hubUnifiedLink();
  const cards = shops.map((row, index) => {
    const active = row.site_id === current || row.id === current;
    const url = row.url || "";
    const action = active
      ? `<button type="button" class="btn primary block mode-pick" data-act="nav" data-page="pos"><strong>حسابداری این فروشگاه</strong><span>همین سرور — بدون کپی فایل</span></button>`
      : url
        ? `<button type="button" class="btn primary block mode-pick" data-act="open-shop" data-url="${esc(url)}" data-site="${esc(row.site_id || "")}"><strong>باز کردن</strong><span class="ltr">${esc(url)}</span></button>`
        : `<p class="hint">آدرس سرور LAN را پایین ذخیره کنید؛ بعد با همان اکانت فروشگاه وارد می‌شوید.</p>`;
    return `<article class="hub-shop-card ${active ? "hub-shop-active" : ""}">
      <h3 class="section-title">${esc(row.label)} ${active ? '<span class="pill">اینجا</span>' : ""}</h3>
      <p class="hint ltr">${esc(row.site_id || row.id || "")}</p>
      ${action}
      <input type="hidden" data-hub-field="label" data-hub-index="${index}" value="${esc(row.label)}">
      <input type="hidden" data-hub-field="site_id" data-hub-index="${index}" value="${esc(row.site_id || "")}">
      <input type="hidden" data-hub-field="note" data-hub-index="${index}" value="${esc(row.note || "")}">
    </article>`;
  }).join("");
  const urlFields = shops.map((row, index) => {
    const url = row.url || "";
    return `<div class="field"><label>${esc(row.label)} — آدرس سرور</label><input class="ltr" data-hub-field="url" data-hub-index="${index}" value="${esc(url)}" placeholder="http://192.168.1.10:8080"></div>`;
  }).join("");
  const userRows = users.map((row) => `<tr><td>${esc(row.name)}</td><td class="ltr">${esc(row.phone)}</td><td>${esc(ROLE_LABEL[row.role] || row.role)}</td></tr>`).join("");
  return `<div class="stack">
    <section class="card hub-hero">
      <h2 class="section-title">یک لینک، همه فروشگاه‌ها</h2>
      <p class="hint">مثل یک فایل مشترک: این آدرس را bookmark کنید، از موبایل یا هر PC با همان اکانت وارد شوید، فروشگاه را انتخاب کنید. داده روی سرور همان فروشگاه می‌ماند — <strong>بدون کپی پوشه data</strong>.</p>
      <p class="hub-link-line"><strong class="ltr" id="hub-unified-link">${esc(link)}</strong>
        <button type="button" class="btn" data-act="copy-hub-link">کپی لینک</button></p>
    </section>
    <section class="card"><div class="mode-grid hub-shop-grid">${cards || `<p class="empty">فروشگاهی تعریف نشده</p>`}</div></section>
    <section class="card"><h2 class="section-title">آدرس سرور هر فروشگاه (LAN)</h2>
      <p class="hint">اشرافی و پاکان اگر روی PC جدا هستند، فقط آدرس مرورگر همان نصب را بزنید.</p>
      ${urlFields}
      <div class="row-actions"><button type="button" class="btn primary" data-act="save-hub-shops">ذخیره</button></div>
    </section>
    <section class="card"><h2 class="section-title">کاربران همین سرور</h2>
      <table><thead><tr><th>نام</th><th>موبایل</th><th>نقش</th></tr></thead><tbody>
        ${userRows || `<tr><td colspan="3">—</td></tr>`}
      </tbody></table>
    </section>
  </div>`;
}
function settingsHtml() {
  const s = state.settings;
  if (!s) return "";
  const mode = state.shopMode;
  const checked = (key) => s[key] === "1" ? "checked" : "";
  const names = { offline: "فروشگاه حضوری", hybrid: "حضوری و آنلاین", online: "فقط آنلاین" };
  const modeCard = `<section class="card"><h2 class="section-title">نوع فروشگاه</h2>
    <p>${esc(names[mode] || "هنوز انتخاب نشده")}</p>
    <div class="row-actions">
      <button type="button" class="btn" data-act="pick-mode" data-mode="offline">حضوری</button>
      <button type="button" class="btn" data-act="pick-mode" data-mode="hybrid">حضوری و آنلاین</button>
      <button type="button" class="btn" data-act="pick-mode" data-mode="online">فقط آنلاین</button>
      <button type="button" class="btn" data-act="tour-replay">آموزش دوباره</button>
    </div>
  </section>`;
  const invoice = `<section class="card"><h2 class="section-title">سربرگ فاکتور</h2>
      ${field("shop_name", "نام فروشگاه روی فاکتور")}
      <input type="hidden" name="shop_logo" value="${esc(s.shop_logo || "")}">
      <div class="field"><label>لوگوی فروشگاه</label><input type="file" name="logo_file" accept="image/*"></div>
      ${s.shop_logo ? `<img class="logo-preview" src="${esc(s.shop_logo)}" alt="">` : ""}
      <label class="check"><input type="checkbox" name="clear_shop_logo">برداشتن لوگو و برگشت به نشان دفترچی</label>
      <div class="row-actions"><button type="button" class="btn" data-act="detect-printer">شناسایی پرینتر</button><button type="button" class="btn" data-act="printer-settings">تنظیم تصویری ابعاد چاپ</button><button type="button" class="btn quiet" data-act="clear-print-memory">پاک کردن حافظه چاپ</button></div><p class="hint">بعد از صدور فاکتور، پرینتر وصل پیدا می‌شود و همان یک نسخه رسید خودکار چاپ می‌شود. طول کاغذ به کالاهای همان فاکتور است، نه صفحه خالی. اگر صف گیر کرد، حافظه چاپ را پاک کنید و پرینتر را یک‌بار خاموش و روشن کنید.</p>
    </section>`;
  const woo = `<section class="card"${mode === "online" ? "" : ` data-tour="woo"`}><h2 class="section-title">اتصال REST ووکامرس</h2>
      ${mode === "online" ? `${field("shop_name", "نام فروشگاه")}${wooFigure()}` : `<p class="hint">آدرس سایت و دو کلید REST را از ووکامرس، پیکربندی، پیشرفته بگیرید.</p>`}
      ${field("wp_url", "آدرس سایت", "text", "https://", "woo-url")}
      ${field("woo_key", "Consumer key", "text", "ck_...", "woo-key")}
      ${field("woo_secret", "Consumer secret", "password", "cs_...", "woo-secret")}
      <label class="check"><input type="checkbox" name="woo_auto_sync" ${checked("woo_auto_sync")}>همگام‌سازی قیمت و موجودی با سایت</label>
      <div class="row-actions"><button type="button" class="btn" data-act="test-setting" data-kind="woo" data-tour="woo-test">آزمایش ووکامرس</button></div>
    </section>`;
  const pos = `<section class="card" data-tour="pos-card"><h2 class="section-title">کارت‌خوان</h2>
      <div class="field"><label>شرکت</label><select name="pos_provider">${options([["manual", "دستی"], ["saman", "سامان کیش"], ["behpardakht", "به‌پرداخت ملت"], ["parsian", "پارسیان"], ["irankish", "ایران‌کیش"], ["sepehr", "سپهر"], ["asanpardakht", "آسان‌پرداخت"], ["pardakhtnovin", "پرداخت نوین"]], s.pos_provider)}</select></div>
      <div class="grid-2">${field("pos_host", "آدرس یا IP")}${field("pos_port", "پورت")}</div>
      ${field("pos_terminal", "شماره پایانه")}
      <button type="button" class="btn" data-act="test-setting" data-kind="pos">آزمایش ارسال مبلغ</button>
      <p class="hint">شرکت دستی یا پرداخت نوین، هر دو مبلغ را به دستگاه می‌فرستند. IP را مثل ۱۹۲.۱۶۸.۱.۲ بنویسید (صفر اضافه حذف می‌شود)، پورت ۱۳۶۲، ذخیره کنید. آزمایش، مبلغ ۱۰۰۰ تومان را واقعاً به کارت‌خوان می‌فرستد نه فقط پینگ TCP. روی صندوق با زدن کارت‌خوان، مبلغ فاکتور به ریال (×۱۰) روی دستگاه می‌آید؛ مشتری فقط کارت و رمز را روی دستگاه می‌زند. پین در دفترچی وارد نمی‌شود. فاکتور بعد از تأیید دستگاه و صدور فاکتور ثبت می‌شود.</p>
    </section>`;
  const extra = `<details><summary>تنظیمات بیشتر</summary>
      <h2 class="section-title">درگاه پرداخت</h2>
      <div class="field"><label>درگاه</label><select name="gateway">${options([["zarinpal", "زرین‌پال"], ["idpay", "آی‌دی‌پی"], ["zibal", "زیبال"]], s.gateway)}</select></div>
      ${field("gateway_merchant", "مرچنت فروش کالا")}${field("gateway_callback", "آدرس بازگشت")}
      <label class="check"><input type="checkbox" name="gateway_sandbox" ${checked("gateway_sandbox")}>حالت آزمایشی زرین‌پال</label>
      <h2 class="section-title">پیامک</h2>
      <p class="hint">الگوهای آماده وب‌بیکری اینجاست. یکی را برای خریدار و یکی را برای مدیر انتخاب کنید. متن هر الگو برای ملی‌پیامک به صورت الگو کنار همان گزینه دیده می‌شود. اگر الگویی نیست، متن را خودتان بنویسید. متغیرها: {نام} {مبلغ} {فاکتور}</p>
      ${patternPicker("buyer", "الگوی پیامک خریدار")}
      ${patternPicker("manager", "الگوی پیامک مدیر")}
      ${field("manager_phone", "موبایل مدیر")}
      <label class="check"><input type="checkbox" name="sms_on_sale" ${checked("sms_on_sale")}>بعد از هر فروش، الگوی مدیر فرستاده شود</label>
      <button type="button" class="btn" data-act="test-setting" data-kind="sms">آزمایش پیامک</button>
      <p class="hint">اتصال ملی‌پیامک فقط در وب‌بیکری است. فروشگاه کلید نمی‌گیرد. شارژ پنل هم از همین مسیر وب‌بیکری پرداخت می‌شود.</p>
      <button type="button" class="btn primary" data-act="nav" data-page="sms">شارژ پنل پیامک</button>
      <h2 class="section-title">سامانه مودیان</h2>
      ${field("moadian_fiscal_id", "شناسه حافظه مالیاتی")}
      <label class="check"><input type="checkbox" name="moadian_enabled" ${checked("moadian_enabled")}>فعال‌سازی اتصال مؤدیان (ارسال هر فاکتور با انتخاب شما)</label>
      <label class="check"><input type="checkbox" name="moadian_sandbox" ${checked("moadian_sandbox")}>ارسال آزمایشی</label>
    </details>`;
  const desk = `<section class="card"><h2 class="section-title">برنامه و ورود</h2><div class="row-actions">${desktopIconButton(false)}<button type="button" class="btn" data-act="password-modal">رمز</button></div></section>`;
  const currentTheme = themeId(s.ui_theme);
  const themeCard = `<section class="card"><h2 class="section-title">ظاهر برنامه</h2>
    <p class="hint">تم تیره و بقیه رنگ‌ها از همین‌جا عوض می‌شود.</p>
    <input type="hidden" name="ui_theme" value="${esc(currentTheme)}">
    <div class="theme-picks">${THEMES.map(([id, label]) => `<button type="button" class="theme-pick ${id === currentTheme ? "on" : ""}" data-act="pick-theme" data-theme="${id}"><span class="theme-swatch ${id}"></span>${label}</button>`).join("")}</div>
  </section>`;
  const backupInfo = state.backups || {};
  const backupRows = backupInfo.backups || [];
  const latestBackup = backupInfo.latest;
  const backupCard = `<section class="card"><h2 class="section-title">بکاپ روزانه</h2>
    <p class="hint">هر روز یک نسخه از فروش‌ها، کالاها، فاکتورها و تنظیمات در همین رایانه ذخیره می‌شود. ${num(backupInfo.keep || 14)} نسخهٔ آخر نگه داشته می‌شود.</p>
    <p>${latestBackup ? `آخرین بکاپ: <strong class="ltr">${esc(latestBackup.when)}</strong> · ${latestBackup.kind === "auto" ? "خودکار" : "دستی"}` : "هنوز بکاپی گرفته نشده."}</p>
    <label class="check"><input type="checkbox" name="backup_auto" ${checked("backup_auto")}>بکاپ خودکار روزانه</label>
    <div class="row-actions"><button type="button" class="btn primary" data-act="make-backup">گرفتن بکاپ الان</button></div>
    ${backupRows.length ? `<table><thead><tr><th>تاریخ</th><th>نوع</th><th></th></tr></thead><tbody>${backupRows.map((row) => `<tr><td class="ltr">${esc(row.when)}</td><td>${row.kind === "auto" ? "خودکار" : "دستی"}</td><td><a class="btn" href="/api/backups/${encodeURIComponent(row.name)}">دانلود</a></td></tr>`).join("")}</tbody></table>` : ""}
  </section>`;
  const parts = [desk, themeCard, backupCard, modeCard].filter(Boolean);
  if (mode === "online") parts.push(woo);
  else if (mode === "offline") parts.push(invoice, pos, extra);
  else if (mode === "hybrid") parts.push(invoice, woo, pos, extra);
  else parts.push(invoice, woo, pos, extra);
  return `<form class="stack" data-act="save-settings">
    ${parts.join("")}
    <button class="btn primary" type="submit">ذخیره تنظیمات</button>
  </form>
  <section class="card" style="margin-top:14px"><h2 class="section-title">ساخت اکانت</h2>
    <p class="hint">نام، موبایل و جیمیل ثبت‌نام، بدون رمز، در وب‌بیکری ذخیره می‌شود.</p>
    <table><thead><tr><th>نام</th><th>موبایل</th><th>جیمیل</th><th>نقش</th><th></th></tr></thead><tbody>${state.users.map((user) => {
      const roleName = user.role === "admin" ? "مدیر" : user.role === "stock" ? "مسئول موجودی" : "صندوقدار";
      const mine = Number(user.id) === Number(state.user?.id);
      return `<tr><td>${esc(user.name)}</td><td class="ltr">${esc(user.phone)}</td><td class="ltr">${esc(user.email || "—")}${user.email && !user.email_verified ? " · تأیید نشده" : ""}</td><td>${roleName}</td><td>${user.role === "admin" && (state.mustChange || state.users.filter(item => item.role === "admin").length < 2) ? `<span class="hint">${state.mustChange ? "ابتدا تغییر رمز" : "آخرین مدیر"}</span>` : `<button type="button" class="btn danger" data-act="delete-user" data-id="${user.id}">${mine ? "حذف حساب من" : "حذف"}</button>`}</td></tr>`;
    }).join("")}</tbody></table>
    <form data-act="create-user"><div class="grid-2"><div class="field"><label>نام</label><input name="name" required></div><div class="field"><label>موبایل</label><input class="ltr" name="phone" required></div></div><div class="grid-2"><div class="field"><label>جیمیل</label><input class="ltr" name="email"></div><div class="field"><label>رمز</label><input type="password" name="password" required></div></div><div class="field"><label>نقش</label><select name="role"><option value="cashier">صندوقدار</option><option value="stock">مسئول موجودی</option><option value="admin">مدیر</option></select></div><button class="btn primary" type="submit">ساخت کاربر</button></form>
  </section>`;
}

function updatesHtml() {
  const info = state.panelUpdate || {};
  const remote = info.update
    ? `<p>نسخه ${esc(info.latest)} آماده است.</p>`
    : `<p>${info.reachable ? "همین نسخه آخر است." : esc(info.error || "ارتباط با سرور به‌روزرسانی برقرار نشد. می‌توانید فایل زیپ را دستی نصب کنید.")}</p>`;
  return `<div class="stack">
    <section class="card"><h2 class="section-title">به‌روزرسانی پنل و اپ</h2>
      <p class="hint">آپدیت روی همین نصب است؛ فروش‌ها و تنظیمات داخل پوشه data نمی‌روند. نیازی به پوشه یا زیپ تازه نیست.</p>
      <p>نسخه این برنامه: <strong class="ltr">${esc(info.current || "")}</strong></p>
      ${info.channel ? `<p>کانال این مشتری: <strong class="ltr">${esc(info.channel)}</strong></p>` : ""}
      ${info.latest ? `<p>نسخه موجود: <strong class="ltr">${esc(info.latest)}</strong></p>` : ""}
      ${remote}
      ${info.notes ? `<h3 class="section-title">تغییرات نسخهٔ ${esc(info.latest || "جدید")}</h3><div class="release-notes">${esc(info.notes)}</div>` : ""}
      ${info.error && info.reachable ? `<p class="hint">${esc(info.error)}</p>` : ""}
      <div class="row-actions">
        <button type="button" class="btn" data-act="check-panel-update" ${state.updating ? "disabled" : ""}>بررسی آپدیت</button>
        <button type="button" class="btn primary" data-act="self-update" ${state.updating ? "disabled" : ""}>${state.updating ? "در حال آپدیت..." : "آپدیت"}</button>
        ${resetProgramButton()}
      </div>
    </section>
    ${(info.history || []).map(release => `<section class="card"><h2 class="section-title">تغییرات نسخهٔ ${esc(release.version)}${release.version === info.current ? " (نصب‌شده)" : ""}</h2><ul class="release-changes">${(release.changes || []).map(text => `<li>${esc(text)}</li>`).join("")}</ul></section>`).join("")}
    <section class="card"><h2 class="section-title">نصب از فایل</h2>
      <div class="field"><label>فایل زیپ پنل یا نسخه کامل</label><input id="update-file" type="file" accept=".zip,application/zip"></div>
      <button type="button" class="btn primary" data-act="install-file">نصب همین فایل</button>
    </section>
  </div>`;
}

function productForm() {
  const product = state.modal?.product || {};
  const price = product.id ? product.price : "";
  const cost = product.cost ? product.cost : "";
  return `<form data-act="save-product"><h2 class="section-title">${product.id ? "ویرایش کالا" : "کالای جدید"}</h2>
    <input type="hidden" name="id" value="${product.id || ""}">
    <div class="field"><label>بارکد</label><input class="ltr" name="barcode" data-barcode="product" autocomplete="off" placeholder="بارکد را با بارکدخوان بگیرید" value="${esc(product.barcode || "")}"><button type="button" class="btn" data-act="make-barcode">ساخت بارکد</button></div>
    <p class="hint">اگر این بارکد مال کالای بایگانی‌شده باشد، همین‌جا عوضش کنید یا همان را بگذارید و ذخیره کنید.</p>
    <div class="field"><label>نام کالا</label><input name="name" value="${esc(product.name || "")}" required></div>
    <div class="grid-2">
      <div class="field"><label>قیمت خرید، تومان</label><input name="cost" inputmode="numeric" autocomplete="off" placeholder="اختیاری" value="${cost}"></div>
      ${product.type === "variable" ? `<div class="field"><label>قیمت فروش از</label><output class="num">${money(price)}</output></div>` : `<div class="field"><label for="product-price">قیمت فروش، تومان</label><input id="product-price" name="price" inputmode="decimal" required autocomplete="off" value="${price}"></div>`}
    </div>
    ${product.type === "variable" ? `<section class="stack"><h3 class="section-title">قیمت و موجودی هر نوع</h3><p class="hint">قیمت و تعداد هر نوع را جدا تغییر دهید و «ذخیره» را بزنید. قیمت پایه و موجودی کل خودکار محاسبه می‌شوند.</p>${(product.variations || []).map(item => `<fieldset class="variation-edit" data-product-variation="${item.id}"><legend>${esc(item.label)}</legend><div class="grid-2"><div class="field"><label for="variation-price-${item.id}">قیمت فروش، تومان</label><input id="variation-price-${item.id}" data-variation-field="price" inputmode="decimal" required autocomplete="off" value="${item.price}"></div><div class="field"><label for="variation-stock-${item.id}">تعداد</label><input id="variation-stock-${item.id}" data-variation-field="stock" inputmode="numeric" autocomplete="off" ${item.tracked ? "required" : ""} placeholder="${item.tracked ? "تعداد" : "بدون کنترل تعداد"}" value="${item.tracked ? item.stock : ""}"></div></div>${item.tracked ? "" : `<p class="hint">با واردکردن تعداد، مدیریت موجودی عددی این نوع فعال می‌شود؛ خالی بگذارید تا وضعیت فعلی حفظ شود.</p>`}</fieldset>`).join("") || `<p class="hint">نوعی دریافت نشده است؛ ابتدا کالا را از ووکامرس دریافت کنید.</p>`}</section>` : `<div class="field"><label for="product-stock">موجودی، عدد</label><input id="product-stock" name="stock" inputmode="numeric" autocomplete="off" value="${product.id ? product.stock : ""}"></div>`}
    <p class="hint">اگر سایت وصل باشد، نام، قیمت فروش و تعداد روی سایت هم می‌نشیند.</p>
    <div class="grid-2">
      <div class="field"><label>دسته‌بندی</label><input name="category_name" list="category-names" autocomplete="off" value="${esc(firstTermName(product, "categories"))}" placeholder="مثلاً عطر"></div>
      <div class="field"><label>برند</label><input name="brand_name" list="brand-names" autocomplete="off" value="${esc(firstTermName(product, "brands"))}" placeholder="مثلاً اشرافی"></div>
    </div>
    <datalist id="category-names">${termNameOptions("categories")}</datalist>
    <datalist id="brand-names">${termNameOptions("brands")}</datalist>
    <div class="field"><label for="price-per-gram">قیمت هر گرم عطر، تومان (اختیاری)</label><input id="price-per-gram" name="price_per_gram" inputmode="numeric" autocomplete="off" value="${product.price_per_gram || ""}" placeholder="فقط برای فروش گرمی عطر"><p class="hint">با واردکردن این نرخ، انتخاب عطر محاسبهٔ وزن و مبلغ را باز می‌کند. نرخ گرمی فقط در دفترچی است و قیمت یا موجودی سایت را تغییر نمی‌دهد. برای غیرفعال‌کردن، این فیلد را خالی کنید.</p></div>
    ${state.shopMode !== "offline" ? `<section class="stack">${termField("categories")}${termField("brands")}</section>` : ""}
    <div class="field"><label>شناسه کالا/خدمت مودیان</label><input class="ltr" name="tax_code" inputmode="numeric" autocomplete="off" placeholder="۱۳ رقم، اختیاری" value="${esc(product.tax_code || "")}"></div>
    <details ${product.id ? "open" : ""}>
      <summary>کد و تصویر</summary>
      <div class="field"><label>کد کالا</label><input name="sku" value="${esc(product.sku || "")}"></div>
      <div class="field"><label>آدرس تصویر</label><input class="ltr" name="image" value="${esc(product.image || "")}"></div>
      <div class="field"><label>یا بارگذاری تصویر</label><input type="file" name="file" accept="image/*"></div>
      ${product.image ? thumb(product.image, product.name) : ""}
    </details>
    <button class="btn primary" type="submit">ذخیره</button></form>`;
}
function modalHtml() {
  const modal = state.modal;
  if (!modal) return "";
  let inner = "";
  if (modal.type === "product") inner = productForm();
  if (modal.type === "perfume") {
    const product = modal.product;
    inner = `<h2 class="section-title">${esc(product.name)}</h2><p>قیمت هر گرم: <strong class="num">${money(product.price_per_gram)}</strong></p><div class="perfume-presets">${[1,3,5,10,20,30,50,100].map(weight => `<button type="button" class="btn" data-act="perfume-weight" data-weight="${weight}">${num(weight)} گرم</button>`).join("")}</div><div class="grid-2"><div class="field"><label for="perfume-grams">وزن عطر، گرم</label><input id="perfume-grams" inputmode="decimal" autocomplete="off" value="10"></div><div class="field"><label for="perfume-amount">مبلغ، تومان</label><input id="perfume-amount" inputmode="numeric" autocomplete="off"></div></div><output id="perfume-result" class="perfume-result" aria-live="polite"></output><p class="hint">وزن یا مبلغ دلخواه را بنویسید؛ طرف دیگر خودکار محاسبه می‌شود. واحد گرم است. این فروش موجودی عددی یا گرمی را تغییر نمی‌دهد.</p><button type="button" class="btn primary" data-act="add-perfume-line">افزودن به فاکتور</button>`;
  }
  if (modal.type === "perfume-reissue") inner = `<h2 class="section-title">اصلاح رسید گرمی</h2><p>وزن و نرخ این رسید در سوابق حفظ می‌شود. برای اصلاح آن، فاکتور را ابطال و از قسمت فروش رسید تازه صادر کنید.</p><p class="hint">این کار پرداخت بانکی را برگشت نمی‌زند؛ فقط سند دفترچی را ابطال می‌کند.</p><button type="button" class="btn danger" data-act="void-perfume-sale" data-id="${modal.sale.id}">ابطال فاکتور و بازگشت به فروش</button>`;
  if (modal.type === "variations") {
    const product = modal.product;
    inner = `<h2 class="section-title">${esc(product.name)}</h2><p class="hint">نوع متغیر را انتخاب کنید. موجودی هر نوع جداست.</p><div class="var-pick">${(product.variations || []).map((item) => `<button type="button" class="btn var-choice" data-act="pick-variation" data-id="${product.id}" data-variation="${item.id}" ${item.in_stock ? "" : "disabled"}><span>${esc(item.label)}</span><span class="num">${item.tracked ? `موجودی ${num(item.stock)}` : (item.in_stock ? "موجود" : "ناموجود")}</span><span class="num">${money(item.price)}</span></button>`).join("")}</div>`;
  }
  if (modal.type === "invoice-read" && modal.waiting) inner = `<h2 class="section-title">خواندن فاکتور</h2><p>گپ‌جی‌پی‌تی در حال خواندن عکس است. قلم‌ها با قیمت خرید و فروش کالاهای برنامه و سایت تطبیق داده می‌شوند.</p>`;
  if (modal.type === "invoice-read" && modal.invoice) inner = invoiceReadHtml(modal.invoice);
  if (modal.type === "pro") inner = `<h2 class="section-title">اکانت Pro</h2><p>${esc(modal.title || "بسته توکن")} · ${money(modal.payable)}</p><p class="hint">صفحه زیبال را باز کنید و پرداخت را تمام کنید. تا وقتی بانک تأیید نکند توکنی اضافه نمی‌شود. این توکن لایسنس فروشگاه و VIP نیست.</p><div class="row-actions">${modal.credit?.pay_url ? `<a class="btn" href="${esc(modal.credit.pay_url)}" target="_blank" rel="noopener">باز کردن زیبال</a>` : ""}<button type="button" class="btn primary" data-act="confirm-pro" data-id="${modal.credit?.id || ""}">پرداخت را انجام دادم</button></div>`;
  if (modal.type === "card" && modal.waiting) inner = `<h2 class="section-title">کارت‌خوان</h2><p>${esc(modal.message || "")}</p><p class="hint">مبلغ روی دستگاه است. مشتری کارت را می‌کشد و رمز را روی دستگاه می‌زند. تا تأیید نیاید فاکتور صادر نمی‌شود.</p>`;
  if (modal.type === "credit") inner = allowCredit()
    ? `<form data-act="credit-payment"><h2 class="section-title">نسیه / چک</h2><p class="hint">موبایل مشتری لازم است. چک تا زمان وصول به‌عنوان طلب مشتری ثبت می‌شود.</p><div class="field"><label>نوع تسویه</label><select id="credit-kind" name="credit_kind"><option value="credit">نسیه</option><option value="check">چک</option></select></div><div id="check-payment-fields" hidden><div class="field"><label>شماره چک</label><input name="check_number" maxlength="40"></div><div class="field"><label>سررسید (شمسی)</label><input name="check_due" placeholder="۱۴۰۵/۰۹/۲۰" autocomplete="off"></div></div><button type="submit" class="btn primary">ثبت فروش</button></form>`
    : `<h2 class="section-title">نسیه / چک</h2><p class="empty">در این نسخه نسیه و چک نیست.</p>`;
  if (modal.type === "card" && modal.approved) inner = `<h2 class="section-title">کارت‌خوان</h2><p>${esc(modal.message || "پرداخت روی کارت‌خوان تأیید شد.")}</p>${modal.ref ? `<p class="ltr">پیگیری ${esc(modal.ref)}</p>` : ""}<p class="hint">اگر فاکتور را می‌خواهید، صدور فاکتور را بزنید. بدون این دکمه فاکتوری ثبت نمی‌شود.</p><div class="row-actions"><button type="button" class="btn primary" data-act="confirm-card">صدور فاکتور</button><button type="button" class="btn" data-act="close-modal">بستن بدون فاکتور</button></div>`;
  if (modal.type === "card" && !modal.waiting && !modal.approved) inner = `<h2 class="section-title">کارت‌خوان</h2><p>${esc(modal.message || "کارت‌خوان وصل نشد.")}</p><p class="hint">فاکتوری ثبت نشد. آدرس را در تنظیمات ذخیره کنید و دوباره کارت‌خوان را بزنید.</p>`;
  if (modal.type === "gateway") inner = `<h2 class="section-title">پرداخت در مرورگر</h2><p>کد <span class="ltr">${esc(modal.sale.invoice_code || "")}</span> · فاکتور ${num(modal.sale.id)} · ${money(modal.sale.payable)}</p>${gatewayPanel(modal.sale)}<div class="row-actions"><button type="button" class="btn primary" data-act="verify-sale" data-id="${modal.sale.id}">استعلام تأیید</button><button type="button" class="btn danger" data-act="cancel-sale" data-id="${modal.sale.id}">لغو</button></div><form data-act="manual-gateway"><input type="hidden" name="id" value="${modal.sale.id}"><div class="field"><label>اگر در پنل درگاه پرداخت را دیدید، پیگیری را بنویسید</label><input name="ref" required></div><button class="btn" type="submit">ثبت دستی</button></form>`;
  if (modal.type === "pay-link") {
    const sale = modal.sale;
    inner = `<h2 class="section-title">لینک پرداخت</h2><p>کد <span class="ltr">${esc(sale.invoice_code || "")}</span> · ${money(sale.payable)} · ${esc(sale.customer_name || "")}</p>${gatewayPanel(sale)}<div class="row-actions"><button type="button" class="btn primary" data-act="verify-sale" data-id="${sale.id}">استعلام تأیید</button><button type="button" class="btn danger" data-act="cancel-sale" data-id="${sale.id}">لغو</button></div>`;
  }
  if (modal.type === "quick-pay") {
    inner = `<form data-act="quick-pay-link"><h2 class="section-title">لینک پرداخت سریع</h2><p class="hint">بدون سبد خرید؛ QR و لینک زیبال برای ارسال به مشتری.</p><div class="field"><label>مبلغ، تومان</label><input name="amount" inputmode="numeric" required value="${esc(modal.amount || "")}"></div><div class="field"><label>شرح روی فاکتور</label><input name="description" value="${esc(modal.description || "پرداخت آنلاین")}"></div><div class="field"><label>موبایل مشتری (اختیاری)</label><input class="ltr" name="phone" inputmode="tel" placeholder="09..."></div><button class="btn primary" type="submit">ساخت لینک و QR</button></form>`;
  }
  if (modal.type === "license") {
    const lic = modal.license;
    inner = `<h2 class="section-title">پرداخت لایسنس در زیبال</h2><p>${esc(lic.title)} · ${esc(lic.buyer_name)} · ${money(lic.payable)}</p>${lic.pay_url ? `<p><a href="${esc(lic.pay_url)}" target="_blank" rel="noopener">باز کردن صفحه زیبال</a></p>` : ""}<div class="row-actions"><button type="button" class="btn primary" data-act="verify-license" data-id="${lic.id}">استعلام تأیید</button><button type="button" class="btn danger" data-act="cancel-license" data-id="${lic.id}">لغو</button></div><form data-act="manual-license"><input type="hidden" name="id" value="${lic.id}"><div class="field"><label>اگر در پنل زیبال پرداخت را دیدید، پیگیری را بنویسید</label><input name="ref" required></div><button class="btn" type="submit">ثبت دستی</button></form>`;
  }
  if (modal.type === "receipt") inner = receiptHtml(modal);
  if (modal.type === "purchase-receipt") inner = purchaseReceiptHtml(modal);
  if (modal.type === "sms-invoice") inner = smsInvoiceHtml(modal.topup || {});
  if (modal.type === "import") {
    const preview = modal.preview;
    const rows = preview?.preview || [];
    const table = rows.length ? `<table><thead><tr><th>نام کالا</th><th>SKU</th><th>موجودی</th><th>قیمت فروش</th></tr></thead><tbody>
      ${rows.map((item) => `<tr><td>${esc(item.name)}</td><td class="ltr">${esc(item.sku || "—")}</td><td class="num">${num(item.stock)}</td><td class="num">${num(item.price)}</td></tr>`).join("")}
    </tbody></table>` : "";
    const problems = preview?.error_count ? `<p class="hint">${num(preview.error_count)} ردیف رد می‌شود. ${preview.errors.slice(0, 3).map((row) => `ردیف ${num(row.row)}: ${esc(row.error)}`).join(" · ")}</p>` : "";
    inner = `<h2 class="section-title">ورود محصولات با فایل اکسل</h2>
      <div class="row-actions">
        <label class="btn">انتخاب فایل<input id="excel-file" type="file" accept=".xlsx,.xlsm" hidden></label>
        <a class="btn" href="/api/products/template">دانلود قالب نمونه اکسل</a>
      </div>
      <p class="hint">${modal.name ? esc(modal.name) : "ستون‌ها را مثل قالب بگذارید: نام کالا، SKU، موجودی، قیمت فروش. قیمت صفر هم ذخیره می‌شود."}</p>
      ${preview ? `<p>پیش‌نمایش ${num(rows.length)} ردیف از ${num(preview.total)} کالا. ${num(preview.updating || 0)} کالا با نام یا کد قبلی به‌روز می‌شود و ${num(preview.creating || 0)} کالا تازه است.</p>${table}${problems}` : ""}
      <div class="row-actions">
        <button type="button" class="btn primary" data-act="commit-import" ${preview?.total ? "" : "disabled"}>ثبت ${num(preview?.total || 0)} کالا</button>
        <button type="button" class="btn" data-act="close-modal">انصراف</button>
      </div>`;
  }
  if (modal.type === "sale-edit") inner = saleEditHtml();
  if (modal.type === "password") {
    const first = state.mustChange && Boolean(state.initialPassword);
    const shown = first ? esc(state.initialPassword || "") : "";
    inner = `<form data-act="change-password"><h2 class="section-title">${first ? "تغییر رمز اولیه" : "تغییر رمز"}</h2>
      <p class="hint">${first ? "رمز اولیه را می‌بینید. همین را عوض کنید." : "اگر رمز را فراموش کرده‌اید، به جیمیل همین حساب فرستاده می‌شود."}</p>
      <div class="field"><label>رمز فعلی</label><input class="ltr" type="${first ? "text" : "password"}" name="current" required value="${shown}" autocomplete="off"></div>
      <div class="field"><label>رمز جدید</label><input type="password" name="password" minlength="6" required></div>
      <button class="btn primary" type="submit">ذخیره</button></form>
      ${first ? "" : `<button type="button" class="btn" data-act="mail-password">رمز را به جیمیل بفرست</button>`}
    <form data-act="attach-email"><h2 class="section-title">ورود با جیمیل</h2><p class="hint">همین جیمیل را ذخیره کنید و بعد با آن وارد شوید.</p><div class="field"><label>جیمیل</label><input class="ltr" name="email" type="email" value="${esc(state.user.email || "")}" required placeholder="name@gmail.com"></div><button class="btn primary" type="submit">ذخیره جیمیل</button></form>
    <form data-act="verify-email"><div class="field"><label>کد ۶ رقمی جیمیل</label><input class="ltr" name="code" inputmode="numeric" required></div><button class="btn" type="submit">تأیید جیمیل</button></form>`;
  }
  const wide = modal.type === "receipt" || modal.type === "purchase-receipt" || modal.type === "sale-edit" || modal.type === "sms-invoice" || modal.type === "invoice-read" ? "invoice-modal" : (modal.type === "import" ? "import-modal" : "");
  return `<div class="modal-back" data-act="close-modal"><div class="modal card ${wide}" data-act="stop"><div class="row-actions"><button type="button" class="btn" data-act="close-modal">بستن</button></div>${inner}</div></div>`;
}

function pageHtml() {
  if (state.access && !state.access.active && state.page !== "licenses" && state.page !== "settings" && state.page !== "updates") {
    return `<section class="card access-card"><h2>مهلت ۷ روز رایگان تمام شده</h2><p class="hint">فروش تا تمدید بسته است. روی لایسنس، «ادامه روزانه» (۱۵۰ هزار تومان برای هر روز) یا طرح‌های ماهانه تا پنجاه‌ساله است. اطلاعات فروشگاه سر جایش می‌ماند.</p><button type="button" class="btn primary" data-act="nav" data-page="licenses">دیدن نسخه‌ها</button></section>`;
  }
  const pages = {
    dashboard: dashboardHtml, pos: posHtml, products: productsHtml, inventory: inventoryHtml, checks: checksHtml, invoices: invoicesHtml, calendar: calendarHtml,
    accounts: accountsHtml, register: registerHtml, licenses: licensesHtml, customers: customersHtml,
    sms: smsHtml, hub: hubHtml, settings: settingsHtml, updates: updatesHtml, siteorders: siteOrdersHtml,
  };
  const draw = pages[state.page];
  return draw ? draw() : "";
}
function render() {
  applyTheme();
  if (!state.user) {
    $app().innerHTML = authHtml();
    return;
  }
  if (state.mustChange && state.initialPassword && !state.passwordPrompted && !state.modal) {
    state.modal = { type: "password" };
    state.passwordPrompted = true;
  }
  const shell = document.querySelector(".webapp");
  if (!shell || shell.dataset.user !== String(state.user.id) || shell.dataset.mode !== state.shopMode) $app().innerHTML = webShell();
  const view = document.getElementById("view");
  if (view) view.innerHTML = pageHtml();
  paintChrome();
  if (state.modal?.type === "perfume") window.DaftarchiPerfume.refresh(state.modal);
  if (state.focusBarcode) {
    document.getElementById("barcode")?.focus();
    state.focusBarcode = false;
  }
  if (state.focusName) {
    document.querySelector('form[data-act="save-product"] input[name="name"]')?.focus();
    state.focusName = false;
  } else if (state.page === "products" && !state.modal && !mobileBrowser()) {
    const active = document.activeElement;
    if (!active || active === document.body) document.getElementById("product-query")?.focus();
  }
  maybeAutoPrintReceipt();
}

async function printIssuedReceipt(sale) {
  if (!sale?.id) return;
  if (state.lastAutoPrint === sale.id) return;
  state.lastAutoPrint = sale.id;
  try {
    const result = await api("/api/printer/print", { method: "POST", body: { sale_id: sale.id, copies: 1 } });
    toast(result.message || "یک نسخه چاپ شد", { ok: true });
  } catch (err) {
    toast(err.message || "پرینتر وصل پیدا نشد", { error: true });
  }
}

async function printCurrentDocument() {
  const modal = state.modal;
  if (modal?.type === "receipt" && modal.sale?.id) {
    const result = await api("/api/printer/print", { method: "POST", body: { sale_id: modal.sale.id, copies: 1 } });
    toast(result.message || "یک نسخه چاپ شد", { ok: true });
    return;
  }
  if (modal?.type === "purchase-receipt" && modal.purchase?.id) {
    const result = await api("/api/printer/print", { method: "POST", body: { purchase_id: modal.purchase.id, copies: 1 } });
    toast(result.message || "یک نسخه چاپ شد", { ok: true });
    return;
  }
  throw new Error("فاکتور فروش یا خرید برای چاپ پیدا نشد");
}

function maybeAutoPrintReceipt() {
  const modal = state.modal;
  if (!modal || modal.type !== "receipt" || !modal.autoPrint) return;
  modal.autoPrint = false;
  const sale = modal.sale;
  window.setTimeout(() => {
    if (state.modal?.type === "receipt" && Number(state.modal.sale?.id) === Number(sale.id)) {
      printIssuedReceipt(sale);
    }
  }, 200);
}

function closePopup(popup) {
  try { if (popup && !popup.closed) popup.close(); } catch { /* پنجره را مرورگر بسته است */ }
}

async function startSmsTopup(amount) {
  const popup = window.open("about:blank", "daftarchi-sms");
  try {
    const result = await api("/api/sms/topup", { method: "POST", body: { amount } });
    const url = result.topup?.pay_url || "";
    if (!url) throw new Error("وب‌بیکری صفحه پرداخت شارژ را نداد");
    if (popup && !popup.closed) popup.location.href = url;
    else location.assign(url);
    toast(`کارمزد ${money(result.fee)} جدا از شارژ ${money(result.credit)} است`);
    watchSms(result.topup.id);
    await loaders.sms();
    render();
  } catch (err) {
    if (popup && !popup.closed) popup.close();
    throw err;
  }
}

function watchSms(id) {
  let tries = 0;
  const timer = setInterval(() => {
    tries += 1;
    if (tries > 40) {
      clearInterval(timer);
      return;
    }
    api(`/api/sms/topup/${id}/confirm`, { method: "POST", body: {} }).then(async (result) => {
      clearInterval(timer);
      toast(`موجودی پیامک ${money(result.balance)} شد`);
      await loaders.sms();
      if (result.topup?.invoice_code) state.modal = { type: "sms-invoice", topup: result.topup };
      render();
    }).catch((err) => {
      if (err.status === 409) return;
      clearInterval(timer);
    });
  }, 4000);
}

function watchLicense(id) {
  let tries = 0;
  const timer = setInterval(() => {
    tries += 1;
    if (tries > 40 || state.modal?.license?.id !== id) {
      clearInterval(timer);
      return;
    }
    api(`/api/licenses/${id}/confirm`, { method: "POST", body: { mode: "verify" } }).then(async (result) => {
      clearInterval(timer);
      state.modal = null;
      toast(`لایسنس فعال شد: ${money(result.payable)}`);
      await loaders.licenses();
      state.access = state.licenses?.access || state.access;
      render();
    }).catch((err) => {
      if (err.status === 409) return;
      clearInterval(timer);
    });
  }, 4000);
}

async function buyLicense(plan, _preset, popup) {
  state.plan = plan;
  const data = {
    plan,
    payment_method: "gateway",
    buyer_name: state.user?.name || "خریدار",
    phone: state.user?.phone || "",
    discount_value: "0",
    discount_type: "amount",
    expected_price: state.licenses?.plans?.find(item => item.id === plan)?.price,
  };
  try {
    const result = await api("/api/licenses", { method: "POST", body: data });
    const url = result.license?.pay_url || "";
    if (!url) {
      closePopup(popup);
      throw new Error("زیبال صفحه پرداخت نداد");
    }
    if (popup && !popup.closed) popup.location.href = url;
    else location.assign(url);
    state.modal = { type: "license", license: result.license };
    await loaders.licenses();
    state.access = state.licenses?.access || state.access;
    render();
    watchLicense(result.license.id);
  } catch (err) {
    closePopup(popup);
    if (err.status === 409) { await loaders.licenses(); render(); }
    throw err;
  }
}

async function onSubmit(event) {
  const form = event.target.closest("form[data-act]");
  if (!form) return;
  event.preventDefault();
  const act = form.dataset.act;
  const data = Object.fromEntries(new FormData(form).entries());
  if (!state.user) state.authDraft = data;
  const licensePopup = act === "sell-license" && !state.busy ? window.open("about:blank", "_blank") : null;
  await guard(async () => {
    if (act === "login") {
      try {
        const result = await api("/api/auth/login", { method: "POST", body: prepareLoginBody(data) });
        state.user = result.user;
        state.mustChange = result.must_change_password;
        state.initialPassword = result.initial_password || "";
        state.access = result.access || null;
        state.shop = result.shop || null;
        state.today = result.today || state.today;
        readSetup(result.setup);
        hideToast();
        await navigate(homePage());
      } catch (err) {
        if (err.data?.needs_phone_verify) {
          state.pendingPhone = err.data.ident || data.ident || data.phone;
          state.phoneNote = err.message;
          state.phoneCode = "";
          try {
            const sent = await api("/api/auth/register", { method: "POST", body: { phone: state.pendingPhone, password: data.password } });
            state.phoneCode = sent.preview_code || "";
            state.phoneNote = sent.delivery === "sms"
              ? "کد تأیید از ملی‌پیامک وب‌بیکری به همین شماره رفت."
              : (err.message || "کد تأیید را وارد کنید.");
          } catch { /* کد را از دکمه ارسال دوباره می‌گیرد */ }
          state.authView = "phone-verify";
          render();
          return;
        }
        if (err.data?.needs_verify) {
          state.pendingEmail = err.data.ident || data.ident;
          state.verifyNote = err.message;
          state.authView = "verify";
          render();
          return;
        }
        throw err;
      }
    } else if (act === "register") {
      if (!String(data.phone || "").trim() && !String(data.email || "").trim()) {
        throw new Error("موبایل یا جیمیل را بنویسید");
      }
      const result = await api("/api/auth/register", { method: "POST", body: data });
      state.authError = "";
      if (result.phone) {
        state.pendingPhone = result.phone;
        state.phoneCode = result.preview_code || "";
        state.phoneNote = result.delivery === "sms"
          ? "کد تأیید از ملی‌پیامک وب‌بیکری به همین شماره رفت."
          : "ملی‌پیامک وب‌بیکری الان پیام نفرستاد؛ کد روی همین صفحه است.";
        state.authView = "phone-verify";
        render();
      } else if (result.needs_verify) {
        state.pendingEmail = result.email;
        state.verifyNote = "کد شش‌رقمی به جیمیل فرستاده شد. صندوق ورودی و هرزنامه را ببینید.";
        state.authView = "verify";
        render();
      } else {
        state.user = result.user;
        state.mustChange = result.must_change_password;
        state.initialPassword = result.initial_password || "";
        hideToast();
        await navigate("dashboard");
      }
    } else if (act === "phone-verify" || act === "phone-enter") {
      const path = act === "phone-enter" ? "/api/auth/phone/enter" : "/api/auth/phone/verify";
      const result = await api(path, { method: "POST", body: data });
      state.user = result.user;
      state.mustChange = result.must_change_password;
      state.initialPassword = result.initial_password || "";
      state.access = result.access || null;
      state.shop = result.shop || null;
      state.today = result.today || state.today;
      readSetup(result.setup);
      state.authError = "";
      hideToast();
      await navigate(homePage());
    } else if (act === "phone-login") {
      const result = await api("/api/auth/phone/code", { method: "POST", body: data });
      state.pendingPhone = result.phone;
      state.phoneCode = result.preview_code || "";
      state.phoneNote = result.delivery === "sms"
        ? "کد ورود از ملی‌پیامک وب‌بیکری به همین شماره رفت."
        : "ملی‌پیامک وب‌بیکری الان پیام نفرستاد؛ کد روی همین صفحه است.";
      state.authError = "";
      state.authView = "phone-enter";
      render();
    } else if (act === "apply-invoice") {
      const count = asInt(data.count);
      const lines = [];
      for (let index = 0; index < count; index += 1) {
        lines.push({
          name: data[`name-${index}`] || "",
          qty: data[`qty-${index}`] || "1",
          unit_price: data[`price-${index}`] || "0",
          product_id: asInt(data[`product-${index}`] || "0"),
        });
      }
      const applied = await api("/api/invoices/apply", { method: "POST", body: { seller: data.seller || "", lines } });
      state.modal = applied.purchases?.length === 1 ? { type: "purchase-receipt", purchase: applied.purchases[0] } : null;
      toast("خرید از روی فاکتور ثبت شد", { ok: true });
      await loaders.invoices();
      render();
    } else if (act === "save-sale") {
      readSaleEdit();
      const modal = state.modal;
      if (!modal?.lines?.length) return toast("حداقل یک کالا لازم است");
      const phone = String(modal.phone || "")
        .replace(/[۰-۹]/g, (digit) => "۰۱۲۳۴۵۶۷۸۹".indexOf(digit))
        .replace(/[٠-٩]/g, (digit) => "٠١٢٣٤٥٦٧٨٩".indexOf(digit))
        .replace(/\D/g, "");
      if (modal.method === "credit" && !allowCredit()) return toast("در این نسخه نسیه و چک نیست");
      if (modal.method === "credit" && !/^09\d{9}$/.test(phone)) return toast("برای نسیه شماره موبایل لازم است");
      const result = await api(`/api/sales/${modal.sale.id}`, {
        method: "PUT",
        body: {
          items: modal.lines.map((line) => ({
            product_id: line.product_id,
            variation_id: line.variation_id || 0,
            qty: line.qty,
            unit_price: line.unit_price,
          })),
          discount_type: "amount",
          discount_value: asInt(modal.discount),
          payment_method: modal.method,
          customer_first: modal.first,
          customer_last: modal.last,
          customer_phone: phone,
          pos_ref: modal.posRef || "",
        },
      });
      state.modal = { type: "receipt", sale: result.sale, items: result.items };
      if (loaders[state.page]) await loaders[state.page]();
      toast("فاکتور اصلاح شد");
      render();
    } else if (act === "send-support") {
      await api("/api/support/messages", { method: "POST", body: {
        kind: "chat",
        body: data.body,
        page: state.page,
      } });
      state.supportLog = (await api("/api/support/messages")).messages;
      state.support = true;
      toast("ارسال شد", { ok: true });
      paintChrome();
    } else if (act === "verify") {
      await api("/api/auth/verify", { method: "POST", body: { ident: data.ident, code: data.code } });
      state.authView = "login";
      toast("جیمیل تأیید شد. حالا وارد شوید.");
      render();
    } else if (act === "attach-email") {
      const result = await api("/api/auth/email", { method: "POST", body: { email: data.email } });
      state.user.email = result.email;
      state.user.email_verified = result.needs_verify ? 0 : 1;
      toast(result.needs_verify ? "کد به جیمیل فرستاده شد" : "جیمیل ذخیره شد");
      render();
    } else if (act === "verify-email") {
      await api("/api/auth/verify", { method: "POST", body: { ident: state.user.email, code: data.code } });
      state.user = (await api("/api/auth/me")).user;
      state.modal = null;
      toast("جیمیل تأیید شد. از این به بعد با همین جیمیل هم می‌توانید وارد شوید.");
      render();
    } else if (act === "forgot") {
      const result = await api("/api/auth/forgot", { method: "POST", body: { ident: data.ident } });
      if (result.sent_password) {
        state.authView = "login";
        toast(result.detail || "رمز به جیمیل فرستاده شد");
        render();
        return;
      }
      state.recovery = { ident: data.ident, code: result.preview_code || "", delivery: result.delivery, detail: result.detail };
      state.authView = "reset";
      render();
    } else if (act === "reset") {
      await api("/api/auth/reset", { method: "POST", body: data });
      state.authView = "login";
      toast("رمز عوض شد. وارد شوید.");
      render();
    } else if (act === "search-site-orders") {
      state.siteOrderPage = 1; state.siteOrderSearch = data.search || ""; state.siteOrderStatus = data.status || "any";
      await loaders.siteorders(); render();
    } else if (act === "credit-payment") {
      if (!allowCredit()) return toast("در این نسخه نسیه و چک نیست");
      state.creditKind = data.credit_kind === "check" ? "check" : "credit";
      state.checkDetails = state.creditKind === "check" ? { number: data.check_number, due_on: data.check_due } : null;
      await submitSale("credit", "");
    } else if (act === "save-product") await saveProduct(form, data);
    else if (act === "bulk-price") {
      const select = form.querySelector("[name=term]");
      const option = select?.selectedOptions?.[0];
      const rawId = Number(select?.value);
      const body = {
        scope: data.scope,
        term_id: Number.isFinite(rawId) && rawId > 0 ? rawId : "",
        term_name: option?.dataset.name || "",
        direction: data.direction,
        mode: data.mode,
        value: data.value,
      };
      const group = data.scope === "brand" ? "برند" : "دسته";
      const action = data.direction === "decrease" ? "کاهش" : "افزایش";
      const how = data.mode === "percent" ? `${data.value} درصد` : `${data.value} تومان`;
      if (!window.confirm(`قیمت فروش کالاهای این ${group} ${action} شود (${how})؟`)) return;
      const result = await api("/api/products/bulk-price", { method: "POST", body });
      await loaders.products();
      toast(result.woo_sync_note ? `${result.message} ${result.woo_sync_note}` : (result.message || "قیمت‌ها عوض شد"));
      render();
    }
    else if (act === "save-settings") await saveSettings(form);
    else if (act === "save-check") {
      if (!allowCredit()) return toast("در این نسخه چک نیست");
      await api("/api/checks", { method: "POST", body: data });
      await loaders.checks();
      toast("چک ثبت شد");
      render();
    } else if (act === "save-bank-sms") {
      const saved = await api("/api/bank/sms", { method: "POST", body: data });
      await loaders.checks();
      toast(saved.message || "ثبت شد");
      render();
    } else if (act === "create-user") {
      await api("/api/users", { method: "POST", body: data });
      await loaders.settings();
      toast("کاربر ساخته شد");
      render();
    } else if (act === "change-password") {
      await api("/api/auth/password", { method: "POST", body: data });
      state.mustChange = false;
      state.initialPassword = "";
      state.modal = null;
      toast("رمز عوض شد");
      render();
    } else if (act === "adjust") {
      data.stock = asInt(data.stock);
      const result = await api("/api/inventory/adjust", { method: "POST", body: data });
      if (result.woo_sync_note) toast(result.woo_sync_note);
      await loaders.inventory();
      render();
    } else if (act === "purchase") {
      const result = await api("/api/purchases", { method: "POST", body: data });
      await loaders.inventory();
      state.modal = result.purchase ? { type: "purchase-receipt", purchase: result.purchase } : null;
      toast("خرید ثبت شد");
      render();
    } else if (act === "journal") await saveJournal(data);
    else if (act === "create-account") {
      await api("/api/accounts", { method: "POST", body: data });
      await loaders.accounts();
      render();
    } else if (act === "open-register") {
      await api("/api/register/open", { method: "POST", body: data });
      await loaders.register();
      render();
    } else if (act === "close-register") {
      const result = await api("/api/register/close", { method: "POST", body: data });
      toast(`صندوق بسته شد. اختلاف ${money(result.difference)}`);
      await loaders.register();
      render();
    } else if (act === "sell-license") {
      await buyLicense(state.plan, data, licensePopup);
    } else if (act === "save-prices") {
      await api("/api/licenses/prices", { method: "PUT", body: data });
      await loaders.licenses();
      toast("قیمت‌ها ذخیره شد");
      render();
    } else if (act === "receipt") {
      await api("/api/receipts", { method: "POST", body: data });
      await loaders.customers();
      toast("طلب دریافت شد");
      render();
    } else if (act === "send-sms") {
      const result = await api("/api/sms", { method: "POST", body: data });
      toast(result.status === "sent" ? `ارسال شد و ${money(result.cost)} از موجودی کم شد` : (result.detail || "ملی‌پیامک وب‌بیکری پیام را نفرستاد"));
      await loaders.sms();
      render();
    } else if (act === "sms-topup-custom") {
      await startSmsTopup(data.amount);
    } else if (act === "manual-gateway") {
      await api(`/api/sales/${data.id}/confirm`, { method: "POST", body: { mode: "manual", ref: data.ref } });
      state.modal = null;
      state.cart = [];
      await loaders.pos();
      toast("پرداخت ثبت شد");
      render();
    } else if (act === "manual-license") {
      const result = await api(`/api/licenses/${data.id}/confirm`, { method: "POST", body: { mode: "manual", ref: data.ref } });
      state.modal = null;
      toast(`لایسنس ثبت شد: ${money(result.payable)}`);
      await loaders.licenses();
      render();
    } else if (act === "quick-pay-link") {
      const amount = asInt(data.amount);
      if (amount < 1000) {
        toast("حداقل مبلغ ۱۰۰۰ تومان است");
        return;
      }
      const result = await api("/api/payments/quick-link", {
        method: "POST",
        body: {
          amount,
          description: data.description || "پرداخت آنلاین",
          phone: data.phone || "",
        },
      });
      state.modal = { type: "pay-link", sale: result.sale };
      toast("لینک پرداخت آماده است");
      render();
    }
  });
}

async function openProductFromBarcode(code) {
  const trimmed = String(code || "").trim();
  if (!trimmed) return;
  try {
    const data = await api(`/api/products/scan?code=${encodeURIComponent(trimmed)}`);
    state.modal = { type: "product", product: data.product };
  } catch (error) {
    if (error.status !== 404) throw error;
    state.modal = { type: "product", product: { barcode: trimmed } };
  }
  state.page = "products";
  state.focusName = true;
  render();
}
async function saveProduct(form, data) {
  const file = form.querySelector('input[type="file"]')?.files?.[0];
  for (const key of ["price", "stock", "cost", "price_per_gram"]) {
    if (data[key] !== undefined) data[key] = productNumberText(data[key]);
  }
  const variations = [...form.querySelectorAll("[data-product-variation]")].map(row => {
    const item = { id: Number(row.dataset.productVariation), price: productNumberText(row.querySelector('[data-variation-field="price"]').value) };
    const stock = productNumberText(row.querySelector('[data-variation-field="stock"]').value);
    if (stock !== "") item.stock = stock;
    return item;
  });
  if (variations.length) data.variations = variations;
  if (file) {
    const body = new FormData();
    body.append("file", file);
    const uploaded = await api("/api/upload", { method: "POST", form: body });
    data.image = uploaded.url;
  }
  delete data.file;
  for (const [kind, terms] of Object.entries(state.modal?.termDraft || {})) data[kind] = terms;
  if (!String(data.barcode || "").trim()) data.assign_barcode = "1";
  const id = data.id;
  delete data.id;
  let result;
  if (id) {
    result = await api(`/api/products/${id}`, { method: "PATCH", body: data });
  } else {
    result = await api("/api/products", { method: "POST", body: data });
  }
  state.modal = null;
  await loaders.products();
  const made = result.product?.barcode;
  const message = data.assign_barcode && made ? `کالا ذخیره شد. بارکد ${made}` : "کالا ذخیره شد";
  toast(result.woo_sync_note ? `${message}. ${result.woo_sync_note}` : message);
  render();
}

function productNumberText(value) {
  return String(value ?? "").trim()
    .replace(/[۰-۹]/g, digit => "۰۱۲۳۴۵۶۷۸۹".indexOf(digit))
    .replace(/[٠-٩]/g, digit => "٠١٢٣٤٥٦٧٨٩".indexOf(digit))
    .replace(/[,٬\s]/g, "").replace(/٫/g, ".");
}
async function saveSettings(form) {
  const data = Object.fromEntries(new FormData(form).entries());
  const file = form.querySelector('input[name="logo_file"]')?.files?.[0];
  if (file) {
    const body = new FormData();
    body.append("file", file);
    const uploaded = await api("/api/upload", { method: "POST", form: body });
    data.shop_logo = uploaded.url;
  }
  if (form.querySelector('[name="clear_shop_logo"]')?.checked) data.shop_logo = "";
  delete data.logo_file;
  delete data.clear_shop_logo;
  for (const role of ["buyer", "manager"]) {
    const picked = state.smsCatalog?.find((item) => item.id === (data[`sms_choice_${role}`] || ""));
    if (picked) data[`sms_pattern_${role}`] = picked.text;
  }
  for (const key of ["gateway_sandbox", "sms_on_close", "sms_on_sale", "sms_low_stock", "moadian_enabled", "moadian_sandbox", "woo_auto_sync", "backup_auto"]) {
    const box = form.querySelector(`[name="${key}"]`);
    if (box) data[key] = box.checked ? "1" : "0";
  }
  state.settings = (await api("/api/settings", { method: "PUT", body: data })).settings;
  state.shop = { name: state.settings.shop_name || "دفترچی", logo: state.settings.shop_logo || "", theme: state.settings.ui_theme || "light", moadian_enabled: state.settings.moadian_enabled === "1", site_connected: Boolean(state.settings.wp_url && state.settings.woo_key && state.settings.woo_secret), allow_credit: allowCredit() };
  state.siteTerms = {};
  applyTheme(state.settings.ui_theme);
  toast("تنظیمات ذخیره شد");
  render();
}
async function saveJournal(data) {
  const amount = asInt(data.amount);
  if (!amount) return toast("مبلغ را وارد کنید");
  if (data.account_id === data.counter_id) return toast("دو حساب باید متفاوت باشند");
  const first = Number(data.account_id);
  const second = Number(data.counter_id);
  const lines = data.side === "credit"
    ? [{ account_id: first, debit: 0, credit: amount }, { account_id: second, debit: amount, credit: 0 }]
    : [{ account_id: first, debit: amount, credit: 0 }, { account_id: second, debit: 0, credit: amount }];
  await api("/api/journal", { method: "POST", body: { memo: data.memo, lines } });
  await loaders.accounts();
  toast("سند ثبت شد");
  render();
}
function buyerFields() {
  syncPos();
  const first = state.buyerFirst.trim();
  const last = state.buyerLast.trim();
  const phone = String(state.buyerPhone || "")
    .replace(/[۰-۹]/g, (digit) => String("۰۱۲۳۴۵۶۷۸۹".indexOf(digit)))
    .replace(/[٠-٩]/g, (digit) => String("٠١٢٣٤٥٦٧٨٩".indexOf(digit)))
    .replace(/\D/g, "");
  if (phone && !/^09\d{9}$/.test(phone)) {
    toast("شماره موبایل را کامل وارد کنید یا خالی بگذارید");
    return null;
  }
  return { first, last, phone };
}
async function openSaleEdit(id) {
  const detail = await api(`/api/sales/${id}`);
  if (detail.items.some(item => item.weight_grams)) { state.modal = { type: "perfume-reissue", sale: detail.sale }; render(); return; }
  if (detail.sale.payment_method === "gateway") {
    toast("فاکتور درگاه را از اینجا عوض نکنید", { error: true });
    return;
  }
  if (!state.products?.length) state.products = stampProducts((await api("/api/products")).products);
  const parts = String(detail.sale.customer_name || "").trim().split(/\s+/).filter(Boolean);
  state.modal = {
    type: "sale-edit",
    sale: detail.sale,
    lines: detail.items.map((item) => ({
      product_id: item.product_id,
      variation_id: item.variation_id || 0,
      name: item.name,
      qty: item.qty,
      unit_price: item.unit_price,
    })),
    discount: detail.sale.discount ? String(detail.sale.discount) : "",
    method: detail.sale.payment_method === "credit" ? "credit" : (detail.sale.payment_method === "card" ? "card" : "cash"),
    first: parts[0] || "",
    last: parts.slice(1).join(" "),
    phone: detail.sale.customer_phone || "",
    posRef: detail.sale.pos_ref || "",
  };
  render();
}
async function sendCartToCardReader() {
  const amount = cartSubtotal() - cartDiscount();
  if (amount <= 0) return toast("مبلغ باید بیشتر از صفر باشد");
  state.modal = { type: "card", waiting: true, approved: false, message: `مبلغ ${money(amount)} (${num(amount * 10)} ریال) به کارت‌خوان فرستاده شد. مبلغ را روی دستگاه ببینید، کارت را بکشید و رمز را روی دستگاه بزنید. پین را در دفترچی وارد نکنید.` };
  render();
  let paid;
  try {
    paid = await api("/api/pos/pay", { method: "POST", body: { amount } });
  } catch (err) {
    state.modal = { type: "card", waiting: false, approved: false, message: err.message || "کارت‌خوان پرداخت را تأیید نکرد", ok: false };
    render();
    throw err;
  }
  if (!paid.sent) {
    state.modal = { type: "card", waiting: false, approved: false, message: paid.message || "کارت‌خوان وصل نشد", ok: false };
    render();
    return;
  }
  state.modal = { type: "card", waiting: false, approved: true, ref: paid.ref || "", message: paid.message || "پرداخت روی کارت‌خوان تأیید شد." };
  render();
}

async function submitSale(method, posRef, popup) {
  const buyer = buyerFields();
  if (!buyer || !state.cart.length) {
    closePopup(popup);
    if (!buyer) return;
    return toast("سبد خالی است");
  }
  if (method === "credit" && !allowCredit()) {
    closePopup(popup);
    return toast("در این نسخه نسیه و چک نیست");
  }
  if (method === "credit" && !/^09\d{9}$/.test(buyer.phone)) {
    closePopup(popup);
    return toast("برای نسیه شماره موبایل لازم است");
  }
  let result;
  try {
    result = await api("/api/sales", {
    method: "POST",
    body: {
      items: state.cart.map((line) => ({ product_id: line.id, variation_id: line.variationId || 0, qty: line.qty, ...(line.perfume ? { perfume: { basis: line.perfume.basis, input: line.perfume.input, expected_rate: line.perfume.rate } } : {}) })),
      discount_type: state.discountType,
      discount_value: state.discountType === "percent" ? Math.round(asInt(state.discountValue)) : asInt(state.discountValue),
      payment_method: method,
      customer_first: buyer.first,
      customer_last: buyer.last,
      customer_phone: buyer.phone,
      pos_ref: posRef || "",
      send_moadian: state.sendMoadian === true,
      credit_kind: state.creditKind || "credit",
      check: state.checkDetails,
    },
  });
  } catch (err) {
    closePopup(popup);
    throw err;
  }
  hideToast();
  if (result.sale.status === "pending") {
    const url = result.sale.pay_url || "";
    if (url && popup && !popup.closed) popup.location.href = url;
    else if (url) window.open(url, "_blank", "noopener");
    state.modal = { type: "gateway", sale: result.sale };
  } else {
    closePopup(popup);
    state.cart = [];
    state.discountValue = "";
    state.buyerFirst = "";
    state.buyerLast = "";
    state.buyerPhone = "";
    state.modal = state.receiptMode === "none" ? null : { type: "receipt", layout: state.receiptMode, sale: result.sale, items: result.items, sms: result.sms, autoPrint: true };
    state.sendMoadian = false;
    state.checkDetails = null;
    state.creditKind = "credit";
    toast("فروش ثبت شد", { ok: true });
  }
  await loaders.pos();
  render();
}

async function onClick(event) {
  if (!event.target.closest("#cal-pop") && !event.target.closest(".jalali-date-wrap")) closeJalaliPicker();
  const el = event.target.closest("[data-act]");
  if (!el || el.tagName === "FORM") return;
  if (el.closest("form[data-act]") && el.type === "submit") return;
  if (el.dataset.act === "stop") { event.stopPropagation(); return; }
  const act = el.dataset.act;
  if (act === "insert-sms-var") {
    const field = document.querySelector(`[name="${el.dataset.field}"]`);
    const token = `{${el.dataset.token || ""}}`;
    if (!field || token === "{}") return;
    const start = field.selectionStart ?? field.value.length;
    const end = field.selectionEnd ?? start;
    field.value = field.value.slice(0, start) + token + field.value.slice(end);
    field.focus();
    const caret = start + token.length;
    field.setSelectionRange(caret, caret);
    return;
  }
  if (act === "close-modal") { state.modal = null; render(); return; }
  if (act === "edit-sale") {
    await guard(() => openSaleEdit(el.dataset.id));
    return;
  }
  if (act === "delete-sale") {
    const saleId = el.dataset.id;
    if (!saleId || !window.confirm("این فاکتور حذف شود؟ کالاها به موجودی برمی‌گردند و حساب به قبل از این فروش برمی‌گردد.")) return;
    await guard(async () => {
      const result = await api(`/api/sales/${saleId}`, { method: "DELETE" });
      state.modal = null;
      if (loaders[state.page]) await loaders[state.page]();
      toast(result.message || "فاکتور حذف شد و کالاها به موجودی برگشت");
      render();
    });
    return;
  }
  if (act === "edit-sale-add") {
    readSaleEdit();
    const picked = document.getElementById("edit-add")?.value || "";
    const [pid, vid] = picked.split(":").map((part) => Number(part || 0));
    const product = (state.products || []).find((row) => row.id === pid);
    if (!product || !state.modal) return;
    const variation = vid ? (product.variations || []).find((row) => row.id === vid) : null;
    if (product.type === "variable" && !variation) return toast("نوع کالا را انتخاب کنید");
    const name = variation ? `${product.name} — ${variation.label}` : product.name;
    const price = variation ? variation.price : product.price;
    const found = state.modal.lines.find((line) => line.product_id === pid && Number(line.variation_id || 0) === (vid || 0));
    if (found) found.qty += 1;
    else state.modal.lines.push({ product_id: pid, variation_id: vid || 0, name, qty: 1, unit_price: price });
    render();
    return;
  }
  if (act === "edit-sale-remove") {
    readSaleEdit();
    const index = Number(el.dataset.index);
    if (state.modal?.lines) state.modal.lines.splice(index, 1);
    render();
    return;
  }
  if (act === "make-backup") {
    await guard(async () => {
      state.backups = await api("/api/backups", { method: "POST" });
      toast("بکاپ ذخیره شد");
      render();
    });
    return;
  }
  if (act === "desktop-icon") { createDesktopIcon(); return; }
  if (act === "install-app") { await guard(installMobileApp); return; }
  if (act === "delete-user") {
    if (!window.confirm("این کاربر حذف شود؟")) return;
    await guard(async () => {
      const result = await api(`/api/users/${el.dataset.id}`, { method: "DELETE" });
      if (result.logged_out) { state.user = null; state.modal = null; state.authView = "login"; state.authError = "حساب شما حذف شد؛ با حساب مدیر دیگر وارد شوید."; render(); return; }
      await loaders.settings();
      toast("کاربر حذف شد");
      render();
    });
    return;
  }
  if (act === "show-forgot") { state.authView = "forgot"; state.authError = ""; render(); return; }
  if (act === "show-phone-login") { state.authView = "phone-login"; state.authError = ""; render(); return; }
  if (act === "show-register") { state.authView = "register"; state.authError = ""; render(); return; }
  if (act === "show-login") { state.authView = "login"; state.authError = ""; render(); return; }
  if (act === "resend-phone" || act === "resend-phone-login") {
    await guard(async () => {
      const phone = state.pendingPhone || document.querySelector('[name="phone"]')?.value || "";
      const path = act === "resend-phone-login" ? "/api/auth/phone/code" : "/api/auth/register";
      const result = await api(path, { method: "POST", body: { phone } });
      state.pendingPhone = result.phone || phone;
      state.phoneCode = result.preview_code || "";
      state.phoneNote = result.delivery === "sms" ? "کد تازه از ملی‌پیامک وب‌بیکری رفت." : "کد تازه روی همین صفحه است.";
      toast("کد دوباره فرستاده شد");
      render();
    });
    return;
  }
  if (act === "resend-verify") {
    await guard(async () => {
      await api("/api/auth/verify/resend", { method: "POST", body: { ident: state.pendingEmail || document.querySelector('[name="ident"]')?.value || "" } });
      state.verifyNote = "کد تازه به جیمیل فرستاده شد.";
      toast("کد دوباره فرستاده شد");
      render();
    });
    return;
  }
  if (act === "pick-mode") { await guard(() => chooseMode(el.dataset.mode)); return; }
  if (act === "skip-mode-setup") { await guard(() => skipModeSetup()); return; }
  if (act === "connect-server") {
    const url = normalizeServerUrl(document.querySelector('[name="server_url"]')?.value || "");
    if (!url) { toast("آدرس سرور درست نیست. مثال: http://192.168.1.10:8080", { error: true }); return; }
    localStorage.setItem(SERVER_STORAGE_KEY, url);
    window.location.replace(`${url}/`);
    return;
  }
  if (act === "clear-server") {
    localStorage.removeItem(SERVER_STORAGE_KEY);
    toast("این نصب محلی است");
    render();
    return;
  }
  if (act === "open-shop") {
    const url = normalizeServerUrl(el.dataset.url || "");
    if (!url) { toast("آدرس سرور خالی است", { error: true }); return; }
    const site = el.dataset.site || "";
    if (site) sessionStorage.setItem("daftarchi_shop_target", site);
    localStorage.setItem(SERVER_STORAGE_KEY, url);
    window.location.replace(`${url}/`);
    return;
  }
  if (act === "copy-hub-link") {
    const text = hubUnifiedLink();
    try {
      await navigator.clipboard.writeText(text);
      toast("لینک کپی شد");
    } catch {
      toast(text);
    }
    return;
  }
  if (act === "save-hub-shops") {
    await guard(async () => {
      const shops = (state.hub?.shops || []).map((row, index) => {
        const pick = (field) => document.querySelector(`[data-hub-field="${field}"][data-hub-index="${index}"]`)?.value;
        return {
          id: row.id,
          label: pick("label") || row.label,
          site_id: pick("site_id") || row.site_id,
          url: pick("url") || "",
          note: pick("note") || "",
        };
      });
      const saved = await api("/api/hub/shops", { method: "PUT", body: { shops } });
      state.hub = { ...(state.hub || {}), shops: saved.shops || shops };
      toast("آدرس فروشگاه‌ها ذخیره شد");
      render();
    });
    return;
  }
  if (act === "pick-theme") {
    await guard(async () => {
      const id = applyTheme(el.dataset.theme);
      const saved = await api("/api/settings", { method: "PUT", body: { ui_theme: id } });
      state.settings = saved.settings;
      if (state.shop) state.shop.theme = id;
      toast("تم عوض شد");
      render();
    });
    return;
  }
  if (act === "tour-next") { await guard(() => goTour(1)); return; }
  if (act === "tour-back") { await guard(() => goTour(-1)); return; }
  if (act === "tour-skip") { await guard(() => finishTour()); return; }
  if (act === "tour-replay") { await guard(() => replayTour()); return; }
  if (act === "nav") { await guard(() => navigate(el.dataset.page)); return; }
  if (act === "chart") { state.chart = el.dataset.range; render(); return; }
  if (act === "report-metric") { state.reportMetric = el.dataset.metric === "count" ? "count" : "total"; render(); return; }
  if (act === "product-report-metric") { state.productMetric = el.dataset.metric === "qty" ? "qty" : "total"; render(); return; }
  if (act === "refresh-reports") { await guard(async () => { await loaders.dashboard(); render(); }); return; }
  if (act === "cal-month") {
    const delta = Number(el.dataset.delta || 0);
    let year = state.calYear || state.today?.year;
    let month = (state.calMonth || state.today?.month || 1) + delta;
    if (month < 1) { month = 12; year -= 1; }
    if (month > 12) { month = 1; year += 1; }
    state.calYear = year;
    state.calMonth = month;
    await guard(() => loaders.calendar().then(() => render()));
    return;
  }
  if (act === "cal-today") {
    state.calYear = state.today?.year || 0;
    state.calMonth = state.today?.month || 0;
    await guard(async () => {
      const data = await api("/api/calendar");
      state.calendar = data;
      state.today = data.today || state.today;
      state.calYear = data.year;
      state.calMonth = data.month;
      render();
    });
    return;
  }
  if (act === "cal-day") {
    state.calYear = Number(el.dataset.year);
    state.calMonth = Number(el.dataset.month);
    await guard(async () => {
      const data = await api(`/api/calendar?year=${el.dataset.year}&month=${el.dataset.month}&day=${el.dataset.day}`);
      state.calendar = data;
      state.today = data.today || state.today;
      render();
    });
    return;
  }
  if (act === "open-datepicker") {
    event.preventDefault();
    const input = el.dataset.name
      ? document.querySelector(`[name="${el.dataset.name}"]`)
      : document.getElementById(el.dataset.target);
    await openJalaliPicker(input);
    return;
  }
  if (act === "picker-month") {
    event.preventDefault();
    const pop = document.getElementById("cal-pop");
    const input = pop?.parentElement?.querySelector("input.jalali-date");
    if (!pop || !input) return;
    let year = Number(pop.dataset.year);
    let month = Number(pop.dataset.month) + Number(el.dataset.delta || 0);
    if (month < 1) { month = 12; year -= 1; }
    if (month > 12) { month = 1; year += 1; }
    await fillJalaliPicker(pop, year, month, input);
    return;
  }
  if (act === "pick-day") {
    event.preventDefault();
    const pop = document.getElementById("cal-pop");
    const input = pop?.parentElement?.querySelector("input.jalali-date");
    if (input) {
      input.value = el.dataset.date;
      input.dispatchEvent(new Event("input", { bubbles: true }));
    }
    closeJalaliPicker();
    return;
  }
  if (act === "dismiss") {
    const id = el.dataset.id || "";
    const bag = dismissedNotices();
    bag[id] = true;
    try { sessionStorage.setItem("daftarchi-dismiss", JSON.stringify(bag)); } catch (error) { /* keep it for this visit */ }
    if (id.startsWith("scan:")) state.scanHit = null;
    if (id === "import") state.importResult = null;
    render();
    return;
  }
  if (act === "sms-topup") { await guard(() => startSmsTopup(el.dataset.amount)); return; }
  if (act === "open-sms-invoice") {
    const topup = (state.smsWallet?.topups || []).find((row) => String(row.id) === String(el.dataset.id));
    if (topup) {
      state.modal = { type: "sms-invoice", topup };
      render();
    }
    return;
  }
  if (act === "send-customer-sms") {
    await guard(async () => {
      const body = document.getElementById("sms-body")?.value || "";
      const ids = [...document.querySelectorAll("input[name=sms-customer]:checked")].map((item) => Number(item.value));
      const result = await api("/api/sms/customers", { method: "POST", body: { body, ids } });
      toast(result.failed ? `${num(result.sent)} پیام رفت و ${num(result.failed)} تا نرفت` : `${num(result.sent)} پیام از ملی‌پیامک وب‌بیکری رفت`);
      await loaders.sms();
      render();
    });
    return;
  }
  if (act === "confirm-sms") {
    await guard(async () => {
      const result = await api(`/api/sms/topup/${el.dataset.id}/confirm`, { method: "POST", body: {} });
      toast(`موجودی پیامک ${money(result.balance)} شد`);
      await loaders.sms();
      if (result.topup?.invoice_code) state.modal = { type: "sms-invoice", topup: result.topup };
      render();
    });
    return;
  }
  if (act === "pick-period") {
    state.billingPeriod = el.dataset.period || "3m";
    render();
    return;
  }
  if (act === "pick-pro-pack") {
    state.proPackId = el.dataset.pack || "pro50";
    render();
    return;
  }
  if (act === "pick-plan") {
    const popup = window.open("about:blank", "_blank");
    await guard(() => buyLicense(el.dataset.plan, null, popup));
    return;
  }
  if (act === "buy-pro") {
    const popup = window.open("about:blank", "_blank");
    await guard(async () => {
      try {
        const result = await api("/api/pro/packs", { method: "POST", body: { pack: el.dataset.pack } });
        const url = result.credit?.pay_url || "";
        if (!url) {
          closePopup(popup);
          throw new Error("زیبال صفحه پرداخت نداد");
        }
        if (popup && !popup.closed) popup.location.href = url;
        else location.assign(url);
        const pack = (result.pro?.packs || []).find((item) => item.id === result.credit?.pack);
        state.modal = { type: "pro", credit: result.credit, payable: result.payable, title: pack?.title || "" };
        state.pro = result.pro;
        render();
      } catch (err) {
        closePopup(popup);
        throw err;
      }
    });
    return;
  }
  if (act === "confirm-pro") {
    await guard(async () => {
      const result = await api(`/api/pro/credits/${el.dataset.id}/confirm`, { method: "POST", body: {} });
      state.pro = result.pro;
      state.modal = null;
      toast(`توکن پرو ${num(result.pro?.tokens || 0)} شد`, { ok: true });
      if (state.page === "licenses") await loaders.licenses();
      render();
    });
    return;
  }
  if (act === "printer-settings") { window.DaftarchiPrinter.open(); return; }
  if (act === "detect-printer") {
    await guard(async () => {
      const result = await api("/api/printer");
      toast(result.message || "پرینتر بررسی شد", { ok: Boolean(result.ready), error: !result.ready });
    });
    return;
  }
  if (act === "clear-print-memory") {
    if (!window.confirm("صف چاپ پاک شود؟ فاکتورهایی که در حافظه پرینتر یا ویندوز مانده‌اند دیگر چاپ نمی‌شوند.")) return;
    await guard(async () => {
      const result = await window.DaftarchiPrinter.clearMemory();
      toast(result.message || "حافظه چاپ پاک شد", { ok: true });
    });
    return;
  }
  if (act === "print") { await guard(() => printCurrentDocument()); return; }
  if (act === "copy-text") {
    const node = document.getElementById(el.dataset.target || "");
    const text = node?.value || node?.textContent || "";
    if (text && navigator.clipboard?.writeText) {
      navigator.clipboard.writeText(text).then(() => toast("کپی شد")).catch(() => toast("کپی نشد"));
    }
    return;
  }
  if (act === "quick-pay-link") {
    state.modal = { type: "quick-pay", amount: cartSubtotal() - cartDiscount() || "" };
    render();
    return;
  }
  if (act === "password-modal") { state.modal = { type: "password" }; render(); return; }
  if (act === "mail-password") {
    await guard(async () => {
      const result = await api("/api/auth/password/mail", { method: "POST" });
      toast(result.detail || "رمز به جیمیل فرستاده شد");
    });
    return;
  }
  if (act === "toggle-support") {
    if (state.support) {
      state.support = false;
      paintChrome();
      return;
    }
    await guard(async () => {
      state.support = true;
      state.supportLog = (await api("/api/support/messages")).messages;
      paintChrome();
    });
    return;
  }
  if (act === "pay" && el.dataset.method === "gateway") {
    if (!buyerFields()) return;
    const popup = window.open("about:blank", "_blank");
    await guard(() => submitSale("gateway", "", popup));
    return;
  }
  if (act === "open-import") { state.modal = { type: "import", name: "", preview: null }; render(); return; }
  if (act === "more-products" || act === "more-pos" || act === "more-inventory") {
    if (act === "more-products") state.productLimit += PAGE_SIZE;
    if (act === "more-pos") state.posLimit += PAGE_SIZE;
    if (act === "more-inventory") state.inventoryLimit += PAGE_SIZE;
    const box = document.getElementById(act === "more-pos" ? "pos-grid" : act === "more-inventory" ? "inventory-box" : "product-table");
    if (box) box.innerHTML = act === "more-pos" ? posTiles() : act === "more-inventory" ? inventoryTable() : productRows();
    return;
  }
  await guard(async () => {
    if (act === "logout") {
      await api("/api/auth/logout", { method: "POST" });
      state.user = null;
      state.showArchivedProducts = false;
      state.archivedProducts = [];
      state.cart = [];
      state.bootstrap = await api("/api/auth/bootstrap");
      state.authView = "login";
      history.replaceState(null, "", location.pathname);
      render();
    } else if (act === "add-cart") {
      syncPos();
      addProduct(state.products.find((item) => item.id === Number(el.dataset.id)));
    } else if (act === "pick-variation") {
      const product = state.products.find((item) => item.id === Number(el.dataset.id)) || state.modal?.product;
      addProduct(product, variationOf(product, el.dataset.variation));
    } else if (act === "inc" || act === "dec" || act === "remove-line") {
      syncPos();
      const line = state.cart.find((item) => el.dataset.perfumeKey ? item.perfumeKey === el.dataset.perfumeKey : !item.perfume && item.id === Number(el.dataset.id) && (item.variationId || 0) === Number(el.dataset.variation || 0));
      if (!line) return;
      if (act === "remove-line") state.cart = state.cart.filter((item) => item !== line);
      else       if (act === "inc") {
        if (line.tracked !== false && line.qty + 1 > line.stock) return toast("موجودی کافی نیست");
        line.qty += 1;
      } else line.qty -= 1;
      if (line.qty <= 0) state.cart = state.cart.filter((item) => item !== line);
      render();
    } else if (act === "pay") {
      if (!buyerFields()) return;
      if (!state.cart.length) return toast("سبد خالی است");
      if (el.dataset.method === "card") {
        await sendCartToCardReader();
      } else if (el.dataset.method === "credit") {
        if (!allowCredit()) return toast("در این نسخه نسیه و چک نیست");
        state.modal = { type: "credit" }; render();
      } else await submitSale(el.dataset.method, "");
    } else if (act === "confirm-card") {
      if (!state.modal || state.modal.type !== "card" || !state.modal.approved) return toast("اول پرداخت روی دستگاه تأیید شود");
      await submitSale("card", state.modal.ref || "");
    } else if (act === "perfume-weight") {
      const input = document.getElementById("perfume-grams"); if (input) input.value = el.dataset.weight;
      window.DaftarchiPerfume.refresh(state.modal, "weight");
    } else if (act === "add-perfume-line") {
      const modal = state.modal; window.DaftarchiPerfume.refresh(modal);
      const draft = modal?.perfumeQuote; if (!draft) return;
      const result = await api(`/api/products/${modal.product.id}/perfume/quote`, { method: "POST", body: { basis: draft.basis, input: draft.input, expected_rate: draft.rate } });
      const quote = result.quote;
      state.cart.push({ id: modal.product.id, variationId: 0, perfumeKey: `p${Date.now()}-${state.cart.length}`, perfume: quote,
        name: `${modal.product.name} — ${quote.approximate ? "حدود " : ""}${window.DaftarchiPerfume.formatGrams(quote.grams)} گرم`,
        price: quote.amount, qty: 1, tracked: false, stock: 1000000, image: modal.product.image });
      state.modal = null; state.focusBarcode = true; render();
    } else if (act === "void-perfume-sale") {
      if (!window.confirm("این فاکتور در دفترچی ابطال شود؟ برای اصلاح فروش باید رسید تازه صادر کنید.")) return;
      await api(`/api/sales/${el.dataset.id}`, { method: "DELETE" }); state.modal = null; await navigate("pos"); toast("فاکتور ابطال شد؛ رسید تازه را از فروش ثبت کنید");
    } else if (act === "receipt-layout") {
      if (state.modal?.type === "receipt") { state.modal.layout = el.dataset.layout === "invoice" ? "invoice" : "receipt"; render(); }
    } else if (act === "site-orders-page") {
      state.siteOrderPage = Math.max(1, Number(el.dataset.page) || 1); await loaders.siteorders(); render();
    } else if (act === "load-site-terms") {
      await loadSiteTerms(el.dataset.kind);
    } else if (act === "create-site-term") {
      const kind = el.dataset.kind, name = document.getElementById(`new-term-${kind}`)?.value.trim();
      if (!name) return toast("نام دسته‌بندی یا برند را بنویسید");
      const result = await api(`/api/site/terms/${kind}`, { method: "POST", body: { name } });
      const cache = state.siteTerms[kind] || {};
      state.siteTerms[kind] = { ...cache, terms: [...(cache.terms || []), result.term] };
      state.modal.termDraft ||= {}; state.modal.termDraft[kind] ||= [...(state.modal.product?.[kind] || [])];
      state.modal.termDraft[kind].push(result.term);
      const box = document.querySelector(`[data-term-box="${kind}"]`); if (box) box.outerHTML = termField(kind);
      toast("در سایت ساخته و برای کالا انتخاب شد؛ برای ذخیرهٔ کالا، ذخیره را بزنید");
    } else if (act === "check-panel-update") {
      await loaders.updates();
      const info = state.panelUpdate || {};
      render();
      toast(info.update ? `نسخه ${info.latest} آماده است` : (info.reachable ? "همین نسخه آخر است" : (info.error || "ارتباط با سرور به‌روزرسانی برقرار نشد")));
    } else if (act === "reload-panel-update") {
      await loaders.updates();
      render();
    } else if (act === "self-update") {
      state.updating = true;
      render();
      let installed = false;
      try {
        await loaders.updates();
        const info = state.panelUpdate || {};
        if (!info.update || !info.app_url) {
          toast(info.reachable ? "همین نسخه آخر است" : (info.error || "ارتباط با سرور به‌روزرسانی برقرار نشد"));
          return;
        }
        const result = await api("/api/panel/update", { method: "POST", body: { source: "remote" } });
        installed = true;
        reopenAfterUpdate();
      } finally {
        if (!installed) {
          state.updating = false;
          render();
        }
      }
    } else if (act === "restart-app") {
      await api("/api/system/restart", { method: "POST", body: {} });
      try { sessionStorage.removeItem("daftarchi-show-reset"); } catch { /* ignore */ }
      state.justUpdated = false;
      toast("برنامه دوباره باز می‌شود", { ok: true });
    } else if (act === "install-remote" || act === "install-file") {
      let result;
      if (act === "install-file") {
        const file = document.getElementById("update-file")?.files?.[0];
        if (!file) throw new Error("فایل زیپ را انتخاب کنید");
        const form = new FormData();
        form.append("file", file);
        result = await api("/api/panel/update", { method: "POST", form });
      } else {
        result = await api("/api/panel/update", { method: "POST", body: { source: "remote" } });
      }
      reopenAfterUpdate();
    } else if (act === "check-update") {
      await loadUpdate();
      await navigate("updates");
      toast(state.update?.update ? `نسخه ${state.update.version} آماده است` : "نسخه تازه‌ای نیست");
    } else if (act === "commit-import") {
      if (!state.importFile || !state.modal?.preview?.total) return toast("اول فایل اکسل را انتخاب کنید");
      const body = new FormData();
      body.append("file", state.importFile);
      const result = await api("/api/products/import", { method: "POST", form: body });
      state.importResult = result;
      delete dismissedNotices().import;
      state.importFile = null;
      state.modal = null;
      await loaders.products();
      const saved = (result.created || 0) + (result.updated || 0);
      toast(result.errors?.length ? `${num(saved)} کالا ثبت شد و ${num(result.errors.length)} ردیف رد شد` : `${num(saved)} کالا ثبت شد`);
      render();
    } else if (act === "collect-check") {
      if (!allowCredit()) return toast("در این نسخه چک نیست");
      await api(`/api/checks/${el.dataset.id}/collect`, { method: "POST", body: {} });
      await loaders.checks();
      toast("وصول چک در حسابداری ثبت شد");
      render();
    } else if (act === "new-product") { state.modal = { type: "product", product: null }; render(); }
    else if (act === "make-barcode") {
      const result = await api("/api/products/barcode", { method: "POST", body: {} });
      const input = document.querySelector('form[data-act="save-product"] input[name="barcode"]');
      if (input) input.value = result.barcode;
      toast(`بارکد ${result.barcode} ساخته شد`);
    }
    else if (act === "edit-product") {
      state.modal = { type: "product", product: state.products.find((item) => item.id === Number(el.dataset.id)) };
      render();
    } else if (act === "toggle-product-archive" && isAdmin()) {
      if (!state.showArchivedProducts) {
        state.archivedProducts = stampProducts((await api("/api/products?archived=1")).products);
      }
      state.showArchivedProducts = !state.showArchivedProducts;
      state.productLimit = PAGE_SIZE;
      render();
    } else if (act === "archive-product" || act === "draft-archived-product") {
      const product = [...state.products, ...state.archivedProducts].find((item) => item.id === Number(el.dataset.id));
      if (!window.confirm(product?.woo_id || ["online", "hybrid"].includes(state.shopMode) ? "این کالا بایگانی و در سایت به پیش‌نویس تبدیل شود؟" : "این کالا بایگانی شود؟")) return;
      const result = await api(`/api/products/${el.dataset.id}/archive`, { method: "POST", body: {} });
      await loaders.products();
      toast(result.woo_sync_note || "کالا بایگانی شد.");
      render();
    } else if (act === "delete-archived-product") {
      const product = state.archivedProducts.find((item) => item.id === Number(el.dataset.id));
      if (!window.confirm(product?.woo_id || ["online", "hybrid"].includes(state.shopMode) ? "کالا از بایگانی دفترچی حذف و در سایت به زباله‌دان منتقل شود؟ سوابق فاکتورها باقی می‌مانند." : "کالا از بایگانی حذف شود؟ سوابق فاکتورها باقی می‌مانند.")) return;
      const result = await api(`/api/products/${el.dataset.id}/archive`, { method: "DELETE" });
      await loaders.products();
      toast(result.woo_sync_note || "کالا از بایگانی حذف شد.");
      render();
    } else if (act === "woo-push" || act === "woo-push-ids") {
      const body = act === "woo-push-ids" ? { ids: state.importResult?.ids || [] } : {};
      const result = await api("/api/products/woo/push", { method: "POST", body });
      const skipped = result.skipped ? `، ${num(result.skipped)} متغیر دست نخورد` : "";
      const failed = result.failed?.length ? `، ${num(result.failed.length)} تا نماند. قبلی‌ها ذخیره شد` : "";
      toast(`ووکامرس: ${num(result.created)} جدید، ${num(result.updated)} به‌روز${skipped}${failed}`, { error: Boolean(result.failed?.length) });
      await loaders.products();
      render();
    } else if (act === "woo-pull") {
      const result = await api("/api/products/woo/pull", { method: "POST", body: {} });
      toast(`از ووکامرس ${num(result.imported)} کالا آمد و ${num(result.updated)} به‌روز شد`);
      await loaders.products();
      render();
    } else if (act === "verify-sale" || act === "open-gateway") {
      const id = el.dataset.id;
      if (act === "verify-sale") {
        const result = await api(`/api/sales/${id}/confirm`, { method: "POST", body: { mode: "verify" } });
        state.modal = state.receiptMode === "none" ? null : { type: "receipt", layout: state.receiptMode, sale: result.sale, items: result.items, sms: result.sms, autoPrint: true };
        state.sendMoadian = false;
        state.cart = [];
      } else {
        const sale = state.pending.find((item) => item.id === Number(id));
        state.modal = { type: "gateway", sale };
      }
      await loaders.pos();
      render();
    } else if (act === "cancel-sale") {
      await api(`/api/sales/${el.dataset.id}/cancel`, { method: "POST", body: {} });
      state.modal = null;
      await loaders.pos();
      render();
    } else if (act === "verify-license" || act === "open-license") {
      const id = el.dataset.id;
      if (act === "verify-license") {
        const result = await api(`/api/licenses/${id}/confirm`, { method: "POST", body: { mode: "verify" } });
        state.modal = null;
        toast(`لایسنس پرداخت شد: ${money(result.payable)}`);
      } else {
        const license = state.licenses?.licenses?.find((item) => item.id === Number(id));
        if (license) state.modal = { type: "license", license };
      }
      await loaders.licenses();
      render();
    } else if (act === "cancel-license") {
      await api(`/api/licenses/${el.dataset.id}/cancel`, { method: "POST", body: {} });
      state.modal = null;
      await loaders.licenses();
      render();
    } else if (act === "filter-account") {
      state.accountId = el.dataset.id || "";
      await loaders.accounts();
      render();
    } else if (act === "moadian-send" || act === "moadian-inquiry") {
      const path = act === "moadian-send" ? "moadian" : "moadian/inquiry";
      const result = await api(`/api/sales/${el.dataset.id}/${path}`, { method: "POST", body: {} });
      if (state.modal?.type === "receipt") {
        state.modal.sale = result.sale;
        state.modal.items = result.items;
      }
      toast(result.message || "انجام شد");
      render();
    } else if (act === "test-setting") {
      const form = document.querySelector("form[data-act=save-settings]");
      const data = form ? Object.fromEntries(new FormData(form).entries()) : {};
      if (el.dataset.kind === "woo" && !String(data.woo_key || "").trim()) {
        toast("کادر Consumer key خالی است. کلید را بچسبانید؛ با ck_ شروع می‌شود.");
        return;
      }
      if (el.dataset.kind === "pos" && !String(data.pos_host || "").trim()) {
        toast("آدرس یا IP کارت‌خوان را بنویسید، بعد آزمایش اتصال را بزنید.");
        return;
      }
      if (el.dataset.kind === "sms" && !String(data.manager_phone || "").trim()) {
        toast("موبایل مدیر را بنویسید، بعد آزمایش پیامک را بزنید.");
        return;
      }
      const result = await api(`/api/settings/test/${el.dataset.kind}`, { method: "POST", body: {
        wp_url: data.wp_url || "",
        woo_key: data.woo_key || "",
        woo_secret: data.woo_secret || "",
        pos_host: data.pos_host || "",
        pos_port: data.pos_port || "",
        pos_provider: data.pos_provider || "",
        pos_terminal: data.pos_terminal || "",
        amount: el.dataset.kind === "pos" ? 1000 : "",
        manager_phone: data.manager_phone || "",
        sms_pattern_manager: data.sms_pattern_manager || "",
      } });
      toast(result.message);
    }
  });
}

function refreshPayable() {
  syncPos();
  const subtotal = cartSubtotal();
  const discount = cartDiscount();
  const set = (id, text) => {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
  };
  set("pay-sub", money(subtotal));
  set("pay-disc", money(discount));
  set("pay-due", money(subtotal - discount));
}
function refreshProductSearch(containerId, renderRows) {
  const container = document.getElementById(containerId);
  if (!container || container.searchFrame) return;
  container.searchFrame = requestAnimationFrame(() => {
    container.searchFrame = null;
    if (container.isConnected) container.innerHTML = renderRows();
  });
}

function onInput(event) {
  if (state.modal?.type === "perfume" && ["perfume-grams", "perfume-amount"].includes(event.target.id)) {
    window.DaftarchiPerfume.refresh(state.modal, event.target.id === "perfume-amount" ? "amount" : "weight"); return;
  }
  if (event.target.id === "price-per-gram" && !state.modal?.product?.id) {
    const price = document.getElementById("product-price"); if (price && !price.value.trim() && asInt(event.target.value) > 0) price.value = "0";
  }
  if (event.target.id === "discount") refreshPayable();
  if (event.target.id === "pos-query") {
    state.posQuery = event.target.value;
    state.posLimit = PAGE_SIZE;
    refreshProductSearch("pos-grid", posTiles);
  }
  if (event.target.id === "product-query") {
    state.productQuery = event.target.value;
    state.productLimit = PAGE_SIZE;
    refreshProductSearch("product-table", productRows);
  }
  if (event.target.id === "invoice-query" || event.target.id === "invoice-date") {
    if (event.target.id === "invoice-query") state.invoiceQuery = event.target.value;
    if (event.target.id === "invoice-date") state.invoiceDate = event.target.value;
    const box = document.getElementById("invoice-table");
    if (box) box.innerHTML = salesTable(filteredInvoices(), true);
  }
  if (String(event.target.name || "").startsWith("edit-") || event.target.id === "edit-discount") {
    readSaleEdit();
    const totals = editTotals();
    const sub = document.getElementById("edit-sub");
    const due = document.getElementById("edit-due");
    if (sub) sub.textContent = money(totals.subtotal);
    if (due) due.textContent = money(totals.payable);
  }
  if (event.target.id === "inventory-query") {
    state.inventoryQuery = event.target.value;
    state.inventoryLimit = PAGE_SIZE;
    const box = document.getElementById("inventory-box");
    if (box) box.innerHTML = inventoryTable();
    document.querySelectorAll("form[data-act=adjust] select[name=product_id], form[data-act=purchase] select[name=product_id]").forEach((select) => {
      select.innerHTML = stockChoices(inventoryChoices().slice(0, PAGE_SIZE));
    });
  }
}
function onChange(event) {
  if (event.target.id === "receipt-mode") {
    state.receiptMode = ["receipt", "invoice", "none"].includes(event.target.value) ? event.target.value : "receipt";
    try { localStorage.setItem("daftarchi-receipt-mode", state.receiptMode); } catch { /* applies for this session */ }
  }
  if (event.target.id === "send-moadian") state.sendMoadian = event.target.checked;
  if (event.target.id === "credit-kind") {
    const check = event.target.value === "check";
    document.getElementById("check-payment-fields").hidden = !check;
    document.querySelectorAll('[name="check_number"], [name="check_due"]').forEach(input => { input.required = check; });
  }
  if (event.target.dataset.siteTerms && state.modal?.type === "product") {
    state.modal.termDraft ||= {};
    state.modal.termDraft[event.target.dataset.siteTerms] = [...event.target.selectedOptions].map(option => ({ id: Number(option.value), name: option.textContent }));
  }
  if (event.target.name === "scope" && event.target.closest("form[data-act=bulk-price]")) {
    const select = event.target.form.querySelector("[name=term]");
    if (select) select.innerHTML = `<option value="">انتخاب کنید</option>${bulkTermOptions(event.target.value === "brand" ? "brands" : "categories")}`;
  }
  if (event.target.id === "product-sort") {
    state.productSort = event.target.value;
    state.productLimit = PAGE_SIZE;
    const box = document.getElementById("product-table");
    if (box) box.innerHTML = productRows();
  }
  if (event.target.id === "discount-type") refreshPayable();
  if (event.target.id === "invoice-photo" && event.target.files[0]) {
    const file = event.target.files[0];
    event.target.value = "";
    guard(async () => {
      state.modal = { type: "invoice-read", waiting: true };
      render();
      const body = new FormData();
      body.append("file", file);
      try {
        const result = await api("/api/invoices/read", { method: "POST", form: body });
        state.pro = { ...(state.pro || {}), tokens: result.invoice?.usage?.remaining || 0 };
        state.modal = { type: "invoice-read", invoice: result.invoice };
        render();
      } catch (err) {
        state.modal = null;
        render();
        throw err;
      }
    });
  }
  if (event.target.id === "excel-file" && event.target.files[0]) {
    const file = event.target.files[0];
    state.importFile = file;
    guard(async () => {
      const body = new FormData();
      body.append("file", file);
      const preview = await api("/api/products/preview", { method: "POST", form: body });
      state.modal = { type: "import", name: file.name, preview };
      render();
    });
  }
}

let scanBuffer = "";
let scanAt = 0;
document.addEventListener("keydown", (event) => {
  if (event.key === "Enter" && event.target?.dataset?.barcode === "add") {
    event.preventDefault();
    const code = event.target.value;
    event.target.value = "";
    guard(() => openProductFromBarcode(code));
    return;
  }
  if (event.key === "Enter" && event.target?.dataset?.barcode === "invoice") {
    event.preventDefault();
    const code = event.target.value;
    event.target.value = "";
    guard(() => openDocumentFromBarcode(code));
    return;
  }
  if (event.target?.dataset?.barcode === "product" && event.key === "Enter") {
    event.preventDefault();
    return;
  }
  const scanPages = state.page === "pos" || state.page === "invoices" || state.page === "inventory";
  if (!state.user || !scanPages || state.modal) return;
  const inField = event.target.matches("input, textarea, select");
  if (inField && event.target.dataset.barcode !== "1" && event.target.dataset.barcode !== "invoice") return;
  const now = Date.now();
  if (now - scanAt > 80) scanBuffer = "";
  scanAt = now;
  if (event.key === "Enter") {
    const code = event.target.dataset?.barcode === "1" || event.target.dataset?.barcode === "invoice" ? event.target.value : scanBuffer;
    scanBuffer = "";
    if (code && code.trim()) {
      event.preventDefault();
      guard(() => (state.page === "pos" ? addByBarcode(code) : openDocumentFromBarcode(code)));
    }
  } else if (!inField && event.key.length === 1) scanBuffer += event.key;
});
document.addEventListener("submit", onSubmit);
document.addEventListener("click", onClick);
document.addEventListener("input", onInput);
document.addEventListener("change", onChange);
window.addEventListener("error", (event) => {
  if (!event.message || event.message === "Script error.") return;
  toast(event.message, { error: true });
});
window.addEventListener("unhandledrejection", (event) => {
  const message = event.reason && event.reason.message ? event.reason.message : "";
  if (message) toast(message, { error: true });
});
window.addEventListener("resize", () => {
  if (state.user && (state.tourOn || !state.shopMode)) paintTour();
});
window.addEventListener("hashchange", () => {
  const page = location.hash.replace("#", "");
  if (state.user && page && page !== state.page) guard(() => navigate(page));
});

window.addEventListener("appinstalled", () => {
  state.installedApp = true;
  if (state.user) paintChrome();
  else render();
});
if (redirectToSavedServer()) {
  // loading remote UI
} else api("/api/auth/me").then(async (me) => {
  state.desktopIcon = Boolean(me.desktop_icon);
  loadUpdate().then(() => {
    if (!state.user) render();
  });
  if (!me.user) {
    state.bootstrap = await api("/api/auth/bootstrap");
    state.desktopIcon = Boolean(state.bootstrap.desktop_icon);
    state.today = state.bootstrap.today || me.today || state.today;
    applyTheme(state.bootstrap.theme);
    try { await loaders.updates(); } catch { /* offline */ }
    render();
    showUpdated();
    return;
  }
  state.user = me.user;
  state.centralHub = Boolean(me.central_hub);
  state.mustChange = me.must_change_password;
  state.initialPassword = me.initial_password || "";
  state.access = me.access || null;
  state.shop = me.shop || null;
  applyTheme(state.shop?.theme);
  state.today = me.today || state.today;
  readSetup(me.setup);
  if (me.owner_skip_shop_setup && !state.shopMode && state.user?.role === "admin") {
    await guard(() => skipModeSetup());
  }
  showUpdated();
  const opened = location.hash.replace("#", "");
  await navigate(opened && allowed(opened) ? opened : homePage());
  showUpdated();
}).catch((err) => {
  $app().innerHTML = `<p class="empty">${esc(err.message || "برنامه باز نشد")}</p>`;
});
