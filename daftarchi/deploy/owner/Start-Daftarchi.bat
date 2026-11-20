@echo off
cd /d "%~dp0"
set "DAFTARCHI_DATA=%~dp0data"
set "DAFTARCHI_PRESET=%~dp0preset.json"
set "DAFTARCHI_CHANNEL=customer"
set "DAFTARCHI_OPEN_BROWSER=1"
set "PORT=8080"
if not exist "%DAFTARCHI_DATA%" mkdir "%DAFTARCHI_DATA%"
where py >nul 2>nul
if %errorlevel%==0 (
  py -3 "%~dp0app\app.py"
) else (
  python "%~dp0app\app.py"
)
if errorlevel 1 pause
