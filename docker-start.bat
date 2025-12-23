@echo off
:: ============================================
:: PTUD TMDT - Docker Quick Start Script
:: ============================================

echo.
echo  ╔═══════════════════════════════════════╗
echo  ║   PTUD TMDT - Docker Environment      ║
echo  ╚═══════════════════════════════════════╝
echo.

:: Check Docker is running
docker version >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Docker is not running! Please start Docker Desktop first.
    pause
    exit /b 1
)

:: Check if .env exists, if not copy from .env.docker
if not exist ".env" (
    echo [INFO] Creating .env from .env.docker...
    copy .env.docker .env
)

echo [1/3] Building containers (first time may take 2-3 minutes)...
docker-compose build --parallel

echo.
echo [2/3] Starting services...
docker-compose up -d

echo.
echo [3/3] Waiting for MySQL to be ready...
timeout /t 15 /nobreak >nul

echo.
echo  ╔═══════════════════════════════════════╗
echo  ║         SETUP COMPLETE!               ║
echo  ╠═══════════════════════════════════════╣
echo  ║  Website:    http://localhost         ║
echo  ║  Admin:      http://localhost/admin   ║
echo  ║  phpMyAdmin: http://localhost:8080    ║
echo  ╠═══════════════════════════════════════╣
echo  ║  DB Host: mysql    DB: ptud_tmdt      ║
echo  ║  DB User: root     Pass: root123      ║
echo  ╚═══════════════════════════════════════╝
echo.

:: Open browser
start http://localhost

pause
