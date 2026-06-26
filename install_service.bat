@echo off
echo Uptime Monitor Service Kurulumu
echo ================================

REM Service adı
set SERVICE_NAME=UptimeMonitor
set SERVICE_DISPLAY_NAME=Uptime Monitor Service
set SERVICE_DESCRIPTION=Uptime monitoring service for website status checking

REM PHP ve script yolları
set PHP_PATH=C:\wamp64\bin\php\php8.3.14\php.exe
set SCRIPT_PATH=%~dp0monitor_daemon.php

echo.
echo Service bilgileri:
echo - Service Adi: %SERVICE_NAME%
echo - PHP Yolu: %PHP_PATH%
echo - Script Yolu: %SCRIPT_PATH%
echo.

REM Service'i kur
echo Service kuruluyor...
sc create %SERVICE_NAME% binPath= "%PHP_PATH% %SCRIPT_PATH% start" DisplayName= "%SERVICE_DISPLAY_NAME%" start= auto

if %errorlevel% equ 0 (
    echo.
    echo Service basariyla kuruldu!
    echo.
    echo Service'i baslatmak icin: sc start %SERVICE_NAME%
    echo Service'i durdurmak icin: sc stop %SERVICE_NAME%
    echo Service'i kaldirmak icin: sc delete %SERVICE_NAME%
    echo.
    echo Service'i simdi baslatmak istiyor musunuz? (E/H)
    set /p start_now=
    if /i "%start_now%"=="E" (
        echo Service baslatiliyor...
        sc start %SERVICE_NAME%
    )
) else (
    echo.
    echo Service kurulumunda hata olustu!
    echo Yonetici olarak calistirdiginizden emin olun.
)

pause
