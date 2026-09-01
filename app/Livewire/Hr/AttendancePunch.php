<?php

namespace App\Livewire\Hr;

use App\Models\Branch;
use App\Models\Company;
use App\Models\StaffAttendanceRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class AttendancePunch extends Component
{
    private const MIN_SIMILARITY = 65;

    public bool $showModal = false;

    public ?string $message = null;

    public ?int $lastSimilarity = null;

    public ?int $lastDistance = null;

    public ?string $lastBranchName = null;

    public function openPunch(): void
    {
        $this->resetErrorBag();
        $this->message = null;
        $this->showModal = true;
    }

    public function closePunch(): void
    {
        $this->showModal = false;
    }

    public function submitPunch(string $descriptorJson, ?string $captureImage, ?float $latitude, ?float $longitude): void
    {
        if ($latitude === null || $longitude === null) {
            throw ValidationException::withMessages([
                'attendance' => 'Activa la ubicacion del navegador para registrar asistencia.',
            ]);
        }

        $user = Auth::user();
        $company = $this->company();

        if (! $user?->face_descriptor) {
            throw ValidationException::withMessages([
                'attendance' => 'Primero registra el rostro de este usuario en el ingreso facial.',
            ]);
        }

        $descriptor = $this->parseDescriptor($descriptorJson);
        $similarity = $this->similarity($descriptor, $user->face_descriptor);
        $this->lastSimilarity = $similarity;

        if ($similarity < self::MIN_SIMILARITY) {
            throw ValidationException::withMessages([
                'attendance' => "No se pudo validar el rostro. Parecido detectado: {$similarity}%.",
            ]);
        }

        $openRecord = StaffAttendanceRecord::query()
            ->where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('check_in_at')
            ->first();

        $branchMatch = $this->matchingBranch($company, $latitude, $longitude, $openRecord === null);
        $imagePath = $this->storeFaceCapture($captureImage);

        if ($openRecord) {
            $openRecord->update([
                'status' => 'completed',
                'check_out_at' => now(),
                'check_out_branch_id' => $branchMatch['branch']->id,
                'check_out_latitude' => $latitude,
                'check_out_longitude' => $longitude,
                'check_out_distance_meters' => $branchMatch['distance'],
                'check_out_face_similarity' => $similarity,
                'check_out_photo_path' => $imagePath,
            ]);

            $this->message = 'Salida registrada correctamente.';
        } else {
            StaffAttendanceRecord::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'user_id' => $user->id,
                    'work_date' => now()->toDateString(),
                ],
                [
                    'status' => 'open',
                    'check_in_at' => now(),
                    'check_in_branch_id' => $branchMatch['branch']->id,
                    'check_in_latitude' => $latitude,
                    'check_in_longitude' => $longitude,
                    'check_in_distance_meters' => $branchMatch['distance'],
                    'check_in_face_similarity' => $similarity,
                    'check_in_photo_path' => $imagePath,
                ],
            );

            $this->message = 'Entrada registrada correctamente.';
        }

        $this->lastDistance = $branchMatch['distance'];
        $this->lastBranchName = $branchMatch['branch']->name;
        $this->dispatch('attendance-saved');
    }

    public function render()
    {
        $company = $this->company();
        $todayRecord = StaffAttendanceRecord::query()
            ->where('company_id', $company->id)
            ->where('user_id', Auth::id())
            ->whereDate('work_date', now()->toDateString())
            ->latest('id')
            ->first();

        $openRecord = StaffAttendanceRecord::query()
            ->where('company_id', $company->id)
            ->where('user_id', Auth::id())
            ->where('status', 'open')
            ->latest('check_in_at')
            ->first();

        return view('livewire.hr.attendance-punch', [
            'todayRecord' => $todayRecord,
            'openRecord' => $openRecord,
            'hasFace' => filled(Auth::user()?->face_descriptor),
            'hasGeofence' => $company->branches()
                ->where('status', 'active')
                ->whereNotNull('attendance_latitude')
                ->whereNotNull('attendance_longitude')
                ->exists(),
        ]);
    }

    private function company(): Company
    {
        return Auth::user()->companies()->firstOrFail();
    }

    private function matchingBranch(Company $company, float $latitude, float $longitude, bool $checkIn): array
    {
        $query = $company->branches()
            ->where('status', 'active')
            ->whereNotNull('attendance_latitude')
            ->whereNotNull('attendance_longitude');

        if ($checkIn) {
            $assignedBranchIds = Auth::user()
                ->branches()
                ->where('branches.company_id', $company->id)
                ->pluck('branches.id')
                ->all();

            if ($assignedBranchIds !== []) {
                $query->whereIn('id', $assignedBranchIds);
            }
        }

        $matches = $query->get()
            ->map(function (Branch $branch) use ($latitude, $longitude) {
                $distance = $this->distanceMeters(
                    $latitude,
                    $longitude,
                    (float) $branch->attendance_latitude,
                    (float) $branch->attendance_longitude,
                );

                return [
                    'branch' => $branch,
                    'distance' => $distance,
                    'allowed' => $distance <= (int) ($branch->attendance_radius_meters ?: 120),
                ];
            })
            ->sortBy('distance')
            ->values();

        $match = $matches->firstWhere('allowed', true);

        if (! $match) {
            throw ValidationException::withMessages([
                'attendance' => 'Estas fuera del radio permitido de las sucursales configuradas.',
            ]);
        }

        return $match;
    }

    private function parseDescriptor(string $descriptorJson): array
    {
        $descriptor = json_decode($descriptorJson, true);

        if (! is_array($descriptor) || count($descriptor) < 64) {
            throw ValidationException::withMessages([
                'attendance' => 'No se pudo leer correctamente el rostro. Intenta con mejor luz.',
            ]);
        }

        return array_map(static fn ($value) => (float) $value, $descriptor);
    }

    private function similarity(array $current, array $stored): int
    {
        $length = min(count($current), count($stored));
        $sum = 0.0;

        for ($index = 0; $index < $length; $index++) {
            $difference = (float) $current[$index] - (float) $stored[$index];
            $sum += $difference * $difference;
        }

        return max(0, min(100, (int) round(100 - (sqrt($sum) * 50))));
    }

    private function distanceMeters(float $fromLat, float $fromLng, float $toLat, float $toLng): int
    {
        $earthRadius = 6371000;
        $latFrom = deg2rad($fromLat);
        $latTo = deg2rad($toLat);
        $deltaLat = deg2rad($toLat - $fromLat);
        $deltaLng = deg2rad($toLng - $fromLng);

        $a = sin($deltaLat / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($deltaLng / 2) ** 2;

        return (int) round($earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    private function storeFaceCapture(?string $captureImage): ?string
    {
        if (! $captureImage || ! str_contains($captureImage, ',')) {
            return null;
        }

        [$metadata, $payload] = explode(',', $captureImage, 2);

        if (! str_contains($metadata, 'image/jpeg')) {
            return null;
        }

        $binary = base64_decode($payload, true);

        if ($binary === false || strlen($binary) < 1000) {
            return null;
        }

        $path = 'staff-attendance/'.now()->format('Y/m').'/'.Auth::id().'-'.Str::uuid().'.jpg';

        Storage::disk('public')->put($path, $binary);

        return $path;
    }
}
