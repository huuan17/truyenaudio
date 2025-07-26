<?php

/**
 * Script to update PHP configuration for large file uploads
 * 
 * This script will backup and update php.ini with optimal settings
 */

require_once __DIR__ . '/../config/php-upload-config.php';

class PHPConfigUpdater
{
    private $phpIniPath;
    private $backupPath;

    public function __construct()
    {
        $this->phpIniPath = php_ini_loaded_file();
        $this->backupPath = $this->phpIniPath . '.backup.' . date('Y-m-d-H-i-s');
    }

    /**
     * Update PHP configuration
     */
    public function updateConfig($useCase = 'audio_library')
    {
        echo "=== PHP Configuration Updater ===\n\n";
        
        if (!$this->phpIniPath) {
            echo "ERROR: Could not find php.ini file\n";
            return false;
        }

        echo "PHP ini file: {$this->phpIniPath}\n";
        echo "Backup will be created at: {$this->backupPath}\n\n";

        // Check if file is writable
        if (!is_writable($this->phpIniPath)) {
            echo "ERROR: php.ini file is not writable. Please run as administrator.\n";
            echo "On Windows: Run Command Prompt as Administrator\n";
            echo "On Linux/Mac: Use sudo\n";
            return false;
        }

        // Create backup
        if (!$this->createBackup()) {
            echo "ERROR: Could not create backup\n";
            return false;
        }

        // Update configuration
        if (!$this->updateIniFile($useCase)) {
            echo "ERROR: Could not update configuration\n";
            return false;
        }

        echo "SUCCESS: PHP configuration updated!\n";
        echo "Please restart your web server (Apache/Nginx) for changes to take effect.\n\n";
        
        echo "XAMPP Users: Restart Apache from XAMPP Control Panel\n";
        echo "Or restart XAMPP completely\n\n";

        return true;
    }

    /**
     * Create backup of php.ini
     */
    private function createBackup()
    {
        echo "Creating backup...\n";
        
        if (!copy($this->phpIniPath, $this->backupPath)) {
            return false;
        }

        echo "Backup created: {$this->backupPath}\n";
        return true;
    }

    /**
     * Update php.ini file
     */
    private function updateIniFile($useCase)
    {
        echo "Updating php.ini...\n";

        $content = file_get_contents($this->phpIniPath);
        if ($content === false) {
            return false;
        }

        $settings = PHPUploadConfig::getOptimalSettings($useCase);
        
        foreach ($settings as $setting => $value) {
            echo "  Setting {$setting} = {$value}\n";
            $content = $this->updateSetting($content, $setting, $value);
        }

        // Add additional settings
        $additionalSettings = [
            'output_buffering' => 'Off',
            'zlib.output_compression' => 'Off'
        ];

        foreach ($additionalSettings as $setting => $value) {
            echo "  Setting {$setting} = {$value}\n";
            $content = $this->updateSetting($content, $setting, $value);
        }

        return file_put_contents($this->phpIniPath, $content) !== false;
    }

    /**
     * Update a specific setting in php.ini content
     */
    private function updateSetting($content, $setting, $value)
    {
        // Pattern to match the setting (commented or uncommented)
        $pattern = '/^(\s*;?\s*)(' . preg_quote($setting, '/') . '\s*=\s*)(.*?)$/m';
        
        if (preg_match($pattern, $content)) {
            // Setting exists, update it
            $replacement = '$1' . $setting . ' = ' . $value;
            $content = preg_replace($pattern, $replacement, $content);
        } else {
            // Setting doesn't exist, add it
            $content .= "\n; Added by Audio Library Upload Config\n";
            $content .= $setting . ' = ' . $value . "\n";
        }

        return $content;
    }

    /**
     * Restore from backup
     */
    public function restoreBackup($backupFile = null)
    {
        if (!$backupFile) {
            // Find latest backup
            $backupPattern = $this->phpIniPath . '.backup.*';
            $backups = glob($backupPattern);
            
            if (empty($backups)) {
                echo "No backup files found\n";
                return false;
            }

            $backupFile = end($backups); // Get latest backup
        }

        echo "Restoring from backup: {$backupFile}\n";

        if (!file_exists($backupFile)) {
            echo "ERROR: Backup file not found\n";
            return false;
        }

        if (!copy($backupFile, $this->phpIniPath)) {
            echo "ERROR: Could not restore backup\n";
            return false;
        }

        echo "SUCCESS: Configuration restored from backup\n";
        echo "Please restart your web server\n";
        return true;
    }

    /**
     * Show current vs recommended settings
     */
    public function showComparison()
    {
        $check = PHPUploadConfig::checkLargeUploadSupport();
        
        echo "=== Current vs Recommended Settings ===\n\n";
        
        $recommended = PHPUploadConfig::getOptimalSettings('audio_library');
        
        foreach ($recommended as $setting => $recValue) {
            $current = $check['current'][$setting] ?? 'Not set';
            $status = $current === $recValue ? '✓' : '✗';
            
            echo sprintf("%-20s | %-10s | %-10s | %s\n", 
                $setting, $current, $recValue, $status);
        }
        
        echo "\n";
        if (!$check['supported']) {
            echo "❌ Current configuration does NOT support large uploads\n";
        } else {
            echo "✅ Current configuration supports large uploads\n";
        }
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $updater = new PHPConfigUpdater();
    
    $command = $argv[1] ?? 'check';
    
    switch ($command) {
        case 'update':
            $useCase = $argv[2] ?? 'audio_library';
            $updater->updateConfig($useCase);
            break;
            
        case 'restore':
            $backupFile = $argv[2] ?? null;
            $updater->restoreBackup($backupFile);
            break;
            
        case 'compare':
            $updater->showComparison();
            break;
            
        case 'check':
        default:
            echo "=== PHP Upload Configuration Tool ===\n\n";
            echo "Usage:\n";
            echo "  php scripts/update-php-config.php check     - Check current settings\n";
            echo "  php scripts/update-php-config.php compare  - Compare current vs recommended\n";
            echo "  php scripts/update-php-config.php update   - Update php.ini (requires admin)\n";
            echo "  php scripts/update-php-config.php restore  - Restore from backup\n\n";
            
            $updater->showComparison();
            break;
    }
}
