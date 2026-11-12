const CACHE = "daftarchi-1.1.36";
const SHELL = [
  "/static/css/app.css?v=1.1.36",
  "/static/js/app.js?v=1.1.36",
  "/static/js/html2canvas.min.js?v=1.1.36",
  "/static/fonts/Vazirmatn-Regular.woff2?v=1.1.36",
  "/static/fonts/Vazirmatn-SemiBold.woff2?v=1.1.36",
  "/static/icon-192.png?v=1.1.36",
  "/static/icon-512.png?v=1.1.36",
];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE).then((cache) => cache.addAll(SHELL)).then(() => self.skipWaiting())
  );
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener("fetch", (event) => {
  const req = event.request;
  if (req.method !== "GET") return;
  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;
  if (url.pathname.startsWith("/api/") || url.pathname.startsWith("/uploads/")) return;
  if (!url.pathname.startsWith("/static/")) return;
  event.respondWith(
    caches.open(CACHE).then(async (cache) => {
      const hit = await cache.match(req);
      if (hit) return hit;
      const res = await fetch(req);
      if (res.ok) cache.put(req, res.clone());
      return res;
    })
  );
});
