<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditTrail
{
    public const IGNORED_ATTRIBUTES = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public static function record(string $event, ?Model $model = null, array $old = [], array $new = [], ?string $description = null, ?string $module = null, ?Company $company = null): void
    {
        if ($model instanceof AuditLog) {
            return;
        }

        $user = Auth::user();
        $companyId = $company?->id ?? self::companyId($model, $user);

        if (! $companyId) {
            return;
        }

        AuditLog::query()->create([
            'company_id' => $companyId,
            'branch_id' => self::branchId($model),
            'user_id' => $user?->id,
            'auditable_type' => $model ? $model::class : null,
            'auditable_id' => $model?->getKey(),
            'module' => $module ?? self::moduleFor($model),
            'event' => $event,
            'description' => $description ?? self::description($event, $model),
            'old_values' => self::clean($old),
            'new_values' => self::clean($new),
            'ip_address' => request()?->ip(),
            'user_agent' => Str::limit((string) request()?->userAgent(), 500, ''),
            'occurred_at' => now(),
        ]);
    }

    public static function logModelEvent(string $event, Model $model): void
    {
        if ($model instanceof AuditLog) {
            return;
        }

        $old = [];
        $new = [];

        if ($event === 'updated') {
            $changes = collect($model->getChanges())->except(['updated_at'])->all();

            if ($changes === []) {
                return;
            }

            $new = $changes;
            $old = collect(array_keys($changes))
                ->mapWithKeys(fn (string $key) => [$key => $model->getOriginal($key)])
                ->all();
        } elseif ($event === 'created') {
            $new = $model->getAttributes();
        } elseif ($event === 'deleted') {
            $old = $model->getOriginal();
        }

        self::record($event, $model, $old, $new);
    }

    public static function companyId(?Model $model, mixed $user = null): ?int
    {
        if ($model && isset($model->company_id)) {
            return (int) $model->company_id;
        }

        if ($model && method_exists($model, 'company')) {
            return $model->company?->id;
        }

        return $user?->companies()?->first()?->id;
    }

    private static function branchId(?Model $model): ?int
    {
        return $model && isset($model->branch_id) ? (int) $model->branch_id : null;
    }

    private static function clean(array $values): array
    {
        return collect($values)
            ->except(self::IGNORED_ATTRIBUTES)
            ->map(fn ($value) => is_string($value) && strlen($value) > 1000 ? Str::limit($value, 1000) : $value)
            ->all();
    }

    private static function description(string $event, ?Model $model): string
    {
        $action = match ($event) {
            'created' => 'Creo',
            'updated' => 'Actualizo',
            'deleted' => 'Elimino',
            'login' => 'Inicio sesion',
            'logout' => 'Cerro sesion',
            default => ucfirst($event),
        };

        return trim($action.' '.self::labelFor($model));
    }

    private static function labelFor(?Model $model): string
    {
        if (! $model) {
            return 'en el sistema';
        }

        $name = $model->name
            ?? $model->title
            ?? $model->full_name
            ?? $model->reference
            ?? $model->description
            ?? ('#'.$model->getKey());

        return self::moduleFor($model).' '.$name;
    }

    private static function moduleFor(?Model $model): string
    {
        if (! $model) {
            return 'sistema';
        }

        $class = class_basename($model);

        return match (true) {
            str_contains($class, 'Inventory') => 'inventario',
            str_contains($class, 'Clinical') => 'historia clinica',
            str_contains($class, 'Appointment') => 'agenda',
            str_contains($class, 'Payment') || str_contains($class, 'Cashbox') || str_contains($class, 'Charge') => 'caja',
            str_contains($class, 'Expense') => 'gastos',
            $class === 'Client' || $class === 'ClientPhone' => 'clientes',
            $class === 'Service' || str_contains($class, 'ServicePackage') => 'servicios',
            $class === 'Branch' || $class === 'Company' => 'sucursales',
            $class === 'User' || $class === 'Role' => 'usuarios',
            default => Str::headline($class),
        };
    }
}
