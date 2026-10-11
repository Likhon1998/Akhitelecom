<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::guard('admin')->user();

        if ($user->is_suspended) {
            Auth::guard('admin')->logout();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'You are suspended by admin. Please connect with admin.',
            ]);
        }

        // Keep any storefront (web) login; only rotate the session id / CSRF.
        $request->session()->regenerate();

        if ($user->requiresDailyOpeningBalance() && ! $user->hasTodayOpenSession()) {
            return redirect()->route('counters.sessions.open-today');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        // Do not session()->invalidate() — that would also log out the website customer tab.
        if ($request->hasSession()) {
            $request->session()->regenerate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login');
    }
}
