@echo off
echo ========================================
echo   LAUNCHING VSCODE OPTIMIZED FOR AUGMENT
echo ========================================

REM Kill existing VSCode processes
echo Closing existing VSCode instances...
taskkill /F /IM "Code.exe" 2>nul

REM Wait a moment
timeout /t 2 /nobreak >nul

REM Set environment variables for performance
echo Setting performance environment...
set NODE_OPTIONS=--max-old-space-size=8192
set ELECTRON_NO_ATTACH_CONSOLE=1
set VSCODE_DISABLE_CRASH_REPORTER=1

REM Clear temp files
echo Cleaning temporary files...
del /q "%TEMP%\vscode-*" 2>nul
del /q "%TEMP%\augment-*" 2>nul

REM Set high priority for VSCode
echo Setting high priority mode...
powershell -Command "Get-Process -Name 'Code' -ErrorAction SilentlyContinue | ForEach-Object { $_.PriorityClass = 'High' }" 2>nul

REM Launch VSCode with optimized flags
echo Launching VSCode with optimization flags...
start "" "C:\Users\%USERNAME%\AppData\Local\Programs\Microsoft VS Code\Code.exe" ^
  --max-memory=8192 ^
  --disable-gpu-sandbox ^
  --disable-software-rasterizer ^
  --enable-features=VaapiVideoDecoder ^
  --disable-background-timer-throttling ^
  --disable-renderer-backgrounding ^
  --disable-backgrounding-occluded-windows ^
  --disable-ipc-flooding-protection ^
  "%CD%"

echo VSCode launched with optimizations!
echo ========================================
pause
