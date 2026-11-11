#!/usr/bin/env python3
"""Pack the latest Daftarchi for عطر فروشی from the customer runtimes."""
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]
DEPLOY = ROOT / "deploy"
HERE = Path(__file__).resolve().parent
APP = ROOT
TARGET = "Daftarchi-Atr/"
SOURCE = "Daftarchi/"


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


def patch_launcher(name: str, data: bytes) -> bytes:
    text = data.decode("utf-8")
    if "DAFTARCHI_PRESET" in text:
        return data
    if name.endswith(".bat"):
        text = text.replace(
            'set "DAFTARCHI_CHANNEL=customer"\n',
            'set "DAFTARCHI_CHANNEL=customer"\nset "DAFTARCHI_PRESET=%~dp0preset.json"\n',
            1,
        )
    else:
        text = text.replace(
            "export DAFTARCHI_CHANNEL=customer\n",
            'export DAFTARCHI_CHANNEL=customer\nexport DAFTARCHI_PRESET="$ROOT/preset.json"\n',
            1,
        )
    if "DAFTARCHI_PRESET" not in text:
        raise SystemExit(f"could not mark preset in {name}")
    return text.encode("utf-8")


def build_one(src_name: str, dest_name: str) -> None:
    src = DEPLOY / src_name
    dest = DEPLOY / dest_name
    fresh = app_files()
    preset = (HERE / "preset.json").read_bytes()
    readme = (HERE / "README-FA.txt").read_bytes()
    with zipfile.ZipFile(src) as zin, zipfile.ZipFile(dest, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as zout:
        for info in zin.infolist():
            if info.is_dir() or not info.filename.startswith(SOURCE):
                continue
            rel = info.filename[len(SOURCE):]
            if rel.startswith("app/") or rel == "README-FA.txt":
                continue
            data = zin.read(info)
            if rel.startswith("Start-Daftarchi."):
                data = patch_launcher(rel, data)
            out = zipfile.ZipInfo(filename=TARGET + rel, date_time=info.date_time)
            out.external_attr = info.external_attr
            out.create_system = info.create_system
            out.compress_type = zipfile.ZIP_DEFLATED
            zout.writestr(out, data)
        for rel, data in fresh:
            out = zipfile.ZipInfo(filename=TARGET + rel)
            out.compress_type = zipfile.ZIP_DEFLATED
            out.external_attr = (0o644 & 0xFFFF) << 16
            zout.writestr(out, data)
        for rel, data in (("preset.json", preset), ("README-FA.txt", readme)):
            out = zipfile.ZipInfo(filename=TARGET + rel)
            out.compress_type = zipfile.ZIP_DEFLATED
            out.external_attr = (0o644 & 0xFFFF) << 16
            zout.writestr(out, data)
    print(dest, dest.stat().st_size)


def main() -> None:
    build_one("Daftarchi-Customer.zip", "Daftarchi-Atr.zip")
    build_one("Daftarchi-Customer-Mac.zip", "Daftarchi-Atr-Mac.zip")
    build_one("Daftarchi-Customer-Linux.zip", "Daftarchi-Atr-Linux.zip")


if __name__ == "__main__":
    main()
