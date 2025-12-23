# Debug script for Docker MySQL initialization (Windows)

Write-Host "=== Docker MySQL Debug ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "Step 1: Checking MySQL container..." -ForegroundColor Yellow
docker compose ps mysql

Write-Host "`nStep 2: Checking MySQL logs for errors..." -ForegroundColor Yellow
docker compose logs mysql --tail=50

Write-Host "`nStep 3: Listing SQL init files..." -ForegroundColor Yellow
docker compose exec mysql ls -la /docker-entrypoint-initdb.d/

Write-Host "`nStep 4: Testing MySQL connection..." -ForegroundColor Yellow
docker compose exec mysql mysql -uroot -proot123 -e "SHOW DATABASES;"

Write-Host "`nStep 5: Checking if darling_cosmetics exists..." -ForegroundColor Yellow
$dbCheck = docker compose exec mysql mysql -uroot -proot123 -e "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='darling_cosmetics';" 2>&1

if ($LASTEXITCODE -eq 0 -and $dbCheck -match "darling_cosmetics") {
    Write-Host "✓ Database 'darling_cosmetics' EXISTS!" -ForegroundColor Green
    
    Write-Host "`nStep 6: Showing tables..." -ForegroundColor Yellow
    docker compose exec mysql mysql -uroot -proot123 darling_cosmetics -e "SHOW TABLES;"
} else {
    Write-Host "✗ Database 'darling_cosmetics' NOT FOUND!" -ForegroundColor Red
    Write-Host "`nThis means SQL init scripts didn't run." -ForegroundColor Yellow
}

Write-Host "`n=== Recommended Fix ===" -ForegroundColor Cyan
Write-Host "If database doesn't exist, run these commands:" -ForegroundColor Yellow
Write-Host "  docker compose down -v" -ForegroundColor White
Write-Host "  docker compose up -d" -ForegroundColor White
Write-Host "  # Wait 60 seconds" -ForegroundColor Gray
Write-Host "  docker compose exec mysql mysql -uroot -proot123 -e 'SHOW DATABASES;'" -ForegroundColor White
Write-Host ""
