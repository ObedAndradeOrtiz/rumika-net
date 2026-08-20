<?php

use App\Models\User;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyPlan;
use App\Models\BusinessType;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $company_name = '';
    public string $branch_name = '';
    public string $business_type_id = '';
    public string $company_plan_id = '';
    public string $password = '';
    public string $password_confirmation = '';
    public array $businessTypes = [];
    public array $companyPlans = [];

    public function mount(): void
    {
        $this->businessTypes = BusinessType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'description'])
            ->toArray();

        $this->companyPlans = CompanyPlan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'description', 'monthly_price', 'currency'])
            ->toArray();

        $this->company_plan_id = (string) (CompanyPlan::query()->where('slug', 'free')->value('id')
            ?? CompanyPlan::query()->orderBy('sort_order')->value('id'));
    }

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'company_name' => ['required', 'string', 'max:255'],
            'branch_name' => ['required', 'string', 'max:255'],
            'business_type_id' => ['required', 'integer', 'exists:business_types,id'],
            'company_plan_id' => ['required', 'integer', 'exists:company_plans,id'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            $company = Company::create([
                'name' => $validated['company_name'],
                'slug' => Str::slug($validated['company_name']) . '-' . Str::lower(Str::random(5)),
                'company_plan_id' => $validated['company_plan_id'],
            ]);

            Branch::create([
                'company_id' => $company->id,
                'business_type_id' => $validated['business_type_id'],
                'name' => $validated['branch_name'],
                'slug' => Str::slug($validated['branch_name']),
            ]);

            $company->users()->attach($user->id, [
                'role' => 'owner',
                'joined_at' => now(),
            ]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="rm-login-shell">
    <div class="rm-login-header">
        <a href="{{ url('/') }}" class="rm-login-brand" wire:navigate>
            <span class="rm-brand-mark">
                <x-application-logo class="h-7 w-7 text-white" />
            </span>

            <span>
                <span class="rm-login-brand-title">Rumika SaaS</span>
                <span class="rm-login-brand-subtitle">Sistema modular para negocios de atención</span>
            </span>
        </a>

        <span class="rm-secure-badge">Registro seguro</span>
    </div>

    <div class="rm-login-copy">
        <h1>Crear empresa</h1>
        <p>
            Registra tu empresa y primera sucursal. Luego podrás sumar más sedes,
            usuarios y módulos según el tipo de negocio.
        </p>
    </div>

    <div class="rm-google-error" data-google-error hidden></div>

    <button type="button" class="rm-google-button" data-firebase-google data-auth-url="{{ route('auth.firebase.google') }}">
        <span class="rm-google-icon">G</span>

        <span class="rm-google-text">
            <strong data-google-label>Registrar con Google</strong>
            <small>Crea tu cuenta en el plan Free</small>
        </span>
    </button>

    <div class="rm-divider">
        <span></span>
        <strong>o registra tus datos</strong>
        <span></span>
    </div>

    <form wire:submit="register" class="rm-login-form">
        <div class="rm-register-grid">
            <div class="rm-field">
                <label class="rm-label" for="name">Tu nombre</label>

                <div class="rm-input-box">
                    <span class="rm-input-icon">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                            <path d="M5 20C5 16.6863 8.13401 14 12 14C15.866 14 19 16.6863 19 20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>

                    <input
                        wire:model="name"
                        id="name"
                        class="rm-input"
                        type="text"
                        name="name"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Rafael Mendoza"
                    >
                </div>

                <x-input-error :messages="$errors->get('name')" class="rm-error" />
            </div>

            <div class="rm-field">
                <label class="rm-label" for="email">Correo electrónico</label>

                <div class="rm-input-box">
                    <span class="rm-input-icon">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none">
                            <path d="M4 6.5H20V17.5C20 18.6046 19.1046 19.5 18 19.5H6C4.89543 19.5 4 18.6046 4 17.5V6.5Z" stroke="currentColor" stroke-width="2"/>
                            <path d="M4.5 7L12 13L19.5 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>

                    <input
                        wire:model="email"
                        id="email"
                        class="rm-input"
                        type="email"
                        name="email"
                        required
                        autocomplete="username"
                        placeholder="admin@empresa.com"
                    >
                </div>

                <x-input-error :messages="$errors->get('email')" class="rm-error" />
            </div>
        </div>

        <div class="rm-field">
            <label class="rm-label" for="company_name">Nombre de empresa</label>

            <div class="rm-input-box">
                <span class="rm-input-icon">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none">
                        <path d="M4 21V6C4 4.89543 4.89543 4 6 4H14C15.1046 4 16 4.89543 16 6V21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M16 10H18C19.1046 10 20 10.8954 20 12V21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M8 8H12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M8 12H12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M8 16H12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </span>

                <input
                    wire:model="company_name"
                    id="company_name"
                    class="rm-input"
                    type="text"
                    name="company_name"
                    required
                    placeholder="Rumika Clínica Centro"
                >
            </div>

            <x-input-error :messages="$errors->get('company_name')" class="rm-error" />
        </div>

        <div class="rm-field">
            <label class="rm-label">Plan inicial</label>

            <div class="rm-plan-grid">
                @foreach ($companyPlans as $plan)
                    <label class="rm-plan-option">
                        <input
                            wire:model="company_plan_id"
                            type="radio"
                            name="company_plan_id"
                            value="{{ $plan['id'] }}"
                        >

                        <span>
                            <strong>{{ $plan['name'] }}</strong>
                            <small>
                                {{ (float) $plan['monthly_price'] > 0 ? '$' . number_format((float) $plan['monthly_price'], 0) . '/mes' : 'Gratis' }}
                            </small>
                        </span>
                    </label>
                @endforeach
            </div>

            <x-input-error :messages="$errors->get('company_plan_id')" class="rm-error" />
        </div>

        <div class="rm-register-grid">
            <div class="rm-field">
                <label class="rm-label" for="branch_name">Sucursal inicial</label>

                <div class="rm-input-box">
                    <span class="rm-input-icon">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none">
                            <path d="M4 21V8.5L12 3L20 8.5V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9 21V14H15V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>

                    <input
                        wire:model="branch_name"
                        id="branch_name"
                        class="rm-input"
                        type="text"
                        name="branch_name"
                        required
                        placeholder="Sucursal Centro"
                    >
                </div>

                <x-input-error :messages="$errors->get('branch_name')" class="rm-error" />
            </div>

            <div class="rm-field">
                <label class="rm-label" for="business_type_id">Tipo de negocio</label>

                <div class="rm-input-box">
                    <span class="rm-input-icon">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none">
                            <path d="M4 7.5L12 4L20 7.5L12 11L4 7.5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M4 12.5L12 16L20 12.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M4 17.5L12 21L20 17.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>

                    <select
                        wire:model="business_type_id"
                        id="business_type_id"
                        class="rm-input rm-select"
                        name="business_type_id"
                        required
                    >
                        <option value="">Selecciona uno</option>
                        @foreach ($businessTypes as $businessType)
                            <option value="{{ $businessType['id'] }}">
                                {{ $businessType['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <x-input-error :messages="$errors->get('business_type_id')" class="rm-error" />
            </div>
        </div>

        <div class="rm-register-grid">
            <div class="rm-field">
                <label class="rm-label" for="password">Contraseña</label>

                <div class="rm-input-box">
                    <span class="rm-input-icon">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none">
                            <rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" stroke-width="2"/>
                            <path d="M8 10V7.5C8 5.29086 9.79086 3.5 12 3.5C14.2091 3.5 16 5.29086 16 7.5V10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>

                    <input
                        wire:model="password"
                        id="password"
                        class="rm-input"
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        placeholder="Mínimo 8 caracteres"
                    >

                    <button
                        class="rm-password-toggle"
                        type="button"
                        data-password-toggle="#password"
                        aria-label="Mostrar contraseña"
                        aria-pressed="false"
                    >
                        Ver
                    </button>
                </div>

                <x-input-error :messages="$errors->get('password')" class="rm-error" />
            </div>

            <div class="rm-field">
                <label class="rm-label" for="password_confirmation">Repetir contraseña</label>

                <div class="rm-input-box">
                    <span class="rm-input-icon">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none">
                            <path d="M9 12L11 14L15.5 9.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" stroke-width="2"/>
                            <path d="M8 10V7.5C8 5.29086 9.79086 3.5 12 3.5C14.2091 3.5 16 5.29086 16 7.5V10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>

                    <input
                        wire:model="password_confirmation"
                        id="password_confirmation"
                        class="rm-input"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Repite tu clave"
                    >

                    <button
                        class="rm-password-toggle"
                        type="button"
                        data-password-toggle="#password_confirmation"
                        aria-label="Mostrar contraseña"
                        aria-pressed="false"
                    >
                        Ver
                    </button>
                </div>

                <x-input-error :messages="$errors->get('password_confirmation')" class="rm-error" />
            </div>
        </div>

        <button class="rm-submit-button" type="submit" wire:loading.attr="disabled" wire:target="register">
            <span wire:loading.remove wire:target="register">
                Crear cuenta y empresa
            </span>

            <span wire:loading wire:target="register" class="rm-loading-content">
                <span class="rm-spinner"></span>
                Creando empresa...
            </span>
        </button>
    </form>

    <p class="rm-auth-switch">
        ¿Ya tienes cuenta?
        <a href="{{ route('login') }}" wire:navigate>Iniciar sesión</a>
    </p>

    <div class="rm-digitbol-card">
        <img src="{{ asset('digitbol-logo.jpg') }}" alt="DigitBol">

        <div>
            <span>Desarrollado por</span>
            <strong>DigitBol</strong>
            <p>Sistemas web, SaaS y soluciones digitales a medida.</p>

            <a
                href="https://wa.me/59177348087?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20DigitBol%20y%20sus%20sistemas."
                target="_blank"
                rel="noopener"
            >
                Hablar por WhatsApp
            </a>
        </div>
    </div>

    <style>
        .rm-register-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .rm-select {
            appearance: none;
            cursor: pointer;
        }

        @media (max-width: 640px) {
            .rm-register-grid {
                grid-template-columns: 1fr;
                gap: 17px;
            }
        }
    </style>
</div>
