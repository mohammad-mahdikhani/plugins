#!/usr/bin/env python3
from __future__ import annotations

import zipfile
from pathlib import Path

ROOT = Path("/workspace")
SRC = ROOT / "pakanmental-wc-shell"
OUT = ROOT / "artifacts" / "pakanmental-wc-shell.zip"


def main() -> None:
    OUT.parent.mkdir(parents=True, exist_ok=True)
    if OUT.exists():
        OUT.unlink()
    with zipfile.ZipFile(OUT, "w", zipfile.ZIP_DEFLATED) as zf:
        for path in SRC.rglob("*"):
            if path.is_file():
                zf.write(path, path.relative_to(SRC.parent))
    print("wrote", OUT, "size", OUT.stat().st_size)


if __name__ == "__main__":
    main()
