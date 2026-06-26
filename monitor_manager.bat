@echo off
title Uptime Monitor Manager

:menu
cls
echo ========================================
echo    UPTIME MONITOR MANAGER
echo ========================================
echo.
echo 1. Monitor Daemon Baslat
echo 2. Monitor Daemon Durdur
echo 3. Monitor Durumu Kontrol Et
echo 4. Tek Seferlik Calistir
echo 5. Log Dosyasini Goruntule
echo 6. Cikis
echo.
set /p choice="Seciminiz (1-6): "

if "%choice%"=="1" goto start
if "%choice%"=="2" goto stop
if "%choice%"=="3" goto status
if "%choice%"=="4" goto run
if "%choice%"=="5" goto logs
if "%choice%"=="6" goto exit
goto menu

:start
echo.
echo Monitor daemon baslatiliyor...
php monitor_daemon.php start
pause
goto menu

:stop
echo.
echo Monitor daemon durduruluyor...
php monitor_daemon.php stop
pause
goto menu

:status
echo.
echo Monitor durumu kontrol ediliyor...
php monitor_daemon.php status
pause
goto menu

:run
echo.
echo Tek seferlik monitoring calistiriliyor...
php monitor_daemon.php run
pause
goto menu

:logs
echo.
echo Log dosyasi goruntuleniyor...
if exist logs\monitor_daemon.log (
    type logs\monitor_daemon.log
) else (
    echo Log dosyasi bulunamadi.
)
pause
goto menu

:exit
echo.
echo Cikiliyor...
exit
