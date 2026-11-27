const VERSION = "__APP_VERSION__";
const CACHE = `daftarchi-${VERSION}`;
const SHELL = [
  `/static/css/app.css?v=${VERSION}`,
  `/static/js/app.js?v=${VERSION}`,
  `/static/js/perfume.js?v=${VERSION}`,
  `/static/css/printer.css?v=${VERSION}`,
  `/static/js/printer.js?v=${VERSION}`,
  `/static/fonts/Vazirmatn-Regular.woff2?v=${VERSION}`,
  `/static/fonts/Vazirmatn-SemiBold.woff2?v=${VERSION}`,
  `/static/icon-192.png?v=${VERSION}`,
  `/static/icon-512.png?v=${VERSION}`,
];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE).then((cache) => cache.addAll(SHELL)).then(() => self.skipWaiting())
  );
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((key) => key.startsWith("daftarchi-") && key !== CACHE).map((key) => caches.delete(key))))
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
