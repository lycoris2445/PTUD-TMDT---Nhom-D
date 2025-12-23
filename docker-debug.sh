#!/bin/bash
# Debug script for Docker MySQL initialization

echo "=== Docker MySQL Debug ==="
echo ""

echo "Step 1: Checking MySQL container..."
docker compose ps mysql

echo ""
echo "Step 2: Checking MySQL logs for errors..."
docker compose logs mysql | tail -50

echo ""
echo "Step 3: Listing SQL init files..."
docker compose exec mysql ls -la /docker-entrypoint-initdb.d/

echo ""
echo "Step 4: Testing MySQL connection..."
docker compose exec mysql mysql -uroot -proot123 -e "SHOW DATABASES;"

echo ""
echo "Step 5: Checking if darling_cosmetics exists..."
docker compose exec mysql mysql -uroot -proot123 -e "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='darling_cosmetics';"

echo ""
echo "Step 6: If database exists, show tables..."
docker compose exec mysql mysql -uroot -proot123 darling_cosmetics -e "SHOW TABLES;" 2>/dev/null || echo "Database not found!"

echo ""
echo "=== Recommended Fix ==="
echo "If database doesn't exist, run:"
echo "  docker compose down -v"
echo "  docker compose up -d"
echo "  # Wait 60 seconds for init"
echo "  docker compose exec mysql mysql -uroot -proot123 -e 'SHOW DATABASES;'"
