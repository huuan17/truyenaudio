@echo off
echo Starting Laravel Queue Worker for Crawl Processing...
echo.
echo This will process crawl jobs one by one with rate limiting.
echo Press Ctrl+C to stop the worker.
echo.

php artisan queue:work --queue=crawl --timeout=14400 --memory=1024 --tries=1 --sleep=30

pause
