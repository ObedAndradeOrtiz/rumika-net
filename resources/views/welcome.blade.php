<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rumika SaaS | Sistema modular para negocios de atención</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900" rel="stylesheet" />

    <style>
        :root {
            --primary: #0f8f7f;
            --primary-dark: #087568;
            --primary-darker: #04584f;
            --primary-soft: #e7f7f5;
            --bg: #f5f8fc;
            --white: #ffffff;
            --text: #101827;
            --muted: #64708a;
            --border: #d9e3ef;
            --shadow: 0 24px 70px rgba(15, 32, 55, .12);
            --radius-xl: 34px;
            --radius-lg: 24px;
            --radius-md: 16px;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: 'Figtree', sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(15, 143, 127, .16), transparent 36%),
                radial-gradient(circle at bottom right, rgba(15, 143, 127, .10), transparent 34%),
                linear-gradient(135deg, #eef8f7 0%, #f8fbff 48%, #eef3f9 100%);
            min-height: 100vh;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input,
        textarea {
            font-family: inherit;
        }

        .page {
            width: min(1380px, calc(100% - 48px));
            margin: 0 auto;
        }

        .navbar {
            min-height: 92px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 27px;
            font-weight: 900;
            letter-spacing: -0.045em;
        }

        .brand-icon {
            width: 58px;
            height: 58px;
            border-radius: 20px;
            background: linear-gradient(145deg, var(--primary), #0aa591);
            display: grid;
            place-items: center;
            box-shadow: 0 18px 36px rgba(15, 143, 127, .26);
            color: #ffffff;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 24px;
            color: var(--muted);
            font-weight: 700;
            font-size: 15px;
        }

        .nav-links a:hover {
            color: var(--primary-dark);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            min-height: 48px;
            padding: 0 22px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
            font-weight: 900;
            font-size: 15px;
            cursor: pointer;
            transition: .22s ease;
            white-space: nowrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 18px 36px rgba(15, 143, 127, .25);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 22px 46px rgba(15, 143, 127, .32);
        }

        .btn-outline {
            background: rgba(255, 255, 255, .76);
            color: var(--primary-dark);
            border-color: var(--border);
            backdrop-filter: blur(8px);
        }

        .btn-outline:hover {
            transform: translateY(-2px);
            background: white;
        }

        .btn-light {
            background: #ffffff;
            color: var(--primary-dark);
            border-color: rgba(255, 255, 255, .5);
        }

        .hero {
            padding: 64px 0 92px;
            display: grid;
            grid-template-columns: minmax(0, 1.08fr) minmax(420px, .92fr);
            align-items: center;
            gap: 64px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .78);
            border: 1px solid var(--border);
            color: var(--primary-dark);
            font-weight: 900;
            margin-bottom: 24px;
            box-shadow: 0 12px 30px rgba(15, 32, 55, .06);
        }

        .eyebrow-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--primary);
            box-shadow: 0 0 0 6px rgba(15, 143, 127, .14);
        }

        h1 {
            margin: 0;
            font-size: clamp(45px, 6vw, 88px);
            line-height: .98;
            letter-spacing: -0.075em;
            color: #111827;
        }

        .hero-text {
            margin: 26px 0 0;
            max-width: 760px;
            color: var(--muted);
            font-size: clamp(18px, 2vw, 23px);
            line-height: 1.72;
        }

        .hero-actions {
            margin-top: 34px;
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
        }

        .trust-line {
            margin-top: 22px;
            color: var(--muted);
            font-weight: 700;
            font-size: 15px;
        }

        .trust-line strong {
            color: var(--primary-dark);
        }

        .chips {
            margin-top: 34px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .chip {
            padding: 12px 18px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .78);
            border: 1px solid var(--border);
            color: #00695d;
            font-weight: 900;
            box-shadow: 0 8px 22px rgba(15, 32, 55, .05);
        }

        .hero-panel {
            position: relative;
        }

        .dashboard-card {
            border-radius: var(--radius-xl);
            background: rgba(255, 255, 255, .88);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            padding: 34px;
            overflow: hidden;
            backdrop-filter: blur(16px);
        }

        .dashboard-top {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: flex-start;
            margin-bottom: 26px;
        }

        .mini-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .mini-icon {
            width: 54px;
            height: 54px;
            border-radius: 18px;
            background: linear-gradient(145deg, var(--primary), #0aa591);
            color: white;
            display: grid;
            place-items: center;
        }

        .mini-title strong {
            display: block;
            font-size: 20px;
            letter-spacing: -0.04em;
        }

        .mini-title span {
            display: block;
            color: var(--muted);
            font-weight: 600;
            margin-top: 3px;
        }

        .status {
            padding: 10px 14px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-weight: 900;
            font-size: 13px;
        }

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .metric {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 20px;
            min-height: 122px;
        }

        .metric span {
            display: block;
            color: var(--muted);
            font-weight: 700;
            margin-bottom: 8px;
        }

        .metric strong {
            display: block;
            font-size: 28px;
            letter-spacing: -0.05em;
        }

        .metric small {
            display: block;
            color: var(--muted);
            margin-top: 8px;
            line-height: 1.45;
        }

        .module-list {
            margin-top: 18px;
            display: grid;
            gap: 12px;
        }

        .module-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding: 15px;
            border-radius: 18px;
            background: #f8fbff;
            border: 1px solid #e1e8f2;
        }

        .module-name {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 900;
        }

        .module-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary);
        }

        .module-state {
            color: var(--primary-dark);
            font-size: 13px;
            font-weight: 900;
        }

        .floating-info {
            position: absolute;
            left: -28px;
            bottom: 36px;
            width: 235px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 18px;
            box-shadow: 0 24px 60px rgba(15, 32, 55, .16);
        }

        .floating-info span {
            display: block;
            color: var(--muted);
            font-weight: 700;
            margin-bottom: 5px;
        }

        .floating-info strong {
            font-size: 22px;
            letter-spacing: -0.045em;
        }

        .section {
            padding: 82px 0;
        }

        .section-header {
            max-width: 760px;
            margin-bottom: 34px;
        }

        .section-kicker {
            color: var(--primary-dark);
            font-weight: 900;
            margin-bottom: 10px;
        }

        .section-title {
            margin: 0;
            font-size: clamp(34px, 4vw, 54px);
            line-height: 1.05;
            letter-spacing: -0.065em;
        }

        .section-text {
            margin: 18px 0 0;
            color: var(--muted);
            font-size: 19px;
            line-height: 1.7;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .card {
            background: rgba(255, 255, 255, .78);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 26px;
            box-shadow: 0 16px 45px rgba(15, 32, 55, .07);
            backdrop-filter: blur(12px);
        }

        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            background: var(--primary-soft);
            color: var(--primary-dark);
            display: grid;
            place-items: center;
            font-weight: 900;
            margin-bottom: 18px;
        }

        .card h3 {
            margin: 0;
            font-size: 22px;
            letter-spacing: -0.035em;
        }

        .card p {
            margin: 12px 0 0;
            color: var(--muted);
            line-height: 1.65;
            font-size: 16px;
        }

        .about-box {
            background:
                linear-gradient(135deg, rgba(15, 143, 127, .96), rgba(4, 88, 79, .96)),
                radial-gradient(circle at top right, rgba(255, 255, 255, .22), transparent 40%);
            color: white;
            border-radius: var(--radius-xl);
            padding: 48px;
            box-shadow: var(--shadow);
            display: grid;
            grid-template-columns: 1fr .8fr;
            gap: 34px;
            align-items: center;
        }

        .about-box h2 {
            margin: 0;
            font-size: clamp(34px, 4vw, 52px);
            letter-spacing: -0.06em;
            line-height: 1.06;
        }

        .about-box p {
            margin: 18px 0 0;
            color: rgba(255, 255, 255, .82);
            font-size: 18px;
            line-height: 1.7;
        }

        .about-points {
            display: grid;
            gap: 12px;
        }

        .about-point {
            padding: 16px;
            border-radius: 18px;
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .18);
            font-weight: 800;
        }

        .faq {
            display: grid;
            gap: 14px;
        }

        .faq-item {
            background: rgba(255, 255, 255, .8);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 22px 24px;
            box-shadow: 0 12px 34px rgba(15, 32, 55, .05);
        }

        .faq-item h3 {
            margin: 0;
            font-size: 19px;
            letter-spacing: -0.03em;
        }

        .faq-item p {
            margin: 10px 0 0;
            color: var(--muted);
            line-height: 1.65;
        }

        .cta {
            padding: 70px 0 95px;
        }

        .cta-box {
            border-radius: var(--radius-xl);
            background: #ffffff;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            padding: 44px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 28px;
        }

        .cta-box h2 {
            margin: 0;
            font-size: clamp(30px, 4vw, 46px);
            letter-spacing: -0.06em;
        }

        .cta-box p {
            margin: 12px 0 0;
            color: var(--muted);
            font-size: 18px;
            line-height: 1.6;
        }

        .footer {
            padding: 26px 0 36px;
            border-top: 1px solid rgba(217, 227, 239, .9);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            color: var(--muted);
            font-weight: 700;
        }

        .footer strong {
            color: var(--primary-dark);
        }

        .float-actions {
            position: fixed;
            right: 22px;
            bottom: 22px;
            display: grid;
            gap: 12px;
            z-index: 60;
        }

        .float-btn {
            width: 58px;
            height: 58px;
            border-radius: 999px;
            border: 0;
            display: grid;
            place-items: center;
            cursor: pointer;
            box-shadow: 0 16px 36px rgba(15, 32, 55, .2);
            transition: .2s ease;
        }

        .float-btn:hover {
            transform: translateY(-3px);
        }

        .bot-btn {
            background: #111827;
            color: white;
        }

        .whatsapp-btn {
            background: #22c55e;
            color: white;
        }

        .chat-modal {
            position: fixed;
            right: 22px;
            bottom: 96px;
            width: min(390px, calc(100vw - 44px));
            height: 560px;
            max-height: calc(100vh - 130px);
            background: white;
            border: 1px solid var(--border);
            border-radius: 26px;
            box-shadow: 0 24px 80px rgba(15, 32, 55, .24);
            z-index: 80;
            display: none;
            overflow: hidden;
        }

        .chat-modal.active {
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            padding: 18px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }

        .chat-user {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chat-avatar {
            width: 42px;
            height: 42px;
            border-radius: 15px;
            background: rgba(255, 255, 255, .16);
            display: grid;
            place-items: center;
        }

        .chat-user strong {
            display: block;
            font-size: 16px;
        }

        .chat-user span {
            display: block;
            font-size: 13px;
            opacity: .82;
            margin-top: 2px;
        }

        .chat-close {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            border: 0;
            background: rgba(255, 255, 255, .16);
            color: white;
            cursor: pointer;
            font-size: 22px;
            line-height: 1;
        }

        .chat-messages {
            flex: 1;
            padding: 18px;
            overflow-y: auto;
            background: #f7fafc;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .message {
            max-width: 86%;
            padding: 12px 14px;
            border-radius: 16px;
            font-size: 14px;
            line-height: 1.55;
        }

        .message.bot {
            align-self: flex-start;
            background: white;
            border: 1px solid #e4ebf5;
            color: #172033;
        }

        .message.user {
            align-self: flex-end;
            background: var(--primary);
            color: white;
        }

        .chat-form {
            padding: 14px;
            background: white;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 10px;
        }

        .chat-form input {
            flex: 1;
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 0 15px;
            height: 44px;
            outline: none;
            color: var(--text);
        }

        .chat-form input:focus {
            border-color: var(--primary);
        }

        .chat-form button {
            width: 44px;
            height: 44px;
            border-radius: 999px;
            border: 0;
            background: var(--primary);
            color: white;
            cursor: pointer;
            font-weight: 900;
        }

        @media (max-width: 1120px) {
            .hero {
                grid-template-columns: 1fr;
                gap: 42px;
            }

            .hero-panel {
                max-width: 720px;
            }

            .floating-info {
                left: auto;
                right: 18px;
            }

            .cards-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .about-box {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 800px) {
            .page {
                width: min(100% - 34px, 1380px);
            }

            .navbar {
                min-height: 78px;
            }

            .brand {
                font-size: 22px;
            }

            .brand-icon {
                width: 48px;
                height: 48px;
                border-radius: 16px;
            }

            .nav-links,
            .nav-actions {
                display: none;
            }

            .hero {
                padding: 38px 0 70px;
            }

            h1 {
                font-size: 45px;
                line-height: 1.03;
            }

            .hero-text {
                font-size: 17px;
            }

            .hero-actions {
                display: grid;
                grid-template-columns: 1fr;
            }

            .btn {
                width: 100%;
                min-height: 54px;
            }

            .chips {
                gap: 9px;
            }

            .chip {
                font-size: 14px;
                padding: 10px 14px;
            }

            .dashboard-card {
                padding: 24px;
                border-radius: 26px;
            }

            .dashboard-top {
                flex-direction: column;
            }

            .metric-grid,
            .cards-grid {
                grid-template-columns: 1fr;
            }

            .floating-info {
                position: static;
                margin-top: 16px;
                width: 100%;
            }

            .section {
                padding: 58px 0;
            }

            .about-box,
            .cta-box {
                padding: 28px;
                border-radius: 26px;
            }

            .cta-box {
                display: grid;
            }

            .footer {
                flex-direction: column;
                align-items: flex-start;
            }

            .float-actions {
                right: 16px;
                bottom: 16px;
            }

            .float-btn {
                width: 54px;
                height: 54px;
            }


            .chat-modal {
                right: 12px;
                bottom: 84px;
                width: calc(100vw - 24px);
                height: 530px;
            }
        }
    </style>
</head>

<body>
    <div class="page">
        <header class="navbar">
            <a href="{{ url('/') }}" class="brand">
                <span class="brand-icon">
                    <x-application-logo class="h-7 w-7 text-white" />
                </span>
                <span>Rumika SaaS</span>
            </a>

            <nav class="nav-links">
                <a href="#que-es">Qué es Rumika</a>
                <a href="#modulos">Módulos</a>
                <a href="#nosotros">Quiénes somos</a>
                <a href="#faq">Preguntas frecuentes</a>
            </nav>

            <div class="nav-actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">
                        Entrar al sistema
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline">
                        Iniciar sesión
                    </a>

                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-primary">
                            Registrar empresa
                        </a>
                    @endif
                @endauth
            </div>
        </header>

        <main>
            <section class="hero">
                <div>
                    <div class="eyebrow">
                        <span class="eyebrow-dot"></span>
                        Sistema base modular para negocios de atención
                    </div>

                    <h1>
                        Gestiona agenda, clientes, inventario y sucursales desde un solo lugar.
                    </h1>

                    <p class="hero-text">
                        Rumika es una plataforma SaaS diseñada para clínicas, spas, centros de belleza, barberías,
                        dentistas y negocios que necesitan organizar sus citas, clientes, servicios, inventarios,
                        pagos e historial de atención de manera simple y profesional.
                    </p>

                    <div class="hero-actions">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                                Entrar al sistema
                            </a>
                        @else
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn btn-primary">
                                    Registrar empresa
                                </a>
                            @endif

                            <a href="{{ route('login') }}" class="btn btn-outline">
                                Iniciar sesión
                            </a>
                        @endauth
                    </div>

                    <div class="trust-line">
                        Un sistema más de <strong>DigitBol</strong>, creado para negocios que quieren crecer con orden.
                    </div>

                    <div class="chips">
                        <span class="chip">Agenda</span>
                        <span class="chip">Clientes</span>
                        <span class="chip">Inventario</span>
                        <span class="chip">Sucursales</span>
                        <span class="chip">Reportes</span>
                    </div>
                </div>

                <div class="hero-panel">
                    <div class="dashboard-card">
                        <div class="dashboard-top">
                            <div class="mini-brand">
                                <div class="mini-icon">
                                    <x-application-logo class="h-7 w-7 text-white" />
                                </div>

                                <div class="mini-title">
                                    <strong>Panel Rumika</strong>
                                    <span>Vista general por sucursal</span>
                                </div>
                            </div>

                            <span class="status">Modular</span>
                        </div>

                        <div class="metric-grid">
                            <div class="metric">
                                <span>Citas de hoy</span>
                                <strong>18</strong>
                                <small>Control de agenda por fecha, sucursal y estado.</small>
                            </div>

                            <div class="metric">
                                <span>Clientes activos</span>
                                <strong>324</strong>
                                <small>Historial completo de visitas, servicios y pagos.</small>
                            </div>
                        </div>

                        <div class="module-list">
                            <div class="module-item">
                                <div class="module-name">
                                    <span class="module-dot"></span>
                                    Agenda por sucursal
                                </div>
                                <span class="module-state">Activo</span>
                            </div>

                            <div class="module-item">
                                <div class="module-name">
                                    <span class="module-dot"></span>
                                    Inventario y productos
                                </div>
                                <span class="module-state">Activable</span>
                            </div>

                            <div class="module-item">
                                <div class="module-name">
                                    <span class="module-dot"></span>
                                    Pagos, caja y reportes
                                </div>
                                <span class="module-state">Activable</span>
                            </div>
                        </div>
                    </div>

                    <div class="floating-info">
                        <span>Cada sucursal puede activar</span>
                        <strong>sus propios módulos</strong>
                    </div>
                </div>
            </section>

            <section class="section" id="que-es">
                <div class="section-header">
                    <div class="section-kicker">Qué es Rumika</div>
                    <h2 class="section-title">
                        Una base flexible para administrar negocios con atención por agenda.
                    </h2>
                    <p class="section-text">
                        Rumika no es un sistema cerrado. Es una base modular que puede adaptarse al tipo de negocio,
                        permitiendo activar funciones según las necesidades reales de cada empresa o sucursal.
                    </p>
                </div>

                <div class="cards-grid">
                    <div class="card">
                        <div class="card-icon">01</div>
                        <h3>Para distintos rubros</h3>
                        <p>
                            Ideal para clínicas, spas, centros estéticos, barberías, consultorios dentales,
                            centros de belleza y otros negocios que trabajan con clientes recurrentes.
                        </p>
                    </div>

                    <div class="card">
                        <div class="card-icon">02</div>
                        <h3>Por sucursal</h3>
                        <p>
                            Cada sucursal puede tener su propia agenda, clientes, servicios, usuarios,
                            inventario, caja y reportes según su operación diaria.
                        </p>
                    </div>

                    <div class="card">
                        <div class="card-icon">03</div>
                        <h3>Por módulos</h3>
                        <p>
                            Activa solo lo que necesitas: agenda, clientes, historial, inventario,
                            ventas, pagos, caja, reportes, roles y más.
                        </p>
                    </div>
                </div>
            </section>

            <section class="section" id="modulos">
                <div class="section-header">
                    <div class="section-kicker">Módulos principales</div>
                    <h2 class="section-title">
                        Herramientas pensadas para ordenar el trabajo diario.
                    </h2>
                </div>

                <div class="cards-grid">
                    <div class="card">
                        <div class="card-icon">A</div>
                        <h3>Agenda y citas</h3>
                        <p>
                            Registra citas, estados, horarios, servicios, profesionales y atención por sucursal.
                        </p>
                    </div>

                    <div class="card">
                        <div class="card-icon">C</div>
                        <h3>Clientes e historial</h3>
                        <p>
                            Consulta datos del cliente, visitas anteriores, tratamientos, observaciones y pagos.
                        </p>
                    </div>

                    <div class="card">
                        <div class="card-icon">I</div>
                        <h3>Inventario</h3>
                        <p>
                            Controla productos, stock, movimientos, sucursales, alertas y disponibilidad.
                        </p>
                    </div>

                    <div class="card">
                        <div class="card-icon">P</div>
                        <h3>Pagos y caja</h3>
                        <p>
                            Organiza ingresos, métodos de pago, cierres de caja, comprobantes y reportes.
                        </p>
                    </div>

                    <div class="card">
                        <div class="card-icon">R</div>
                        <h3>Reportes</h3>
                        <p>
                            Visualiza información clave para tomar decisiones por día, mes, sucursal o servicio.
                        </p>
                    </div>

                    <div class="card">
                        <div class="card-icon">U</div>
                        <h3>Usuarios y roles</h3>
                        <p>
                            Define permisos para administradores, recepción, profesionales y personal interno.
                        </p>
                    </div>
                </div>
            </section>

            <section class="section" id="nosotros">
                <div class="about-box">
                    <div>
                        <h2>
                            Creado por DigitBol para negocios que necesitan orden, velocidad y control.
                        </h2>

                        <p>
                            Somos un equipo enfocado en crear soluciones digitales útiles, simples y adaptables.
                            Rumika nace como una base SaaS para que distintos negocios puedan administrar su operación
                            sin depender de hojas de cálculo, mensajes perdidos o procesos desordenados.
                        </p>

                        <p>
                            La idea es clara: que cada empresa tenga una herramienta moderna, escalable y lista para
                            crecer.
                        </p>
                    </div>

                    <div class="about-points">
                        <div class="about-point">Sistema modular y escalable</div>
                        <div class="about-point">Diseñado para múltiples sucursales</div>
                        <div class="about-point">Preparado para crecer por rubro</div>
                        <div class="about-point">Un sistema más de DigitBol</div>
                    </div>
                </div>
            </section>

            <section class="section" id="faq">
                <div class="section-header">
                    <div class="section-kicker">Preguntas frecuentes</div>
                    <h2 class="section-title">
                        Respuestas rápidas antes de comenzar.
                    </h2>
                </div>

                <div class="faq">
                    <div class="faq-item">
                        <h3>¿Rumika sirve solo para spas?</h3>
                        <p>
                            No. Rumika está pensado para spas, clínicas, centros de belleza, barberías,
                            dentistas y cualquier negocio que necesite agenda, clientes, inventario y control por
                            sucursal.
                        </p>
                    </div>

                    <div class="faq-item">
                        <h3>¿Puedo activar solo algunos módulos?</h3>
                        <p>
                            Sí. Cada empresa o sucursal puede trabajar con los módulos que realmente necesita.
                            Por ejemplo, una sucursal puede usar agenda e historial, mientras otra también activa
                            inventario y caja.
                        </p>
                    </div>

                    <div class="faq-item">
                        <h3>¿Se puede usar con varias sucursales?</h3>
                        <p>
                            Sí. Rumika está preparado para separar información por sucursal y permitir una
                            administración más ordenada.
                        </p>
                    </div>

                    <div class="faq-item">
                        <h3>¿Puedo hablar con un representante?</h3>
                        <p>
                            Sí. Puedes escribir por WhatsApp desde el botón flotante o usar el asistente virtual para
                            resolver dudas iniciales.
                        </p>
                    </div>
                </div>
            </section>

            <section class="cta">
                <div class="cta-box">
                    <div>
                        <h2>Empieza a ordenar tu negocio con Rumika.</h2>
                        <p>
                            Registra tu empresa, activa los módulos necesarios y administra tu operación desde una
                            plataforma moderna.
                        </p>
                    </div>

                    <div class="hero-actions">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                                Entrar al sistema
                            </a>
                        @else
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn btn-primary">
                                    Registrar empresa
                                </a>
                            @endif

                            <a href="{{ route('login') }}" class="btn btn-outline">
                                Iniciar sesión
                            </a>
                        @endauth
                    </div>
                </div>
            </section>
        </main>

        <footer class="footer">
            <div>© {{ date('Y') }} Rumika SaaS. Todos los derechos reservados.</div>
            <div>Creado por <strong>DigitBol</strong></div>
        </footer>
    </div>

    <div class="float-actions">
        <button type="button" class="float-btn bot-btn" onclick="toggleChat()"
            aria-label="Abrir asistente virtual">
            <svg width="27" height="27" viewBox="0 0 24 24" fill="none">
                <path d="M12 3V5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                <rect x="4" y="6" width="16" height="12" rx="4" stroke="currentColor"
                    stroke-width="2" />
                <path d="M8.5 11.5H8.51" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                <path d="M15.5 11.5H15.51" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                <path d="M9 15C10.6 16 13.4 16 15 15" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" />
            </svg>
        </button>

        <a class="float-btn whatsapp-btn"
            href="https://wa.me/59177348087?text=Hola%2C%20quiero%20comunicarme%20con%20un%20representante%20de%20Rumika."
            target="_blank" rel="noopener" aria-label="Contactar por WhatsApp">
            <svg width="28" height="28" viewBox="0 0 32 32" fill="none">
                <path fill="currentColor"
                    d="M16.02 4C9.4 4 4.02 9.28 4.02 15.78c0 2.08.56 4.1 1.62 5.88L4 28l6.52-1.68a12.2 12.2 0 0 0 5.5 1.32C22.64 27.64 28 22.36 28 15.86C28.02 9.36 22.64 4 16.02 4Zm0 21.62c-1.78 0-3.52-.46-5.04-1.34l-.36-.22l-3.86 1l1.02-3.68l-.24-.38a9.66 9.66 0 0 1-1.5-5.18c0-5.38 4.48-9.76 9.98-9.76S26 10.44 26 15.82c0 5.4-4.48 9.8-9.98 9.8Zm5.48-7.32c-.3-.14-1.78-.86-2.06-.96c-.28-.1-.48-.14-.68.14c-.2.3-.78.96-.96 1.16c-.18.2-.36.22-.66.08c-.3-.14-1.26-.46-2.4-1.46c-.88-.78-1.48-1.74-1.66-2.04c-.18-.3-.02-.46.14-.6c.14-.14.3-.36.46-.54c.16-.18.2-.3.3-.5c.1-.2.06-.38-.02-.54c-.08-.14-.68-1.62-.94-2.22c-.24-.58-.5-.5-.68-.52h-.58c-.2 0-.52.08-.8.38c-.28.3-1.04 1-1.04 2.44s1.06 2.84 1.2 3.04c.14.2 2.08 3.12 5.04 4.38c.7.3 1.26.48 1.68.62c.7.22 1.34.18 1.84.12c.56-.08 1.78-.72 2.04-1.42c.26-.7.26-1.3.18-1.42c-.08-.14-.28-.22-.58-.36Z" />
            </svg>
        </a>
    </div>

    <div class="chat-modal" id="chatModal">
        <div class="chat-header">
            <div class="chat-user">
                <div class="chat-avatar">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M12 3V5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        <rect x="4" y="6" width="16" height="12" rx="4" stroke="currentColor"
                            stroke-width="2" />
                        <path d="M8.5 11.5H8.51" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                        <path d="M15.5 11.5H15.51" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                    </svg>
                </div>

                <div>
                    <strong>Asistente Rumika</strong>
                    <span>Consulta sobre módulos, planes o funcionamiento</span>
                </div>
            </div>

            <button type="button" class="chat-close" onclick="toggleChat()">×</button>
        </div>

        <div class="chat-messages" id="chatMessages">
            <div class="message bot">
                Hola, soy el asistente de Rumika. Puedo ayudarte con información sobre agenda, clientes,
                inventario, sucursales, módulos y contacto con un representante.
            </div>
        </div>

        <form class="chat-form" onsubmit="sendBotMessage(event)">
            <input type="text" id="chatInput" placeholder="Escribe tu consulta..." autocomplete="off">
            <button type="submit">➜</button>
        </form>
    </div>

    <script>
        const chatModal = document.getElementById('chatModal');
        const chatMessages = document.getElementById('chatMessages');
        const chatInput = document.getElementById('chatInput');

        function toggleChat() {
            chatModal.classList.toggle('active');

            if (chatModal.classList.contains('active')) {
                setTimeout(() => chatInput.focus(), 150);
            }
        }

        function addMessage(text, type = 'bot') {
            const message = document.createElement('div');
            message.className = `message ${type}`;
            message.textContent = text;

            chatMessages.appendChild(message);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        async function sendBotMessage(event) {
            event.preventDefault();

            const question = chatInput.value.trim();

            if (!question) {
                return;
            }

            addMessage(question, 'user');
            chatInput.value = '';

            const loadingMessage = document.createElement('div');
            loadingMessage.className = 'message bot';
            loadingMessage.textContent = 'Escribiendo...';
            chatMessages.appendChild(loadingMessage);
            chatMessages.scrollTop = chatMessages.scrollHeight;

            try {
                const response = await fetch('{{ route('rumika.bot') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    },
                    body: JSON.stringify({
                        message: question
                    })
                });

                const data = await response.json();



                loadingMessage.remove();

                if (data.ok) {
                    addMessage(data.answer, 'bot');
                } else {
                    addMessage(
                        'No pude responder en este momento. Por favor intenta nuevamente o comunícate por WhatsApp.',
                        'bot');
                }
            } catch (error) {
                loadingMessage.remove();
                addMessage('No pude conectarme con el asistente. Puedes escribirnos directamente por WhatsApp.', 'bot');
            }
        }
    </script>
</body>

</html>
