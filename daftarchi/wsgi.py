"""WSGI entry for light-host (gunicorn, Passenger, Apache).

Shop-PC LAN still starts with ``python app.py`` / Start-Daftarchi.* and is unchanged.
Set DAFTARCHI_LIGHT=1 before importing this module (start-light.sh and systemd do that).
"""

from __future__ import annotations

import os

os.environ.setdefault("DAFTARCHI_LIGHT", "1")
os.environ.setdefault("DAFTARCHI_OPEN_BROWSER", "0")
os.environ.setdefault("DAFTARCHI_BIND", "127.0.0.1")

from app import app, apply_light_host, start_backup_loop  # noqa: E402

apply_light_host(app)
if os.environ.get("DAFTARCHI_BACKUP_LOOP", "1").strip().lower() not in ("0", "false", "off", "no"):
    start_backup_loop()

application = app
