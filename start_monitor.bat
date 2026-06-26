@echo off
title Uptime Monitor - Sürekli Çalışan
echo ========================================
echo    UPTIME MONITOR - SUREKLI CALISAN
echo ========================================
echo.
echo Monitor baslatiliyor...
echo Cikmak icin Ctrl+C basin
echo.

:loop
php monitor_daemon.php run
echo.
echo [%date% %time%] Monitoring tamamlandi, 10 saniye bekleniyor...
timeout /t 10 /nobreak > nul
goto loop
