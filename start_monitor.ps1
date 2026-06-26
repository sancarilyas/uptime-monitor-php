# Uptime Monitor PowerShell Script
# Sürekli çalışan monitoring sistemi

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "    UPTIME MONITOR - POWERSHELL" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# PHP yolu
$phpPath = "C:\wamp64\bin\php\php8.3.14\php.exe"
$scriptPath = "C:\wamp64\www\uptime\monitor_daemon.php"

# Log dosyası
$logFile = "C:\wamp64\www\uptime\logs\monitor_powershell.log"

# Log klasörünü oluştur
$logDir = Split-Path $logFile
if (!(Test-Path $logDir)) {
    New-Item -ItemType Directory -Path $logDir -Force
}

function Write-Log {
    param($Message)
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] $Message"
    Write-Host $logMessage -ForegroundColor Green
    Add-Content -Path $logFile -Value $logMessage
}

function Start-Monitoring {
    Write-Log "Monitoring başlatılıyor..."
    
    try {
        # PHP script'ini çalıştır
        $result = & $phpPath $scriptPath run 2>&1
        
        if ($LASTEXITCODE -eq 0) {
            Write-Log "Monitoring başarıyla tamamlandı"
        } else {
            Write-Log "Monitoring hatası: $result"
        }
    } catch {
        Write-Log "Hata: $($_.Exception.Message)"
    }
}

# Ana döngü
Write-Host "Monitor başlatıldı. Çıkmak için Ctrl+C basın" -ForegroundColor Yellow
Write-Host "Log dosyası: $logFile" -ForegroundColor Gray
Write-Host ""

$counter = 0
while ($true) {
    $counter++
    Write-Host "=== Monitoring Cycle #$counter ===" -ForegroundColor Magenta
    
    Start-Monitoring
    
    Write-Host "10 saniye bekleniyor..." -ForegroundColor Gray
    Start-Sleep -Seconds 10
}
