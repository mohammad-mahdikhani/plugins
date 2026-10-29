@echo off
chcp 65001 >nul
cd /d "%~dp0"
set "DAFTARCHI_DATA=%~dp0data"
set "DAFTARCHI_PRESET=%~dp0preset.json"
if not exist "%DAFTARCHI_DATA%" mkdir "%DAFTARCHI_DATA%"
if not exist "%~dp0Daftarchi.exe" (
  echo فایل Daftarchi.exe کنار همین bat نیست.
  pause
  exit /b 1
)
start "" "%~dp0Daftarchi.exe"
