#!/usr/bin/env python3
"""Generate 20 VIP site folders for E:\\Daftarchi\\KharidaraneAvval."""

from __future__ import annotations

import json
from pathlib import Path

ROOT = Path(__file__).resolve().parent / "E-Drive-KharidaraneAvval"
BASE_VERSION = "1.1.7"
EXE_URL = "https://webakery.ir/license-server/releases/Daftarchi.exe"

SITES = [
    ("01", "webakery", "وب‌بیکری", "https://webakery.ir", "فروشگاه نمونه و پشتیبانی دفترچی"),
    ("02", "vip-aqua", "آب‌فروشی VIP", "https://example-shop-02.ir", "کسب‌وکار VIP شماره ۲"),
    ("03", "vip-home", "خانه‌وآشپز VIP", "https://example-shop-03.ir", "کسب‌وکار VIP شماره ۳"),
    ("04", "vip-fashion", "پوشاک VIP", "https://example-shop-04.ir", "کسب‌وکار VIP شماره ۴"),
    ("05", "vip-digital", "دیجیتال VIP", "https://example-shop-05.ir", "کسب‌وکار VIP شماره ۵"),
    ("06", "vip-food", "موادغذایی VIP", "https://example-shop-06.ir", "کسب‌وکار VIP شماره ۶"),
    ("07", "vip-beauty", "آرایشی VIP", "https://example-shop-07.ir", "کسب‌وکار VIP شماره ۷"),
    ("08", "vip-sport", "ورزشی VIP", "https://example-shop-08.ir", "کسب‌وکار VIP شماره ۸"),
    ("09", "vip-kids", "کودک VIP", "https://example-shop-09.ir", "کسب‌وکار VIP شماره ۹"),
    ("10", "vip-tools", "ابزار VIP", "https://example-shop-10.ir", "کسب‌وکار VIP شماره ۱۰"),
    ("11", "vip-auto", "لوازم خودرو VIP", "https://example-shop-11.ir", "کسب‌وکار VIP شماره ۱۱"),
    ("12", "vip-pet", "حیوانات VIP", "https://example-shop-12.ir", "کسب‌وکار VIP شماره ۱۲"),
    ("13", "vip-book", "لوازم‌التحریر VIP", "https://example-shop-13.ir", "کسب‌وکار VIP شماره ۱۳"),
    ("14", "vip-gift", "کادویی VIP", "https://example-shop-14.ir", "کسب‌وکار VIP شماره ۱۴"),
    ("15", "vip-organic", "ارگانیک VIP", "https://example-shop-15.ir", "کسب‌وکار VIP شماره ۱۵"),
    ("16", "vip-cafe", "کافه VIP", "https://example-shop-16.ir", "کسب‌وکار VIP شماره ۱۶"),
    ("17", "vip-pharm", "سلامت VIP", "https://example-shop-17.ir", "کسب‌وکار VIP شماره ۱۷"),
    ("18", "vip-decor", "دکور VIP", "https://example-shop-18.ir", "کسب‌وکار VIP شماره ۱۸"),
    ("19", "vip-mobile", "موبایل VIP", "https://example-shop-19.ir", "کسب‌وکار VIP شماره ۱۹"),
    ("20", "vip-20", "فروشگاه VIP ۲۰", "https://example-shop-20.ir", "کسب‌وکار VIP شماره ۲۰"),
]

RUN_BAT = """@echo off
chcp 65001 >nul
cd /d "%~dp0"
set "DAFTARCHI_DATA=%~dp0data"
set "DAFTARCHI_PRESET=%~dp0preset.json"
if not exist "%~dp0data" mkdir "%~dp0data"
set "EXE=%~dp0..\\Daftarchi.exe"
if not exist "%EXE%" set "EXE=%~dp0..\\..\\Daftarchi.exe"
if not exist "%EXE%" (
  echo فایل Daftarchi.exe پیدا نشد. اول Install-To-E.bat را بزنید.
  pause
  exit /b 1
)
start "" "%EXE%"
"""

INSTALL_BAT = r"""@echo off
chcp 65001 >nul
setlocal
set "TARGET=E:\Daftarchi\KharidaraneAvval"
echo.
echo === نصب دفترچی — ۲۰ مشتری VIP ===
echo مقصد: %TARGET%
echo.
if not exist "%TARGET%" mkdir "%TARGET%"
xcopy /E /Y /I "%~dp0*" "%TARGET%\" >nul
if errorlevel 1 (
  echo کپی ناموفق بود.
  pause
  exit /b 1
)
echo.
echo نصب روی درایو E انجام شد.
echo.
echo   %TARGET%\Daftarchi.exe          فایل برنامه
echo   %TARGET%\sites\01-webakery\      اجرای فروشگاه نمونه
echo.
echo برای هر فروشگاه، Run-Daftarchi.bat داخل همان پوشه را بزنید.
echo preset.json را ویرایش کنید؛ بار اول تنظیمات اعمال می‌شود.
echo.
pause
"""


def main() -> None:
    if ROOT.exists():
        for child in ROOT.iterdir():
            if child.name == "sites":
                import shutil
                shutil.rmtree(child)
    sites_dir = ROOT / "sites"
    sites_dir.mkdir(parents=True, exist_ok=True)
    catalog = []
    for num, slug, shop_name, wp_url, note in SITES:
        folder = f"{num}-{slug}"
        site_dir = sites_dir / folder
        site_dir.mkdir(parents=True, exist_ok=True)
        (site_dir / "data").mkdir(exist_ok=True)
        site_id = f"vip-{num}"
        patch = int(num)
        host_version = f"1.1.7.{patch:02d}" if patch else BASE_VERSION
        preset = {
            "site_id": site_id,
            "label": shop_name,
            "note": note,
            "settings": {
                "shop_name": shop_name,
                "wp_url": wp_url.rstrip("/"),
                "gateway_callback": wp_url.rstrip("/"),
                "woo_key": "",
                "woo_secret": "",
                "woo_auto_sync": "1",
                "manager_phone": "",
            },
        }
        version_host = {
            "version": host_version,
            "url": EXE_URL,
            "notes": f"نسخه اختصاصی {shop_name} — کانال {site_id}",
            "features": [
                f"کانال به‌روزرسانی: {site_id}",
                note,
                "همگام‌سازی فوری ووکامرس، QR پرداخت، فاکتور ۸ سانتی‌متری",
            ],
            "plugins": [],
            "site_id": site_id,
            "shop_name": shop_name,
        }
        (site_dir / "preset.json").write_text(json.dumps(preset, ensure_ascii=False, indent=2), encoding="utf-8")
        (site_dir / "version-host.json").write_text(
            json.dumps(version_host, ensure_ascii=False, indent=2), encoding="utf-8"
        )
        (site_dir / "Run-Daftarchi.bat").write_text(RUN_BAT, encoding="utf-8")
        (site_dir / "HOST-UPLOAD.txt").write_text(
            f"این version-host.json را روی هاست بگذارید:\n\n"
            f"  public_html/license-server/sites/{site_id}/version.json\n\n"
            f"آدرس خوانده‌شده توسط دفترچی (بعد از تنظیم در هاست):\n"
            f"  https://webakery.ir/license-server/sites/{site_id}/version.json\n",
            encoding="utf-8",
        )
        catalog.append(
            {
                "folder": folder,
                "site_id": site_id,
                "shop_name": shop_name,
                "wp_url": wp_url,
                "host_version": host_version,
            }
        )
    (ROOT / "Install-To-E.bat").write_text(INSTALL_BAT, encoding="utf-8")
    (ROOT / "sites-index.json").write_text(json.dumps(catalog, ensure_ascii=False, indent=2), encoding="utf-8")
    (ROOT / "README-FA.txt").write_text(
        "بسته ۲۰ مشتری اول (VIP) — دفترچی\n"
        "================================\n\n"
        "۱) کل پوشه E-Drive-KharidaraneAvval را روی ویندوز کپی کنید.\n"
        "۲) Install-To-E.bat را Run as administrator بزنید → روی E:\\Daftarchi\\KharidaraneAvval نصب می‌شود.\n"
        "۳) Daftarchi.exe همان فایل نصب است (کنار پوشه sites).\n"
        "۴) برای هر فروشگاه: sites\\NN-slug\\Run-Daftarchi.bat\n"
        "۵) preset.json همان فروشگاه را با نام، آدرس سایت و کلید ووکامرس پر کنید.\n"
        "۶) version-host.json را طبق HOST-UPLOAD.txt روی هاست آپلود کنید.\n\n"
        "هر فروشگاه data جدا دارد (فروش و حساب‌ها قاطی نمی‌شوند).\n",
        encoding="utf-8",
    )
    print(f"Created {len(SITES)} sites under {ROOT}")


if __name__ == "__main__":
    main()
