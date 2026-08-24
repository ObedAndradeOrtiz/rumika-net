<?php

namespace App\Livewire\Security;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class FaceVerificationManager extends Component
{
    private const MIN_SIMILARITY = 65;

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

    public function saveDescriptor(string $descriptorJson, ?string $captureImage = null)
    {
        $descriptor = $this->parseDescriptor($descriptorJson);
        $user = Auth::user();
        $imagePath = $this->storeFaceCapture($captureImage);

        $user->forceFill([
            'face_descriptor' => $descriptor,
            'face_registered_at' => now(),
            'last_face_verified_at' => now(),
            'last_face_verified_ip' => request()->ip(),
        ])->save();

        $this->recordFaceAttempt('enroll', true, 100, $imagePath);

        session([
            'face_verified_user_id' => $user->id,
            'face_verified_ip' => request()->ip(),
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function verifyDescriptor(string $descriptorJson, ?string $captureImage = null)
    {
        $user = Auth::user();
        $imagePath = $this->storeFaceCapture($captureImage);

        if (! $user?->face_descriptor) {
            $this->mode = 'enroll';
            $this->recordFaceAttempt('verify', false, null, $imagePath);

            throw ValidationException::withMessages([
                'face' => 'Primero registra el rostro de este usuario.',
            ]);
        }

        $descriptor = $this->parseDescriptor($descriptorJson);
        $distance = $this->distance($descriptor, $user->face_descriptor);
        $similarity = max(0, min(100, (int) round(100 - ($distance * 50))));
        $this->lastSimilarity = $similarity;

        if ($similarity < self::MIN_SIMILARITY) {
            $this->recordFaceAttempt('verify', false, $similarity, $imagePath);

            throw ValidationException::withMessages([
                'face' => "No se pudo validar el rostro. Parecido detectado: {$similarity}%.",
            ]);
        }

        $this->recordFaceAttempt('verify', true, $similarity, $imagePath);

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

    public function recordFailedAttempt(?string $captureImage = null): void
    {
        $imagePath = $this->storeFaceCapture($captureImage);
        $this->recordFaceAttempt($this->mode, false, null, $imagePath);

        throw ValidationException::withMessages([
            'face' => 'No se encontro un rostro claro. Mejora la luz e intenta otra vez.',
        ]);
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

        $path = 'face-verifications/'.now()->format('Y/m').'/'.Auth::id().'-'.Str::uuid().'.jpg';

        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    private function recordFaceAttempt(string $mode, bool $successful, ?int $similarity, ?string $imagePath): void
    {
        DB::table('face_verification_logs')->insert([
            'user_id' => Auth::id(),
            'mode' => $mode,
            'similarity' => $similarity,
            'successful' => $successful,
            'ip_address' => request()->ip(),
            'image_path' => $imagePath,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function render()
    {
        return view('livewire.security.face-verification-manager')
            ->layout('layouts.guest');
    }
}
