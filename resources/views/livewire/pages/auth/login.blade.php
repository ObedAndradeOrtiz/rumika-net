<div class="rm-login-page">
    <main class="rm-login-wrapper">
        <section class="rm-login-card">
            <div class="rm-login-header">
                <a href="{{ url('/') }}" class="rm-login-brand" wire:navigate>
                    <span class="rm-brand-mark">
                        <x-application-logo class="h-7 w-7 text-white" />
                    </span>

                    <span>
                        <span class="rm-brand-title">Rumika SaaS</span>
                        <span class="rm-brand-subtitle">Sistema modular para negocios de atención</span>
                    </span>
                </a>

                <span class="rm-secure-badge">Acceso seguro</span>
            </div>

            <div class="rm-login-copy">
                <h1>Iniciar sesión</h1>
                <p>
                    Ingresa a Rumika para administrar agenda, clientes, historial,
                    inventario y sucursales desde una sola plataforma.
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
        </section>
    </main>

    <style>
        .rm-login-page {
            min-height: 100vh;
            width: 100%;
            font-family: 'Figtree', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(15, 143, 127, .14), transparent 32%),
                radial-gradient(circle at bottom right, rgba(29, 79, 145, .10), transparent 34%),
                linear-gradient(135deg, #eef8f7 0%, #f8fbff 48%, #f5f7fb 100%);
            display: grid;
            place-items: center;
            padding: 26px;
            color: #101828;
        }

        .rm-login-wrapper {
            width: 100%;
            display: grid;
            place-items: center;
        }

        .rm-login-card {
            width: min(100%, 520px);
            border-radius: 34px;
            padding: 34px;
            background: rgba(255, 255, 255, .90);
            border: 1px solid rgba(219, 227, 238, .96);
            box-shadow: 0 30px 90px rgba(15, 23, 42, .12);
            backdrop-filter: blur(16px);
            display: grid;
            gap: 22px;
            animation: rmFadeUp .42s ease both;
        }

        .rm-login-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }

        .rm-login-brand {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            color: inherit;
            text-decoration: none;
            min-width: 0;
        }

        .rm-brand-mark {
            width: 58px;
            height: 58px;
            border-radius: 20px;
            background: linear-gradient(145deg, #0f8f7f, #0aa591);
            display: grid;
            place-items: center;
            color: white;
            box-shadow: 0 18px 36px rgba(15, 143, 127, .24);
            flex: 0 0 auto;
        }

        .rm-brand-title {
            display: block;
            color: #0f172a;
            font-size: 24px;
            font-weight: 900;
            letter-spacing: -0.04em;
            line-height: 1;
        }

        .rm-brand-subtitle {
            display: block;
            margin-top: 6px;
            color: #667085;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.35;
        }

        .rm-secure-badge {
            padding: 9px 12px;
            border-radius: 999px;
            background: #e7f7f5;
            color: #087568;
            font-size: 12px;
            font-weight: 900;
            white-space: nowrap;
            border: 1px solid #cfeee8;
        }

        .rm-login-copy h1 {
            margin: 0;
            color: #111827;
            font-size: 42px;
            line-height: 1.05;
            font-weight: 900;
            letter-spacing: -0.04em;
        }

        .rm-login-copy p {
            margin: 12px 0 0;
            color: #667085;
            font-size: 15.5px;
            line-height: 1.7;
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
            min-height: 58px;
            border: 1px solid #dbe3ee;
            background: rgba(255, 255, 255, .84);
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
            color: #101828;
            font-size: 14px;
            font-weight: 900;
        }

        .rm-input-box {
            min-height: 58px;
            border: 1px solid #dbe3ee;
            border-radius: 19px;
            background: rgba(255, 255, 255, .94);
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 14px;
            transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .rm-input-box:focus-within {
            border-color: #0f8f7f;
            background: white;
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
            background: white;
            display: grid;
            place-items: center;
            color: white;
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

        .rm-remember-text,
        .rm-forgot {
            white-space: nowrap;
        }

        .rm-forgot {
            color: #087568;
            font-size: 14px;
            font-weight: 900;
            text-decoration: none;
        }

        .rm-forgot:hover,
        .rm-auth-switch a:hover,
        .rm-digitbol-card a:hover {
            text-decoration: underline;
            text-underline-offset: 4px;
        }

        .rm-submit-button {
            min-height: 58px;
            border: 0;
            border-radius: 19px;
            background: linear-gradient(135deg, #0f8f7f, #087568);
            color: white;
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
            border-top-color: white;
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
            padding: 16px;
            border-radius: 20px;
            background: linear-gradient(135deg, #ffffff 0%, #f7fbfd 100%);
            border: 1px solid #dbe3ee;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .06);
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .rm-digitbol-card img {
            width: 58px;
            height: 58px;
            object-fit: contain;
            border-radius: 15px;
            border: 1px solid #e7edf5;
            padding: 4px;
            background: white;
            flex: 0 0 auto;
        }

        .rm-digitbol-card span {
            display: block;
            color: #667085;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .rm-digitbol-card strong {
            display: block;
            color: #0f172a;
            font-size: 17px;
            font-weight: 900;
            margin-top: 2px;
        }

        .rm-digitbol-card p {
            margin: 4px 0 0;
            color: #667085;
            font-size: 13px;
            line-height: 1.45;
            font-weight: 600;
        }

        .rm-digitbol-card a {
            display: inline-block;
            margin-top: 6px;
            color: #087568;
            font-size: 13px;
            font-weight: 900;
            text-decoration: none;
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

        @media (max-width: 560px) {
            .rm-login-page {
                min-height: 100vh;
                padding: 18px;
                place-items: start center;
            }

            .rm-login-card {
                width: 100%;
                padding: 24px 20px;
                border-radius: 28px;
                gap: 19px;
            }

            .rm-login-header {
                align-items: center;
            }

            .rm-brand-mark {
                width: 50px;
                height: 50px;
                border-radius: 17px;
            }

            .rm-brand-title {
                font-size: 21px;
            }

            .rm-brand-subtitle,
            .rm-secure-badge {
                display: none;
            }

            .rm-login-copy h1 {
                font-size: 32px;
                letter-spacing: -0.025em;
            }

            .rm-login-copy p {
                font-size: 14.5px;
            }

            .rm-google-button {
                min-height: 56px;
            }

            .rm-login-options {
                align-items: center;
                gap: 10px;
            }

            .rm-remember,
            .rm-forgot {
                font-size: 13.5px;
            }

            .rm-digitbol-card {
                align-items: flex-start;
            }
        }

        @media (max-width: 390px) {
            .rm-login-options {
                flex-direction: column;
                align-items: flex-start;
            }

            .rm-digitbol-card {
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
