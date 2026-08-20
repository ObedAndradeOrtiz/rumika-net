<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RumikaBotController;
use App\Livewire\Onboarding\CompanySetup;

use Illuminate\Support\Facades\Http;

Route::get('/test-gemini-directo', function () {
    $apiKey = config('services.google_ai.key');
    $model = config('services.google_ai.model', 'gemini-3.5-flash');

    $response = Http::withoutVerifying()
        ->timeout(20)
        ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => 'Responde solamente: OK'],
                    ],
                ],
            ],
        ]);

    return response()->json([
        'status' => $response->status(),
        'model' => $model,
        'body' => $response->json(),
    ]);
});

Route::post('/rumika-bot', [RumikaBotController::class, 'ask'])
    ->name('rumika.bot');


Route::get('/', function () {
    return view('welcome');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified', 'rumika.onboarding', 'rumika.permission:inicio'])
    ->name('dashboard');

Route::view('configuracion/comercios', 'settings.commerce')
    ->middleware(['auth', 'verified', 'rumika.onboarding', 'rumika.permission:sucursales'])
    ->name('settings.commerce');

Route::view('configuracion/usuarios', 'settings.users')
    ->middleware(['auth', 'verified', 'rumika.onboarding', 'rumika.permission:usuarios|roles'])
    ->name('settings.users');

Route::view('configuracion/servicios', 'settings.services')
    ->middleware(['auth', 'verified', 'rumika.onboarding', 'rumika.permission:servicios'])
    ->name('settings.services');

Route::view('configuracion/registros', 'settings.records')
    ->middleware(['auth', 'verified', 'rumika.onboarding', 'rumika.permission:registros'])
    ->name('settings.records');

Route::view('agenda', 'clinic.agenda')
    ->middleware(['auth', 'verified', 'rumika.onboarding', 'rumika.permission:agenda'])
    ->name('clinic.agenda');

Route::view('clientes', 'clinic.clients')
    ->middleware(['auth', 'verified', 'rumika.onboarding', 'rumika.permission:clientes'])
    ->name('clinic.clients');

Route::view('caja', 'clinic.cashbox')
    ->middleware(['auth', 'verified', 'rumika.onboarding', 'rumika.permission:caja'])
    ->name('clinic.cashbox');

Route::view('inventario', 'inventory.index')
    ->middleware(['auth', 'verified', 'rumika.onboarding', 'rumika.permission:inventario'])
    ->name('inventory.index');

Route::view('inventario/operaciones', 'inventory.operations')
    ->middleware(['auth', 'verified', 'rumika.onboarding', 'rumika.permission:inventario_operaciones'])
    ->name('inventory.operations');

Route::view('finanzas/gastos', 'finance.expenses')
    ->middleware(['auth', 'verified', 'rumika.onboarding', 'rumika.permission:gastos'])
    ->name('finance.expenses');

Route::view('finanzas/resumen', 'finance.summary')
    ->middleware(['auth', 'verified', 'rumika.onboarding', 'rumika.permission:resumen_financiero'])
    ->name('finance.summary');

Route::get('configuracion-inicial', CompanySetup::class)
    ->middleware(['auth', 'verified'])
    ->name('onboarding.company');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__ . '/auth.php';
