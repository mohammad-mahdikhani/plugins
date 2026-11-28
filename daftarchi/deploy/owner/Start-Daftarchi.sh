#!/bin/bash
cd "$(dirname "$0")"
ROOT="$(pwd)"
export DAFTARCHI_DATA="$ROOT/data"
export DAFTARCHI_PRESET="$ROOT/preset.json"
export DAFTARCHI_CHANNEL=customer
export DAFTARCHI_OPEN_BROWSER=1
export PORT="${PORT:-8080}"
mkdir -p "$DAFTARCHI_DATA"
PY="${DAFTARCHI_PYTHON:-python3}"
exec "$PY" "$ROOT/app/app.py"
