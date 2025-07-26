<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CustomLoggerService;

class LogViewerController extends Controller
{
    protected $logger;

    public function __construct()
    {
        $this->logger = new CustomLoggerService();
    }

    /**
     * Show logs dashboard
     */
    public function index(Request $request)
    {
        $context = $request->get('context', 'video-template');
        $days = $request->get('days', 3);

        $logFiles = $this->logger->getLogFiles($context, $days);
        $recentErrors = $this->logger->getRecentErrors($context, 24);

        $contexts = [
            'video-template' => 'Video Template',
            'video-generation' => 'Video Generation',
            'audio-library' => 'Audio Library',
            'crawl' => 'Story Crawling',
            'tts' => 'Text-to-Speech',
            'system' => 'System'
        ];

        return view('admin.logs.index', compact(
            'logFiles',
            'recentErrors',
            'contexts',
            'context',
            'days'
        ));
    }

    /**
     * Download log file
     */
    public function download(Request $request)
    {
        $context = $request->get('context');
        $date = $request->get('date');

        if (!$context || !$date) {
            return back()->with('error', 'Missing context or date parameter');
        }

        $filename = "logs/{$context}-{$date}.log";

        if (!\Storage::disk('local')->exists($filename)) {
            return back()->with('error', 'Log file not found');
        }

        return \Storage::disk('local')->download($filename);
    }

    /**
     * Clear old logs
     */
    public function clear(Request $request)
    {
        $context = $request->get('context');
        $days = $request->get('days', 30);

        if (!$context) {
            return back()->with('error', 'Context is required');
        }

        $this->logger->cleanOldLogs($context, $days);

        return back()->with('success', "Cleared logs older than {$days} days for {$context}");
    }

    /**
     * Get logs via AJAX
     */
    public function ajax(Request $request)
    {
        $context = $request->get('context', 'video-template');
        $days = $request->get('days', 1);
        $errorsOnly = $request->boolean('errors_only');

        if ($errorsOnly) {
            $data = $this->logger->getRecentErrors($context, 24);
        } else {
            $data = $this->logger->getLogFiles($context, $days);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'context' => $context,
            'days' => $days,
            'errors_only' => $errorsOnly
        ]);
    }
}
