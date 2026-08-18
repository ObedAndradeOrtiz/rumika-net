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
            --rm-primary-deep: #054d45;
            --rm-primary-soft: #e6f7f4;
            --rm-text: #101828;
            --rm-muted: #667085;
            --rm-border: #dbe3ee;
            --rm-white: #ffffff;
            --rm-bg: #f5f8fc;
            --rm-shadow-lg: 0 30px 90px rgba(15, 23, 42, .14);
            --rm-shadow-md: 0 18px 50px rgba(15, 23, 42, .09);
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
                radial-gradient(circle at top left, rgba(15, 143, 127, .14), transparent 32%),
                radial-gradient(circle at bottom right, rgba(29, 79, 145, .10), transparent 34%),
                linear-gradient(135deg, #eef8f7 0%, #f8fbff 50%, #f3f6fb 100%);
        }

        a {
            text-decoration: none;
        }

        .rm-auth-shell {
            min-height: 100vh;
            width: 100%;
            padding: 34px;
            display: grid;
            place-items: center;
        }

        .rm-auth-layout {
            width: min(1450px, 100%);
            min-height: calc(100vh - 68px);
            display: grid;
            grid-template-columns: minmax(0, 1.08fr) minmax(430px, .92fr);
            gap: 0;
            border-radius: 34px;
            overflow: hidden;
            background: rgba(255, 255, 255, .74);
            border: 1px solid rgba(219, 227, 238, .92);
            box-shadow: var(--rm-shadow-lg);
            backdrop-filter: blur(18px);
        }

        .rm-auth-preview {
            position: relative;
            overflow: hidden;
            padding: 48px 54px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 34px;
            background:
                radial-gradient(circle at 20% 10%, rgba(15, 143, 127, .18), transparent 28%),
                linear-gradient(135deg, rgba(255, 255, 255, .84), rgba(244, 250, 251, .76));
        }

        .rm-auth-preview::before {
            content: "";
            position: absolute;
            width: 310px;
            height: 310px;
            border-radius: 999px;
            right: -120px;
            top: -110px;
            background: radial-gradient(circle, rgba(15, 143, 127, .16), transparent 68%);
            pointer-events: none;
        }

        .rm-auth-preview::after {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            border-radius: 999px;
            left: -130px;
            bottom: -90px;
            background: radial-gradient(circle, rgba(29, 79, 145, .10), transparent 68%);
            pointer-events: none;
        }

        .rm-preview-inner {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 36px;
        }

        .rm-auth-brand {
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
            box-shadow: 0 18px 36px rgba(15, 143, 127, .25);
            flex: 0 0 auto;
        }

        .rm-auth-brand-title {
            display: block;
            font-size: 28px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -0.055em;
            color: #0f172a;
        }

        .rm-auth-brand-subtitle {
            display: block;
            margin-top: 6px;
            font-size: 13px;
            color: var(--rm-muted);
            font-weight: 700;
        }

        .rm-auth-copy {
            max-width: 760px;
        }

        .rm-auth-kicker {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            margin-bottom: 22px;
            border-radius: 999px;
            background: rgba(15, 143, 127, .09);
            border: 1px solid rgba(15, 143, 127, .14);
            color: var(--rm-primary-dark);
            font-size: 14px;
            font-weight: 900;
        }

        .rm-auth-kicker-dot {
            width: 9px;
            height: 9px;
            border-radius: 999px;
            background: var(--rm-primary);
            box-shadow: 0 0 0 6px rgba(15, 143, 127, .12);
        }

        .rm-auth-copy h1 {
            margin: 0;
            max-width: 780px;
            color: #101828;
            font-size: clamp(48px, 5.8vw, 86px);
            line-height: .98;
            font-weight: 900;
            letter-spacing: -0.08em;
        }

        .rm-auth-copy p {
            margin: 22px 0 0;
            max-width: 760px;
            color: #5d6b84;
            font-size: clamp(18px, 1.65vw, 22px);
            line-height: 1.72;
            font-weight: 500;
        }

        .rm-module-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .rm-module-chip {
            display: inline-flex;
            align-items: center;
            min-height: 46px;
            padding: 0 17px;
            border-radius: 999px;
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
            gap: 18px;
        }

        .rm-preview-card {
            position: relative;
            min-height: 146px;
            padding: 24px;
            border-radius: 24px;
            background: rgba(255, 255, 255, .76);
            border: 1px solid rgba(219, 227, 238, .96);
            box-shadow: 0 18px 44px rgba(15, 23, 42, .07);
            backdrop-filter: blur(12px);
            overflow: hidden;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .rm-preview-card:hover {
            transform: translateY(-3px);
            border-color: rgba(15, 143, 127, .25);
            box-shadow: 0 22px 54px rgba(15, 23, 42, .10);
        }

        .rm-preview-icon {
            position: absolute;
            top: 22px;
            right: 22px;
            width: 48px;
            height: 48px;
            border-radius: 17px;
            display: grid;
            place-items: center;
            color: var(--rm-primary-dark);
            background: linear-gradient(135deg, #e7f7f5, #f7fffd);
            border: 1px solid #cfeee8;
        }

        .rm-preview-card span {
            display: block;
            position: relative;
            z-index: 1;
            color: #667085;
            font-size: 16px;
            font-weight: 600;
            line-height: 1.45;
        }

        .rm-preview-card strong {
            display: block;
            position: relative;
            z-index: 1;
            margin: 8px 0;
            padding-right: 58px;
            color: #020617;
            font-size: 26px;
            line-height: 1.08;
            font-weight: 900;
            letter-spacing: -0.045em;
        }

        .rm-preview-card span:first-of-type {
            padding-right: 58px;
        }

        .rm-preview-card span:last-of-type {
            max-width: 280px;
            padding-right: 28px;
            font-size: 15px;
            color: #5d6b84;
        }

        .rm-digitbol-mini {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 18px;
            border-radius: 24px;
            background: rgba(255, 255, 255, .74);
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
            background: white;
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
            letter-spacing: .045em;
            margin-bottom: 4px;
        }

        .rm-digitbol-info strong {
            display: block;
            color: #0f172a;
            font-size: 18px;
            font-weight: 900;
            letter-spacing: -0.035em;
            line-height: 1.15;
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
                rgba(255, 255, 255, .92);
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

        @media (max-width: 1180px) {
            .rm-auth-layout {
                grid-template-columns: 1fr;
            }

            .rm-auth-preview {
                padding: 38px;
            }

            .rm-auth-form-panel {
                padding: 38px;
            }

            .rm-auth-form-panel::before {
                display: none;
            }

            .rm-auth-copy h1 {
                font-size: 52px;
            }
        }

        @media (max-width: 820px) {
            .rm-auth-shell {
                padding: 18px;
                align-items: start;
            }

            .rm-auth-layout {
                min-height: auto;
                border-radius: 28px;
            }

            .rm-auth-preview {
                padding: 24px;
                gap: 22px;
            }

            .rm-preview-inner {
                gap: 22px;
            }

            .rm-auth-brand-title {
                font-size: 24px;
            }

            .rm-auth-brand-subtitle {
                display: none;
            }

            .rm-brand-mark {
                width: 50px;
                height: 50px;
                border-radius: 17px;
            }

            .rm-auth-kicker {
                width: 100%;
                justify-content: center;
                text-align: center;
                font-size: 12px;
                padding: 10px 12px;
                margin-bottom: 16px;
            }

            .rm-auth-copy h1 {
                font-size: 34px;
                line-height: 1.08;
                letter-spacing: -0.055em;
            }

            .rm-auth-copy p {
                margin-top: 16px;
                font-size: 15.5px;
                line-height: 1.62;
            }

            .rm-module-strip {
                gap: 8px;
            }

            .rm-module-chip {
                min-height: 38px;
                padding: 0 13px;
                font-size: 13px;
            }

            .rm-preview-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .rm-preview-card {
                min-height: 122px;
                padding: 20px;
                border-radius: 20px;
            }

            .rm-preview-icon {
                top: 18px;
                right: 18px;
                width: 42px;
                height: 42px;
                border-radius: 15px;
            }

            .rm-preview-card strong {
                font-size: 22px;
            }

            .rm-auth-form-panel {
                padding: 26px 20px 28px;
            }

            .rm-digitbol-mini {
                align-items: flex-start;
                flex-direction: column;
            }

            .rm-digitbol-action {
                width: 100%;
            }
        }

        @media (max-width: 560px) {
            .rm-auth-shell {
                padding: 0;
                display: block;
                background: transparent;
            }

            .rm-auth-layout {
                width: 100%;
                min-height: 100vh;
                border-radius: 0;
                border: 0;
                box-shadow: none;
            }

            .rm-auth-preview {
                padding: 18px 18px 0;
                background: transparent;
            }

            .rm-auth-copy,
            .rm-module-strip,
            .rm-preview-grid,
            .rm-digitbol-mini {
                display: none;
            }

            .rm-preview-inner {
                gap: 0;
            }

            .rm-auth-brand {
                width: 100%;
                justify-content: center;
                padding: 14px 0 8px;
            }

            .rm-auth-brand-title {
                font-size: 23px;
            }

            .rm-auth-form-panel {
                padding: 22px 18px 30px;
                background: transparent;
            }
        }
    </style>
</head>

<body class="font-sans antialiased">
    <main class="rm-auth-shell">
        <div class="rm-auth-layout">
            <section class="rm-auth-preview" aria-label="Resumen de Rumika SaaS">
                <div class="rm-preview-inner">
                    <a href="{{ url('/') }}" class="rm-auth-brand" wire:navigate>
                        <span class="rm-brand-mark">
                            <x-application-logo class="h-7 w-7 text-white" />
                        </span>

                        <span>
                            <span class="rm-auth-brand-title">Rumika SaaS</span>
                            <span class="rm-auth-brand-subtitle">Sistema modular para negocios de atención</span>
                        </span>
                    </a>

                    <div class="rm-auth-copy">
                        <div class="rm-auth-kicker">
                            <span class="rm-auth-kicker-dot"></span>
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

                <div class="rm-digitbol-mini">
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
                        <svg width="18" height="18" viewBox="0 0 32 32" fill="none">
                            <path fill="currentColor" d="M16.02 4C9.4 4 4.02 9.28 4.02 15.78c0 2.08.56 4.1 1.62 5.88L4 28l6.52-1.68a12.2 12.2 0 0 0 5.5 1.32C22.64 27.64 28 22.36 28 15.86C28.02 9.36 22.64 4 16.02 4Zm0 21.62c-1.78 0-3.52-.46-5.04-1.34l-.36-.22l-3.86 1l1.02-3.68l-.24-.38a9.66 9.66 0 0 1-1.5-5.18c0-5.38 4.48-9.76 9.98-9.76S26 10.44 26 15.82c0 5.4-4.48 9.8-9.98 9.8Zm5.48-7.32c-.3-.14-1.78-.86-2.06-.96c-.28-.1-.48-.14-.68.14c-.2.3-.78.96-.96 1.16c-.18.2-.36.22-.66.08c-.3-.14-1.26-.46-2.4-1.46c-.88-.78-1.48-1.74-1.66-2.04c-.18-.3-.02-.46.14-.6c.14-.14.3-.36.46-.54c.16-.18.2-.3.3-.5c.1-.2.06-.38-.02-.54c-.08-.14-.68-1.62-.94-2.22c-.24-.58-.5-.5-.68-.52h-.58c-.2 0-.52.08-.8.38c-.28.3-1.04 1-1.04 2.44s1.06 2.84 1.2 3.04c.14.2 2.08 3.12 5.04 4.38c.7.3 1.26.48 1.68.62c.7.22 1.34.18 1.84.12c.56-.08 1.78-.72 2.04-1.42c.26-.7.26-1.3.18-1.42c-.08-.14-.28-.22-.58-.36Z"/>
                        </svg>
                        Hablar con DigitBol
                    </a>
                </div>
            </section>

            <section class="rm-auth-card rm-auth-form-panel">
                {{ $slot }}
            </section>
        </div>
    </main>
</body>
</html>
