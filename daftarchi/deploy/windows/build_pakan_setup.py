#!/usr/bin/env python3
"""Build a one-click Windows installer for فروشگاه پاکان."""
import shutil
import subprocess
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
DEPLOY = ROOT / "deploy"
ZIP_PATH = DEPLOY / "Daftarchi-Pakan.zip"
OUT_PATH = DEPLOY / "Daftarchi-Pakan-setup.exe"
STAGE = Path("/tmp/daftarchi-pakan-setup")
SOURCE_NAME = "Daftarchi-Pakan"


def nsis_path(path: Path) -> str:
    return str(path).replace("\\", "/")


def main() -> None:
    if not ZIP_PATH.is_file():
        raise SystemExit(f"missing {ZIP_PATH}")
    if STAGE.exists():
        shutil.rmtree(STAGE)
    STAGE.mkdir(parents=True)
    with zipfile.ZipFile(ZIP_PATH) as archive:
        archive.extractall(STAGE)
    source = STAGE / SOURCE_NAME
    if not (source / "Start-Daftarchi.bat").is_file():
        raise SystemExit("windows launcher is missing from the Pakan zip")
    readme = ROOT / "deploy" / "customers" / "pakan" / "README-FA.txt"
    shutil.copy(readme, source / "README-FA.txt")
    script = STAGE / "pakan.nsi"
    script.write_text(
        f"""Unicode true
SetCompressor /SOLID lzma
!include "MUI2.nsh"

Name "دفترچی فروشگاه پاکان"
OutFile "{nsis_path(OUT_PATH)}"
InstallDir "$LOCALAPPDATA\\Daftarchi-Pakan"
RequestExecutionLevel user
ShowInstDetails show
BrandingText "دفترچی فروشگاه پاکان"
VIProductVersion 1.1.13.0
VIAddVersionKey "ProductName" "دفترچی فروشگاه پاکان"
VIAddVersionKey "FileDescription" "نصب دفترچی برای فروشگاه پاکان"
VIAddVersionKey "FileVersion" "1.1.13"
VIAddVersionKey "ProductVersion" "1.1.13"

!define MUI_ABORTWARNING
!define MUI_WELCOMEPAGE_TITLE "نصب دفترچی"
!define MUI_WELCOMEPAGE_TEXT "دفترچی فروشگاه پاکان روی این ویندوز نصب می‌شود.$\\r$\\nمیانبر روی دسکتاپ ساخته می‌شود و برنامه باز می‌شود.$\\r$\\n$\\r$\\nورود اولیه: 09120000000 / daftarchi"
!define MUI_FINISHPAGE_TITLE "دفترچی آماده است"
!define MUI_FINISHPAGE_TEXT "نصب تمام شد. دفعه بعد میانبر دفترچی پاکان را از دسکتاپ باز کنید."
!define MUI_FINISHPAGE_RUN
!define MUI_FINISHPAGE_RUN_TEXT "الان دفترچی را باز کن"
!define MUI_FINISHPAGE_RUN_FUNCTION LaunchPakan
!insertmacro MUI_PAGE_WELCOME
!insertmacro MUI_PAGE_INSTFILES
!insertmacro MUI_PAGE_FINISH
!insertmacro MUI_LANGUAGE "Farsi"

Function LaunchPakan
  SetOutPath "$INSTDIR"
  ExecShell "open" "$INSTDIR\\Start-Daftarchi.bat"
FunctionEnd

Section "دفترچی"
  SetOutPath "$INSTDIR"
  File /r "{nsis_path(source)}\\*.*"
  SetOutPath "$INSTDIR"
  CreateShortcut "$DESKTOP\\دفترچی پاکان.lnk" "$INSTDIR\\Start-Daftarchi.bat" "" "$INSTDIR\\python\\python.exe" 0
  CreateDirectory "$SMPROGRAMS\\دفترچی پاکان"
  CreateShortcut "$SMPROGRAMS\\دفترچی پاکان\\دفترچی پاکان.lnk" "$INSTDIR\\Start-Daftarchi.bat" "" "$INSTDIR\\python\\python.exe" 0
  CreateShortcut "$SMPROGRAMS\\دفترچی پاکان\\حذف دفترچی.lnk" "$INSTDIR\\Uninstall.exe"
  WriteUninstaller "$INSTDIR\\Uninstall.exe"
SectionEnd

Section "Uninstall"
  Delete "$DESKTOP\\دفترچی پاکان.lnk"
  Delete "$SMPROGRAMS\\دفترچی پاکان\\دفترچی پاکان.lnk"
  Delete "$SMPROGRAMS\\دفترچی پاکان\\حذف دفترچی.lnk"
  RMDir "$SMPROGRAMS\\دفترچی پاکان"
  RMDir /r "$INSTDIR\\app"
  RMDir /r "$INSTDIR\\python"
  Delete "$INSTDIR\\Start-Daftarchi.bat"
  Delete "$INSTDIR\\preset.json"
  Delete "$INSTDIR\\README-FA.txt"
  Delete "$INSTDIR\\channel.txt"
  Delete "$INSTDIR\\version.json"
  Delete "$INSTDIR\\Uninstall.exe"
  RMDir "$INSTDIR"
SectionEnd
""",
        encoding="utf-8",
    )
    subprocess.check_call(["makensis", str(script)])
    blob = OUT_PATH.read_bytes()
    if blob[:2] != b"MZ":
        raise SystemExit("setup.exe is not a Windows program")
    if len(blob) < 8_000_000:
        raise SystemExit("setup.exe is too small to contain the program")
    print(f"{OUT_PATH} {OUT_PATH.stat().st_size} bytes")


if __name__ == "__main__":
    main()
