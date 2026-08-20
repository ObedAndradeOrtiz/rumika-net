<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Cookie;

class ActiveBranch
{
    public static function cookieName(User $user): string
    {
        return 'rumika_active_branch_'.$user->id;
    }

    public static function remember(User $user, int $branchId): void
    {
        session(['active_branch_id' => $branchId]);
        Cookie::queue(self::cookieName($user), (string) $branchId, 60 * 24 * 365);
    }

    public static function resolve(User $user, $branches): ?Branch
    {
        $sessionBranchId = session('active_branch_id');
        $rememberedBranchId = request()->cookie(self::cookieName($user));

        $activeBranch = $sessionBranchId
            ? $branches->firstWhere('id', (int) $sessionBranchId)
            : null;

        $activeBranch ??= $rememberedBranchId
            ? $branches->firstWhere('id', (int) $rememberedBranchId)
            : null;

        $activeBranch ??= $branches->first();

        if ($activeBranch) {
            self::remember($user, $activeBranch->id);
        }

        return $activeBranch;
    }
}
