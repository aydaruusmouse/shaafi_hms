<?php

use App\Http\Middleware\CheckUserStatus;
use App\Http\Middleware\SetLanguage;
use App\Http\Middleware\SetTenantFromUsername;
use App\Http\Middleware\XSS;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(
            except: ['appointment-phonepe-payment-success', 'ipd-phonepe-payment-success', 'medicine-bill-phonepe-payment-success', 'phonepe-payment-success', 'patient-razorpay-payment-success', 'purchase-medicine-phonepe-payment-success', 'ipd-razorpay-payment-success', 'appointment-razorpay-payment-success', 'medicine-purchase-razorpay-success', 'medicine-bill-razorpay-success', 'razorpay-payment-success', 'subscription-phonepe-payment-success', 'web-razorpay-payment-success', 'web-phonepe-payment-success']
        );
        $middleware->redirectGuestsTo(fn (Request $request) => route('filament.auth.auth.login'));
        $middleware->alias([
            'xss' => XSS::class,
            'setLanguage' => SetLanguage::class,
            'setTenantFromUsername' => SetTenantFromUsername::class,
            'languageChangeName' => App\Http\Middleware\LanguageChangeMiddleware::class,
            'checkUserStatus' => CheckUserStatus::class,
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
// pdf

$app->register(\Barryvdh\DomPDF\ServiceProvider::class);
