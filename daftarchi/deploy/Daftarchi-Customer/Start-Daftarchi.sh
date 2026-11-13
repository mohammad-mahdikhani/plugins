#!/bin/bash
cd "$(dirname "$0")"
ROOT="$(pwd)"
ARCH="$(uname -m)"
case "$ARCH" in
  x86_64) PY="$ROOT/python-x64/bin/python3"; WHEELS="$ROOT/wheels-x64" ;;
  aarch64|arm64) PY="$ROOT/python-arm64/bin/python3"; WHEELS="$ROOT/wheels-arm64" ;;
  *)
    echo "This Linux CPU is not supported: $ARCH"
    exit 1
    ;;
esac
chmod +x "$ROOT/python-x64/bin/python3.12" "$ROOT/python-arm64/bin/python3.12" 2>/dev/null || true
if [ ! -x "$PY" ]; then
  PY="$(dirname "$PY")/python3.12"
fi
if [ ! -x "$PY" ]; then
  echo "Python for this Linux is missing. Unzip the whole folder, then run this file again."
  exit 1
fi
export DAFTARCHI_DATA="$ROOT/data"
export DAFTARCHI_CHANNEL=customer
if [ -n "${DISPLAY:-}${WAYLAND_DISPLAY:-}" ]; then
  export DAFTARCHI_OPEN_BROWSER=1
fi
export DAFTARCHI_PYTHON="$PY"
export PORT="${PORT:-8080}"
mkdir -p "$DAFTARCHI_DATA"
if [ ! -f "$DAFTARCHI_DATA/.wheels-$ARCH" ]; then
  "$PY" -m pip install --disable-pip-version-check --no-index --find-links "$WHEELS" flask openpyxl jdatetime waitress colorama tzdata cryptography
  if [ $? -ne 0 ]; then
    echo "Could not install the Linux libraries."
    exit 1
  fi
  touch "$DAFTARCHI_DATA/.wheels-$ARCH"
fi
exec "$PY" "$ROOT/app/app.py"
