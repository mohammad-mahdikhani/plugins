#!/usr/bin/env python3
"""Build PakanMental-Setup.exe, a Windows installer for the shell plugin."""
from __future__ import annotations

import subprocess
import sys
from pathlib import Path

ROOT = Path("/workspace")
HERE = Path(__file__).resolve().parent
ARTIFACTS = ROOT / "artifacts"
ZIP = ARTIFACTS / "pakanmental-shell.zip"
GUIDE = ARTIFACTS / "install-guide.txt"
EXE = ARTIFACTS / "PakanMental-Setup.exe"
NSI = HERE / "pakanmental-setup.nsi"

GUIDE_TEXT = """راهنمای نصب افزونه پاکان منتال روی وردپرس

۱. برنامه PakanMental-Setup.exe را روی ویندوز اجرا کنید.
۲. پوشه نصب را تأیید کنید. فایل pakanmental-shell.zip آنجا کپی می‌شود.
۳. وارد پیشخوان سایت شوید.
۴. افزونه‌ها → افزودن → بارگذاری افزونه.
۵. فایل pakanmental-shell.zip را انتخاب کنید، نصب را بزنید و بعد فعال‌سازی.
۶. در پیشخوان، منوی «قالب پاکان» را باز کنید و «ساخت / تعمیر قالب‌ها» را بزنید.

اگر نسخه قدیمی افزونه فعال است، اول آن را غیرفعال کنید و بعد این فایل را نصب کنید.
"""


def main() -> None:
    subprocess.check_call([sys.executable, str(HERE / "build_shell_zip.py")])
    ARTIFACTS.mkdir(parents=True, exist_ok=True)
    GUIDE.write_text(GUIDE_TEXT, encoding="utf-8-sig")
    if EXE.exists():
        EXE.unlink()
    subprocess.check_call(
        [
            "makensis",
            "-INPUTCHARSET",
            "UTF8",
            f"-DZIPFILE={ZIP}",
            f"-DGUIDEFILE={GUIDE}",
            f"-DOUTFILE={EXE}",
            str(NSI),
        ]
    )
    print("wrote", EXE, "size", EXE.stat().st_size)


if __name__ == "__main__":
    main()
