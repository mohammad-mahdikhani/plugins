#!/usr/bin/env python3
"""Build a Mac setup.dmg from an existing Daftarchi Mac zip."""
import argparse
import os
import shutil
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
DEPLOY = ROOT / "deploy"
MAC = DEPLOY / "mac"


def tool(name: str) -> str:
    env = os.environ.get(name.upper().replace("-", "_"))
    if env and Path(env).is_file():
        return env
    found = shutil.which(name)
    if found:
        return found
    raise SystemExit(f"missing tool: {name}")


def build(zip_path: Path, setup_script: Path, out_path: Path) -> None:
    hfsplus = tool("hfsplus")
    dmg = tool("dmg")
    mkfs = tool("mkfs.hfsplus")
    stage = Path("/tmp/daftarchi-dmg-stage")
    if stage.exists():
        shutil.rmtree(stage)
    stage.mkdir()
    shutil.copy(zip_path, stage / "payload.zip")
    setup = stage / "setup.command"
    shutil.copy(setup_script, setup)
    setup.chmod(0o755)
    shutil.copy(MAC / "راهنما.txt", stage / "راهنما.txt")
    payload = (stage / "payload.zip").stat().st_size
    image_mb = max(32, int(payload / (1024 * 1024)) + 16)
    image = Path("/tmp/daftarchi-dmg.img")
    if image.exists():
        image.unlink()
    with image.open("wb") as handle:
        handle.truncate(image_mb * 1024 * 1024)
    subprocess.check_call([mkfs, "-v", "setup", str(image)])
    subprocess.check_call([hfsplus, str(image), "addall", str(stage), "/"])
    subprocess.check_call([hfsplus, str(image), "chmod", "755", "/setup.command"])
    out_path.parent.mkdir(parents=True, exist_ok=True)
    if out_path.exists():
        out_path.unlink()
    subprocess.check_call([dmg, "-c", "zlib", "-l", "9", "build", str(image), str(out_path)])
    blob = out_path.read_bytes()[-512:]
    if not blob.startswith(b"koly"):
        raise SystemExit("dmg is missing the koly trailer")
    print(f"{out_path} {out_path.stat().st_size} bytes")
    image.unlink()
    shutil.rmtree(stage)


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("kind", choices=("rst", "customer"))
    args = parser.parse_args()
    if args.kind == "rst":
        build(
            DEPLOY / "Daftarchi-rst-Mac.zip",
            MAC / "setup-rst.command",
            DEPLOY / "Daftarchi-rst-setup.dmg",
        )
    else:
        build(
            DEPLOY / "Daftarchi-Customer-Mac.zip",
            MAC / "setup-customer.command",
            DEPLOY / "Daftarchi-Customer-setup.dmg",
        )


if __name__ == "__main__":
    main()
