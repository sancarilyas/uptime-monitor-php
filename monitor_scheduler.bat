@echo off
:loop
php C:\wamp64\www\uptime\monitor.php
timeout /t 10 /nobreak > nul
goto loop
