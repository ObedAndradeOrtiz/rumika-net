<?php

namespace App\Livewire\Security;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class FaceVerificationManager extends Component
{
    private const MIN_SIMILARITY = 75;

    public string $mode = 'verify';

    public bool $hasDescriptor = false;

    public ?int $lastSimilarity = null;

    public function mount(): void
    {
        $user = Auth::user();
        $this->hasDescriptor = filled($user?->face_descriptor);
        $requestedMode = request()->query('mode');
        $this->mode = $requestedMode === 'enroll' || ! $this->hasDescriptor ? 'enroll' : 'verify';
    }

    public function saveDescriptor(string $descriptorJson)
    {
        $descriptor = $this->parseDescriptor($descriptorJson);
        $user = Auth::user();

        $user->forceFill([
            'face_descriptor' => $descriptor,
            'face_registered_at' => now(),
            'last_face_verified_at' => now(),
            'last_face_verified_ip' => request()->ip(),
        ])->save();

        session([
            'face_verified_user_id' => $user->id,
            'face_verified_ip' => request()->ip(),
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function verifyDescriptor(string $descriptorJson)
    {
        $user = Auth::user();

        if (! $user?->face_descriptor) {
            $this->mode = 'enroll';
            throw ValidationException::withMessages([
                'face' => 'Primero registra el rostro de este usuario.',
            ]);
        }

        $descriptor = $this->parseDescriptor($descriptorJson);
        $distance = $this->distance($descriptor, $user->face_descriptor);
        $similarity = max(0, min(100, (int) round(100 - ($distance * 50))));
        $this->lastSimilarity = $similarity;

        if ($similarity < self::MIN_SIMILARITY) {
            throw ValidationException::withMessages([
                'face' => "No se pudo validar el rostro. Parecido detectado: {$similarity}%.",
            ]);
        }

        $user->forceFill([
            'last_face_verified_at' => now(),
            'last_face_verified_ip' => request()->ip(),
        ])->save();

        session([
            'face_verified_user_id' => $user->id,
            'face_verified_ip' => request()->ip(),
        ]);

        return redirect()->intended(route('dashboard'));
    }

    private function parseDescriptor(string $descriptorJson): array
    {
        $descriptor = json_decode($descriptorJson, true);

        if (! is_array($descriptor) || count($descriptor) < 64) {
            throw ValidationException::withMessages([
                'face' => 'No se pudo leer correctamente el rostro. Intenta con mejor luz.',
            ]);
        }

        return array_map(static fn ($value) => (float) $value, $descriptor);
    }

    private function distance(array $current, array $stored): float
    {
        $length = min(count($current), count($stored));
        $sum = 0.0;

        for ($index = 0; $index < $length; $index++) {
            $difference = (float) $current[$index] - (float) $stored[$index];
            $sum += $difference * $difference;
        }

        return sqrt($sum);
    }

    public function render()
    {
        return view('livewire.security.face-verification-manager')
            ->layout('layouts.guest');
    }
}
