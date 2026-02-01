<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Refresh the user model to get the latest is_active value from database
            $user->refresh();
            
            // Check if user is inactive
            if (($user->is_active ?? true) === false) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                return redirect(Filament::getPanel('commhealth')->getLoginUrl())
                    ->with('error', 'Your account has been deactivated. Please contact an administrator.');
            }
        }

        return $next($request);
    }
}
