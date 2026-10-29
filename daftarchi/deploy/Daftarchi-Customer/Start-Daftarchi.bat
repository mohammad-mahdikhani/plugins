@echo off
cd /d "%~dp0"
set "DAFTARCHI_DATA=%~dp0data"
set "DAFTARCHI_OPEN_BROWSER=1"
set "PORT=8080"
if not exist "%DAFTARCHI_DATA%" mkdir "%DAFTARCHI_DATA%"
if not exist "%~dp0python\python.exe" (
  echo python.exe missing. Unzip the whole folder first.
  pause
  exit /b 1
)
"%~dp0python\python.exe" "%~dp0app\app.py"
if errorlevel 1 pause
