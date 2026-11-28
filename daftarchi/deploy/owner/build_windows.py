#!/usr/bin/env python3
"""Windows Owner folder with embedded Python (no Microsoft Store)."""
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
DEPLOY = ROOT / "deploy"
HERE = Path(__file__).resolve().parent
APP = ROOT
TARGET = "Daftarchi-Owner/"
SOURCE = "Daftarchi/"
OUT_ZIP = DEPLOY / "Daftarchi-Owner-Windows.zip"


def app_files():
    found = []
    for name in ("app.py", "moadian.py", "version.json", "requirements.txt"):
        path = APP / name
        if path.is_file():
            found.append((f"app/{name}", path.read_bytes()))
    for folder in ("static", "templates"):
        base = APP / folder
        for path in base.rglob("*"):
            if not path.is_file() or "__pycache__" in path.parts:
                continue
            rel = path.relative_to(APP).as_posix()
            found.append((f"app/{rel}", path.read_bytes()))
    return found


def main() -> None:
    src = DEPLOY / "Daftarchi-Customer.zip"
    if not src.is_file():
        raise SystemExit(f"missing {src}")
    launcher = (HERE / "Start-Daftarchi.bat").read_bytes()
    preset = (HERE / "preset.json").read_bytes()
    readme = (HERE / "README-FA.txt").read_bytes()
    icon_src = DEPLOY / "windows" / "daftarchi.ico"
    with zipfile.ZipFile(src) as zin, zipfile.ZipFile(OUT_ZIP, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as zout:
        for info in zin.infolist():
            if info.is_dir() or not info.filename.startswith(SOURCE):
                continue
            rel = info.filename[len(SOURCE) :]
            if rel.startswith("app/") or rel in {"README-FA.txt", "Start-Daftarchi.bat"}:
                continue
            data = zin.read(info)
            out = zipfile.ZipInfo(filename=TARGET + rel, date_time=info.date_time)
            out.external_attr = info.external_attr
            out.create_system = info.create_system
            out.compress_type = zipfile.ZIP_DEFLATED
            zout.writestr(out, data)
        for rel, data in app_files():
            out = zipfile.ZipInfo(filename=TARGET + rel)
            out.compress_type = zipfile.ZIP_DEFLATED
            out.external_attr = (0o644 & 0xFFFF) << 16
            zout.writestr(out, data)
        extras = (
            ("Start-Daftarchi.bat", launcher),
            ("preset.json", preset),
            ("README-FA.txt", readme),
        )
        for rel, data in extras:
            out = zipfile.ZipInfo(filename=TARGET + rel)
            out.compress_type = zipfile.ZIP_DEFLATED
            out.external_attr = (0o755 & 0xFFFF) << 16 if rel.endswith(".bat") else (0o644 & 0xFFFF) << 16
            zout.writestr(out, data)
        if icon_src.is_file():
            out = zipfile.ZipInfo(filename=TARGET + "daftarchi.ico")
            out.compress_type = zipfile.ZIP_DEFLATED
            zout.writestr(out, icon_src.read_bytes())
    names = zipfile.ZipFile(OUT_ZIP).namelist()
    if not any(n.endswith("python/python.exe") for n in names):
        raise SystemExit("embedded python missing from owner windows zip")
    print(OUT_ZIP, OUT_ZIP.stat().st_size)


if __name__ == "__main__":
    main()