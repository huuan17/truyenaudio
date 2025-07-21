@echo off
echo ========================================
echo    Laravel Queue Worker Starter
echo ========================================
echo.
echo This script will start the Laravel queue worker
echo to process background jobs including:
echo - Auto crawl jobs
echo - TTS conversion jobs
echo - Video generation jobs
echo.
echo Press Ctrl+C to stop the worker
echo.

:menu
echo Choose queue to start:
echo 1. All queues (default, crawl, tts, video)
echo 2. Crawl queue only
echo 3. Default queue only (TTS, Video)
echo 4. Video queue only
echo 5. Exit
echo.
set /p choice="Enter your choice (1-5): "

if "%choice%"=="1" goto all_queues
if "%choice%"=="2" goto crawl_queue
if "%choice%"=="3" goto default_queue
if "%choice%"=="4" goto video_queue
if "%choice%"=="5" goto exit
if "%choice%"=="" goto all_queues

echo Invalid choice. Please try again.
echo.
goto menu

:all_queues
echo.
echo Starting worker for ALL queues...
echo.
php artisan queue:work --timeout=3600 --memory=512 --tries=3 --sleep=3
goto end

:crawl_queue
echo.
echo Starting worker for CRAWL queue only...
echo.
php artisan queue:work --queue=crawl --timeout=14400 --memory=1024 --tries=1 --sleep=30
goto end

:default_queue
echo.
echo Starting worker for DEFAULT queue (TTS, Video)...
echo.
php artisan queue:work --queue=default --timeout=1800 --memory=512 --tries=3 --sleep=5
goto end

:video_queue
echo.
echo Starting worker for VIDEO queue only...
echo.
php artisan queue:work --queue=video --timeout=1800 --memory=512 --tries=3
goto end

:exit
echo Goodbye!
goto end

:end
echo.
echo Queue worker stopped.
pause
