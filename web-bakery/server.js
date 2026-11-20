#!/usr/bin/env node
"use strict";

const http = require("http");
const fs = require("fs");
const path = require("path");

const ROOT = __dirname;
const PORT = Number(process.env.PORT || 8765);
const ZIP_NAME = "schema-nova-1.1.zip";
const ZIP_PATH = path.join(ROOT, ZIP_NAME);

const MIME = {
  ".html": "text/html; charset=utf-8",
  ".css": "text/css; charset=utf-8",
  ".js": "text/javascript; charset=utf-8",
  ".zip": "application/zip",
  ".png": "image/png",
  ".ico": "image/x-icon",
};

function sendFile(res, filePath, headers) {
  const stat = fs.statSync(filePath);
  const ext = path.extname(filePath).toLowerCase();
  res.writeHead(200, {
    "Content-Type": MIME[ext] || "application/octet-stream",
    "Content-Length": stat.size,
    "Cache-Control": ext === ".zip" ? "no-store" : "public, max-age=300",
    ...headers,
  });
  fs.createReadStream(filePath).pipe(res);
}

function sendZip(res) {
  if (!fs.existsSync(ZIP_PATH)) {
    res.writeHead(404, { "Content-Type": "text/plain; charset=utf-8" });
    res.end("plugin zip missing");
    return;
  }
  sendFile(res, ZIP_PATH, {
    "Content-Type": "application/zip",
    "Content-Disposition": `attachment; filename="${ZIP_NAME}"`,
    "X-Content-Type-Options": "nosniff",
  });
}

const server = http.createServer((req, res) => {
  const url = new URL(req.url || "/", `http://${req.headers.host || "127.0.0.1"}`);
  const pathname = decodeURIComponent(url.pathname);

  if (req.method !== "GET" && req.method !== "HEAD") {
    res.writeHead(405, { Allow: "GET, HEAD" });
    res.end();
    return;
  }

  if (pathname === "/health") {
    res.writeHead(200, { "Content-Type": "text/plain; charset=utf-8" });
    res.end("ok");
    return;
  }

  if (
    pathname === "/download" ||
    pathname === `/${ZIP_NAME}` ||
    pathname === "/schema-nova.zip"
  ) {
    sendZip(res);
    return;
  }

  if (pathname === "/" || pathname === "/index.html") {
    sendFile(res, path.join(ROOT, "index.html"));
    return;
  }

  res.writeHead(404, { "Content-Type": "text/plain; charset=utf-8" });
  res.end("not found");
});

server.listen(PORT, "0.0.0.0", () => {
  process.stdout.write(`web-bakery listening on http://127.0.0.1:${PORT}\n`);
});
