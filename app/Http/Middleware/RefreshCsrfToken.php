<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RefreshCsrfToken
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
        $response = $next($request);

        // If this is a successful login redirect, add CSRF token to session flash
        if ($response instanceof \Illuminate\Http\RedirectResponse && 
            $request->route() && 
            $request->route()->getName() === null && 
            $request->is('login') && 
            $request->isMethod('POST') && 
            $response->getTargetUrl() !== $request->url()) {
            
            // Add fresh CSRF token to flash data
            $request->session()->flash('csrf_token', $request->session()->token());
        }

        return $response;
    }
}
