<?php

namespace App\Support;

use App\Models\Branch;
use Illuminate\Support\Facades\Auth;

class Money
{
    private static ?Branch $resolvedBranch = null;

    public static function symbol(?Branch $branch = null): string
    {
        $branch ??= self::activeBranch();

        return $branch?->moneySymbol() ?: 'Bs';
    }

    public static function format(float|int|string|null $amount, ?Branch $branch = null, int $decimals = 2): string
    {
        return self::symbol($branch).' '.number_format((float) ($amount ?? 0), $decimals);
    }

    public static function activeBranch(): ?Branch
    {
        if (self::$resolvedBranch) {
            return self::$resolvedBranch;
        }

        $user = Auth::user();

        if (! $user) {
            return null;
        }

        $company = $user->companies()->first();

        if (! $company) {
            return null;
        }

        $branches = $user->branches()
            ->where('company_id', $company->id)
            ->get();

        if ($branches->isEmpty()) {
            $branches = $company->branches()->get();
        }

        self::$resolvedBranch = ActiveBranch::resolve($user, $branches);

        return self::$resolvedBranch;
    }
}
