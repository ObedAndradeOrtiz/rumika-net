<?php

namespace App\Livewire\Settings;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\InventoryProduct;
use App\Support\RumikaAccess;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Throwable;

class AuditLogManager extends Component
{
    private const ROLLBACK_EVENTS = ['created', 'updated', 'deleted'];

    public string $dateFrom = '';
    public string $dateTo = '';
    public string $userFilter = '';
    public string $moduleFilter = '';
    public string $eventFilter = '';
    public string $search = '';
    public bool $showRollbackModal = false;
    public string $rollbackConfirmation = '';
    public string $rollbackMessage = '';
    public array $rollbackSummary = [];
    public array $rollbackPreviewRows = [];

    public function mount(): void
    {
        abort_unless($this->isAdmin(), 403);

        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function exportExcel()
    {
        abort_unless($this->isAdmin(), 403);

        $logs = $this->filteredLogs()->with('user')->orderBy('occurred_at')->get();
        $xml = $this->buildExcelXml($logs);
        $fileName = 'bitacora-rumika-'.$this->dateFrom.'-'.$this->dateTo.'.xls';

        return response()->streamDownload(function () use ($xml) {
            echo $xml;
        }, $fileName, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    public function openRollbackPreview(): void
    {
        abort_unless($this->isAdmin(), 403);

        $this->rollbackMessage = '';
        $this->rollbackConfirmation = '';

        if ($this->userFilter === '') {
            $this->rollbackMessage = 'Selecciona una persona antes de preparar el rollback.';

            return;
        }

        $logs = $this->rollbackableLogs()->with(['user', 'branch'])->get();

        $this->rollbackSummary = [
            'total' => $logs->count(),
            'created' => $logs->where('event', 'created')->count(),
            'updated' => $logs->where('event', 'updated')->count(),
            'deleted' => $logs->where('event', 'deleted')->count(),
            'user' => $logs->first()?->user?->name ?? $this->company()->users()->whereKey($this->userFilter)->value('name') ?? 'Usuario seleccionado',
            'range' => $this->dateFrom.' - '.$this->dateTo,
        ];

        $this->rollbackPreviewRows = $logs
            ->take(35)
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'date' => $log->occurred_at?->format('d/m/Y H:i') ?? 'Sin fecha',
                'event' => $this->eventLabel($log->event),
                'module' => ucfirst($log->module),
                'description' => $log->description ?? 'Sin descripcion',
                'branch' => $log->branch?->name,
            ])
            ->values()
            ->all();

        $this->showRollbackModal = true;
    }

    public function closeRollbackPreview(): void
    {
        $this->showRollbackModal = false;
        $this->rollbackConfirmation = '';
    }

    public function confirmRollback(): void
    {
        abort_unless($this->isAdmin(), 403);

        if ($this->rollbackConfirmation !== 'REVERSAR') {
            $this->rollbackMessage = 'Escribe REVERSAR para confirmar.';

            return;
        }

        $logs = $this->rollbackableLogs()->get();

        if ($logs->isEmpty()) {
            $this->rollbackMessage = 'No hay movimientos reversibles con los filtros actuales.';
            $this->closeRollbackPreview();

            return;
        }

        $result = DB::transaction(function () use ($logs) {
            return $logs->reduce(function (array $carry, AuditLog $log) {
                $result = $this->rollbackLog($log);
                $carry[$result['status']]++;

                if ($result['status'] === 'skipped') {
                    $carry['reasons'][$result['reason']] = ($carry['reasons'][$result['reason']] ?? 0) + 1;
                }

                return $carry;
            }, ['reversed' => 0, 'skipped' => 0, 'reasons' => []]);
        });

        $this->rollbackMessage = 'Rollback aplicado: '.$result['reversed'].' revertidos, '.$result['skipped'].' omitidos.';

        if ($result['skipped'] > 0 && $result['reasons'] !== []) {
            $this->rollbackMessage .= ' Motivos: '.$this->rollbackReasonsText($result['reasons']).'.';
        }

        $this->closeRollbackPreview();
    }

    public function render()
    {
        abort_unless($this->isAdmin(), 403);

        $company = $this->company();

        return view('livewire.settings.audit-log-manager', [
            'logs' => $this->filteredLogs()
                ->with(['user', 'branch'])
                ->latest('occurred_at')
                ->limit(250)
                ->get(),
            'users' => $company->users()->orderBy('name')->get(),
            'modules' => $company->auditLogs()->select('module')->distinct()->orderBy('module')->pluck('module'),
            'events' => $company->auditLogs()->select('event')->distinct()->orderBy('event')->pluck('event'),
            'rollbackMessage' => $this->rollbackMessage,
        ]);
    }

    private function filteredLogs()
    {
        $company = $this->company();
        $from = $this->dateFrom ? Carbon::parse($this->dateFrom)->startOfDay() : now()->startOfMonth();
        $to = $this->dateTo ? Carbon::parse($this->dateTo)->endOfDay() : now()->endOfDay();
        $search = trim($this->search);

        return $company->auditLogs()
            ->when($this->userFilter !== '', fn ($query) => $query->where('user_id', $this->userFilter))
            ->when($this->moduleFilter !== '', fn ($query) => $query->where('module', $this->moduleFilter))
            ->when($this->eventFilter !== '', fn ($query) => $query->where('event', $this->eventFilter))
            ->whereBetween('occurred_at', [$from, $to])
            ->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested
                ->where('description', 'like', "%{$search}%")
                ->orWhere('module', 'like', "%{$search}%")
                ->orWhere('event', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"))));
    }

    private function rollbackableLogs()
    {
        return $this->filteredLogs()
            ->whereIn('event', self::ROLLBACK_EVENTS)
            ->whereNotNull('auditable_type')
            ->whereNotNull('auditable_id')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');
    }

    private function rollbackLog(AuditLog $log): array
    {
        $class = $log->auditable_type;

        if (! is_string($class) || ! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            return $this->rollbackResult('skipped', 'modelo no disponible');
        }

        if ($class === AuditLog::class || $class === Company::class) {
            return $this->rollbackResult('skipped', 'modelo protegido');
        }

        if ($class === InventoryProduct::class && $log->event === 'created') {
            return $this->rollbackCreatedInventoryProduct($log);
        }

        $model = new $class();
        $table = $model->getTable();
        $primaryKey = $model->getKeyName();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $primaryKey)) {
            return $this->rollbackResult('skipped', 'tabla no disponible');
        }

        if ($log->event === 'created') {
            $query = DB::table($table)->where($primaryKey, $log->auditable_id);

            if (! $query->exists()) {
                return $this->rollbackResult('skipped', 'registro ya no existe');
            }

            if (! $this->rawRecordBelongsToCompany($table, $primaryKey, $log)) {
                return $this->rollbackResult('skipped', 'registro de otra empresa');
            }

            try {
                $deleted = DB::table($table)->where($primaryKey, $log->auditable_id)->delete();
            } catch (Throwable) {
                return $this->rollbackResult('skipped', 'restriccion de base de datos');
            }

            return $deleted > 0
                ? $this->rollbackResult('reversed')
                : $this->rollbackResult('skipped', 'no se pudo eliminar');
        }

        if ($log->event === 'updated') {
            $oldValues = $this->safeAttributes($table, $log->old_values ?? []);

            if ($oldValues === []) {
                return $this->rollbackResult('skipped', 'sin valores anteriores');
            }

            if (! DB::table($table)->where($primaryKey, $log->auditable_id)->exists()) {
                return $this->rollbackResult('skipped', 'registro ya no existe');
            }

            if (! $this->rawRecordBelongsToCompany($table, $primaryKey, $log)) {
                return $this->rollbackResult('skipped', 'registro de otra empresa');
            }

            try {
                DB::table($table)->where($primaryKey, $log->auditable_id)->update($oldValues);
            } catch (Throwable) {
                return $this->rollbackResult('skipped', 'restriccion de base de datos');
            }

            return $this->rollbackResult('reversed');
        }

        if ($log->event === 'deleted') {
            $oldValues = $this->safeAttributes($table, $log->old_values ?? []);

            if ($oldValues === []) {
                return $this->rollbackResult('skipped', 'sin datos para restaurar');
            }

            if (DB::table($table)->where($primaryKey, $log->auditable_id)->exists()) {
                return $this->rollbackResult('skipped', 'registro ya existe');
            }

            try {
                DB::table($table)->insert($oldValues);
            } catch (Throwable) {
                return $this->rollbackResult('skipped', 'restriccion de base de datos');
            }

            return $this->rollbackResult('reversed');
        }

        return $this->rollbackResult('skipped', 'accion no reversible');
    }

    private function safeAttributes(string $table, array $attributes): array
    {
        $columns = Schema::getColumnListing($table);

        return collect($attributes)
            ->only($columns)
            ->all();
    }

    private function rawRecordBelongsToCompany(string $table, string $primaryKey, AuditLog $log): bool
    {
        if (Schema::hasColumn($table, 'company_id')) {
            return DB::table($table)
                ->where($primaryKey, $log->auditable_id)
                ->where('company_id', $log->company_id)
                ->exists();
        }

        if (Schema::hasColumn($table, 'branch_id') && $log->branch_id) {
            return DB::table($table)
                ->where($primaryKey, $log->auditable_id)
                ->where('branch_id', $log->branch_id)
                ->exists();
        }

        return true;
    }

    private function rollbackResult(string $status, string $reason = ''): array
    {
        return ['status' => $status, 'reason' => $reason];
    }

    private function rollbackReasonsText(array $reasons): string
    {
        return collect($reasons)
            ->map(fn (int $count, string $reason) => $reason.' ('.$count.')')
            ->implode(', ');
    }

    private function rollbackCreatedInventoryProduct(AuditLog $log): array
    {
        $productId = (int) $log->auditable_id;
        $productName = trim((string) data_get($log->new_values, 'name', ''));
        $changes = 0;

        if (! Schema::hasTable('inventory_products')) {
            return $this->rollbackResult('skipped', 'tabla de productos no disponible');
        }

        if (Schema::hasTable('product_sale_items') && Schema::hasTable('product_sales')) {
            $saleIds = DB::table('product_sale_items')
                ->where('inventory_product_id', $productId)
                ->pluck('product_sale_id')
                ->unique()
                ->values();

            if ($saleIds->isNotEmpty()) {
                $changes += DB::table('product_sales')
                    ->where('company_id', $log->company_id)
                    ->whereIn('id', $saleIds)
                    ->delete();
            }
        }

        $changes += $this->deleteProductClientCharges($log, $productId, $productName);
        $changes += $this->deleteProductTreatmentPaymentItems($log, $productId, $productName);

        foreach (['inventory_movements', 'inventory_count_items', 'inventory_product_batches'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'inventory_product_id')) {
                $changes += DB::table($table)
                    ->where('inventory_product_id', $productId)
                    ->when(Schema::hasColumn($table, 'company_id'), fn ($query) => $query->where('company_id', $log->company_id))
                    ->delete();
            }
        }

        try {
            $productDeleted = DB::table('inventory_products')
                ->where('id', $productId)
                ->where('company_id', $log->company_id)
                ->delete();
        } catch (Throwable) {
            return $changes > 0
                ? $this->rollbackResult('reversed')
                : $this->rollbackResult('skipped', 'restriccion de base de datos');
        }

        $changes += $productDeleted;

        if ($changes > 0) {
            return $this->rollbackResult('reversed');
        }

        return $this->rollbackResult('skipped', 'producto ya estaba eliminado sin registros relacionados');
    }

    private function deleteProductClientCharges(AuditLog $log, int $productId, string $productName): int
    {
        if (! Schema::hasTable('client_charges')) {
            return 0;
        }

        $chargeIds = DB::table('client_charges')
            ->where('company_id', $log->company_id)
            ->where('type', 'product')
            ->where(function ($query) use ($productId, $productName) {
                $query->where('inventory_product_id', $productId);

                if ($productName !== '') {
                    $query->orWhere(function ($query) use ($productName) {
                        $query->whereNull('inventory_product_id')
                            ->where('name', $productName);
                    });
                }
            })
            ->pluck('id');

        if ($chargeIds->isEmpty()) {
            return 0;
        }

        $changes = 0;

        if (Schema::hasTable('client_charge_payments')) {
            $changes += DB::table('client_charge_payments')
                ->whereIn('client_charge_id', $chargeIds)
                ->delete();
        }

        $changes += DB::table('client_charges')
            ->whereIn('id', $chargeIds)
            ->delete();

        return $changes;
    }

    private function deleteProductTreatmentPaymentItems(AuditLog $log, int $productId, string $productName): int
    {
        if (! Schema::hasTable('treatment_payment_items') || ! Schema::hasTable('treatment_payments')) {
            return 0;
        }

        $items = DB::table('treatment_payment_items')
            ->join('treatment_payments', 'treatment_payment_items.treatment_payment_id', '=', 'treatment_payments.id')
            ->where('treatment_payments.company_id', $log->company_id)
            ->where('treatment_payment_items.type', 'product')
            ->where(function ($query) use ($productId, $productName) {
                $query->where('treatment_payment_items.inventory_product_id', $productId);

                if ($productName !== '') {
                    $query->orWhere(function ($query) use ($productName) {
                        $query->whereNull('treatment_payment_items.inventory_product_id')
                            ->where('treatment_payment_items.name', $productName);
                    });
                }
            })
            ->get([
                'treatment_payment_items.id',
                'treatment_payment_items.treatment_payment_id',
                'treatment_payment_items.total',
            ]);

        if ($items->isEmpty()) {
            return 0;
        }

        $changes = DB::table('treatment_payment_items')
            ->whereIn('id', $items->pluck('id'))
            ->delete();

        $items
            ->groupBy('treatment_payment_id')
            ->each(function (Collection $paymentItems, int $paymentId) use (&$changes) {
                $remainingItemsTotal = (float) DB::table('treatment_payment_items')
                    ->where('treatment_payment_id', $paymentId)
                    ->sum('total');

                if ($remainingItemsTotal <= 0) {
                    $changes += DB::table('treatment_payments')->where('id', $paymentId)->delete();

                    return;
                }

                $oldAmount = (float) DB::table('treatment_payments')->where('id', $paymentId)->value('amount');
                DB::table('treatment_payments')->where('id', $paymentId)->update(['amount' => $remainingItemsTotal]);
                $this->rebalancePaymentSplits($paymentId, $oldAmount, $remainingItemsTotal);
                $changes++;
            });

        return $changes;
    }

    private function rebalancePaymentSplits(int $paymentId, float $oldAmount, float $newAmount): void
    {
        if (! Schema::hasTable('treatment_payment_splits') || $oldAmount <= 0) {
            return;
        }

        $splits = DB::table('treatment_payment_splits')
            ->where('treatment_payment_id', $paymentId)
            ->orderBy('id')
            ->get();

        if ($splits->isEmpty()) {
            return;
        }

        $ratio = $newAmount / $oldAmount;
        $runningTotal = 0.0;
        $lastSplitId = $splits->last()->id;

        foreach ($splits as $split) {
            $amount = $split->id === $lastSplitId
                ? round($newAmount - $runningTotal, 2)
                : round((float) $split->amount * $ratio, 2);

            $runningTotal += $amount;

            DB::table('treatment_payment_splits')
                ->where('id', $split->id)
                ->update(['amount' => max(0, $amount)]);
        }
    }

    private function buildExcelXml(Collection $logs): string
    {
        $sheets = $logs
            ->groupBy(fn (AuditLog $log) => $log->user?->name ?: 'Sistema')
            ->values()
            ->map(fn (Collection $userLogs, int $index) => $this->worksheetXml(($userLogs->first()?->user?->name ?: 'Sistema').' '.($index + 1), $userLogs->groupBy(fn (AuditLog $log) => $log->occurred_at?->format('Y-m-d') ?: 'Sin fecha')));

        if ($sheets->isEmpty()) {
            $sheets = collect([$this->worksheetXml('Sin registros', collect())]);
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<?mso-application progid="Excel.Sheet"?>'
            .'<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
            .'<Styles><Style ss:ID="header"><Font ss:Bold="1"/><Interior ss:Color="#E9F6F4" ss:Pattern="Solid"/></Style></Styles>'
            .$sheets->implode('')
            .'</Workbook>';
    }

    private function worksheetXml(string $name, Collection $groups): string
    {
        $sheetName = htmlspecialchars(mb_substr(preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', ' ', $name), 0, 31), ENT_XML1);
        $rows = '<Row>'
            .$this->cell('Fecha', 'header')
            .$this->cell('Hora', 'header')
            .$this->cell('Personal', 'header')
            .$this->cell('Modulo', 'header')
            .$this->cell('Accion', 'header')
            .$this->cell('Descripcion', 'header')
            .$this->cell('Sucursal', 'header')
            .$this->cell('IP', 'header')
            .'</Row>';

        foreach ($groups as $date => $logs) {
            $rows .= '<Row>'.$this->cell($date, 'header').'</Row>';

            foreach ($logs as $log) {
                $rows .= '<Row>'
                    .$this->cell($log->occurred_at?->format('d/m/Y') ?? '')
                    .$this->cell($log->occurred_at?->format('H:i:s') ?? '')
                    .$this->cell($log->user?->name ?? 'Sistema')
                    .$this->cell($log->module)
                    .$this->cell($this->eventLabel($log->event))
                    .$this->cell($log->description ?? '')
                    .$this->cell($log->branch?->name ?? '')
                    .$this->cell($log->ip_address ?? '')
                    .'</Row>';
            }
        }

        return '<Worksheet ss:Name="'.$sheetName.'"><Table>'.$rows.'</Table></Worksheet>';
    }

    private function cell(string $value, ?string $style = null): string
    {
        $styleAttribute = $style ? ' ss:StyleID="'.$style.'"' : '';

        return '<Cell'.$styleAttribute.'><Data ss:Type="String">'.htmlspecialchars($value, ENT_XML1).'</Data></Cell>';
    }

    private function eventLabel(string $event): string
    {
        return match ($event) {
            'created' => 'Creacion',
            'updated' => 'Edicion',
            'deleted' => 'Eliminacion',
            'login' => 'Ingreso',
            'logout' => 'Cierre de sesion',
            default => ucfirst($event),
        };
    }

    private function company(): Company
    {
        return Auth::user()->companies()->firstOrFail();
    }

    private function isAdmin(): bool
    {
        $user = Auth::user();
        $company = $user?->companies()->first();

        if (! $user || ! $company) {
            return false;
        }

        $companyRole = $user->companies()->where('companies.id', $company->id)->value('company_user.role');
        $branchAdmin = $user->branches()
            ->where('branches.company_id', $company->id)
            ->leftJoin('roles', 'roles.id', '=', 'branch_user.role_id')
            ->whereIn('roles.slug', RumikaAccess::ADMIN_ROLES)
            ->exists();

        return in_array($companyRole, RumikaAccess::ADMIN_ROLES, true)
            || $branchAdmin
            || $user->is_saas_admin;
    }
}
