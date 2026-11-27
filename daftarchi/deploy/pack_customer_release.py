#!/usr/bin/env python3
"""Refresh customer update zip and overlay app/ inside full install zips."""
import io
import shutil
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
DEPLOY = ROOT / "deploy"
CHANNEL = DEPLOY / "channels" / "customer"
APP_NAMES = ("app.py", "moadian.py", "version.json", "requirements.txt")


def panel_files() -> list[tuple[str, bytes]]:
    found: list[tuple[str, bytes]] = []
    for name in APP_NAMES:
        path = ROOT / name
        if path.is_file():
            found.append((name, path.read_bytes()))
    for folder in ("static", "templates"):
        base = ROOT / folder
        for path in base.rglob("*"):
            if not path.is_file() or "__pycache__" in path.parts:
                continue
            rel = path.relative_to(ROOT).as_posix()
            found.append((rel, path.read_bytes()))
    return found


def write_app_zip(dest: Path) -> None:
    dest.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(dest, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as zout:
        for rel, data in panel_files():
            zout.writestr(rel, data)


def overlay_full_zip(zip_path: Path, prefix: str) -> None:
    if not zip_path.is_file():
        print("skip", zip_path)
        return
    stage = zip_path.with_suffix(".staging")
    if stage.exists():
        shutil.rmtree(stage)
    stage.mkdir()
    with zipfile.ZipFile(zip_path) as zin:
        zin.extractall(stage)
    app_dir = stage / prefix / "app"
    if not app_dir.is_dir():
        raise SystemExit(f"missing {app_dir}")
    if app_dir.exists():
        shutil.rmtree(app_dir)
    app_dir.mkdir()
    for name in APP_NAMES:
        src = ROOT / name
        if src.is_file():
            shutil.copy(src, app_dir / name)
    for folder in ("static", "templates"):
        shutil.copytree(ROOT / folder, app_dir / folder, ignore=shutil.ignore_patterns("__pycache__"))
    tmp = zip_path.with_suffix(".next.zip")
    with zipfile.ZipFile(tmp, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as zout:
        for path in stage.rglob("*"):
            if path.is_file():
                zout.write(path, path.relative_to(stage).as_posix())
    tmp.replace(zip_path)
    shutil.rmtree(stage)
    print(zip_path, zip_path.stat().st_size)


def main() -> None:
    version = (ROOT / "app.py").read_text(encoding="utf-8").split('APP_VERSION = "', 1)[1].split('"', 1)[0]
    app_zip = CHANNEL / "Daftarchi-Customer-App.zip"
    write_app_zip(app_zip)
    shutil.copy(app_zip, CHANNEL / f"customer-{version}-app.zip")
    print(app_zip, app_zip.stat().st_size)
    overlay_full_zip(DEPLOY / "Daftarchi-Customer.zip", "Daftarchi")
    overlay_full_zip(DEPLOY / "Daftarchi-Customer-Mac.zip", "Daftarchi")
    overlay_full_zip(DEPLOY / "Daftarchi-Customer-Linux.zip", "Daftarchi")


if __name__ == "__main__":
    main()