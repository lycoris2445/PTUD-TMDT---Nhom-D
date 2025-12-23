#!/bin/bash
# ============================================
# PTUD TMDT - Docker Quick Start Script (Mac/Linux)
# ============================================

echo ""
echo "╔═══════════════════════════════════════╗"
echo "║   PTUD TMDT - Docker Environment      ║"
echo "╚═══════════════════════════════════════╝"
echo ""

# Check Docker is running
if ! docker version > /dev/null 2>&1; then
    echo "[ERROR] Docker is not running! Please start Docker first."
    exit 1
fi

# Check if .env exists, if not copy from .env.docker
if [ ! -f ".env" ]; then
    echo "[INFO] Creating .env from .env.docker..."
    cp .env.docker .env
fi

echo "[1/3] Building containers (first time may take 2-3 minutes)..."
docker-compose build --parallel

echo ""
echo "[2/3] Starting services..."
docker-compose up -d

echo ""
echo "[3/3] Waiting for MySQL to be ready..."
sleep 15

echo ""
echo "╔═══════════════════════════════════════╗"
echo "║         SETUP COMPLETE!               ║"
echo "╠═══════════════════════════════════════╣"
echo "║  Website:    http://localhost         ║"
echo "║  Admin:      http://localhost/admin   ║"
echo "║  phpMyAdmin: http://localhost:8080    ║"
echo "╠═══════════════════════════════════════╣"
echo "║  DB Host: mysql    DB: ptud_tmdt      ║"
echo "║  DB User: root     Pass: root123      ║"
echo "╚═══════════════════════════════════════╝"
echo ""

# Open browser (Mac)
if [[ "$OSTYPE" == "darwin"* ]]; then
    open http://localhost
fi
