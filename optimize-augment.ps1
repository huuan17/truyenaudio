# Augment VSCode Optimization Script
# Run as Administrator

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   OPTIMIZING SYSTEM FOR AUGMENT" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# 1. Stop VSCode processes
Write-Host "1. Stopping VSCode processes..." -ForegroundColor Yellow
Get-Process -Name "Code" -ErrorAction SilentlyContinue | Stop-Process -Force
Start-Sleep -Seconds 2

# 2. Clear VSCode cache
Write-Host "2. Clearing VSCode cache..." -ForegroundColor Yellow
$paths = @(
    "$env:APPDATA\Code\User\workspaceStorage",
    "$env:APPDATA\Code\logs",
    "$env:APPDATA\Code\CachedExtensions",
    "$env:TEMP\vscode-*"
)

foreach ($path in $paths) {
    if (Test-Path $path) {
        Remove-Item -Path $path -Recurse -Force -ErrorAction SilentlyContinue
        Write-Host "   Cleared: $path" -ForegroundColor Green
    }
}

# 3. Set high performance power plan
Write-Host "3. Setting high performance mode..." -ForegroundColor Yellow
powercfg /setactive 8c5e7fda-e8bf-4a96-9a85-a6e23a8c635c

# 4. Optimize network settings
Write-Host "4. Optimizing network..." -ForegroundColor Yellow
netsh int tcp set global autotuninglevel=normal
netsh int tcp set global chimney=enabled
netsh int tcp set global rss=enabled
ipconfig /flushdns

# 5. Set process priority for VSCode
Write-Host "5. Setting process priorities..." -ForegroundColor Yellow
$script = {
    while ($true) {
        $processes = Get-Process -Name "Code" -ErrorAction SilentlyContinue
        foreach ($proc in $processes) {
            if ($proc.PriorityClass -ne "High") {
                $proc.PriorityClass = "High"
            }
        }
        Start-Sleep -Seconds 5
    }
}

Start-Job -ScriptBlock $script | Out-Null

# 6. Windows Defender exclusions
Write-Host "6. Adding Windows Defender exclusions..." -ForegroundColor Yellow
$exclusions = @(
    "$env:LOCALAPPDATA\Programs\Microsoft VS Code",
    "$env:APPDATA\Code",
    "$env:TEMP"
)

foreach ($exclusion in $exclusions) {
    Add-MpPreference -ExclusionPath $exclusion -ErrorAction SilentlyContinue
    Write-Host "   Added exclusion: $exclusion" -ForegroundColor Green
}

# 7. Registry tweaks
Write-Host "7. Applying registry tweaks..." -ForegroundColor Yellow
try {
    # Disable Windows Search indexing for temp folders
    Set-ItemProperty -Path "HKLM:\SOFTWARE\Microsoft\Windows Search\Gather\Windows\SystemIndex" -Name "EnableIndexingEncryptedStoresOrItems" -Value 0 -ErrorAction SilentlyContinue
    
    # Optimize visual effects
    Set-ItemProperty -Path "HKCU:\Control Panel\Desktop" -Name "MenuShowDelay" -Value "0" -ErrorAction SilentlyContinue
    Set-ItemProperty -Path "HKCU:\Control Panel\Desktop" -Name "WaitToKillAppTimeout" -Value "2000" -ErrorAction SilentlyContinue
    
    Write-Host "   Registry tweaks applied" -ForegroundColor Green
} catch {
    Write-Host "   Registry tweaks failed (run as admin)" -ForegroundColor Red
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   OPTIMIZATION COMPLETE!" -ForegroundColor Green
Write-Host "   Now launching VSCode..." -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# 8. Launch VSCode with optimizations
$env:NODE_OPTIONS = "--max-old-space-size=8192"
$env:ELECTRON_NO_ATTACH_CONSOLE = "1"

$vscodePath = "$env:LOCALAPPDATA\Programs\Microsoft VS Code\Code.exe"
if (Test-Path $vscodePath) {
    Start-Process -FilePath $vscodePath -ArgumentList "--max-memory=8192", "--disable-gpu-sandbox", (Get-Location).Path
    Write-Host "VSCode launched with optimizations!" -ForegroundColor Green
} else {
    Write-Host "VSCode not found at expected location" -ForegroundColor Red
}

Write-Host "Press any key to continue..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
