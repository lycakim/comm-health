<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePrivacyAccepted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && !auth()->user()->hasAcceptedPrivacy()) {
            // User must stay on current page until they accept
            // The modal will handle the acceptance
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Please accept the privacy policy to continue.'
                ], 403);
            }
        }

        return $next($request);
    }
}