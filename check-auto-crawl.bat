@echo off
echo ========================================
echo    Auto Crawl System Checker
echo ========================================
echo.

echo 1. Checking queue configuration...
php artisan config:show queue.default
echo.

echo 2. Checking pending crawl jobs...
php artisan queue:work --once --stop-when-empty --queue=crawl
echo.

echo 3. Checking stories with auto_crawl enabled...
php artisan tinker --execute="
echo 'Stories with auto_crawl enabled:';
\App\Models\Story::where('auto_crawl', true)->get(['id', 'title', 'crawl_status', 'auto_crawl'])->each(function(\$story) {
    echo \"ID: {\$story->id} | Title: {\$story->title} | Status: {\$story->crawl_status} | Auto: {\$story->auto_crawl}\";
});
"
echo.

echo 4. Running auto crawl check (dry run)...
php artisan auto:crawl-stories --dry-run
echo.

echo 5. Checking failed jobs...
php artisan queue:failed
echo.

echo ========================================
echo    Manual Actions Available:
echo ========================================
echo.
echo A. Start queue worker for crawl
echo B. Run auto crawl now
echo C. Clear failed jobs
echo D. Exit
echo.

:menu
set /p choice="Choose an action (A-D): "

if /i "%choice%"=="A" goto start_worker
if /i "%choice%"=="B" goto run_auto_crawl
if /i "%choice%"=="C" goto clear_failed
if /i "%choice%"=="D" goto exit

echo Invalid choice. Please try again.
goto menu

:start_worker
echo.
echo Starting crawl queue worker...
echo Press Ctrl+C to stop
echo.
php artisan queue:work --queue=crawl --timeout=14400 --memory=1024 --tries=1 --sleep=30
goto end

:run_auto_crawl
echo.
echo Running auto crawl now...
php artisan auto:crawl-stories
echo.
echo Auto crawl completed. Check the output above.
pause
goto menu

:clear_failed
echo.
echo Clearing failed jobs...
php artisan queue:flush
echo Failed jobs cleared.
pause
goto menu

:exit
echo Goodbye!

:end
pause
