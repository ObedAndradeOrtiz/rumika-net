<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="rm-login-page">
    <div class="rm-login-shell">
        <div class="rm-login-head">
            <a href="{{ url('/') }}" class="rm-brand-link" wire:navigate>
                <span class="rm-brand-mark">
                    <x-application-logo class="h-7 w-7 text-white" />
                </span>

                <span>
                    <span class="rm-login-brand-title">Rumika SaaS</span>
                    <span class="rm-login-brand-subtitle">Sistema modular para negocios de atención</span>
                </span>
            </a>

            <div class="rm-auth-badge">
                Acceso seguro
            </div>
        </div>

        <div class="rm-login-copy">
            <h2>Iniciar sesión</h2>
            <p>
                Ingresa a Rumika para administrar agenda, clientes, historial, inventario y sucursales desde una sola plataforma.
            </p>
        </div>

        <x-auth-session-status
            class="rm-session-status"
            :status="session('status')"
        />

        <button type="button" class="rm-google-button" disabled aria-disabled="true">
            <span class="rm-google-icon">G</span>

            <span class="rm-google-text">
                <strong>Continuar con Google</strong>
                <small>Disponible pronto</small>
            </span>

            <span class="rm-google-pill">Pronto</span>
        </button>

        <div class="rm-divider">
            <span></span>
            <strong>o ingresa con tu correo</strong>
            <span></span>
        </div>

        <form wire:submit="login" class="rm-login-form">
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
                        wire:model="form.email"
                        id="email"
                        class="rm-input"
                        type="email"
                        name="email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="admin@rumika.app"
                    >
                </div>

                <x-input-error :messages="$errors->get('form.email')" class="rm-error" />
            </div>

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
                        wire:model="form.password"
                        id="password"
                        class="rm-input"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="Tu contraseña"
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

                <x-input-error :messages="$errors->get('form.password')" class="rm-error" />
            </div>

            <div class="rm-login-options">
                <label for="remember" class="rm-remember">
                    <input
                        wire:model="form.remember"
                        id="remember"
                        type="checkbox"
                        name="remember"
                        class="rm-remember-input"
                    >

                    <span class="rm-remember-box">
                        <svg viewBox="0 0 20 20" fill="none">
                            <path d="M5 10.5L8.2 13.5L15 6.5" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>

                    <span class="rm-remember-text">Recordarme</span>
                </label>

                @if (Route::has('password.request'))
                    <a class="rm-forgot" href="{{ route('password.request') }}" wire:navigate>
                        Olvidé mi clave
                    </a>
                @endif
            </div>

            <button class="rm-submit-button" type="submit" wire:loading.attr="disabled" wire:target="login">
                <span wire:loading.remove wire:target="login">
                    Entrar al sistema
                </span>

                <span wire:loading wire:target="login" class="rm-loading-content">
                    <span class="rm-spinner"></span>
                    Ingresando...
                </span>
            </button>
        </form>

        @if (Route::has('register'))
            <p class="rm-auth-switch">
                ¿Aún no tienes cuenta?
                <a href="{{ route('register') }}" wire:navigate>Crear empresa</a>
            </p>
        @endif

    </div>
</div>
