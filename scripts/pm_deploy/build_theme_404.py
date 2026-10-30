#!/usr/bin/env python3
"""Build hello-pakan child theme zip for site-wide 404 routing."""
from __future__ import annotations

import zipfile
from pathlib import Path

ROOT = Path("/workspace")
SRC = ROOT / "themes" / "hello-pakan"
OUT = ROOT / "artifacts" / "hello-pakan.zip"


def main() -> None:
	OUT.parent.mkdir(parents=True, exist_ok=True)
	if OUT.exists():
		OUT.unlink()
	with zipfile.ZipFile(OUT, "w", zipfile.ZIP_DEFLATED) as zf:
		for path in SRC.rglob("*"):
			if path.is_file():
				arc = path.relative_to(SRC.parent)
				zf.write(path, arc)
	print("wrote", OUT, "size", OUT.stat().st_size)


if __name__ == "__main__":
	main()
