@echo off
echo ========================================
echo    WINDOWS TASK SCHEDULER KURULUMU
echo ========================================
echo.

REM Task adı
set TASK_NAME=UptimeMonitor
set TASK_DESCRIPTION=Uptime monitoring service

REM PHP ve script yolları
set PHP_PATH=C:\wamp64\bin\php\php8.3.14\php.exe
set SCRIPT_PATH=C:\wamp64\www\uptime\monitor_daemon.php
set WORK_DIR=C:\wamp64\www\uptime

echo Task bilgileri:
echo - Task Adi: %TASK_NAME%
echo - PHP Yolu: %PHP_PATH%
echo - Script Yolu: %SCRIPT_PATH%
echo - Calisma Dizini: %WORK_DIR%
echo.

REM Mevcut task'ı sil (varsa)
schtasks /delete /tn "%TASK_NAME%" /f >nul 2>&1

echo Task olusturuluyor...
schtasks /create /tn "%TASK_NAME%" /tr "\"%PHP_PATH%\" \"%SCRIPT_PATH%\" run" /sc minute /mo 1 /ru "SYSTEM" /f

if %errorlevel% equ 0 (
    echo.
    echo Task basariyla olusturuldu!
    echo.
    echo Task'i baslatmak icin: schtasks /run /tn "%TASK_NAME%"
    echo Task'i durdurmak icin: schtasks /end /tn "%TASK_NAME%"
    echo Task'i silmek icin: schtasks /delete /tn "%TASK_NAME%"
    echo.
    echo Task'i simdi baslatmak istiyor musunuz? (E/H)
    set /p start_now=
    if /i "%start_now%"=="E" (
        echo Task baslatiliyor...
        schtasks /run /tn "%TASK_NAME%"
        echo.
        echo Task durumunu kontrol etmek icin: schtasks /query /tn "%TASK_NAME%"
    )
) else (
    echo.
    echo Task olusturulamadi!
    echo Yonetici olarak calistirdiginizden emin olun.
)

pause
