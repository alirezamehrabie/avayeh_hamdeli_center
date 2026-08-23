@echo off
rem ---------------------------------------------------------------
rem  Avaye Print Bridge - starts the local print agent.
rem  Keep this window open while you want to print from the panel.
rem  Stop: close this window (or press Ctrl+C).
rem ---------------------------------------------------------------
chcp 65001 >nul
title Avaye Print Bridge (http://127.0.0.1:9235)
cd /d "%~dp0"
echo Starting Avaye Print Bridge ...
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0bridge.ps1" %*
echo.
echo Bridge stopped.
pause
