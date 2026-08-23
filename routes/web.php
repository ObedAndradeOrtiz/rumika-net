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
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.permission:inicio'])
    ->name('dashboard');

Route::view('saas', 'saas.dashboard')
    ->middleware(['auth', 'verified', 'rumika.saas_admin'])
    ->name('saas.dashboard');

Route::view('configuracion/comercios', 'settings.commerce')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.permission:sucursales'])
    ->name('settings.commerce');

Route::view('configuracion/usuarios', 'settings.users')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.permission:usuarios|roles'])
    ->name('settings.users');

Route::view('configuracion/servicios', 'settings.services')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.permission:servicios'])
    ->name('settings.services');

Route::view('configuracion/registros', 'settings.records')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.permission:registros'])
    ->name('settings.records');

Route::view('configuracion/bitacora', 'settings.audit')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.permission:bitacora'])
    ->name('settings.audit');

Route::view('agenda', 'clinic.agenda')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.permission:agenda'])
    ->name('clinic.agenda');

Route::view('clientes', 'clinic.clients')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.permission:clientes'])
    ->name('clinic.clients');

Route::view('historia-clinica', 'clinic.clinical-history')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.permission:historia_clinica'])
    ->name('clinic.clinical-history');

Route::view('caja', 'clinic.cashbox')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.permission:caja'])
    ->name('clinic.cashbox');

Route::view('ventas/productos', 'sales.products')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.permission:ventas_productos'])
    ->name('sales.products');

Route::view('inventario', 'inventory.index')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.permission:inventario'])
    ->name('inventory.index');

Route::view('inventario/operaciones', 'inventory.operations')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.permission:inventario_operaciones'])
    ->name('inventory.operations');

Route::view('finanzas/gastos', 'finance.expenses')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.permission:gastos'])
    ->name('finance.expenses');

Route::view('finanzas/resumen', 'finance.summary')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.permission:resumen_financiero'])
    ->name('finance.summary');

Route::view('finanzas/facturacion', 'finance.invoicing')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.permission:facturacion'])
    ->name('finance.invoicing');

Route::view('estadisticas', 'statistics.index')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.permission:estadisticas'])
    ->name('statistics.index');

Route::get('configuracion-inicial', CompanySetup::class)
    ->middleware(['auth', 'verified'])
    ->name('onboarding.company');

Route::view('acceso-pausado', 'billing.blocked')
    ->middleware(['auth', 'verified'])
    ->name('billing.blocked');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__ . '/auth.php';
