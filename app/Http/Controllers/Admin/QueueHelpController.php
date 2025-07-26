<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class QueueHelpController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show queue workers guide
     */
    public function index()
    {
        return view('admin.help.queue-workers');
    }

    /**
     * Get queue status via AJAX
     */
    public function getQueueStatus()
    {
        try {
            // Check if jobs table exists
            $tablesExist = $this->checkQueueTables();
            
            if (!$tablesExist) {
                return response()->json([
                    'all_queue' => 'Tables not found',
                    'crawl_queue' => 'Tables not found',
                    'video_queue' => 'Tables not found',
                    'tts_queue' => 'Tables not found',
                    'error' => 'Queue tables not found. Please run migrations.'
                ]);
            }

            // Get queue statistics
            $stats = $this->getQueueStats();
            
            return response()->json([
                'all_queue' => $this->getQueueStatusText($stats['total']),
                'crawl_queue' => $this->getQueueStatusText($stats['crawl']),
                'video_queue' => $this->getQueueStatusText($stats['video']),
                'tts_queue' => $this->getQueueStatusText($stats['tts']),
                'stats' => $stats
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'all_queue' => 'Error checking',
                'crawl_queue' => 'Error checking',
                'video_queue' => 'Error checking',
                'tts_queue' => 'Error checking',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if queue tables exist
     */
    private function checkQueueTables()
    {
        try {
            return DB::getSchemaBuilder()->hasTable('jobs') && 
                   DB::getSchemaBuilder()->hasTable('failed_jobs');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get queue statistics
     */
    private function getQueueStats()
    {
        $stats = [
            'total' => 0,
            'crawl' => 0,
            'video' => 0,
            'tts' => 0,
            'default' => 0,
            'failed' => 0
        ];

        try {
            // Count jobs by queue
            $stats['total'] = DB::table('jobs')->count();
            $stats['crawl'] = DB::table('jobs')->where('queue', 'crawl')->count();
            $stats['video'] = DB::table('jobs')->where('queue', 'video')->count();
            $stats['tts'] = DB::table('jobs')->where('queue', 'tts')->count();
            $stats['default'] = DB::table('jobs')->where('queue', 'default')->count();
            $stats['failed'] = DB::table('failed_jobs')->count();
            
        } catch (\Exception $e) {
            // If tables don't exist or error, return zeros
        }

        return $stats;
    }

    /**
     * Get status text based on job count
     */
    private function getQueueStatusText($jobCount)
    {
        if ($jobCount > 0) {
            return $jobCount . ' jobs pending';
        } else {
            return 'No pending jobs';
        }
    }

    /**
     * Execute queue commands via AJAX
     */
    public function executeCommand(Request $request)
    {
        $command = $request->input('command');
        
        try {
            switch ($command) {
                case 'restart':
                    Artisan::call('queue:restart');
                    $output = 'Queue workers restarted successfully';
                    break;
                    
                case 'flush':
                    Artisan::call('queue:flush');
                    $output = 'All jobs cleared from queue';
                    break;
                    
                case 'retry':
                    Artisan::call('queue:retry', ['id' => 'all']);
                    $output = 'All failed jobs retried';
                    break;
                    
                case 'forget-failed':
                    Artisan::call('queue:forget-failed');
                    $output = 'All failed jobs forgotten';
                    break;
                    
                case 'monitor':
                    Artisan::call('queue:monitor');
                    $output = Artisan::output();
                    break;
                    
                case 'failed':
                    Artisan::call('queue:failed');
                    $output = Artisan::output();
                    break;
                    
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Unknown command'
                    ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Command executed successfully',
                'output' => $output
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error executing command: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Create queue tables if they don't exist
     */
    public function createQueueTables()
    {
        try {
            // Run queue table migration
            Artisan::call('queue:table');
            Artisan::call('queue:failed-table');
            Artisan::call('migrate');
            
            return response()->json([
                'success' => true,
                'message' => 'Queue tables created successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating tables: ' . $e->getMessage()
            ]);
        }
    }
}
