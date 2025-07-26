<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin/video-templates*')) {
            \Log::info('=== REQUEST TO VIDEO TEMPLATES ===', [
                'method' => $request->method(),
                'url' => $request->url(),
                'route' => $request->route() ? $request->route()->getName() : 'no-route',
                'user_id' => auth()->id(),
                'has_data' => !empty($request->all())
            ]);
        }

        return $next($request);
    }
}
