#!/usr/bin/env python3
"""Build pm-404-router.zip — small plugin to route all 404s to page 404-pm."""
from __future__ import annotations

import zipfile
from pathlib import Path

ROOT = Path("/workspace")
SRC = ROOT / "pm-404-router"
OUT = ROOT / "artifacts" / "pm-404-router.zip"


def main() -> None:
    OUT.parent.mkdir(parents=True, exist_ok=True)
    if OUT.exists():
        OUT.unlink()
    with zipfile.ZipFile(OUT, "w", zipfile.ZIP_DEFLATED) as zf:
        for path in SRC.rglob("*"):
            if path.is_file():
                zf.write(path, path.relative_to(ROOT))
    print("wrote", OUT, "size", OUT.stat().st_size)


if __name__ == "__main__":
    main()
