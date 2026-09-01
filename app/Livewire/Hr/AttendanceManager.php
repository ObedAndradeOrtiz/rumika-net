<?php

namespace App\Livewire\Hr;

use App\Models\Company;
use App\Models\StaffAttendanceRecord;
use App\Models\StaffSchedule;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AttendanceManager extends Component
{
    public string $fromDate = '';

    public string $toDate = '';

    public string $userFilter = '';

    public string $activeTab = 'users';

    public ?int $scheduleUserId = null;

    public array $scheduleForm = [];

    public function mount(): void
    {
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();

        $company = $this->company();
        $this->scheduleUserId = $company->users()
            ->where('tracks_attendance', true)
            ->orderBy('name')
            ->value('users.id');
        $this->loadScheduleForm();
    }

    public function updatedScheduleUserId(): void
    {
        $this->loadScheduleForm();
    }

    public function setActiveTab(string $tab): void
    {
        if (! in_array($tab, ['users', 'schedule', 'history'], true)) {
            return;
        }

        $this->activeTab = $tab;
    }

    public function editScheduleFor(int $userId): void
    {
        $company = $this->company();

        if (! $company->users()
            ->where('users.id', $userId)
            ->where('tracks_attendance', true)
            ->exists()) {
            return;
        }

        $this->scheduleUserId = $userId;
        $this->activeTab = 'schedule';
        $this->loadScheduleForm();
    }

    public function saveSchedule(): void
    {
        $company = $this->company();
        $userIds = $company->users()
            ->where('tracks_attendance', true)
            ->pluck('users.id')
            ->all();
        $branchIds = $company->branches()->pluck('id')->all();

        $validated = $this->validate([
            'scheduleUserId' => ['required', Rule::in($userIds)],
            'scheduleForm' => ['array'],
            'scheduleForm.*.is_working_day' => ['boolean'],
            'scheduleForm.*.starts_at' => ['nullable', 'date_format:H:i'],
            'scheduleForm.*.ends_at' => ['nullable', 'date_format:H:i'],
            'scheduleForm.*.branch_id' => ['nullable', Rule::in($branchIds)],
            'scheduleForm.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($validated['scheduleForm'] as $weekday => $row) {
            StaffSchedule::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'user_id' => (int) $validated['scheduleUserId'],
                    'weekday' => (int) $weekday,
                ],
                [
                    'branch_id' => $row['branch_id'] ? (int) $row['branch_id'] : null,
                    'is_working_day' => (bool) ($row['is_working_day'] ?? false),
                    'starts_at' => $row['is_working_day'] ? ($row['starts_at'] ?: null) : null,
                    'ends_at' => $row['is_working_day'] ? ($row['ends_at'] ?: null) : null,
                    'notes' => $row['notes'] ?: null,
                ],
            );
        }

        $this->dispatch('schedule-saved');
    }

    public function resetDateFilter(): void
    {
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
    }

    public function deleteRecord(int $recordId): void
    {
        $company = $this->company();
        $record = StaffAttendanceRecord::query()
            ->where('company_id', $company->id)
            ->whereKey($recordId)
            ->firstOrFail();

        collect([$record->check_in_photo_path, $record->check_out_photo_path])
            ->filter()
            ->each(fn (string $path) => Storage::disk('public')->delete($path));

        $record->delete();
        $this->dispatch('attendance-record-deleted');
    }

    public function render()
    {
        $company = $this->company();
        $from = Carbon::parse($this->fromDate)->startOfDay();
        $to = Carbon::parse($this->toDate)->endOfDay();

        $users = $company->users()
            ->where('tracks_attendance', true)
            ->orderBy('name')
            ->get();

        if ($this->userFilter !== '' && ! $users->contains('id', (int) $this->userFilter)) {
            $this->userFilter = '';
        }

        if ($this->scheduleUserId && ! $users->contains('id', $this->scheduleUserId)) {
            $this->scheduleUserId = $users->first()?->id;
            $this->loadScheduleForm();
        }

        $records = StaffAttendanceRecord::query()
            ->with(['user', 'checkInBranch', 'checkOutBranch'])
            ->where('company_id', $company->id)
            ->whereIn('user_id', $users->pluck('id'))
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->when($this->userFilter !== '', fn ($query) => $query->where('user_id', (int) $this->userFilter))
            ->latest('work_date')
            ->latest('check_in_at')
            ->get();

        $filteredUsers = $this->userFilter !== ''
            ? $users->where('id', (int) $this->userFilter)->values()
            : $users;

        $schedules = StaffSchedule::query()
            ->where('company_id', $company->id)
            ->whereIn('user_id', $filteredUsers->pluck('id'))
            ->get()
            ->groupBy('user_id');

        $rows = $this->attendanceRows($filteredUsers, $records, $schedules, $from, $to);

        return view('livewire.hr.attendance-manager', [
            'users' => $users,
            'branches' => $company->branches()->where('status', 'active')->orderBy('name')->get(),
            'records' => $records,
            'rows' => $rows,
            'summary' => [
                'present' => $records->whereNotNull('check_in_at')->count(),
                'completed' => $records->where('status', 'completed')->count(),
                'open' => $records->where('status', 'open')->count(),
                'missing' => $rows->sum('missing_count'),
            ],
            'weekdays' => $this->weekdays(),
        ]);
    }

    private function company(): Company
    {
        return Auth::user()->companies()->firstOrFail();
    }

    private function loadScheduleForm(): void
    {
        $company = $this->company();
        $existing = $this->scheduleUserId
            ? StaffSchedule::query()
                ->where('company_id', $company->id)
                ->where('user_id', $this->scheduleUserId)
                ->get()
                ->keyBy('weekday')
            : collect();

        $this->scheduleForm = collect($this->weekdays())
            ->mapWithKeys(function (string $label, int $weekday) use ($existing) {
                $row = $existing->get($weekday);

                return [
                    $weekday => [
                        'is_working_day' => $row ? (bool) $row->is_working_day : $weekday !== 7,
                        'starts_at' => $row?->starts_at ? substr((string) $row->starts_at, 0, 5) : '08:30',
                        'ends_at' => $row?->ends_at ? substr((string) $row->ends_at, 0, 5) : '18:30',
                        'branch_id' => $row?->branch_id ? (string) $row->branch_id : '',
                        'notes' => $row?->notes ?? '',
                    ],
                ];
            })
            ->all();
    }

    private function attendanceRows(Collection $users, Collection $records, Collection $schedules, Carbon $from, Carbon $to): Collection
    {
        $today = now()->startOfDay();

        return $users->map(function (User $user) use ($records, $schedules, $from, $to, $today) {
            $userRecords = $records->where('user_id', $user->id);
            $userSchedules = $schedules->get($user->id, collect())->keyBy('weekday');
            $expectedDates = collect(CarbonPeriod::create($from, $to->min($today)))
                ->filter(fn (Carbon $date) => $this->isExpectedWorkday($date, $userSchedules));
            $recordedDates = $userRecords->pluck('work_date')->map(fn ($date) => $date->format('Y-m-d'))->unique();
            $missingDates = $expectedDates
                ->map(fn (Carbon $date) => $date->format('Y-m-d'))
                ->reject(fn (string $date) => $recordedDates->contains($date))
                ->values();

            return [
                'user' => $user,
                'present_count' => $userRecords->whereNotNull('check_in_at')->count(),
                'completed_count' => $userRecords->where('status', 'completed')->count(),
                'open_count' => $userRecords->where('status', 'open')->count(),
                'missing_count' => $missingDates->count(),
                'missing_dates' => $missingDates->map(fn (string $date) => Carbon::parse($date)->format('d/m'))->take(8)->implode(', '),
                'last_record' => $userRecords->sortByDesc('work_date')->first(),
                'avatar_url' => $this->attendanceAvatarUrl($user, $userRecords),
            ];
        });
    }

    private function attendanceAvatarUrl(User $user, Collection $records): ?string
    {
        $record = $records
            ->sortByDesc(fn (StaffAttendanceRecord $record) => $record->check_out_at ?? $record->check_in_at ?? $record->created_at)
            ->first();

        $path = $record?->check_out_photo_path
            ?: $record?->check_in_photo_path
            ?: $user->profile_photo_path;

        if (! $path) {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://'])
            ? $path
            : Storage::url($path);
    }

    private function isExpectedWorkday(Carbon $date, Collection $schedules): bool
    {
        $weekday = (int) $date->isoWeekday();

        if ($weekday === 7) {
            return false;
        }

        $schedule = $schedules->get($weekday);

        return $schedule ? (bool) $schedule->is_working_day : true;
    }

    private function weekdays(): array
    {
        return [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miercoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sabado',
            7 => 'Domingo',
        ];
    }
}
