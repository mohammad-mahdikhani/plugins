#!/bin/bash
# Light-host Daftarchi: gunicorn 1 worker + SQLite WAL.
# Does not replace LAN Start-Daftarchi.bat / .sh on the shop PC.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

if [ -z "${DAFTARCHI_DATA:-}" ]; then
  export DAFTARCHI_DATA="$ROOT/data"
fi
mkdir -p "$DAFTARCHI_DATA"

export DAFTARCHI_LIGHT=1
export DAFTARCHI_OPEN_BROWSER=0
export DAFTARCHI_BIND="${DAFTARCHI_BIND:-127.0.0.1}"
export PORT="${PORT:-8080}"
export DAFTARCHI_LAN_CORS="${DAFTARCHI_LAN_CORS:-0}"

PY="${DAFTARCHI_PYTHON:-python3}"
if [ ! -x "$PY" ]; then
  PY="$(command -v python3 || true)"
fi
if [ -z "$PY" ]; then
  echo "python3 is missing. On cPanel use Setup Python App, or install python3 on the VPS." >&2
  exit 1
fi

REQ="$ROOT/requirements-light.txt"
if [ -n "${DAFTARCHI_VENV:-}" ]; then
  # shellcheck disable=SC1091
  . "$DAFTARCHI_VENV/bin/activate"
  PY="python"
fi
"$PY" -m pip install --disable-pip-version-check -q -r "$REQ"

CONF="$ROOT/deploy/light-host/gunicorn.conf.py"
exec "$PY" -m gunicorn --config "$CONF" wsgi:application
