#!/usr/bin/env python3
"""Build the WordPress-installable pakanmental-shell.zip."""
from __future__ import annotations

import zipfile
from pathlib import Path

ROOT = Path("/workspace")
SRC = ROOT / "pakanmental-shell"
OUT = ROOT / "artifacts" / "pakanmental-shell.zip"
SKIP_PREFIXES = ("snippet-",)


def main() -> None:
    OUT.parent.mkdir(parents=True, exist_ok=True)
    if OUT.exists():
        OUT.unlink()
    with zipfile.ZipFile(OUT, "w", zipfile.ZIP_DEFLATED) as zf:
        for path in sorted(SRC.rglob("*")):
            if not path.is_file():
                continue
            if path.name.startswith(SKIP_PREFIXES):
                continue
            zf.write(path, path.relative_to(SRC.parent).as_posix())
    print("wrote", OUT, "size", OUT.stat().st_size)


if __name__ == "__main__":
    main()
