@echo off
echo ========================================
echo    SERVICE SORUNU COZUMU
echo ========================================
echo.

REM Service'i durdur ve sil
echo Mevcut service durduruluyor...
sc stop UptimeMonitor >nul 2>&1
sc delete UptimeMonitor >nul 2>&1

echo.
echo Service temizlendi.
echo.
echo Alternatif yontemler:
echo.
echo 1. start_monitor.bat - Basit batch script
echo 2. start_monitor.ps1 - PowerShell script  
echo 3. setup_task_scheduler.bat - Task Scheduler
echo 4. monitor_manager.bat - Grafik arayuz
echo.
echo Hangi yontemi kullanmak istiyorsunuz?
echo.
echo 1) Basit Batch Script
echo 2) PowerShell Script
echo 3) Task Scheduler
echo 4) Grafik Arayuz
echo 5) Cikis
echo.
set /p choice="Seciminiz (1-5): "

if "%choice%"=="1" goto batch
if "%choice%"=="2" goto powershell
if "%choice%"=="3" goto scheduler
if "%choice%"=="4" goto manager
if "%choice%"=="5" goto exit
goto menu

:batch
echo.
echo Basit batch script baslatiliyor...
start_monitor.bat
goto exit

:powershell
echo.
echo PowerShell script baslatiliyor...
powershell -ExecutionPolicy Bypass -File start_monitor.ps1
goto exit

:scheduler
echo.
echo Task Scheduler kurulumu baslatiliyor...
setup_task_scheduler.bat
goto exit

:manager
echo.
echo Grafik arayuz baslatiliyor...
monitor_manager.bat
goto exit

:exit
echo.
echo Cikiliyor...
exit
