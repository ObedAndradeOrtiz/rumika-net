<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'webhook/whatsapp',
        ]);

        $middleware->alias([
            'rumika.permission' => \App\Http\Middleware\EnsureRumikaPermission::class,
            'rumika.onboarding' => \App\Http\Middleware\EnsureCompanyOnboardingIsComplete::class,
            'rumika.saas_admin' => \App\Http\Middleware\EnsureSaasAdmin::class,
            'rumika.subscription' => \App\Http\Middleware\EnsureCompanySubscriptionIsActive::class,
            'rumika.face' => \App\Http\Middleware\EnsureFaceVerification::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
