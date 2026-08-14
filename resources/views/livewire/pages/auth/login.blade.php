<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="grid gap-6">
    <div class="grid gap-2">
        <span class="rm-brand-mark">
            <x-application-logo class="h-7 w-7 text-white" />
        </span>
        <div>
            <h2 class="text-3xl font-black text-slate-950">Iniciar sesion</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                Ingresa a Rumika para administrar agenda, clientes e historial por sucursal.
            </p>
        </div>
    </div>

    <x-auth-session-status class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm font-bold text-emerald-800" :status="session('status')" />

    <button type="button" class="rm-button rm-button-outline w-full" disabled aria-disabled="true">
        <span class="grid h-6 w-6 place-items-center rounded-full bg-slate-100 text-sm font-black text-slate-700">G</span>
        Continuar con Google
        <span class="text-xs font-black text-slate-400">Pronto</span>
    </button>

    <div class="flex items-center gap-3 text-xs font-bold uppercase text-slate-400">
        <span class="h-px flex-1 bg-slate-200"></span>
        <span>o usa tu correo</span>
        <span class="h-px flex-1 bg-slate-200"></span>
    </div>

    <form wire:submit="login" class="grid gap-4">
        <div class="rm-field">
            <label class="rm-label" for="email">Correo</label>
            <input wire:model="form.email" id="email" class="rm-input" type="email" name="email" required autofocus autocomplete="username" placeholder="admin@rumika.app">
            <x-input-error :messages="$errors->get('form.email')" class="rm-error" />
        </div>

        <div class="rm-field">
            <label class="rm-label" for="password">Contrasena</label>
            <div class="rm-input-wrap">
                <input wire:model="form.password" id="password" class="rm-input" type="password" name="password" required autocomplete="current-password" placeholder="Tu contrasena">
                <button class="rm-input-action" type="button" data-password-toggle="#password" aria-label="Mostrar contrasena" aria-pressed="false">Ver</button>
            </div>
            <x-input-error :messages="$errors->get('form.password')" class="rm-error" />
        </div>

        <div class="flex items-center justify-between gap-3">
            <label for="remember" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600">
                <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-slate-300 text-teal-700 shadow-sm focus:ring-teal-600" name="remember">
                Recordarme
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-black text-teal-700" href="{{ route('password.request') }}" wire:navigate>
                    Olvide mi clave
                </a>
            @endif
        </div>

        <button class="rm-button rm-button-primary w-full" type="submit">
            Entrar al sistema
        </button>
    </form>

    <p class="rm-auth-switch">
        Aun no tienes cuenta?
        <a href="{{ route('register') }}" wire:navigate>Crear empresa</a>
    </p>
</div>
