@echo off
chcp 65001 >nul
cd /d "%~dp0"
set "DAFTARCHI_DATA=%~dp0data"
set "DAFTARCHI_PRESET=%~dp0preset.json"
if not exist "%~dp0data" mkdir "%~dp0data"
set "EXE=%~dp0..\Daftarchi.exe"
if not exist "%EXE%" set "EXE=%~dp0..\..\Daftarchi.exe"
if not exist "%EXE%" (
  echo فایل Daftarchi.exe پیدا نشد. اول Install-To-E.bat را بزنید.
  pause
  exit /b 1
)
start "" "%EXE%"
