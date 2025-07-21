<?php

namespace App\Traits;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

trait HasToastMessages
{
    /**
     * Flash success message and redirect
     */
    protected function toastSuccess(string $message, string $route = null, array $parameters = []): RedirectResponse
    {
        $redirect = $route ? redirect()->route($route, $parameters) : back();
        return $redirect->with('success', $message);
    }

    /**
     * Flash error message and redirect
     */
    protected function toastError(string $message, string $route = null, array $parameters = []): RedirectResponse
    {
        $redirect = $route ? redirect()->route($route, $parameters) : back();
        return $redirect->with('error', $message);
    }

    /**
     * Flash warning message and redirect
     */
    protected function toastWarning(string $message, string $route = null, array $parameters = []): RedirectResponse
    {
        $redirect = $route ? redirect()->route($route, $parameters) : back();
        return $redirect->with('warning', $message);
    }

    /**
     * Flash info message and redirect
     */
    protected function toastInfo(string $message, string $route = null, array $parameters = []): RedirectResponse
    {
        $redirect = $route ? redirect()->route($route, $parameters) : back();
        return $redirect->with('info', $message);
    }

    /**
     * Return JSON response with toast message for AJAX requests
     */
    protected function toastJson(string $type, string $message, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => $type === 'success',
            'type' => $type,
            'message' => $message,
            'toast' => true,
            ...$data
        ], $status);
    }

    /**
     * Return success JSON with toast
     */
    protected function toastJsonSuccess(string $message, array $data = []): JsonResponse
    {
        return $this->toastJson('success', $message, $data);
    }

    /**
     * Return error JSON with toast
     */
    protected function toastJsonError(string $message, array $data = [], int $status = 400): JsonResponse
    {
        return $this->toastJson('error', $message, $data, $status);
    }

    /**
     * Return warning JSON with toast
     */
    protected function toastJsonWarning(string $message, array $data = []): JsonResponse
    {
        return $this->toastJson('warning', $message, $data);
    }

    /**
     * Return info JSON with toast
     */
    protected function toastJsonInfo(string $message, array $data = []): JsonResponse
    {
        return $this->toastJson('info', $message, $data);
    }

    /**
     * Handle both AJAX and regular requests
     */
    protected function toastResponse(string $type, string $message, string $route = null, array $parameters = [], array $jsonData = [])
    {
        if (request()->ajax() || request()->wantsJson()) {
            return $this->toastJson($type, $message, $jsonData);
        }

        $method = 'toast' . ucfirst($type);
        return $this->$method($message, $route, $parameters);
    }

    /**
     * Success response for both AJAX and regular requests
     */
    protected function successResponse(string $message, string $route = null, array $parameters = [], array $jsonData = [])
    {
        return $this->toastResponse('success', $message, $route, $parameters, $jsonData);
    }

    /**
     * Error response for both AJAX and regular requests
     */
    protected function errorResponse(string $message, string $route = null, array $parameters = [], array $jsonData = [])
    {
        return $this->toastResponse('error', $message, $route, $parameters, $jsonData);
    }
}
