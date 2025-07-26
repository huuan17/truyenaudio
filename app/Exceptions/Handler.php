<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Handle CSRF token mismatch
        $this->renderable(function (TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'CSRF token mismatch.',
                    'error' => 'Token expired'
                ], 419);
            }

            // Clear session to prevent further issues
            $request->session()->flush();
            $request->session()->regenerate();

            return redirect()->route('login')
                ->withErrors(['email' => 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại.'])
                ->with('error', 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại.');
        });

        // Handle PostTooLargeException
        $this->renderable(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, $request) {
            // Log the error
            $logger = new \App\Services\CustomLoggerService();
            $logger->logError('system', 'Post too large exception caught globally', [
                'content_length' => $request->server('CONTENT_LENGTH'),
                'post_max_size' => ini_get('post_max_size'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_agent' => $request->userAgent()
            ], $e);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Upload quá lớn',
                    'message' => 'Tổng dung lượng files vượt quá giới hạn ' . ini_get('post_max_size') . '. Vui lòng giảm kích thước files.',
                    'code' => 'POST_TOO_LARGE',
                    'limits' => [
                        'post_max_size' => ini_get('post_max_size'),
                        'upload_max_filesize' => ini_get('upload_max_filesize'),
                        'max_file_uploads' => ini_get('max_file_uploads')
                    ]
                ], 413);
            }

            return back()->withInput()->with('error',
                'Dung lượng upload quá lớn! Tổng dung lượng files vượt quá giới hạn ' .
                ini_get('post_max_size') . '. Vui lòng giảm kích thước hoặc số lượng files và thử lại.'
            );
        });
    }
}
