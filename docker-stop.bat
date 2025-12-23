@echo off
:: Stop and remove all containers
echo Stopping Docker containers...
docker-compose down

echo.
echo Containers stopped!
pause
