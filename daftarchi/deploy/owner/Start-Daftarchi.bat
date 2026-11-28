@echo off
cd /d "%~dp0"
set "DAFTARCHI_DATA=%~dp0data"
set "DAFTARCHI_PRESET=%~dp0preset.json"
set "DAFTARCHI_CHANNEL=customer"
set "DAFTARCHI_OPEN_BROWSER=1"
set "PORT=8080"
if not exist "%DAFTARCHI_DATA%" mkdir "%DAFTARCHI_DATA%"
if not exist "%~dp0python\python.exe" (
  echo python.exe missing. From setup.exe install again, or use the full Windows zip with the python folder.
  pause
  exit /b 1
)
"%~dp0python\python.exe" "%~dp0app\app.py"
if errorlevel 1 pause