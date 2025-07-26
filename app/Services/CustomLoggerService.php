<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CustomLoggerService
{
    protected $logPath = 'logs';
    
    /**
     * Log error with daily rotation
     */
    public function logError($context, $message, $data = [], $exception = null)
    {
        $this->writeLog('error', $context, $message, $data, $exception);
    }
    
    /**
     * Log info with daily rotation
     */
    public function logInfo($context, $message, $data = [])
    {
        $this->writeLog('info', $context, $message, $data);
    }
    
    /**
     * Log warning with daily rotation
     */
    public function logWarning($context, $message, $data = [])
    {
        $this->writeLog('warning', $context, $message, $data);
    }
    
    /**
     * Log debug with daily rotation
     */
    public function logDebug($context, $message, $data = [])
    {
        $this->writeLog('debug', $context, $message, $data);
    }
    
    /**
     * Write log entry
     */
    protected function writeLog($level, $context, $message, $data = [], $exception = null)
    {
        $timestamp = Carbon::now();
        $date = $timestamp->format('Y-m-d');
        $time = $timestamp->format('H:i:s');
        
        // Create log filename with date
        $filename = "{$this->logPath}/{$context}-{$date}.log";
        
        // Prepare log entry
        $logEntry = [
            'timestamp' => $timestamp->toISOString(),
            'level' => strtoupper($level),
            'context' => $context,
            'message' => $message,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ];
        
        // Add data if provided
        if (!empty($data)) {
            $logEntry['data'] = $data;
        }
        
        // Add exception details if provided
        if ($exception) {
            $logEntry['exception'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'code' => $exception->getCode(),
            ];
        }
        
        // Format log line
        $logLine = sprintf(
            "[%s] %s.%s: %s %s\n",
            $time,
            $level,
            $context,
            $message,
            !empty($data) || $exception ? json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : ''
        );
        
        // Write to file
        Storage::disk('local')->append($filename, $logLine);
        
        // Also log to Laravel log for critical errors
        if ($level === 'error' && $exception) {
            \Log::error("[$context] $message", [
                'data' => $data,
                'exception' => $exception
            ]);
        }
    }
    
    /**
     * Get log files for a specific context
     */
    public function getLogFiles($context, $days = 7)
    {
        $files = [];
        
        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $filename = "{$this->logPath}/{$context}-{$date}.log";
            
            if (Storage::disk('local')->exists($filename)) {
                $files[] = [
                    'date' => $date,
                    'filename' => $filename,
                    'size' => Storage::disk('local')->size($filename),
                    'content' => Storage::disk('local')->get($filename)
                ];
            }
        }
        
        return $files;
    }
    
    /**
     * Clean old log files
     */
    public function cleanOldLogs($context, $keepDays = 30)
    {
        $cutoffDate = Carbon::now()->subDays($keepDays);
        $allFiles = Storage::disk('local')->files($this->logPath);
        
        foreach ($allFiles as $file) {
            if (strpos($file, "{$context}-") !== false) {
                // Extract date from filename
                preg_match('/(\d{4}-\d{2}-\d{2})\.log$/', $file, $matches);
                
                if (isset($matches[1])) {
                    $fileDate = Carbon::createFromFormat('Y-m-d', $matches[1]);
                    
                    if ($fileDate->lt($cutoffDate)) {
                        Storage::disk('local')->delete($file);
                    }
                }
            }
        }
    }
    
    /**
     * Get recent errors for dashboard
     */
    public function getRecentErrors($context, $hours = 24)
    {
        $errors = [];
        $startDate = Carbon::now()->subHours($hours);
        
        // Check today and yesterday logs
        for ($i = 0; $i <= 1; $i++) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $filename = "{$this->logPath}/{$context}-{$date}.log";
            
            if (Storage::disk('local')->exists($filename)) {
                $content = Storage::disk('local')->get($filename);
                $lines = explode("\n", $content);
                
                foreach ($lines as $line) {
                    if (strpos($line, 'ERROR.') !== false) {
                        // Parse timestamp from log line
                        preg_match('/\[(\d{2}:\d{2}:\d{2})\]/', $line, $matches);
                        
                        if (isset($matches[1])) {
                            $logTime = Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . $matches[1]);
                            
                            if ($logTime->gte($startDate)) {
                                $errors[] = [
                                    'timestamp' => $logTime,
                                    'message' => $line
                                ];
                            }
                        }
                    }
                }
            }
        }
        
        return collect($errors)->sortByDesc('timestamp')->take(50)->values()->all();
    }
}
