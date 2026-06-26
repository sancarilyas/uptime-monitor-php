@echo off
title Uptime Monitor - Başlatma Menüsü

:menu
cls
echo ========================================
echo    UPTIME MONITOR - BASLATMA MENUSU
echo ========================================
echo.
echo Hangi yontemi kullanmak istiyorsunuz?
echo.
echo 1. Basit Batch Script (Onerilen)
echo 2. PowerShell Script
echo 3. Task Scheduler Kurulumu
echo 4. Grafik Arayuz Manager
echo 5. Service Sorunu Cozumu
echo 6. Cikis
echo.
set /p choice="Seciminiz (1-6): "

if "%choice%"=="1" goto simple
if "%choice%"=="2" goto powershell
if "%choice%"=="3" goto scheduler
if "%choice%"=="4" goto manager
if "%choice%"=="5" goto fix
if "%choice%"=="6" goto exit
goto menu

:simple
echo.
echo Basit batch script baslatiliyor...
echo Bu yontem en kolay ve guvenilir yontemdir.
echo Cikmak icin Ctrl+C basin.
echo.
pause
start_monitor.bat
goto menu

:powershell
echo.
echo PowerShell script baslatiliyor...
echo PowerShell ExecutionPolicy ayari gerekebilir.
echo.
pause
powershell -ExecutionPolicy Bypass -File start_monitor.ps1
goto menu

:scheduler
echo.
echo Task Scheduler kurulumu baslatiliyor...
echo Bu yontem Windows'un kendi zamanlayicisini kullanir.
echo.
pause
setup_task_scheduler.bat
goto menu

:manager
echo.
echo Grafik arayuz manager baslatiliyor...
echo Bu yontem kolay yonetim saglar.
echo.
pause
monitor_manager.bat
goto menu

:fix
echo.
echo Service sorunu cozumu baslatiliyor...
echo.
pause
fix_service.bat
goto menu

:exit
echo.
echo Cikiliyor...
exit
