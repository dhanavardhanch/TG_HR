# Troogood HRMS - Start Backend
Write-Host "=====================================================================" -ForegroundColor Cyan
Write-Host " Starting Troogood HRMS Backend on Local XAMPP PHP" -ForegroundColor Green
Write-Host " Port: 8000 (http://127.0.0.1:8000)" -ForegroundColor Yellow
Write-Host " Database: Troogood_HR_APP" -ForegroundColor Cyan
Write-Host "=====================================================================" -ForegroundColor Cyan

$phpPath = "C:\xampp\php\php.exe"
$router = Join-Path $PSScriptRoot "public\index.php"

if (Test-Path $phpPath) {
    Write-Host "[OK] Using XAMPP PHP: $phpPath" -ForegroundColor Green
    & $phpPath -S 127.0.0.1:8000 -t public $router
} else {
    Write-Host "[INFO] Using PATH php" -ForegroundColor Yellow
    php -S 127.0.0.1:8000 -t public $router
}
