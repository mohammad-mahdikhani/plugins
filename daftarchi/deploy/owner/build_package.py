#!/usr/bin/env python3
"""Pack Mohammad's unlimited private copy. Do not ship this as the customer channel."""
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
HERE = Path(__file__).resolve().parent
DEST = ROOT / "deploy" / "Daftarchi-Owner.zip"
TARGET = "Daftarchi-Owner/"
APP_NAMES = ("app.py", "moadian.py", "version.json", "requirements.txt")


def app_files():
    found = []
    for name in APP_NAMES:
        path = ROOT / name
        if path.is_file():
            found.append((f"app/{name}", path.read_bytes()))
    for folder in ("static", "templates"):
        base = ROOT / folder
        for path in base.rglob("*"):
            if not path.is_file() or "__pycache__" in path.parts:
                continue
            rel = path.relative_to(ROOT).as_posix()
            found.append((f"app/{rel}", path.read_bytes()))
    return found


def main() -> None:
    extras = [
        ("preset.json", (HERE / "preset.json").read_bytes()),
        ("README-FA.txt", (HERE / "README-FA.txt").read_bytes()),
        ("Start-Daftarchi.bat", (HERE / "Start-Daftarchi.bat").read_bytes()),
        ("Start-Daftarchi.sh", (HERE / "Start-Daftarchi.sh").read_bytes()),
        ("Start-Daftarchi.command", (HERE / "Start-Daftarchi.command").read_bytes()),
    ]
    DEST.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(DEST, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as zout:
        for rel, data in list(app_files()) + extras:
            info = zipfile.ZipInfo(filename=TARGET + rel)
            info.compress_type = zipfile.ZIP_DEFLATED
            mode = 0o755 if rel.startswith("Start-Daftarchi.") and not rel.endswith(".bat") else 0o644
            info.external_attr = (mode & 0xFFFF) << 16
            zout.writestr(info, data)
    print(DEST, DEST.stat().st_size)


if __name__ == "__main__":
    main()
