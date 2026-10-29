#!/usr/bin/env python3
"""Build pakanmental-home.zip for upload in wp-admin → Plugins → Add New → Upload."""
from __future__ import annotations

import shutil
import zipfile
from pathlib import Path

ROOT = Path("/workspace")
PLUGIN_DIR = ROOT / "pakanmental-home"
OUT = ROOT / "artifacts" / "pakanmental-home.zip"


def main() -> None:
    OUT.parent.mkdir(parents=True, exist_ok=True)
    if OUT.exists():
        OUT.unlink()
    with zipfile.ZipFile(OUT, "w", zipfile.ZIP_DEFLATED) as zf:
        for path in PLUGIN_DIR.rglob("*"):
            if path.is_file():
                arc = path.relative_to(ROOT)
                zf.write(path, arc)
    print("wrote", OUT, "size", OUT.stat().st_size)


if __name__ == "__main__":
    main()
