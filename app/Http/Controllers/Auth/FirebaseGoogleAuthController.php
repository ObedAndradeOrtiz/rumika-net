<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyPlan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FirebaseGoogleAuthController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        $payload = $this->verifyToken($validated['id_token']);

        if (($payload['firebase']['sign_in_provider'] ?? null) !== 'google.com') {
            throw ValidationException::withMessages([
                'google' => 'Solo se permite iniciar sesion con Google.',
            ]);
        }

        if (empty($payload['email']) || empty($payload['email_verified'])) {
            throw ValidationException::withMessages([
                'google' => 'Google no devolvio un correo verificado.',
            ]);
        }

        $user = DB::transaction(fn () => $this->findOrCreateUser($payload));

        if (($user->status ?? 'active') !== 'active') {
            throw ValidationException::withMessages([
                'google' => 'Tu usuario esta inactivo. Comunicate con administracion para mayor informacion.',
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return response()->json([
            'redirect' => $user->companies()->first()?->onboarding_completed_at
                ? route('dashboard', absolute: false)
                : route('onboarding.company', absolute: false),
        ]);
    }

    private function verifyToken(string $token): array
    {
        [$headerEncoded, $payloadEncoded, $signatureEncoded] = array_pad(explode('.', $token), 3, null);

        if (! $headerEncoded || ! $payloadEncoded || ! $signatureEncoded) {
            throw ValidationException::withMessages(['google' => 'Token de Google invalido.']);
        }

        $header = json_decode($this->base64UrlDecode($headerEncoded), true);
        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);
        $signature = $this->base64UrlDecode($signatureEncoded);

        if (! is_array($header) || ! is_array($payload) || empty($header['kid'])) {
            throw ValidationException::withMessages(['google' => 'Token de Google incompleto.']);
        }

        $projectId = config('services.firebase.project_id');
        $issuer = "https://securetoken.google.com/{$projectId}";

        if (($payload['aud'] ?? null) !== $projectId || ($payload['iss'] ?? null) !== $issuer) {
            throw ValidationException::withMessages(['google' => 'Este acceso no pertenece al proyecto Rumika.']);
        }

        if (($payload['exp'] ?? 0) < time()) {
            throw ValidationException::withMessages(['google' => 'El acceso de Google expiro. Intenta de nuevo.']);
        }

        $certificates = Cache::remember('firebase.auth.certificates', now()->addHours(6), fn () => Http::timeout(10)
            ->get('https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com')
            ->throw()
            ->json());

        $certificate = $certificates[$header['kid']] ?? null;

        if (! $certificate || openssl_verify("{$headerEncoded}.{$payloadEncoded}", $signature, $certificate, OPENSSL_ALGO_SHA256) !== 1) {
            throw ValidationException::withMessages(['google' => 'No se pudo validar la firma de Google.']);
        }

        return $payload;
    }

    private function findOrCreateUser(array $payload): User
    {
        $user = User::query()
            ->where('firebase_uid', $payload['sub'])
            ->orWhere('email', Str::lower($payload['email']))
            ->first();

        if ($user) {
            $user->forceFill([
                'firebase_uid' => $user->firebase_uid ?: $payload['sub'],
                'auth_provider' => 'google',
                'email_verified_at' => $user->email_verified_at ?: now(),
                'profile_photo_path' => $user->profile_photo_path ?: ($payload['picture'] ?? null),
            ])->save();

            return $user;
        }

        $user = User::query()->create([
            'name' => $payload['name'] ?? Str::before($payload['email'], '@'),
            'email' => Str::lower($payload['email']),
            'email_verified_at' => now(),
            'password' => Str::password(32),
            'firebase_uid' => $payload['sub'],
            'auth_provider' => 'google',
            'profile_photo_path' => $payload['picture'] ?? null,
            'status' => 'active',
        ]);

        $company = Company::query()->create([
            'name' => 'Empresa de '.$user->name,
            'slug' => Str::slug('empresa-'.$user->name).'-'.Str::lower(Str::random(5)),
            'company_plan_id' => CompanyPlan::query()->where('slug', 'free')->value('id'),
            'status' => 'trial',
            'billing_status' => 'trial',
            'trial_ends_at' => now()->addDays(3),
        ]);

        $company->users()->attach($user->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        return $user;
    }

    private function base64UrlDecode(string $value): string
    {
        $value = strtr($value, '-_', '+/');
        $padding = strlen($value) % 4;

        if ($padding) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode($value);
    }
}
