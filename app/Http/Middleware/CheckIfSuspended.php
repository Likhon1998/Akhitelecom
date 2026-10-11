<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckIfSuspended
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('admin')->user();

        if ($user && $user->is_suspended) {
            Auth::guard('admin')->logout();
            if ($request->hasSession()) {
                $request->session()->regenerate();
                $request->session()->regenerateToken();
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Your account is suspended. Please contact your shop owner.',
            ]);
        }

        return $next($request);
    }
}
