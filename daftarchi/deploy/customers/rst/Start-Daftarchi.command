#!/bin/bash
cd "$(dirname "$0")"
ROOT="$(pwd)"
ARCH="$(uname -m)"
case "$ARCH" in
  arm64) PY="$ROOT/python-arm64/bin/python3"; WHEELS="$ROOT/wheels-arm64" ;;
  x86_64) PY="$ROOT/python-x64/bin/python3"; WHEELS="$ROOT/wheels-x64" ;;
  *)
    echo "This Mac is not supported: $ARCH"
    read -r _
    exit 1
    ;;
esac
xattr -dr com.apple.quarantine "$ROOT" 2>/dev/null || true
chmod +x "$ROOT/python-arm64/bin/python3.12" "$ROOT/python-x64/bin/python3.12" 2>/dev/null || true
if [ ! -x "$PY" ]; then
  PY="$(dirname "$PY")/python3.12"
fi
if [ ! -x "$PY" ]; then
  echo "Python for this Mac is missing. Unzip the whole folder, then run this file again."
  read -r _
  exit 1
fi
export DAFTARCHI_DATA="$ROOT/data"
export DAFTARCHI_PRESET="$ROOT/preset.json"
export DAFTARCHI_CHANNEL=rst
export DAFTARCHI_OPEN_BROWSER=1
export DAFTARCHI_PYTHON="$PY"
export PORT=8080
mkdir -p "$DAFTARCHI_DATA"
if [ ! -f "$DAFTARCHI_DATA/.wheels-$ARCH" ]; then
  "$PY" -m pip install --disable-pip-version-check --no-index --find-links "$WHEELS" flask openpyxl jdatetime waitress colorama tzdata cryptography
  if [ $? -ne 0 ]; then
    echo "Could not install the Mac libraries."
    read -r _
    exit 1
  fi
  touch "$DAFTARCHI_DATA/.wheels-$ARCH"
fi
"$PY" "$ROOT/app/app.py"
if [ $? -ne 0 ]; then
  echo
  echo "Daftarchi stopped. Press Enter to close."
  read -r _
fi
