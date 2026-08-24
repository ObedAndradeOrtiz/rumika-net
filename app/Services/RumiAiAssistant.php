<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Expense;
use App\Models\InventoryProductBatch;
use App\Models\ProductSale;
use App\Models\TreatmentPayment;
use App\Models\User;
use App\Support\Money;
use App\Support\RumikaAccess;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RumiAiAssistant
{
    private const MODULES = [
        'inicio' => ['label' => 'Inicio', 'route' => 'dashboard'],
        'agenda' => ['label' => 'Agenda', 'route' => 'clinic.agenda'],
        'clientes' => ['label' => 'Clientes', 'route' => 'clinic.clients'],
        'historia_clinica' => ['label' => 'Historia clinica', 'route' => 'clinic.clinical-history'],
        'ventas_productos' => ['label' => 'Ventas', 'route' => 'sales.products'],
        'crm' => ['label' => 'Centro de mensajes', 'route' => 'crm.index'],
        'inventario' => ['label' => 'Inventario', 'route' => 'inventory.index'],
        'inventario_operaciones' => ['label' => 'Operaciones de inventario', 'route' => 'inventory.operations'],
        'caja' => ['label' => 'Caja', 'route' => 'clinic.cashbox'],
        'gastos' => ['label' => 'Gastos', 'route' => 'finance.expenses'],
        'facturacion' => ['label' => 'Facturacion', 'route' => 'finance.invoicing'],
        'deudas' => ['label' => 'Deudas', 'route' => 'finance.debts'],
        'reportes' => ['label' => 'Reportes', 'route' => 'finance.reports'],
        'comisiones' => ['label' => 'Comisiones', 'route' => 'finance.commissions'],
        'resumen_financiero' => ['label' => 'Resumen financiero', 'route' => 'finance.summary'],
        'estadisticas' => ['label' => 'Estadisticas', 'route' => 'statistics.index'],
        'sucursales' => ['label' => 'Sucursales', 'route' => 'settings.commerce'],
        'servicios' => ['label' => 'Servicios', 'route' => 'settings.services'],
        'registros' => ['label' => 'Registros', 'route' => 'settings.records'],
        'bitacora' => ['label' => 'Bitacora', 'route' => 'settings.audit'],
        'usuarios' => ['label' => 'Usuarios y roles', 'route' => 'settings.users'],
        'roles' => ['label' => 'Usuarios y roles', 'route' => 'settings.users'],
        'mi_sistema' => ['label' => 'Mi sistema', 'route' => 'settings.system'],
    ];

    public function answer(User $user, string $question): array
    {
        $company = $user->companies()->first();

        if (! $company) {
            return $this->response('Primero necesitas estar asociado a una empresa para que pueda ayudarte dentro de Rumika.');
        }

        $branch = $this->activeBranch($user, $company);
        $question = trim($question);
        $normalized = Str::of($question)->lower()->ascii()->toString();

        $local = $this->localAnswer($user, $company, $branch, $normalized);

        if ($local) {
            return $local;
        }

        $context = $this->allowedContext($user, $company, $branch);
        $ai = $this->askConfiguredProvider($question, $context);

        if ($ai) {
            return $this->response($ai, $this->suggestedActions($user, $normalized));
        }

        return $this->response(
            'Puedo ayudarte a ubicar pantallas, abrir acciones seguras y resumir datos segun tu rol. Prueba con: "donde cierro caja", "resumen de hoy" o "que puedo hacer con mi rol".',
            $this->suggestedActions($user, $normalized)
        );
    }

    public function action(string $key, User $user): ?array
    {
        $company = $user->companies()->first();

        if (! $company) {
            return null;
        }

        $map = [
            'open_branch' => ['type' => 'click', 'selector' => '.rm-branch-switcher-button'],
            'open_more' => ['type' => 'click', 'selector' => '[data-mobile-more-toggle]'],
            'open_quick_cashbox' => ['type' => 'click', 'selector' => '.rm-top-wallet-button', 'module' => 'caja'],
            'new_appointment' => ['type' => 'safe_route_action', 'route' => 'clinic.agenda', 'query' => 'new_appointment', 'selector' => '[data-rumi-action="new-appointment"]', 'module' => 'agenda', 'action' => 'create'],
            'new_client' => ['type' => 'safe_route_action', 'route' => 'clinic.clients', 'query' => 'new_client', 'selector' => '[data-rumi-action="new-client"]', 'module' => 'clientes', 'action' => 'create'],
        ];

        foreach (self::MODULES as $module => $data) {
            $map['go_' . $module] = ['type' => 'route', 'route' => $data['route'], 'module' => $module];
        }

        $action = $map[$key] ?? null;

        if (! $action) {
            return null;
        }

        $module = $action['module'] ?? null;
        $neededAction = $action['action'] ?? 'view';

        if ($module && ! $this->can($user, $module, $neededAction, $company)) {
            return [
                'type' => 'message',
                'message' => 'Tu rol no tiene permiso para ejecutar esa accion.',
            ];
        }

        if (($action['type'] ?? null) === 'safe_route_action') {
            if (request()->routeIs($action['route'])) {
                return ['type' => 'click', 'selector' => $action['selector']];
            }

            return [
                'type' => 'route',
                'url' => route($action['route'], ['rumi_action' => $action['query']]),
            ];
        }

        if (($action['type'] ?? null) === 'route') {
            return ['type' => 'route', 'url' => route($action['route'])];
        }

        return $action;
    }

    private function localAnswer(User $user, Company $company, ?Branch $branch, string $normalized): ?array
    {
        if ($this->isSalesQuestion($normalized)) {
            return $this->salesSummary($user, $company, $branch, $normalized);
        }

        if (str_contains($normalized, 'resumen') || str_contains($normalized, 'hoy') || str_contains($normalized, 'como vamos')) {
            return $this->todaySummary($user, $company, $branch);
        }

        if (str_contains($normalized, 'rol') || str_contains($normalized, 'permiso') || str_contains($normalized, 'puedo hacer')) {
            return $this->roleSummary($user, $company);
        }

        $navigation = [
            'agenda' => ['agenda', 'cita', 'agendar'],
            'clientes' => ['cliente', 'paciente'],
            'historia_clinica' => ['historia', 'ficha', 'receta', 'archivo clinico'],
            'caja' => ['caja', 'cobro', 'ticket', 'cierre'],
            'ventas_productos' => ['venta', 'vender producto', 'farmacia'],
            'reportes' => ['reporte', 'pdf', 'gerente'],
            'gastos' => ['gasto', 'egreso'],
            'inventario' => ['inventario', 'stock', 'producto'],
            'inventario_operaciones' => ['operacion', 'entrada', 'salida', 'traspaso', 'desecho'],
            'crm' => ['whatsapp', 'mensaje', 'crm', 'bandeja'],
            'servicios' => ['servicio', 'tratamiento', 'paquete'],
            'usuarios' => ['usuario', 'personal'],
            'roles' => ['rol', 'roles'],
            'mi_sistema' => ['plan', 'mi sistema', 'pago mensual'],
        ];

        foreach ($navigation as $module => $words) {
            foreach ($words as $word) {
                if (str_contains($normalized, $word)) {
                    return $this->navigationAnswer($user, $company, $module);
                }
            }
        }

        return null;
    }

    private function isSalesQuestion(string $normalized): bool
    {
        $hasSalesWord = str_contains($normalized, 'vendi')
            || str_contains($normalized, 'vendido')
            || str_contains($normalized, 'ventas')
            || str_contains($normalized, 'ingreso')
            || str_contains($normalized, 'facture')
            || str_contains($normalized, 'facturado');

        $hasPeriodWord = str_contains($normalized, 'mes')
            || str_contains($normalized, 'semana')
            || str_contains($normalized, 'hoy')
            || str_contains($normalized, 'ayer');

        return $hasSalesWord && $hasPeriodWord;
    }

    private function salesSummary(User $user, Company $company, ?Branch $branch, string $normalized): array
    {
        if (! $this->can($user, 'caja', company: $company)
            && ! $this->can($user, 'reportes', company: $company)
            && ! $this->can($user, 'resumen_financiero', company: $company)
            && ! $this->can($user, 'estadisticas', company: $company)) {
            return $this->response('Tu rol no tiene permiso para ver montos de ventas o ingresos.');
        }

        if (! $branch) {
            return $this->response('No encontre una sucursal activa para revisar ventas.');
        }

        [$from, $to, $label] = $this->periodFromQuestion($normalized);
        $useAllBranches = str_contains($normalized, 'todas') || str_contains($normalized, 'general') || str_contains($normalized, 'empresa');
        $branchIds = $useAllBranches && $this->can($user, 'reportes', company: $company)
            ? $company->branches()->pluck('id')->all()
            : [$branch->id];

        $serviceIncome = (float) TreatmentPayment::query()
            ->where('company_id', $company->id)
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        $productIncome = (float) ProductSale::query()
            ->where('company_id', $company->id)
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('sold_at', [$from, $to])
            ->sum('paid_amount');

        $total = $serviceIncome + $productIncome;
        $scope = count($branchIds) > 1 ? 'todas las sucursales' : $branch->name;

        return $this->response(
            "Ventas de {$label} en {$scope}:\n"
            . 'Servicios: ' . Money::symbol() . ' ' . number_format($serviceIncome, 2) . "\n"
            . 'Productos: ' . Money::symbol() . ' ' . number_format($productIncome, 2) . "\n"
            . 'Total vendido: ' . Money::symbol() . ' ' . number_format($total, 2),
            [
                $this->actionButton('Abrir caja', 'open_quick_cashbox'),
                $this->actionButton('Ver reportes', 'go_reportes'),
                $this->actionButton('Ver estadisticas', 'go_estadisticas'),
            ]
        );
    }

    private function periodFromQuestion(string $normalized): array
    {
        $now = now();

        if (str_contains($normalized, 'ayer')) {
            $date = $now->copy()->subDay();

            return [$date->copy()->startOfDay(), $date->copy()->endOfDay(), 'ayer'];
        }

        if (str_contains($normalized, 'hoy')) {
            return [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'hoy'];
        }

        if (str_contains($normalized, 'semana')) {
            return [$now->copy()->startOfWeek(), $now->copy()->endOfWeek(), 'esta semana'];
        }

        return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'este mes'];
    }

    private function todaySummary(User $user, Company $company, ?Branch $branch): array
    {
        if (! $branch) {
            return $this->response('No encontre una sucursal activa para revisar el resumen.');
        }

        $today = now();
        $dayRange = [$today->copy()->startOfDay(), $today->copy()->endOfDay()];
        $parts = ["Sucursal activa: {$branch->name}."];
        $actions = [];

        if ($this->can($user, 'agenda', company: $company)) {
            $appointments = Appointment::query()
                ->where('company_id', $company->id)
                ->where('branch_id', $branch->id)
                ->whereBetween('scheduled_at', $dayRange)
                ->get();
            $parts[] = 'Agenda: ' . $appointments->count() . ' citas, ' . $appointments->where('attended', true)->count() . ' asistidas y ' . $appointments->where('status', 'no_show')->count() . ' no asistidas.';
            $actions[] = $this->actionButton('Abrir agenda', 'go_agenda');
        }

        if ($this->can($user, 'caja', company: $company)) {
            $serviceIncome = (float) TreatmentPayment::query()
                ->where('company_id', $company->id)
                ->where('branch_id', $branch->id)
                ->whereBetween('paid_at', $dayRange)
                ->sum('amount');
            $productIncome = (float) ProductSale::query()
                ->where('company_id', $company->id)
                ->where('branch_id', $branch->id)
                ->whereBetween('sold_at', $dayRange)
                ->sum('paid_amount');
            $parts[] = 'Caja: ' . Money::symbol() . ' ' . number_format($serviceIncome + $productIncome, 2) . ' ingresados entre servicios y productos.';
            $actions[] = $this->actionButton('Abrir caja', 'open_quick_cashbox');
        }

        if ($this->can($user, 'gastos', company: $company)) {
            $expenses = (float) Expense::query()
                ->where('company_id', $company->id)
                ->where('branch_id', $branch->id)
                ->whereDate('spent_at', $today->toDateString())
                ->sum('amount');
            $parts[] = 'Gastos de hoy: ' . Money::symbol() . ' ' . number_format($expenses, 2) . '.';
        }

        if ($this->can($user, 'inventario', company: $company)) {
            $expiring = InventoryProductBatch::query()
                ->where('company_id', $company->id)
                ->where('branch_id', $branch->id)
                ->where('status', 'active')
                ->where('current_quantity', '>', 0)
                ->whereBetween('expires_at', [$today->copy()->startOfDay(), $today->copy()->addDays(30)->endOfDay()])
                ->count();
            $parts[] = "Inventario: {$expiring} lotes vencen en los proximos 30 dias.";
            $actions[] = $this->actionButton('Ver inventario', 'go_inventario');
        }

        if (count($parts) === 1) {
            $parts[] = 'Tu rol no tiene permisos suficientes para ver indicadores operativos.';
        }

        return $this->response(implode("\n", $parts), $actions);
    }

    private function roleSummary(User $user, Company $company): array
    {
        $allowed = collect(self::MODULES)
            ->filter(fn (array $data, string $module) => $this->can($user, $module, company: $company))
            ->map(fn (array $data) => $data['label'])
            ->unique()
            ->values();

        if ($allowed->isEmpty()) {
            return $this->response('Tu rol no tiene vistas activas en este momento. Pide a administracion que revise tus permisos.');
        }

        return $this->response(
            'Con tu rol puedes ver: ' . $allowed->join(', ') . '. Si necesitas otra vista, administracion puede activarla desde Usuarios y roles.',
            [
                $this->actionButton('Usuarios y roles', 'go_usuarios'),
                $this->actionButton('Mas opciones', 'open_more'),
            ]
        );
    }

    private function navigationAnswer(User $user, Company $company, string $module): array
    {
        $data = self::MODULES[$module] ?? null;

        if (! $data) {
            return $this->response('Todavia no tengo ubicada esa pantalla.');
        }

        if (! $this->can($user, $module, company: $company)) {
            return $this->response("Tu rol no tiene permiso para abrir {$data['label']}. Puedes pedir acceso desde administracion.");
        }

        $actions = [$this->actionButton('Abrir ' . $data['label'], 'go_' . $module)];

        if ($module === 'agenda' && $this->can($user, 'agenda', 'create', company: $company)) {
            $actions[] = $this->actionButton('Nueva cita', 'new_appointment');
        }

        if ($module === 'clientes' && $this->can($user, 'clientes', 'create', company: $company)) {
            $actions[] = $this->actionButton('Nuevo cliente', 'new_client');
        }

        return $this->response("Para trabajar con {$data['label']}, entra a esa pantalla desde el menu. Puedo abrirla por ti.", $actions);
    }

    private function allowedContext(User $user, Company $company, ?Branch $branch): array
    {
        return [
            'empresa' => $company->name,
            'sucursal_activa' => $branch?->name,
            'rol' => $user->companies()->where('companies.id', $company->id)->value('company_user.role') ?: 'personal',
            'modulos_permitidos' => collect(self::MODULES)
                ->filter(fn (array $data, string $module) => $this->can($user, $module, company: $company))
                ->map(fn (array $data) => $data['label'])
                ->unique()
                ->values()
                ->all(),
        ];
    }

    private function askConfiguredProvider(string $question, array $context): ?string
    {
        $endpoint = config('services.rumi_ai.endpoint');

        if ($endpoint) {
            try {
                $response = Http::timeout(20)
                    ->withToken((string) config('services.rumi_ai.token'))
                    ->post($endpoint, [
                        'question' => $question,
                        'context' => $context,
                        'rules' => [
                            'language' => 'es',
                            'no_sensitive_data_without_permission' => true,
                            'no_database_writes' => true,
                        ],
                    ]);

                if ($response->successful()) {
                    return trim((string) ($response->json('answer') ?? $response->json('message')));
                }

                Log::warning('Rumi endpoint error', ['status' => $response->status(), 'body' => $response->body()]);
            } catch (\Throwable $e) {
                Log::warning('Rumi endpoint exception', ['message' => $e->getMessage()]);
            }
        }

        $apiKey = config('services.google_ai.key');

        if (! $apiKey) {
            return null;
        }

        try {
            $model = config('services.google_ai.model', 'gemini-2.0-flash');
            $prompt = "Eres Rumi, asistente interno de Rumika SaaS. Responde en espanol, breve y claro. Solo usa el contexto permitido. No inventes datos. No digas que puedes editar, eliminar, cobrar o cerrar caja. Contexto permitido: "
                . json_encode($context, JSON_UNESCAPED_UNICODE)
                . "\nPregunta: {$question}";

            $response = Http::timeout(20)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [[
                        'role' => 'user',
                        'parts' => [['text' => $prompt]],
                    ]],
                ]);

            if (! $response->successful()) {
                Log::warning('Gemini Rumi error', ['status' => $response->status(), 'body' => $response->body()]);

                return null;
            }

            return trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text'));
        } catch (\Throwable $e) {
            Log::warning('Gemini Rumi exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    private function suggestedActions(User $user, string $normalized): array
    {
        $company = $user->companies()->first();

        if (! $company) {
            return [];
        }

        $actions = [
            $this->actionButton('Cambiar sucursal', 'open_branch'),
            $this->actionButton('Mas opciones', 'open_more'),
        ];

        foreach (['agenda', 'clientes', 'caja', 'reportes'] as $module) {
            if ($this->can($user, $module, company: $company)) {
                $actions[] = $this->actionButton(self::MODULES[$module]['label'], 'go_' . $module);
            }
        }

        return array_slice($actions, 0, 5);
    }

    private function response(string $answer, array $actions = []): array
    {
        return [
            'answer' => trim($answer),
            'actions' => array_values(array_filter($actions)),
        ];
    }

    private function actionButton(string $label, string $key): array
    {
        return ['label' => $label, 'key' => $key];
    }

    private function can(User $user, string $module, string $action = 'view', ?Company $company = null): bool
    {
        if ($module === 'mi_sistema') {
            return true;
        }

        return RumikaAccess::can($user, $module, $action, company: $company);
    }

    private function activeBranch(User $user, Company $company): ?Branch
    {
        $branches = $user->branches()->where('company_id', $company->id)->orderBy('name')->get();
        $branches = $branches->isNotEmpty() ? $branches : $company->branches()->orderBy('name')->get();

        return $branches->firstWhere('id', session('active_branch_id')) ?? $branches->first();
    }
}
