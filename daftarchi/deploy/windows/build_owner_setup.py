#!/usr/bin/env python3
"""One-click Windows installer for Owner (embedded Python, private copy)."""
import shutil
import subprocess
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
DEPLOY = ROOT / "deploy"
SOURCE_ZIP = DEPLOY / "Daftarchi-Owner-Windows.zip"
OUT_PATH = DEPLOY / "Daftarchi-Owner-setup.exe"
STAGE = Path("/tmp/daftarchi-owner-setup")
SOURCE_NAME = "Daftarchi-Owner"
README = DEPLOY / "owner" / "README-FA.txt"


def nsis_path(path: Path) -> str:
    return str(path).replace("\\", "/")


def main() -> None:
    owner_build = ROOT / "deploy" / "owner" / "build_windows.py"
    if not SOURCE_ZIP.is_file():
        subprocess.check_call(["python3", str(owner_build)], cwd=ROOT)
    if not SOURCE_ZIP.is_file():
        raise SystemExit(f"missing {SOURCE_ZIP}")
    if STAGE.exists():
        shutil.rmtree(STAGE)
    STAGE.mkdir(parents=True)
    with zipfile.ZipFile(SOURCE_ZIP) as archive:
        archive.extractall(STAGE)
    source = STAGE / SOURCE_NAME
    if not (source / "python" / "python.exe").is_file():
        raise SystemExit("embedded python.exe is missing")
    if not (source / "Start-Daftarchi.bat").is_file():
        raise SystemExit("Start-Daftarchi.bat is missing")
    if README.is_file():
        shutil.copy(README, source / "README-FA.txt")
    icon = ROOT / "deploy" / "windows" / "daftarchi.ico"
    if icon.is_file():
        shutil.copy(icon, source / "daftarchi.ico")
    version = "1.1.94"
    for line in (source / "app" / "app.py").read_text(encoding="utf-8").splitlines():
        if line.startswith("APP_VERSION"):
            version = line.split("=", 1)[1].strip().strip('"')
            break
    script = STAGE / "owner.nsi"
    script.write_text(
        f"""Unicode true
SetCompressor /SOLID lzma
!include "MUI2.nsh"

Name "دفترچی محمد (Owner)"
OutFile "{nsis_path(OUT_PATH)}"
InstallDir "$LOCALAPPDATA\\Daftarchi-Owner"
RequestExecutionLevel user
ShowInstDetails show
BrandingText "دفترچی Owner"
VIProductVersion {version}.0
VIAddVersionKey "ProductName" "دفترچی Owner"
VIAddVersionKey "FileDescription" "نصب نسخه شخصی محمد"
VIAddVersionKey "FileVersion" "{version}"
VIAddVersionKey "ProductVersion" "{version}"

!define MUI_ABORTWARNING
!define MUI_WELCOMEPAGE_TITLE "نصب دفترچی Owner"
!define MUI_WELCOMEPAGE_TEXT "پایتون همراه نصب می‌شود؛ Microsoft Store لازم نیست.$\\r$\\nنسخه شخصی محمد — به مشتری ندهید.$\\r$\\n$\\r$\\nورود: 09373211344 / 3351252"
!define MUI_FINISHPAGE_TITLE "دفترچی Owner آماده است"
!define MUI_FINISHPAGE_TEXT "نصب تمام شد. دفترچی را از دسکتاپ یا منوی شروع باز کنید."
!define MUI_FINISHPAGE_RUN
!define MUI_FINISHPAGE_RUN_TEXT "الان دفترچی را باز کن"
!define MUI_FINISHPAGE_RUN_FUNCTION LaunchOwner
!insertmacro MUI_PAGE_WELCOME
!insertmacro MUI_PAGE_INSTFILES
!insertmacro MUI_PAGE_FINISH
!insertmacro MUI_LANGUAGE "Farsi"

Function LaunchOwner
  SetOutPath "$INSTDIR"
  ExecShell "open" "$INSTDIR\\Start-Daftarchi.bat"
FunctionEnd

Section "دفترچی Owner"
  SetShellVarContext current
  SetOutPath "$INSTDIR"
  File /r "{nsis_path(source)}\\*.*"
  SetOutPath "$INSTDIR"
  CreateShortcut "$DESKTOP\\دفترچی محمد.lnk" "$INSTDIR\\Start-Daftarchi.bat" "" "$INSTDIR\\daftarchi.ico" 0 SW_SHOWNORMAL "" "دفترچی Owner"
  CreateDirectory "$SMPROGRAMS\\دفترچی Owner"
  CreateShortcut "$SMPROGRAMS\\دفترچی Owner\\دفترچی محمد.lnk" "$INSTDIR\\Start-Daftarchi.bat" "" "$INSTDIR\\daftarchi.ico"
  CreateShortcut "$SMPROGRAMS\\دفترچی Owner\\حذف.lnk" "$INSTDIR\\Uninstall.exe"
  WriteUninstaller "$INSTDIR\\Uninstall.exe"
SectionEnd

Section "Uninstall"
  SetShellVarContext current
  Delete "$DESKTOP\\دفترچی محمد.lnk"
  Delete "$SMPROGRAMS\\دفترچی Owner\\دفترچی محمد.lnk"
  Delete "$SMPROGRAMS\\دفترچی Owner\\حذف.lnk"
  RMDir "$SMPROGRAMS\\دفترچی Owner"
  RMDir /r "$INSTDIR\\app"
  RMDir /r "$INSTDIR\\python"
  Delete "$INSTDIR\\Start-Daftarchi.bat"
  Delete "$INSTDIR\\preset.json"
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
    print(f"{OUT_PATH} {OUT_PATH.stat().st_size} bytes {version}")
    print(
        "https://github.com/mohammad-mahdikhani/plugins/raw/"
        "cursor/instant-woo-pos-receipt-3e51/daftarchi/deploy/Daftarchi-Owner-setup.exe"
    )


if __name__ == "__main__":
    main()