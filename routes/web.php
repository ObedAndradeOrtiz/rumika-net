<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FinanceReportExportController;
use App\Http\Controllers\RumikaBotController;
use App\Http\Controllers\WhatsappWebhookController;
use App\Livewire\Onboarding\CompanySetup;
use App\Livewire\Security\FaceVerificationManager;

Route::post('/rumika-bot', [RumikaBotController::class, 'ask'])
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face'])
    ->name('rumika.bot');

Route::get('/webhook/whatsapp', [WhatsappWebhookController::class, 'verify'])
    ->name('webhook.whatsapp.verify');

Route::post('/webhook/whatsapp', [WhatsappWebhookController::class, 'receive'])
    ->name('webhook.whatsapp.receive');


Route::get('/', function () {
    return view('welcome');
});

Route::view('terminos-y-condiciones', 'legal.terms')
    ->name('legal.terms');

Route::view('politica-de-privacidad', 'legal.privacy')
    ->name('legal.privacy');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face', 'rumika.permission:inicio'])
    ->name('dashboard');

Route::view('saas', 'saas.dashboard')
    ->middleware(['auth', 'verified', 'rumika.face', 'rumika.saas_admin'])
    ->name('saas.dashboard');

Route::view('configuracion/comercios', 'settings.commerce')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face', 'rumika.permission:sucursales'])
    ->name('settings.commerce');

Route::view('configuracion/usuarios', 'settings.users')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face', 'rumika.permission:usuarios|roles'])
    ->name('settings.users');

Route::view('configuracion/servicios', 'settings.services')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face', 'rumika.permission:servicios'])
    ->name('settings.services');

Route::view('configuracion/registros', 'settings.records')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face', 'rumika.permission:registros'])
    ->name('settings.records');

Route::view('configuracion/bitacora', 'settings.audit')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face', 'rumika.permission:bitacora'])
    ->name('settings.audit');

Route::view('recursos-humanos/asistencia', 'hr.attendance')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face', 'rumika.permission:recursos_humanos'])
    ->name('hr.attendance');

Route::view('configuracion/mi-sistema', 'settings.system')
    ->middleware(['auth', 'verified', 'rumika.onboarding', 'rumika.face'])
    ->name('settings.system');

Route::view('agenda', 'clinic.agenda')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face', 'rumika.permission:agenda'])
    ->name('clinic.agenda');

Route::view('clientes', 'clinic.clients')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face', 'rumika.permission:clientes'])
    ->name('clinic.clients');

Route::view('historia-clinica', 'clinic.clinical-history')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face', 'rumika.permission:historia_clinica'])
    ->name('clinic.clinical-history');

Route::view('caja', 'clinic.cashbox')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face', 'rumika.permission:caja'])
    ->name('clinic.cashbox');

Route::view('ventas/productos', 'sales.products')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face', 'rumika.permission:ventas_productos'])
    ->name('sales.products');

Route::view('crm', 'crm.index')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face', 'rumika.permission:crm'])
    ->name('crm.index');

Route::view('inventario', 'inventory.index')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face', 'rumika.permission:inventario'])
    ->name('inventory.index');

Route::view('inventario/operaciones', 'inventory.operations')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face', 'rumika.permission:inventario_operaciones'])
    ->name('inventory.operations');

Route::view('finanzas/gastos', 'finance.expenses')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face', 'rumika.permission:gastos'])
    ->name('finance.expenses');

Route::view('finanzas/resumen', 'finance.summary')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face', 'rumika.permission:resumen_financiero'])
    ->name('finance.summary');

Route::view('finanzas/facturacion', 'finance.invoicing')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face', 'rumika.permission:facturacion'])
    ->name('finance.invoicing');

Route::view('finanzas/deudas', 'finance.debts')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face', 'rumika.permission:deudas'])
    ->name('finance.debts');

Route::view('finanzas/reportes', 'finance.reports')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face', 'rumika.permission:reportes'])
    ->name('finance.reports');

Route::view('finanzas/comisiones', 'finance.commissions')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face', 'rumika.permission:comisiones'])
    ->name('finance.commissions');

Route::get('finanzas/reportes/pdf', FinanceReportExportController::class)
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face', 'rumika.permission:reportes'])
    ->name('finance.reports.pdf');

Route::view('estadisticas', 'statistics.index')
    ->middleware(['auth', 'verified', 'rumika.subscription', 'rumika.onboarding', 'rumika.face', 'rumika.permission:estadisticas'])
    ->name('statistics.index');

Route::get('seguridad/facial', FaceVerificationManager::class)
    ->middleware(['auth', 'verified'])
    ->name('security.face');

Route::get('configuracion-inicial', CompanySetup::class)
    ->middleware(['auth', 'verified'])
    ->name('onboarding.company');

Route::view('acceso-pausado', 'billing.blocked')
    ->middleware(['auth', 'verified'])
    ->name('billing.blocked');

Route::view('profile', 'profile')
    ->middleware(['auth', 'rumika.face'])
    ->name('profile');

require __DIR__ . '/auth.php';
