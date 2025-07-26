<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Exceptions\PostTooLargeException;

class HandlePostTooLarge
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Check if request size exceeds limits before processing
            $this->checkRequestSize($request);

            return $next($request);

        } catch (PostTooLargeException $e) {
            return $this->handlePostTooLarge($request, $e);
        }
    }

    /**
     * Check request size against PHP limits
     */
    protected function checkRequestSize(Request $request)
    {
        $postMaxSize = $this->parseSize(ini_get('post_max_size'));
        $contentLength = $request->server('CONTENT_LENGTH');

        if ($contentLength && $contentLength > $postMaxSize) {
            throw new PostTooLargeException('Request entity too large');
        }
    }

    /**
     * Handle PostTooLargeException
     */
    protected function handlePostTooLarge(Request $request, PostTooLargeException $e)
    {
        // Log the error
        $logger = new \App\Services\CustomLoggerService();
        $logger->logError('video-template', 'Post too large exception', [
            'content_length' => $request->server('CONTENT_LENGTH'),
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'url' => $request->fullUrl(),
            'method' => $request->method()
        ], $e);

        // Return user-friendly error
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Dung lượng upload quá lớn',
                'message' => 'Tổng dung lượng files vượt quá giới hạn cho phép. Vui lòng giảm kích thước hoặc số lượng files.',
                'limits' => [
                    'post_max_size' => ini_get('post_max_size'),
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                    'max_file_uploads' => ini_get('max_file_uploads')
                ]
            ], 413);
        }

        return back()->withInput()->with('error',
            'Dung lượng upload quá lớn. Tổng dung lượng files vượt quá giới hạn ' .
            ini_get('post_max_size') . '. Vui lòng giảm kích thước hoặc số lượng files.'
        );
    }

    /**
     * Parse size string to bytes
     */
    protected function parseSize($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);

        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }

        return round($size);
    }
}
