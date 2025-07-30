<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

class EnsureFreshCsrfToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Only apply to POST, PUT, PATCH, DELETE requests
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        // Skip for login route
        if ($request->is('login')) {
            return $next($request);
        }

        // Check if CSRF token is valid
        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');
        
        if (!$token || !hash_equals($request->session()->token(), $token)) {
            // Log CSRF token mismatch for debugging
            \Log::warning('CSRF Token Mismatch', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'session_id' => $request->session()->getId(),
                'session_token' => $request->session()->token(),
                'provided_token' => $token,
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
                'user_id' => auth()->id(),
            ]);

            // If this is an AJAX request, return JSON with fresh token
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'CSRF token mismatch. Please refresh and try again.',
                    'csrf_token' => $request->session()->token(),
                    'error_code' => 419
                ], 419);
            }

            // For regular requests, redirect back with error and fresh token
            return back()->withErrors([
                'csrf' => 'Phiên làm việc đã hết hạn. Vui lòng thử lại.'
            ])->with('csrf_token', $request->session()->token());
        }

        $response = $next($request);

        // Add fresh CSRF token to response headers for AJAX requests
        if ($request->expectsJson() && method_exists($response, 'header')) {
            $response->header('X-CSRF-TOKEN', $request->session()->token());
        }

        return $response;
    }
}
