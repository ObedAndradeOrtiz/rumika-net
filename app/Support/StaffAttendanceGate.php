<?php

namespace App\Support;

use App\Models\Company;
use App\Models\StaffAttendanceExemption;
use App\Models\StaffAttendanceRecord;
use App\Models\StaffSchedule;
use App\Models\User;
use Carbon\Carbon;

class StaffAttendanceGate
{
    public static function requiresOpenAttendance(User $user, ?Company $company = null, ?Carbon $now = null): bool
    {
        if (! $user->tracks_attendance) {
            return false;
        }

        $company ??= $user->companies()->first();
        $now ??= now();

        if (
            ! $company
            || self::isAdminUser($user, $company)
            || ! self::hasConfiguredGeofence($company)
            || self::blocksOutsideSchedule($user, $company, $now)
            || ! self::isExpectedWorkday($user, $company, $now)
        ) {
            return false;
        }

        return ! self::hasOpenAttendance($user, $company, $now);
    }

    public static function blocksOutsideSchedule(User $user, ?Company $company = null, ?Carbon $now = null): bool
    {
        if (! $user->tracks_attendance || $user->can_use_system_outside_schedule) {
            return false;
        }

        $company ??= $user->companies()->first();
        $now ??= now();

        if (! $company || self::isAdminUser($user, $company) || ! self::hasConfiguredGeofence($company)) {
            return false;
        }

        return ! self::isExpectedWorkday($user, $company, $now);
    }

    public static function hasOpenAttendance(User $user, Company $company, ?Carbon $now = null): bool
    {
        $now ??= now();

        return StaffAttendanceRecord::query()
            ->where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->whereDate('work_date', $now->toDateString())
            ->where('status', 'open')
            ->exists();
    }

    public static function hasConfiguredGeofence(Company $company): bool
    {
        return $company->branches()
            ->where('status', 'active')
            ->whereNotNull('attendance_latitude')
            ->whereNotNull('attendance_longitude')
            ->exists();
    }

    public static function isAdminUser(User $user, Company $company): bool
    {
        $companyRole = $user->companies()
            ->where('companies.id', $company->id)
            ->value('company_user.role');

        if (in_array($companyRole, RumikaAccess::ADMIN_ROLES, true)) {
            return true;
        }

        return $user->branches()
            ->where('branches.company_id', $company->id)
            ->leftJoin('roles', 'roles.id', '=', 'branch_user.role_id')
            ->whereIn('roles.slug', RumikaAccess::ADMIN_ROLES)
            ->exists();
    }

    public static function isExpectedWorkday(User $user, Company $company, Carbon $now): bool
    {
        $weekday = (int) $now->isoWeekday();

        if ($weekday === 7) {
            return false;
        }

        $schedule = StaffSchedule::query()
            ->where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->where('weekday', $weekday)
            ->first();

        if (! $schedule) {
            return ! self::hasExemption($user, $company, $now, null);
        }

        if (! $schedule->is_working_day) {
            return false;
        }

        if (self::hasExemption($user, $company, $now, $schedule)) {
            return false;
        }

        if (! $schedule->starts_at || ! $schedule->ends_at) {
            return true;
        }

        $startsAt = Carbon::parse($now->toDateString().' '.substr((string) $schedule->starts_at, 0, 5))->subMinutes(45);
        $endsAt = Carbon::parse($now->toDateString().' '.substr((string) $schedule->ends_at, 0, 5))->addMinutes(45);

        return $now->between($startsAt, $endsAt);
    }

    private static function hasExemption(User $user, Company $company, Carbon $date, ?StaffSchedule $schedule): bool
    {
        $userBranchIds = $user->branches()
            ->where('branches.company_id', $company->id)
            ->pluck('branches.id');

        return StaffAttendanceExemption::query()
            ->where('company_id', $company->id)
            ->whereDate('work_date', $date->toDateString())
            ->where(function ($query) use ($user) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $user->id);
            })
            ->where(function ($query) use ($schedule, $userBranchIds) {
                $query->whereNull('branch_id')
                    ->when($schedule?->branch_id, fn ($inner) => $inner->orWhere('branch_id', $schedule->branch_id))
                    ->when($userBranchIds->isNotEmpty(), fn ($inner) => $inner->orWhereIn('branch_id', $userBranchIds));
            })
            ->exists();
    }
}
