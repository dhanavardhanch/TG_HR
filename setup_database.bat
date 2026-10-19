@echo off
title Troogood HRMS - Database Setup
echo =====================================================================
echo  Setting up Troogood_HR_APP Database in XAMPP MySQL
echo =====================================================================

powershell -ExecutionPolicy Bypass -File "%~dp0setup_database.ps1"
pause
