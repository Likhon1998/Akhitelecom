<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'suspended' => \App\Http\Middleware\CheckIfSuspended::class,
            'staff.only' => \App\Http\Middleware\EnsureNotStorefrontCustomer::class,
        ]);

        // Storefront shoppers should return to the shop home, not the staff login.
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('account', 'account/*', 'checkout')) {
                return route('home');
            }

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->routeIs('website.account.logout') || $request->is('account/logout')) {
                Auth::guard('web')->logout();
                if ($request->hasSession()) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }

                return redirect()->route('home');
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Your session expired. Please refresh the page and try again.',
                ], 419);
            }

            return redirect()
                ->back()
                ->withInput($request->except('_token', 'password', 'password_confirmation'))
                ->with('error', 'Your session expired for security. Please try again.');
        });
    })->create();
