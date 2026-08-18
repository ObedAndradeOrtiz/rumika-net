<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Rumika SaaS') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('rumika-favicon.svg') }}?v=2">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --rm-primary: #0f8f7f;
            --rm-primary-dark: #087568;
            --rm-primary-soft: #e7f7f5;
            --rm-text: #101828;
            --rm-muted: #667085;
            --rm-border: #dbe3ee;
            --rm-white: #ffffff;
            --rm-shadow-lg: 0 30px 90px rgba(15, 23, 42, .12);
            --rm-shadow-md: 0 18px 46px rgba(15, 23, 42, .08);
            --rm-shadow-sm: 0 10px 28px rgba(15, 23, 42, .06);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Figtree', sans-serif;
            color: var(--rm-text);
            background:
                radial-gradient(circle at top left, rgba(15, 143, 127, .12), transparent 30%),
                radial-gradient(circle at bottom right, rgba(29, 79, 145, .08), transparent 34%),
                linear-gradient(135deg, #eef8f7 0%, #f8fbff 48%, #f5f7fb 100%);
        }

        a {
            text-decoration: none;
        }

        .rm-auth-shell {
            min-height: 100vh;
            width: 100%;
            padding: 28px;
            display: grid;
            place-items: center;
        }

        .rm-auth-layout {
            width: min(1380px, 100%);
            min-height: calc(100vh - 56px);
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(420px, .86fr);
            overflow: hidden;
            border-radius: 34px;
            background: rgba(255, 255, 255, .82);
            border: 1px solid rgba(219, 227, 238, .95);
            box-shadow: var(--rm-shadow-lg);
            backdrop-filter: blur(16px);
        }

        .rm-auth-preview {
            position: relative;
            overflow: hidden;
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 34px;
            background:
                radial-gradient(circle at 14% 12%, rgba(15, 143, 127, .16), transparent 28%),
                linear-gradient(135deg, rgba(255, 255, 255, .86), rgba(244, 250, 251, .74));
        }

        .rm-auth-preview::before {
            content: "";
            position: absolute;
            width: 340px;
            height: 340px;
            border-radius: 50%;
            right: -150px;
            top: -120px;
            background: radial-gradient(circle, rgba(15, 143, 127, .16), transparent 68%);
            pointer-events: none;
        }

        .rm-preview-main {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 34px;
        }

        .rm-preview-brand {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            width: fit-content;
            color: inherit;
        }

        .rm-brand-mark {
            width: 58px;
            height: 58px;
            border-radius: 20px;
            background: linear-gradient(145deg, var(--rm-primary), #0aa591);
            display: grid;
            place-items: center;
            color: #ffffff;
            box-shadow: 0 18px 36px rgba(15, 143, 127, .24);
            flex: 0 0 auto;
        }

        .rm-preview-brand-title {
            display: block;
            font-size: 28px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -0.04em;
            color: #0f172a;
        }

        .rm-preview-brand-subtitle {
            display: block;
            margin-top: 6px;
            color: var(--rm-muted);
            font-size: 13px;
            font-weight: 700;
        }

        .rm-preview-copy {
            max-width: 760px;
        }

        .rm-preview-kicker {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            margin-bottom: 22px;
            border-radius: 999px;
            color: var(--rm-primary-dark);
            background: rgba(15, 143, 127, .09);
            border: 1px solid rgba(15, 143, 127, .14);
            font-size: 14px;
            font-weight: 900;
        }

        .rm-preview-kicker-dot {
            width: 9px;
            height: 9px;
            border-radius: 999px;
            background: var(--rm-primary);
            box-shadow: 0 0 0 6px rgba(15, 143, 127, .12);
        }

        .rm-preview-copy h1 {
            margin: 0;
            max-width: 720px;
            font-size: clamp(44px, 5vw, 76px);
            line-height: 1.02;
            font-weight: 900;
            letter-spacing: -0.045em;
            color: #101828;
        }

        .rm-preview-copy p {
            margin: 22px 0 0;
            max-width: 740px;
            color: #5d6b84;
            font-size: 19px;
            line-height: 1.72;
            font-weight: 500;
        }

        .rm-module-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .rm-module-chip {
            min-height: 44px;
            padding: 0 17px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            color: var(--rm-primary-dark);
            background: rgba(255, 255, 255, .78);
            border: 1px solid var(--rm-border);
            font-size: 15px;
            font-weight: 900;
            box-shadow: var(--rm-shadow-sm);
        }

        .rm-preview-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .rm-preview-card {
            position: relative;
            min-height: 138px;
            padding: 22px;
            border-radius: 24px;
            background: rgba(255, 255, 255, .78);
            border: 1px solid rgba(219, 227, 238, .95);
            box-shadow: 0 16px 40px rgba(15, 23, 42, .07);
            overflow: hidden;
        }

        .rm-preview-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 46px;
            height: 46px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            color: var(--rm-primary-dark);
            background: linear-gradient(135deg, #e7f7f5, #f7fffd);
            border: 1px solid #cfeee8;
        }

        .rm-preview-card span {
            display: block;
            color: #667085;
            font-size: 15px;
            font-weight: 600;
            line-height: 1.4;
        }

        .rm-preview-card strong {
            display: block;
            margin: 8px 0;
            padding-right: 56px;
            color: #020617;
            font-size: 24px;
            line-height: 1.12;
            font-weight: 900;
            letter-spacing: -0.035em;
        }

        .rm-preview-card span:first-of-type {
            padding-right: 56px;
        }

        .rm-digitbol-box {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 18px;
            border-radius: 24px;
            background: rgba(255, 255, 255, .76);
            border: 1px solid rgba(219, 227, 238, .94);
            box-shadow: var(--rm-shadow-sm);
        }

        .rm-digitbol-info {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .rm-digitbol-logo {
            width: 64px;
            height: 64px;
            object-fit: contain;
            border-radius: 18px;
            background: #fff;
            border: 1px solid #e7edf5;
            padding: 5px;
            flex: 0 0 auto;
        }

        .rm-digitbol-info span {
            display: block;
            color: #667085;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 4px;
        }

        .rm-digitbol-info strong {
            display: block;
            color: #0f172a;
            font-size: 18px;
            font-weight: 900;
            letter-spacing: -0.03em;
        }

        .rm-digitbol-info small {
            display: block;
            margin-top: 5px;
            color: #667085;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.4;
        }

        .rm-digitbol-action {
            min-height: 44px;
            padding: 0 16px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            background: #25D366;
            color: white;
            font-size: 13px;
            font-weight: 900;
            box-shadow: 0 14px 28px rgba(37, 211, 102, .20);
            white-space: nowrap;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .rm-digitbol-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 34px rgba(37, 211, 102, .28);
        }

        .rm-auth-form-panel {
            position: relative;
            display: grid;
            align-items: center;
            padding: 48px;
            background:
                radial-gradient(circle at bottom left, rgba(15, 143, 127, .08), transparent 36%),
                rgba(255, 255, 255, .94);
        }

        .rm-auth-form-panel::before {
            content: "";
            position: absolute;
            left: 0;
            top: 42px;
            bottom: 42px;
            width: 1px;
            background: linear-gradient(180deg, transparent, #dbe3ee, transparent);
        }

        /* LOGIN FORM */
        .rm-login-page {
            width: 100%;
        }

        .rm-login-shell {
            width: 100%;
            max-width: 470px;
            margin: 0 auto;
            display: grid;
            gap: 22px;
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
            color: inherit;
        }

        .rm-login-brand-title {
            display: block;
            font-size: 22px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.035em;
            line-height: 1;
        }

        .rm-login-brand-subtitle {
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
            color: #111827;
            font-size: clamp(31px, 4.4vw, 40px);
            line-height: 1.08;
            font-weight: 900;
            letter-spacing: -0.035em;
        }

        .rm-login-copy p {
            margin: 12px 0 0;
            color: #667085;
            line-height: 1.7;
            font-size: 15px;
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

        .rm-remember-text,
        .rm-forgot {
            white-space: nowrap;
        }

        .rm-forgot {
            color: #087568;
            font-size: 14px;
            font-weight: 900;
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
        }

        .rm-form-digitbol {
            padding: 16px;
            border-radius: 20px;
            background: linear-gradient(135deg, #ffffff 0%, #f7fbfd 100%);
            border: 1px solid #dbe3ee;
            box-shadow: var(--rm-shadow-sm);
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .rm-form-digitbol img {
            width: 54px;
            height: 54px;
            object-fit: contain;
            border-radius: 15px;
            border: 1px solid #e7edf5;
            padding: 4px;
            background: #fff;
        }

        .rm-form-digitbol span {
            display: block;
            color: #667085;
            font-size: 12px;
            font-weight: 800;
        }

        .rm-form-digitbol strong {
            display: block;
            color: #0f172a;
            font-size: 16px;
            font-weight: 900;
            margin-top: 2px;
        }

        .rm-form-digitbol a {
            display: inline-block;
            margin-top: 6px;
            color: #087568;
            font-size: 13px;
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

        @media (max-width: 1180px) {
            .rm-auth-layout {
                grid-template-columns: 1fr;
            }

            .rm-auth-preview {
                padding: 34px;
            }

            .rm-auth-form-panel {
                padding: 38px;
            }

            .rm-auth-form-panel::before {
                display: none;
            }

            .rm-preview-copy h1 {
                font-size: 48px;
            }
        }

        @media (max-width: 640px) {
            .rm-auth-shell {
                min-height: 100vh;
                padding: 0;
                display: block;
            }

            .rm-auth-layout {
                min-height: 100vh;
                border: 0;
                border-radius: 0;
                box-shadow: none;
                background: transparent;
            }

            .rm-auth-preview {
                display: none;
            }

            .rm-auth-form-panel {
                min-height: 100vh;
                padding: 22px 18px 30px;
                background:
                    radial-gradient(circle at top left, rgba(15, 143, 127, .11), transparent 34%),
                    linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
            }

            .rm-login-shell {
                max-width: 100%;
                gap: 19px;
            }

            .rm-login-head {
                align-items: center;
            }

            .rm-brand-mark {
                width: 50px;
                height: 50px;
                border-radius: 17px;
            }

            .rm-login-brand-title {
                font-size: 21px;
            }

            .rm-login-brand-subtitle,
            .rm-auth-badge {
                display: none;
            }

            .rm-login-copy h2 {
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
        }

        @media (max-width: 390px) {
            .rm-login-options {
                flex-direction: column;
                align-items: flex-start;
            }

            .rm-form-digitbol {
                align-items: flex-start;
            }
        }
    </style>
</head>

<body class="font-sans antialiased">
    <main class="rm-auth-shell">
        <div class="rm-auth-layout">
            <section class="rm-auth-preview" aria-label="Resumen de Rumika SaaS">
                <div class="rm-preview-main">
                    <div class="rm-preview-copy">
                        <div class="rm-preview-kicker">
                            <span class="rm-preview-kicker-dot"></span>
                            Plataforma escalable para múltiples sucursales
                        </div>

                        <h1>Agenda, clientes e historial en bloques.</h1>

                        <p>
                            Una base modular para clínicas, spas, centros de belleza, barberías y dentistas.
                            Cada sucursal podrá activar sus módulos según su tipo de negocio.
                        </p>
                    </div>

                    <div class="rm-module-strip" aria-label="Módulos iniciales">
                        <span class="rm-module-chip">Agenda</span>
                        <span class="rm-module-chip">Clientes</span>
                        <span class="rm-module-chip">Historial</span>
                        <span class="rm-module-chip">Sucursales</span>
                    </div>

                    <div class="rm-preview-grid">
                        <article class="rm-preview-card">
                            <div class="rm-preview-icon">
                                <svg width="23" height="23" viewBox="0 0 24 24" fill="none">
                                    <rect x="3" y="5" width="18" height="16" rx="3" stroke="currentColor" stroke-width="2"/>
                                    <path d="M8 3V7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M16 3V7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M3 10H21" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </div>
                            <span>Hoy</span>
                            <strong>18 citas</strong>
                            <span>4 tipos de servicio activos</span>
                        </article>

                        <article class="rm-preview-card">
                            <div class="rm-preview-icon">
                                <svg width="23" height="23" viewBox="0 0 24 24" fill="none">
                                    <path d="M4 21V8.5L12 3L20 8.5V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M9 21V14H15V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <span>Sucursal</span>
                            <strong>Centro</strong>
                            <span>Clínica estética</span>
                        </article>

                        <article class="rm-preview-card">
                            <div class="rm-preview-icon">
                                <svg width="23" height="23" viewBox="0 0 24 24" fill="none">
                                    <path d="M16 19C16 16.7909 14.2091 15 12 15C9.79086 15 8 16.7909 8 19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                                    <path d="M19 8V13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M21.5 10.5H16.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <span>Cliente</span>
                            <strong>Historial visible</strong>
                            <span>Desde cada cita en agenda</span>
                        </article>

                        <article class="rm-preview-card">
                            <div class="rm-preview-icon">
                                <svg width="23" height="23" viewBox="0 0 24 24" fill="none">
                                    <path d="M4 7.5L12 4L20 7.5L12 11L4 7.5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                    <path d="M4 12.5L12 16L20 12.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M4 17.5L12 21L20 17.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <span>Marca</span>
                            <strong>Logo propio</strong>
                            <span>Nombre y sucursales configurables</span>
                        </article>
                    </div>
                </div>

                <div class="rm-digitbol-box">
                    <div class="rm-digitbol-info">
                        <img src="{{ asset('digitbol-logo.jpg') }}" alt="DigitBol" class="rm-digitbol-logo">

                        <div>
                            <span>Desarrollado por</span>
                            <strong>DigitBol</strong>
                            <small>Sistemas web, SaaS y soluciones a medida para negocios.</small>
                        </div>
                    </div>

                    <a
                        href="https://wa.me/59177348087?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20DigitBol%20y%20sus%20sistemas."
                        target="_blank"
                        rel="noopener"
                        class="rm-digitbol-action"
                    >
                        Hablar por WhatsApp
                    </a>
                </div>
            </section>

            <section class="rm-auth-form-panel">
                {{ $slot }}
            </section>
        </div>
    </main>

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
</body>

</html>
