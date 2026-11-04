<?php

use App\Http\Middleware\DemoRefresh;
use App\Http\Middleware\EnsureStaff;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use SergiX44\Nutgram\Nutgram;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Telegram calls this address. It has no session or CSRF: Telegram is not a browser.
            Route::post('/telegram/webhook', function (Nutgram $bot) {
                $bot->run();

                return response()->noContent();
            })->middleware(DemoRefresh::class)->name('telegram.webhook');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [DemoRefresh::class]);
        $middleware->alias(['staff' => EnsureStaff::class]);
        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()->homeRoute());
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
