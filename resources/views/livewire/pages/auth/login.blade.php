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

        <div class="rm-digitbol-card">
            <div class="rm-digitbol-top">
                <img src="{{ asset('digitbol-logo.jpg') }}" alt="DigitBol" class="rm-digitbol-logo">

                <div class="rm-digitbol-copy">
                    <span class="rm-digitbol-label">Desarrollado por</span>
                    <h3>DigitBol</h3>
                    <p>
                        Creamos sistemas web, plataformas SaaS y soluciones a medida para empresas que buscan trabajar con más orden, velocidad y control.
                    </p>
                </div>
            </div>

            <a
                href="https://wa.me/59177348087?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20DigitBol%20y%20sus%20sistemas."
                target="_blank"
                rel="noopener"
                class="rm-digitbol-whatsapp"
            >
                <svg width="18" height="18" viewBox="0 0 32 32" fill="none">
                    <path fill="currentColor" d="M16.02 4C9.4 4 4.02 9.28 4.02 15.78c0 2.08.56 4.1 1.62 5.88L4 28l6.52-1.68a12.2 12.2 0 0 0 5.5 1.32C22.64 27.64 28 22.36 28 15.86C28.02 9.36 22.64 4 16.02 4Zm0 21.62c-1.78 0-3.52-.46-5.04-1.34l-.36-.22l-3.86 1l1.02-3.68l-.24-.38a9.66 9.66 0 0 1-1.5-5.18c0-5.38 4.48-9.76 9.98-9.76S26 10.44 26 15.82c0 5.4-4.48 9.8-9.98 9.8Zm5.48-7.32c-.3-.14-1.78-.86-2.06-.96c-.28-.1-.48-.14-.68.14c-.2.3-.78.96-.96 1.16c-.18.2-.36.22-.66.08c-.3-.14-1.26-.46-2.4-1.46c-.88-.78-1.48-1.74-1.66-2.04c-.18-.3-.02-.46.14-.6c.14-.14.3-.36.46-.54c.16-.18.2-.3.3-.5c.1-.2.06-.38-.02-.54c-.08-.14-.68-1.62-.94-2.22c-.24-.58-.5-.5-.68-.52h-.58c-.2 0-.52.08-.8.38c-.28.3-1.04 1-1.04 2.44s1.06 2.84 1.2 3.04c.14.2 2.08 3.12 5.04 4.38c.7.3 1.26.48 1.68.62c.7.22 1.34.18 1.84.12c.56-.08 1.78-.72 2.04-1.42c.26-.7.26-1.3.18-1.42c-.08-.14-.28-.22-.58-.36Z"/>
                </svg>
                Hablar con DigitBol
            </a>
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
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-top: 2px;
        }

        .rm-remember {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #475467;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            user-select: none;
            line-height: 1;
            min-width: 0;
        }

        .rm-remember-input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            width: 1px;
            height: 1px;
        }

        .rm-remember-box {
            width: 22px;
            height: 22px;
            border-radius: 7px;
            border: 1.5px solid #aab4c3;
            background: #ffffff;
            display: grid;
            place-items: center;
            color: #ffffff;
            flex: 0 0 auto;
            transition: .18s ease;
        }

        .rm-remember-box svg {
            width: 15px;
            height: 15px;
            opacity: 0;
            transform: scale(.7);
            transition: .18s ease;
        }

        .rm-remember-input:checked + .rm-remember-box {
            background: #0f8f7f;
            border-color: #0f8f7f;
            box-shadow: 0 8px 18px rgba(15, 143, 127, .22);
        }

        .rm-remember-input:checked + .rm-remember-box svg {
            opacity: 1;
            transform: scale(1);
        }

        .rm-remember-input:focus + .rm-remember-box {
            box-shadow: 0 0 0 5px rgba(15, 143, 127, .10);
            border-color: #0f8f7f;
        }

        .rm-remember-text {
            white-space: nowrap;
        }

        .rm-forgot {
            color: #087568;
            font-size: 14px;
            font-weight: 900;
            text-decoration: none;
            white-space: nowrap;
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

        .rm-digitbol-card {
            padding: 18px;
            border-radius: 22px;
            background: linear-gradient(135deg, #ffffff 0%, #f7fbfd 100%);
            border: 1px solid #dbe3ee;
            box-shadow: 0 16px 34px rgba(15, 23, 42, .06);
            display: grid;
            gap: 16px;
        }

        .rm-digitbol-top {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .rm-digitbol-logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
            border-radius: 16px;
            background: #ffffff;
            padding: 6px;
            border: 1px solid #e6edf5;
            flex: 0 0 auto;
        }

        .rm-digitbol-copy {
            min-width: 0;
        }

        .rm-digitbol-label {
            display: block;
            font-size: 12px;
            font-weight: 800;
            color: #1d4f91;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .rm-digitbol-copy h3 {
            margin: 0;
            font-size: 22px;
            line-height: 1;
            letter-spacing: -0.04em;
            color: #0f172a;
            font-weight: 900;
        }

        .rm-digitbol-copy p {
            margin: 8px 0 0;
            font-size: 14px;
            line-height: 1.65;
            color: #667085;
        }

        .rm-digitbol-whatsapp {
            min-height: 48px;
            border-radius: 16px;
            background: #25D366;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 0 18px;
            font-size: 14px;
            font-weight: 900;
            text-decoration: none;
            box-shadow: 0 14px 28px rgba(37, 211, 102, .20);
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .rm-digitbol-whatsapp:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 34px rgba(37, 211, 102, .28);
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
                align-items: center;
                gap: 10px;
            }

            .rm-remember {
                font-size: 13.5px;
            }

            .rm-forgot {
                font-size: 13.5px;
            }

            .rm-digitbol-top {
                align-items: flex-start;
            }

            .rm-digitbol-logo {
                width: 62px;
                height: 62px;
            }

            .rm-digitbol-copy h3 {
                font-size: 20px;
            }
        }

        @media (max-width: 380px) {
            .rm-login-options {
                flex-direction: column;
                align-items: flex-start;
            }

            .rm-digitbol-top {
                flex-direction: column;
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
