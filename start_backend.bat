@echo off
title Troogood HRMS - Backend Server
echo =====================================================================
echo  Starting Troogood HRMS Backend on Local XAMPP PHP
echo  Port: 8000 (http://127.0.0.1:8000)
echo  Database: Troogood_HR_APP
echo =====================================================================

if exist "C:\xampp\php\php.exe" (
    echo [OK] Using XAMPP PHP: C:\xampp\php\php.exe
    "C:\xampp\php\php.exe" -S 127.0.0.1:8000 -t public public\index.php
) else (
    echo [INFO] C:\xampp\php\php.exe not found, trying system PATH php...
    php -S 127.0.0.1:8000 -t public public\index.php
)
pause
