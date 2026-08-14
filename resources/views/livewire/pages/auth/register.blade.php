<?php

use App\Models\User;
use App\Models\Branch;
use App\Models\Company;
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
    public string $password = '';
    public string $password_confirmation = '';
    public array $businessTypes = [];

    public function mount(): void
    {
        $this->businessTypes = BusinessType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'description'])
            ->toArray();
    }

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'company_name' => ['required', 'string', 'max:255'],
            'branch_name' => ['required', 'string', 'max:255'],
            'business_type_id' => ['required', 'integer', 'exists:business_types,id'],
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
                'slug' => Str::slug($validated['company_name']).'-'.Str::lower(Str::random(5)),
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

<div class="grid gap-6">
    <div class="grid gap-2">
        <span class="rm-brand-mark">
            <x-application-logo class="h-7 w-7 text-white" />
        </span>
        <div>
            <h2 class="text-3xl font-black text-slate-950">Crear empresa</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                Registra tu primera sucursal. Luego podras sumar otras y cambiar sus modulos por tipo de negocio.
            </p>
        </div>
    </div>

    <button type="button" class="rm-button rm-button-outline w-full" disabled aria-disabled="true">
        <span class="grid h-6 w-6 place-items-center rounded-full bg-slate-100 text-sm font-black text-slate-700">G</span>
        Registrar con Google
        <span class="text-xs font-black text-slate-400">Pronto</span>
    </button>

    <form wire:submit="register" class="grid gap-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rm-field">
                <label class="rm-label" for="name">Tu nombre</label>
                <input wire:model="name" id="name" class="rm-input" type="text" name="name" required autofocus autocomplete="name" placeholder="Rafael Mendoza">
                <x-input-error :messages="$errors->get('name')" class="rm-error" />
            </div>

            <div class="rm-field">
                <label class="rm-label" for="email">Correo</label>
                <input wire:model="email" id="email" class="rm-input" type="email" name="email" required autocomplete="username" placeholder="admin@empresa.com">
                <x-input-error :messages="$errors->get('email')" class="rm-error" />
            </div>
        </div>

        <div class="rm-field">
            <label class="rm-label" for="company_name">Nombre de empresa</label>
            <input wire:model="company_name" id="company_name" class="rm-input" type="text" name="company_name" required placeholder="Rumika Clinica Centro">
            <x-input-error :messages="$errors->get('company_name')" class="rm-error" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rm-field">
                <label class="rm-label" for="branch_name">Sucursal inicial</label>
                <input wire:model="branch_name" id="branch_name" class="rm-input" type="text" name="branch_name" required placeholder="Sucursal Centro">
                <x-input-error :messages="$errors->get('branch_name')" class="rm-error" />
            </div>

            <div class="rm-field">
                <label class="rm-label" for="business_type_id">Tipo de negocio</label>
                <select wire:model="business_type_id" id="business_type_id" class="rm-input" name="business_type_id" required>
                    <option value="">Selecciona uno</option>
                    @foreach ($businessTypes as $businessType)
                        <option value="{{ $businessType['id'] }}">{{ $businessType['name'] }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('business_type_id')" class="rm-error" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rm-field">
                <label class="rm-label" for="password">Contrasena</label>
                <div class="rm-input-wrap">
                    <input wire:model="password" id="password" class="rm-input" type="password" name="password" required autocomplete="new-password" placeholder="Minimo 8 caracteres">
                    <button class="rm-input-action" type="button" data-password-toggle="#password" aria-label="Mostrar contrasena" aria-pressed="false">Ver</button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="rm-error" />
            </div>

            <div class="rm-field">
                <label class="rm-label" for="password_confirmation">Repetir contrasena</label>
                <input wire:model="password_confirmation" id="password_confirmation" class="rm-input" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Repite tu clave">
                <x-input-error :messages="$errors->get('password_confirmation')" class="rm-error" />
            </div>
        </div>

        <button class="rm-button rm-button-primary w-full" type="submit">
            Crear cuenta y empresa
        </button>
    </form>

    <p class="rm-auth-switch">
        Ya tienes cuenta?
        <a href="{{ route('login') }}" wire:navigate>Iniciar sesion</a>
    </p>
</div>
