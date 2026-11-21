#!/usr/bin/env python3
"""Build a Windows installer of the current Daftarchi for local testing."""
import shutil
import subprocess
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
DEPLOY = ROOT / "deploy"
SOURCE_ZIP = DEPLOY / "Daftarchi-Customer.zip"
OUT_PATH = DEPLOY / "Daftarchi-Test-setup.exe"
STAGE = Path("/tmp/daftarchi-test-setup")
SOURCE_NAME = "Daftarchi"


def nsis_path(path: Path) -> str:
    return str(path).replace("\\", "/")


def refresh_app(folder: Path) -> None:
    app = folder / "app"
    if app.exists():
        shutil.rmtree(app)
    app.mkdir()
    for name in ("app.py", "moadian.py", "version.json", "requirements.txt"):
        src = ROOT / name
        if src.is_file():
            shutil.copy(src, app / name)
    for dirname in ("static", "templates"):
        shutil.copytree(ROOT / dirname, app / dirname, ignore=shutil.ignore_patterns("__pycache__"))


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
    refresh_app(source)
    icon = ROOT / "deploy" / "windows" / "daftarchi.ico"
    shutil.copy(icon, source / "daftarchi.ico")
    version = "1.1.17"
    for line in (source / "app" / "app.py").read_text(encoding="utf-8").splitlines():
        if line.startswith("APP_VERSION"):
            version = line.split("=", 1)[1].strip().strip('"')
            break
    script = STAGE / "test.nsi"
    script.write_text(
        f"""Unicode true
SetCompressor /SOLID lzma
!include "MUI2.nsh"

Name "دفترچی آزمایشی"
OutFile "{nsis_path(OUT_PATH)}"
InstallDir "$LOCALAPPDATA\\Daftarchi-Test"
RequestExecutionLevel user
ShowInstDetails show
BrandingText "دفترچی آزمایشی"
VIProductVersion {version}.0
VIAddVersionKey "ProductName" "دفترچی آزمایشی"
VIAddVersionKey "FileDescription" "نسخه آزمایشی دفترچی"
VIAddVersionKey "FileVersion" "{version}"
VIAddVersionKey "ProductVersion" "{version}"

!define MUI_ABORTWARNING
!define MUI_WELCOMEPAGE_TITLE "نصب دفترچی برای تست"
!define MUI_WELCOMEPAGE_TEXT "همین نسخه روی این ویندوز نصب می‌شود تا تغییرات را ببینید.$\\r$\\nآیکون دفترچی روی دسکتاپ می‌آید و برنامه باز می‌شود.$\\r$\\n$\\r$\\nورود: 09120000000 / daftarchi"
!define MUI_FINISHPAGE_TITLE "دفترچی آماده است"
!define MUI_FINISHPAGE_TEXT "نصب تمام شد. آیکون دفترچی روی دسکتاپ است."
!define MUI_FINISHPAGE_RUN
!define MUI_FINISHPAGE_RUN_TEXT "الان دفترچی را باز کن"
!define MUI_FINISHPAGE_RUN_FUNCTION LaunchTest
!insertmacro MUI_PAGE_WELCOME
!insertmacro MUI_PAGE_INSTFILES
!insertmacro MUI_PAGE_FINISH
!insertmacro MUI_LANGUAGE "Farsi"

Function LaunchTest
  SetOutPath "$INSTDIR"
  ExecShell "open" "$INSTDIR\\Start-Daftarchi.bat"
FunctionEnd

Section "دفترچی"
  SetShellVarContext current
  SetOutPath "$INSTDIR"
  File /r "{nsis_path(source)}\\*.*"
  SetOutPath "$INSTDIR"
  CreateShortcut "$DESKTOP\\دفترچی.lnk" "$INSTDIR\\Start-Daftarchi.bat" "" "$INSTDIR\\daftarchi.ico" 0 SW_SHOWNORMAL "" "دفترچی"
  WriteUninstaller "$INSTDIR\\Uninstall.exe"
SectionEnd

Section "Uninstall"
  SetShellVarContext current
  Delete "$DESKTOP\\دفترچی.lnk"
  RMDir /r "$INSTDIR\\app"
  RMDir /r "$INSTDIR\\python"
  Delete "$INSTDIR\\Start-Daftarchi.bat"
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
    if blob[:2] != b"MZ" or len(blob) < 8_000_000:
        raise SystemExit("setup.exe is not a complete Windows program")
    if f"دفترچی آزمایشی".encode("utf-16le") not in blob:
        raise SystemExit("installer title is missing")
    print(f"{OUT_PATH} {OUT_PATH.stat().st_size} bytes {version}")


if __name__ == "__main__":
    main()
