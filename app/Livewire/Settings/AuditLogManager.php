<?php

namespace App\Livewire\Settings;

use App\Models\AuditLog;
use App\Models\Company;
use App\Support\RumikaAccess;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

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
                $status = $this->rollbackLog($log);
                $carry[$status]++;

                return $carry;
            }, ['reversed' => 0, 'skipped' => 0]);
        });

        $this->rollbackMessage = 'Rollback aplicado: '.$result['reversed'].' revertidos, '.$result['skipped'].' omitidos.';
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

    private function rollbackLog(AuditLog $log): string
    {
        $class = $log->auditable_type;

        if (! is_string($class) || ! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            return 'skipped';
        }

        if ($class === AuditLog::class || $class === Company::class) {
            return 'skipped';
        }

        if ($log->event === 'created') {
            $record = $class::query()->whereKey($log->auditable_id)->first();

            if (! $record || ! $this->recordBelongsToCompany($record, $log)) {
                return 'skipped';
            }

            $record->delete();

            return 'reversed';
        }

        if ($log->event === 'updated') {
            $record = $class::query()->whereKey($log->auditable_id)->first();
            $oldValues = $this->safeAttributes($log->old_values ?? []);

            if (! $record || $oldValues === [] || ! $this->recordBelongsToCompany($record, $log)) {
                return 'skipped';
            }

            $record->forceFill($oldValues)->save();

            return 'reversed';
        }

        if ($log->event === 'deleted') {
            $oldValues = $this->safeAttributes($log->old_values ?? []);

            if ($oldValues === [] || $class::query()->whereKey($log->auditable_id)->exists()) {
                return 'skipped';
            }

            $record = new $class();
            $record->forceFill($oldValues);
            $record->save();

            return 'reversed';
        }

        return 'skipped';
    }

    private function safeAttributes(array $attributes): array
    {
        return collect($attributes)
            ->except(['created_at', 'updated_at'])
            ->all();
    }

    private function recordBelongsToCompany(Model $record, AuditLog $log): bool
    {
        if (isset($record->company_id)) {
            return (int) $record->company_id === (int) $log->company_id;
        }

        return true;
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
