"""Low-RAM gunicorn: one sync worker, no thread pool, SQLite-safe."""

import os

_host = os.environ.get("DAFTARCHI_BIND", "127.0.0.1")
_port = os.environ.get("PORT", "8080")
bind = f"{_host}:{_port}"
workers = 1
threads = 1
worker_class = "sync"
timeout = 60
graceful_timeout = 20
keepalive = 2
max_requests = 800
max_requests_jitter = 40
preload_app = False
accesslog = os.environ.get("DAFTARCHI_ACCESSLOG", "-")
errorlog = os.environ.get("DAFTARCHI_ERRORLOG", "-")
loglevel = os.environ.get("DAFTARCHI_LOGLEVEL", "warning")
proc_name = "daftarchi-light"
