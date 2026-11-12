#!/usr/bin/env python3
"""Build the Windows installer named for اشرافی."""
import shutil
import subprocess
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
DEPLOY = ROOT / "deploy"
ZIP_PATH = DEPLOY / "Daftarchi-اشرافی.zip"
OUT_PATH = DEPLOY / "Daftarchi-اشرافی-setup.exe"
STAGE = Path("/tmp/daftarchi-ashrafi-setup")
SOURCE_NAME = "Daftarchi-اشرافی"


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
    if not (source / "Start-Daftarchi.vbs").is_file():
        raise SystemExit("quiet launcher is missing")
    icon = ROOT / "deploy" / "windows" / "daftarchi.ico"
    if not icon.is_file():
        raise SystemExit(f"missing {icon}")
    shutil.copy(icon, source / "daftarchi.ico")
    script = STAGE / "ashrafi.nsi"
    script.write_text(
        f"""Unicode true
SetCompressor /SOLID lzma
!include "MUI2.nsh"

Name "دفترچی اشرافی"
OutFile "{nsis_path(OUT_PATH)}"
InstallDir "$LOCALAPPDATA\\Daftarchi-Ashrafi"
RequestExecutionLevel user
ShowInstDetails nevershow
BrandingText "دفترچی اشرافی"
VIProductVersion 1.1.33.0
VIAddVersionKey "ProductName" "دفترچی اشرافی"
VIAddVersionKey "FileDescription" "نصب دفترچی برای اشرافی"
VIAddVersionKey "FileVersion" "1.1.33"
VIAddVersionKey "ProductVersion" "1.1.33"

!define MUI_ABORTWARNING
!define MUI_WELCOMEPAGE_TITLE "نصب دفترچی اشرافی"
!define MUI_WELCOMEPAGE_TEXT "دفترچی اشرافی روی این ویندوز نصب می‌شود.$\\r$\\nدر صفحهٔ بعد محل نصب را خودتان انتخاب می‌کنید.$\\r$\\nمیانبر روی دسکتاپ ساخته می‌شود و برنامه بدون پنجرهٔ سیاه باز می‌شود.$\\r$\\n$\\r$\\nورود اولیه: 09120000000 / daftarchi"
!define MUI_DIRECTORYPAGE_TEXT_TOP "پوشهٔ نصب دفترچی اشرافی را انتخاب کنید. فروش‌ها در همین پوشه می‌مانند."
!define MUI_DIRECTORYPAGE_TEXT_DESTINATION "محل نصب"
!define MUI_FINISHPAGE_TITLE "دفترچی اشرافی آماده است"
!define MUI_FINISHPAGE_TEXT "نصب تمام شد. آیکون دفترچی اشرافی روی دسکتاپ است."
!define MUI_FINISHPAGE_RUN
!define MUI_FINISHPAGE_RUN_TEXT "الان دفترچی را باز کن"
!define MUI_FINISHPAGE_RUN_FUNCTION LaunchShop
!insertmacro MUI_PAGE_WELCOME
!insertmacro MUI_PAGE_DIRECTORY
!insertmacro MUI_PAGE_INSTFILES
!insertmacro MUI_PAGE_FINISH
!insertmacro MUI_LANGUAGE "Farsi"

Function LaunchShop
  SetOutPath "$INSTDIR"
  ExecShell "open" "$INSTDIR\\Start-Daftarchi.vbs"
FunctionEnd

Section "دفترچی"
  SetShellVarContext current
  SetOutPath "$INSTDIR"
  File /r "{nsis_path(source)}\\*.*"
  SetOutPath "$INSTDIR"
  CreateShortcut "$DESKTOP\\دفترچی اشرافی.lnk" "$INSTDIR\\Start-Daftarchi.vbs" "" "$INSTDIR\\daftarchi.ico" 0 SW_SHOWNORMAL "" "دفترچی اشرافی"
  CreateDirectory "$SMPROGRAMS\\دفترچی اشرافی"
  CreateShortcut "$SMPROGRAMS\\دفترچی اشرافی\\دفترچی اشرافی.lnk" "$INSTDIR\\Start-Daftarchi.vbs" "" "$INSTDIR\\daftarchi.ico" 0 SW_SHOWNORMAL "" "دفترچی اشرافی"
  WriteUninstaller "$INSTDIR\\Uninstall.exe"
SectionEnd

Section "Uninstall"
  SetShellVarContext current
  Delete "$DESKTOP\\دفترچی اشرافی.lnk"
  Delete "$SMPROGRAMS\\دفترچی اشرافی\\دفترچی اشرافی.lnk"
  RMDir "$SMPROGRAMS\\دفترچی اشرافی"
  RMDir /r "$INSTDIR\\app"
  RMDir /r "$INSTDIR\\python"
  Delete "$INSTDIR\\Start-Daftarchi.bat"
  Delete "$INSTDIR\\Start-Daftarchi.vbs"
  Delete "$INSTDIR\\preset.json"
  Delete "$INSTDIR\\README-FA.txt"
  Delete "$INSTDIR\\channel.txt"
  Delete "$INSTDIR\\version.json"
  Delete "$INSTDIR\\daftarchi.ico"
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
    if "دفترچی اشرافی".encode("utf-16le") not in blob:
        raise SystemExit("installer name is missing")
    print(f"{OUT_PATH} {OUT_PATH.stat().st_size} bytes")


if __name__ == "__main__":
    main()
