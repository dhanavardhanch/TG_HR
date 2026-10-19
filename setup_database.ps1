# Troogood HRMS Database Setup
Write-Host "=====================================================================" -ForegroundColor Cyan
Write-Host " Setting up Troogood_HR_APP Database on Local XAMPP MySQL" -ForegroundColor Green
Write-Host "=====================================================================" -ForegroundColor Cyan

$mysqlExe = "C:\xampp\mysql\bin\mysql.exe"
$schemaSql = Join-Path $PSScriptRoot "database\Troogood_HR_APP_schema.sql"

if (-not (Test-Path $mysqlExe)) {
    $cmd = Get-Command "mysql" -ErrorAction SilentlyContinue
    if ($cmd) {
        $mysqlExe = $cmd.Source
    } else {
        Write-Host "[ERROR] MySQL not found at $mysqlExe or in PATH!" -ForegroundColor Red
        Write-Host "Please start MySQL in XAMPP Control Panel." -ForegroundColor Yellow
        Exit 1
    }
}

Write-Host "[EXECUTING] Importing schema from: $schemaSql" -ForegroundColor Cyan
& $mysqlExe -u root -e "source $schemaSql"

if ($LASTEXITCODE -eq 0) {
    Write-Host "`n[SUCCESS] Database 'Troogood_HR_APP' created and populated successfully!" -ForegroundColor Green
    Write-Host "Default company, branches, shifts, and admin user initialized." -ForegroundColor Yellow
} else {
    Write-Host "`n[WARNING] MySQL command returned code $LASTEXITCODE. Make sure MySQL service is running in XAMPP." -ForegroundColor Red
}
