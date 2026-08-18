<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RumikaBotController;

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
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('configuracion/comercios', 'settings.commerce')
    ->middleware(['auth', 'verified'])
    ->name('settings.commerce');

Route::view('configuracion/usuarios', 'settings.users')
    ->middleware(['auth', 'verified'])
    ->name('settings.users');

Route::view('configuracion/servicios', 'settings.services')
    ->middleware(['auth', 'verified'])
    ->name('settings.services');

Route::view('configuracion/registros', 'settings.records')
    ->middleware(['auth', 'verified'])
    ->name('settings.records');

Route::view('agenda', 'clinic.agenda')
    ->middleware(['auth', 'verified'])
    ->name('clinic.agenda');

Route::view('clientes', 'clinic.clients')
    ->middleware(['auth', 'verified'])
    ->name('clinic.clients');

Route::view('caja', 'clinic.cashbox')
    ->middleware(['auth', 'verified'])
    ->name('clinic.cashbox');

Route::view('inventario', 'inventory.index')
    ->middleware(['auth', 'verified'])
    ->name('inventory.index');

Route::view('inventario/operaciones', 'inventory.operations')
    ->middleware(['auth', 'verified'])
    ->name('inventory.operations');

Route::view('finanzas/gastos', 'finance.expenses')
    ->middleware(['auth', 'verified'])
    ->name('finance.expenses');

Route::view('finanzas/resumen', 'finance.summary')
    ->middleware(['auth', 'verified'])
    ->name('finance.summary');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__ . '/auth.php';
