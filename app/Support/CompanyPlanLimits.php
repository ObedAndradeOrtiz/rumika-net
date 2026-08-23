<?php

namespace App\Support;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class CompanyPlanLimits
{
    public static function features(Company $company): array
    {
        $features = $company->plan?->features ?? CompanyPlanCatalog::forSlug('free')['features'];

        if (is_string($features)) {
            $features = json_decode($features, true);
        }

        return is_array($features) ? $features : [];
    }

    public static function limit(Company $company, string $key): ?int
    {
        $value = self::features($company)['limits'][$key] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    public static function usage(Company $company): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        return [
            'branches' => $company->branches()->count(),
            'users' => $company->users()->count(),
            'clients' => $company->clients()->count(),
            'products' => $company->inventoryProducts()->count(),
            'appointments_per_month' => $company->appointments()
                ->whereBetween('scheduled_at', [$start, $end])
                ->count(),
        ];
    }

    public static function assertCanCreate(Company $company, string $limitKey, string $label, int $increment = 1): void
    {
        $limit = self::limit($company, $limitKey);

        if ($limit === null) {
            return;
        }

        if ($limit <= 0) {
            throw ValidationException::withMessages([
                $limitKey => "Tu plan actual no incluye {$label}.",
            ]);
        }

        $usage = self::usage($company)[$limitKey] ?? 0;

        if ($usage + $increment > $limit) {
            throw ValidationException::withMessages([
                $limitKey => "Tu plan permite {$limit} {$label}. Ya llegaste al limite.",
            ]);
        }
    }

    public static function isExpired(Company $company): bool
    {
        $now = now();

        return ($company->status === 'trial' && $company->trial_ends_at && $company->trial_ends_at->lt($now))
            || (in_array($company->status, ['active', 'past_due'], true) && $company->access_expires_at && $company->access_expires_at->lt($now))
            || in_array($company->status, ['blocked', 'suspended'], true);
    }

    public static function daysLeft(Company $company): ?int
    {
        $until = $company->status === 'trial' ? $company->trial_ends_at : $company->access_expires_at;

        return $until ? Carbon::parse($until)->startOfDay()->diffInDays(now()->startOfDay(), false) * -1 : null;
    }
}
