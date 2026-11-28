#!/usr/bin/env python3
"""Pack the Windows folder for اشرافی. No Mac or Linux package."""
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]
DEPLOY = ROOT / "deploy"
HERE = Path(__file__).resolve().parent
APP = ROOT
TARGET = "Daftarchi-اشرافی/"
SOURCE = "Daftarchi/"
QUIET_VBS = r"""Set shell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
root = fso.GetParentFolderName(WScript.ScriptFullName)
shell.CurrentDirectory = root
Set env = shell.Environment("PROCESS")
env("DAFTARCHI_DATA") = root & "\data"
env("DAFTARCHI_CHANNEL") = "customer"
env("DAFTARCHI_PRESET") = root & "\preset.json"
env("DAFTARCHI_OPEN_BROWSER") = "1"
env("PORT") = "8080"
If Not fso.FolderExists(root & "\data") Then fso.CreateFolder root & "\data"
python = root & "\python\pythonw.exe"
If Not fso.FileExists(python) Then
  WScript.Quit 1
End If
shell.Run Chr(34) & python & Chr(34) & " " & Chr(34) & root & "\app\app.py" & Chr(34), 0, False
"""


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


def patch_launcher(data: bytes) -> bytes:
    text = data.decode("utf-8")
    text = text.replace(
        'set "DAFTARCHI_CHANNEL=customer"\r\n',
        'set "DAFTARCHI_CHANNEL=customer"\r\nset "DAFTARCHI_PRESET=%~dp0preset.json"\r\n',
        1,
    )
    text = text.replace(
        'set "DAFTARCHI_CHANNEL=customer"\n',
        'set "DAFTARCHI_CHANNEL=customer"\nset "DAFTARCHI_PRESET=%~dp0preset.json"\n',
        1,
    )
    text = text.replace(
        '"%~dp0python\\python.exe" "%~dp0app\\app.py"\r\nif errorlevel 1 pause\r\n',
        'start "" "%~dp0python\\pythonw.exe" "%~dp0app\\app.py"\r\nexit /b 0\r\n',
    )
    text = text.replace(
        '"%~dp0python\\python.exe" "%~dp0app\\app.py"\nif errorlevel 1 pause\n',
        'start "" "%~dp0python\\pythonw.exe" "%~dp0app\\app.py"\nexit /b 0\n',
    )
    if "DAFTARCHI_PRESET" not in text or "pythonw.exe" not in text:
        raise SystemExit("could not make the Windows launcher quiet")
    return text.encode("utf-8")


def main() -> None:
    src = DEPLOY / "Daftarchi-Customer.zip"
    dest = DEPLOY / "Daftarchi-اشرافی.zip"
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
            if rel == "Start-Daftarchi.bat":
                data = patch_launcher(data)
            out = zipfile.ZipInfo(filename=TARGET + rel, date_time=info.date_time)
            out.external_attr = info.external_attr
            out.create_system = info.create_system
            out.compress_type = zipfile.ZIP_DEFLATED
            zout.writestr(out, data)
        for rel, data in app_files():
            out = zipfile.ZipInfo(filename=TARGET + rel)
            out.compress_type = zipfile.ZIP_DEFLATED
            out.external_attr = (0o644 & 0xFFFF) << 16
            zout.writestr(out, data)
        extras = (
            ("preset.json", preset),
            ("README-FA.txt", readme),
            ("Start-Daftarchi.vbs", QUIET_VBS.encode("ascii")),
        )
        for rel, data in extras:
            out = zipfile.ZipInfo(filename=TARGET + rel)
            out.compress_type = zipfile.ZIP_DEFLATED
            out.external_attr = (0o644 & 0xFFFF) << 16
            zout.writestr(out, data)
    print(dest, dest.stat().st_size)


if __name__ == "__main__":
    main()
