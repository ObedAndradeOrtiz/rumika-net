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

<div class="rm-login-page">
    <div class="rm-login-shell">
        <div class="rm-login-head">
            <a href="{{ url('/') }}" class="rm-brand-link" wire:navigate>
                <span class="rm-brand-mark">
                    <x-application-logo class="h-7 w-7 text-white" />
                </span>

                <span>
                    <span class="rm-brand-title">Rumika SaaS</span>
                    <span class="rm-brand-subtitle">Sistema modular para negocios de atención</span>
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
                        class="rm-input rm-input-password"
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
                <label for="remember" class="rm-check">
                    <input
                        wire:model="form.remember"
                        id="remember"
                        type="checkbox"
                        name="remember"
                    >
                    <span>Recordarme</span>
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

        <div class="rm-login-foot">
            <span>Un sistema más de</span>
            <strong>DigitBol</strong>
        </div>
    </div>

    <style>
        .rm-login-page {
            width: 100%;
        }

        .rm-login-shell {
            width: 100%;
            max-width: 470px;
            margin: 0 auto;
            display: grid;
            gap: 24px;
            animation: rmFadeUp .42s ease both;
        }

        .rm-login-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }

        .rm-brand-link {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
            text-decoration: none;
        }

        .rm-brand-mark {
            width: 58px;
            height: 58px;
            border-radius: 20px;
            background: linear-gradient(145deg, #0f8f7f, #0aa591);
            display: grid;
            place-items: center;
            color: #ffffff;
            box-shadow: 0 18px 36px rgba(15, 143, 127, .24);
            flex: 0 0 auto;
        }

        .rm-brand-title {
            display: block;
            font-size: 22px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.045em;
            line-height: 1;
        }

        .rm-brand-subtitle {
            display: block;
            margin-top: 6px;
            font-size: 12.5px;
            font-weight: 700;
            color: #667085;
            line-height: 1.35;
        }

        .rm-auth-badge {
            padding: 9px 12px;
            border-radius: 999px;
            background: #e7f7f5;
            color: #087568;
            font-size: 12px;
            font-weight: 900;
            white-space: nowrap;
            border: 1px solid #cfeee8;
        }

        .rm-login-copy h2 {
            margin: 0;
            font-size: clamp(32px, 5vw, 42px);
            line-height: 1.02;
            font-weight: 900;
            letter-spacing: -0.065em;
            color: #0f172a;
        }

        .rm-login-copy p {
            margin: 14px 0 0;
            color: #667085;
            line-height: 1.72;
            font-size: 15.5px;
        }

        .rm-session-status {
            border-radius: 18px;
            border: 1px solid #a7f3d0;
            background: #ecfdf5;
            padding: 13px 15px;
            color: #047857;
            font-weight: 800;
            font-size: 14px;
        }

        .rm-google-button {
            width: 100%;
            min-height: 60px;
            border: 1px solid #dbe3ee;
            background: rgba(255, 255, 255, .82);
            border-radius: 20px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 13px;
            color: #101828;
            cursor: not-allowed;
            opacity: .82;
            box-shadow: 0 12px 32px rgba(15, 23, 42, .05);
        }

        .rm-google-icon {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            background: #f2f4f7;
            color: #344054;
            display: grid;
            place-items: center;
            font-weight: 900;
            flex: 0 0 auto;
        }

        .rm-google-text {
            display: grid;
            gap: 2px;
            text-align: left;
            flex: 1;
            min-width: 0;
        }

        .rm-google-text strong {
            font-size: 14.5px;
            font-weight: 900;
        }

        .rm-google-text small {
            color: #98a2b3;
            font-size: 12px;
            font-weight: 700;
        }

        .rm-google-pill {
            padding: 7px 10px;
            border-radius: 999px;
            background: #f2f4f7;
            color: #98a2b3;
            font-size: 11px;
            font-weight: 900;
        }

        .rm-divider {
            display: flex;
            align-items: center;
            gap: 13px;
            color: #98a2b3;
            font-size: 11.5px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .rm-divider span {
            height: 1px;
            flex: 1;
            background: #dbe3ee;
        }

        .rm-login-form {
            display: grid;
            gap: 17px;
        }

        .rm-field {
            display: grid;
            gap: 8px;
        }

        .rm-label {
            font-size: 14px;
            font-weight: 900;
            color: #101828;
        }

        .rm-input-box {
            min-height: 58px;
            border: 1px solid #dbe3ee;
            border-radius: 19px;
            background: rgba(255, 255, 255, .92);
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 14px;
            transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .rm-input-box:focus-within {
            border-color: #0f8f7f;
            background: #ffffff;
            box-shadow: 0 0 0 5px rgba(15, 143, 127, .10);
        }

        .rm-input-icon {
            display: grid;
            place-items: center;
            color: #98a2b3;
            flex: 0 0 auto;
        }

        .rm-input {
            width: 100%;
            min-width: 0;
            border: 0;
            outline: 0;
            background: transparent;
            color: #101828;
            font-size: 15px;
            font-weight: 700;
        }

        .rm-input::placeholder {
            color: #98a2b3;
            font-weight: 700;
        }

        .rm-input-password {
            padding-right: 4px;
        }

        .rm-password-toggle {
            border: 0;
            background: transparent;
            color: #087568;
            font-size: 13px;
            font-weight: 900;
            cursor: pointer;
            padding: 8px 4px;
            flex: 0 0 auto;
        }

        .rm-error {
            color: #dc2626;
            font-size: 12.5px;
            font-weight: 700;
            margin-top: 2px;
        }

        .rm-login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 2px;
        }

        .rm-check {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: #475467;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            user-select: none;
        }

        .rm-check input {
            width: 17px;
            height: 17px;
            border-radius: 5px;
            accent-color: #087568;
        }

        .rm-forgot {
            color: #087568;
            font-size: 14px;
            font-weight: 900;
            text-decoration: none;
        }

        .rm-forgot:hover,
        .rm-auth-switch a:hover {
            text-decoration: underline;
            text-underline-offset: 4px;
        }

        .rm-submit-button {
            min-height: 58px;
            border: 0;
            border-radius: 19px;
            background: linear-gradient(135deg, #0f8f7f, #087568);
            color: #ffffff;
            font-size: 16px;
            font-weight: 900;
            cursor: pointer;
            box-shadow: 0 18px 36px rgba(15, 143, 127, .24);
            transition: transform .18s ease, box-shadow .18s ease, opacity .18s ease;
        }

        .rm-submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 22px 42px rgba(15, 143, 127, .30);
        }

        .rm-submit-button:disabled {
            opacity: .78;
            cursor: wait;
            transform: none;
        }

        .rm-loading-content {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .rm-spinner {
            width: 17px;
            height: 17px;
            border-radius: 999px;
            border: 2px solid rgba(255, 255, 255, .45);
            border-top-color: #ffffff;
            animation: rmSpin .75s linear infinite;
        }

        .rm-auth-switch {
            margin: 0;
            text-align: center;
            color: #667085;
            font-size: 14.5px;
            font-weight: 700;
        }

        .rm-auth-switch a {
            color: #087568;
            font-weight: 900;
            text-decoration: none;
        }

        .rm-login-foot {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            color: #98a2b3;
            font-size: 12.5px;
            font-weight: 700;
        }

        .rm-login-foot strong {
            color: #1d4f91;
            font-weight: 900;
        }

        @keyframes rmSpin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes rmFadeUp {
            from {
                opacity: 0;
                transform: translateY(14px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 520px) {
            .rm-login-shell {
                max-width: 100%;
                gap: 20px;
            }

            .rm-login-head {
                align-items: center;
            }

            .rm-brand-mark {
                width: 50px;
                height: 50px;
                border-radius: 17px;
            }

            .rm-brand-title {
                font-size: 20px;
            }

            .rm-brand-subtitle {
                display: none;
            }

            .rm-auth-badge {
                display: none;
            }

            .rm-login-copy h2 {
                font-size: 34px;
            }

            .rm-google-button {
                min-height: 58px;
            }

            .rm-login-options {
                align-items: flex-start;
            }

            .rm-forgot {
                margin-left: auto;
            }
        }
    </style>

    <script>
        document.addEventListener('click', function (event) {
            const button = event.target.closest('[data-password-toggle]');

            if (!button) {
                return;
            }

            const selector = button.getAttribute('data-password-toggle');
            const input = document.querySelector(selector);

            if (!input) {
                return;
            }

            const isPassword = input.getAttribute('type') === 'password';

            input.setAttribute('type', isPassword ? 'text' : 'password');
            button.textContent = isPassword ? 'Ocultar' : 'Ver';
            button.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
        });
    </script>
</div>
