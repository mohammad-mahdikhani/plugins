#!/usr/bin/env python3
"""Build one-click Windows installer for the public customer channel."""
import shutil
import subprocess
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
DEPLOY = ROOT / "deploy"
SOURCE_ZIP = DEPLOY / "Daftarchi-Customer.zip"
OUT_PATH = DEPLOY / "Daftarchi-Customer-setup.exe"
STAGE = Path("/tmp/daftarchi-customer-setup")
SOURCE_NAME = "Daftarchi"
README = DEPLOY / "Daftarchi-Customer" / "README-FA.txt"


def nsis_path(path: Path) -> str:
    return str(path).replace("\\", "/")


def main() -> None:
    if not SOURCE_ZIP.is_file():
        raise SystemExit(f"missing {SOURCE_ZIP}")
    if STAGE.exists():
        shutil.rmtree(STAGE)
    STAGE.mkdir(parents=True)
    with zipfile.ZipFile(SOURCE_ZIP) as archive:
        archive.extractall(STAGE)
    source = STAGE / SOURCE_NAME
    if not (source / "Start-Daftarchi.bat").is_file():
        raise SystemExit("windows launcher is missing")
    if README.is_file():
        shutil.copy(README, source / "README-FA.txt")
    icon = ROOT / "deploy" / "windows" / "daftarchi.ico"
    shutil.copy(icon, source / "daftarchi.ico")
    version = "1.1.94"
    for line in (source / "app" / "app.py").read_text(encoding="utf-8").splitlines():
        if line.startswith("APP_VERSION"):
            version = line.split("=", 1)[1].strip().strip('"')
            break
    script = STAGE / "customer.nsi"
    script.write_text(
        f"""Unicode true
SetCompressor /SOLID lzma
!include "MUI2.nsh"

Name "دفترچی"
OutFile "{nsis_path(OUT_PATH)}"
InstallDir "$LOCALAPPDATA\\Daftarchi"
RequestExecutionLevel user
ShowInstDetails show
BrandingText "دفترچی"
VIProductVersion {version}.0
VIAddVersionKey "ProductName" "دفترچی"
VIAddVersionKey "FileDescription" "نصب دفترچی فروشگاه"
VIAddVersionKey "FileVersion" "{version}"
VIAddVersionKey "ProductVersion" "{version}"

!define MUI_ABORTWARNING
!define MUI_WELCOMEPAGE_TITLE "نصب دفترچی"
!define MUI_WELCOMEPAGE_TEXT "دفترچی روی این ویندوز نصب می‌شود.$\\r$\\n۷ روز رایگان است؛ بعداً می‌توانید روزانه یا ماهانه تمدید کنید.$\\r$\\n$\\r$\\nورود اول: 09120000000 / daftarchi"
!define MUI_FINISHPAGE_TITLE "دفترچی آماده است"
!define MUI_FINISHPAGE_TEXT "نصب تمام شد. آیکون دفترچی روی دسکتاپ است."
!define MUI_FINISHPAGE_RUN
!define MUI_FINISHPAGE_RUN_TEXT "الان دفترچی را باز کن"
!define MUI_FINISHPAGE_RUN_FUNCTION LaunchCustomer
!insertmacro MUI_PAGE_WELCOME
!insertmacro MUI_PAGE_INSTFILES
!insertmacro MUI_PAGE_FINISH
!insertmacro MUI_LANGUAGE "Farsi"

Function LaunchCustomer
  SetOutPath "$INSTDIR"
  ExecShell "open" "$INSTDIR\\Start-Daftarchi.bat"
FunctionEnd

Section "دفترچی"
  SetShellVarContext current
  SetOutPath "$INSTDIR"
  File /r "{nsis_path(source)}\\*.*"
  SetOutPath "$INSTDIR"
  CreateShortcut "$DESKTOP\\دفترچی.lnk" "$INSTDIR\\Start-Daftarchi.bat" "" "$INSTDIR\\daftarchi.ico" 0 SW_SHOWNORMAL "" "دفترچی"
  CreateDirectory "$SMPROGRAMS\\دفترچی"
  CreateShortcut "$SMPROGRAMS\\دفترچی\\دفترچی.lnk" "$INSTDIR\\Start-Daftarchi.bat" "" "$INSTDIR\\daftarchi.ico"
  CreateShortcut "$SMPROGRAMS\\دفترچی\\حذف دفترچی.lnk" "$INSTDIR\\Uninstall.exe"
  WriteUninstaller "$INSTDIR\\Uninstall.exe"
SectionEnd

Section "Uninstall"
  SetShellVarContext current
  Delete "$DESKTOP\\دفترچی.lnk"
  Delete "$SMPROGRAMS\\دفترچی\\دفترچی.lnk"
  Delete "$SMPROGRAMS\\دفترچی\\حذف دفترچی.lnk"
  RMDir "$SMPROGRAMS\\دفترچی"
  RMDir /r "$INSTDIR\\app"
  RMDir /r "$INSTDIR\\python"
  Delete "$INSTDIR\\Start-Daftarchi.bat"
  Delete "$INSTDIR\\README-FA.txt"
  Delete "$INSTDIR\\daftarchi.ico"
  Delete "$INSTDIR\\Uninstall.exe"
  RMDir "$INSTDIR"
SectionEnd
""",
        encoding="utf-8",
    )
    subprocess.check_call(["makensis", str(script)])
    blob = OUT_PATH.read_bytes()
    if blob[:2] != b"MZ" or len(blob) < 8_000_000:
        raise SystemExit("setup.exe is not a complete Windows program")
    if "دفترچی".encode("utf-16le") not in blob:
        raise SystemExit("installer title is missing")
    print(f"{OUT_PATH} {OUT_PATH.stat().st_size} bytes {version}")


if __name__ == "__main__":
    main()