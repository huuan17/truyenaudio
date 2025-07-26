# PowerShell Script to Update PHP Configuration for Large File Uploads
# Run as Administrator

param(
    [string]$PhpIniPath = "C:\xampp\php\php.ini",
    [switch]$Backup = $true,
    [switch]$RestartApache = $true
)

Write-Host "=== PHP Upload Configuration Updater for Windows ===" -ForegroundColor Cyan
Write-Host ""

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")

if (-not $isAdmin) {
    Write-Host "ERROR: This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "Right-click PowerShell and select 'Run as Administrator'" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host "✓ Running as Administrator" -ForegroundColor Green

# Check if php.ini exists
if (-not (Test-Path $PhpIniPath)) {
    Write-Host "ERROR: php.ini not found at: $PhpIniPath" -ForegroundColor Red
    Write-Host "Please specify the correct path using -PhpIniPath parameter" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host "✓ Found php.ini at: $PhpIniPath" -ForegroundColor Green

# Create backup if requested
if ($Backup) {
    $backupPath = "$PhpIniPath.backup.$(Get-Date -Format 'yyyy-MM-dd-HH-mm-ss')"
    try {
        Copy-Item $PhpIniPath $backupPath
        Write-Host "✓ Backup created: $backupPath" -ForegroundColor Green
    }
    catch {
        Write-Host "ERROR: Could not create backup: $($_.Exception.Message)" -ForegroundColor Red
        Read-Host "Press Enter to exit"
        exit 1
    }
}

# Define new settings for audio library
$settings = @{
    'upload_max_filesize' = '500M'
    'post_max_size' = '1G'
    'max_execution_time' = '600'
    'max_input_time' = '300'
    'memory_limit' = '1G'
    'max_file_uploads' = '50'
    'output_buffering' = 'Off'
    'zlib.output_compression' = 'Off'
}

Write-Host ""
Write-Host "Updating PHP settings..." -ForegroundColor Yellow

try {
    # Read current php.ini content
    $content = Get-Content $PhpIniPath -Raw
    
    foreach ($setting in $settings.GetEnumerator()) {
        $name = $setting.Key
        $value = $setting.Value
        
        Write-Host "  Setting $name = $value" -ForegroundColor White
        
        # Pattern to match the setting (commented or uncommented)
        $pattern = "(?m)^(\s*;?\s*)($([regex]::Escape($name))\s*=\s*)(.*?)$"
        
        if ($content -match $pattern) {
            # Setting exists, update it
            $replacement = "`$1$name = $value"
            $content = $content -replace $pattern, $replacement
        }
        else {
            # Setting doesn't exist, add it
            $content += "`n; Added by Audio Library Upload Config`n"
            $content += "$name = $value`n"
        }
    }
    
    # Write updated content back to php.ini
    Set-Content -Path $PhpIniPath -Value $content -Encoding UTF8
    Write-Host "✓ PHP configuration updated successfully!" -ForegroundColor Green
    
} catch {
    Write-Host "ERROR: Could not update php.ini: $($_.Exception.Message)" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

# Restart Apache if requested
if ($RestartApache) {
    Write-Host ""
    Write-Host "Restarting Apache..." -ForegroundColor Yellow
    
    # Try to restart Apache service
    try {
        $apacheService = Get-Service -Name "Apache*" -ErrorAction SilentlyContinue
        if ($apacheService) {
            Restart-Service $apacheService.Name
            Write-Host "✓ Apache service restarted" -ForegroundColor Green
        }
        else {
            Write-Host "⚠ Apache service not found. Please restart manually from XAMPP Control Panel" -ForegroundColor Yellow
        }
    }
    catch {
        Write-Host "⚠ Could not restart Apache service automatically" -ForegroundColor Yellow
        Write-Host "Please restart Apache manually from XAMPP Control Panel" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "=== Configuration Complete ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor White
Write-Host "1. Restart your web server if not done automatically" -ForegroundColor Gray
Write-Host "2. Visit your admin panel to verify the changes" -ForegroundColor Gray
Write-Host "3. Test uploading large files" -ForegroundColor Gray
Write-Host ""

# Show current settings
Write-Host "Updated settings:" -ForegroundColor White
foreach ($setting in $settings.GetEnumerator()) {
    Write-Host "  $($setting.Key) = $($setting.Value)" -ForegroundColor Gray
}

Write-Host ""
Write-Host "✓ PHP is now configured for large file uploads!" -ForegroundColor Green
Read-Host "Press Enter to exit"
