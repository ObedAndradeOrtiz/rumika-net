<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rumika SaaS | Agenda, clientes e historial</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900" rel="stylesheet" />

    <style>
        :root {
            --primary: #0f8f7f;
            --primary-dark: #087568;
            --primary-soft: #e8f7f5;
            --text: #101827;
            --muted: #63708a;
            --border: #d7e0ec;
            --bg: #f5f8fc;
            --white: #ffffff;
            --shadow: 0 24px 80px rgba(15, 32, 55, .12);
            --radius-xl: 32px;
            --radius-lg: 24px;
            --radius-md: 16px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Figtree', sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(15, 143, 127, .14), transparent 34%),
                linear-gradient(135deg, #eef8f7 0%, #f8fbff 48%, #eef3f9 100%);
            min-height: 100vh;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .page {
            min-height: 100vh;
            padding: 34px 7%;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 56px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            font-weight: 900;
            font-size: 28px;
            letter-spacing: -0.04em;
        }

        .brand-icon,
        .hero-icon {
            width: 58px;
            height: 58px;
            border-radius: 20px;
            background: linear-gradient(145deg, var(--primary), #0aa591);
            display: grid;
            place-items: center;
            box-shadow: 0 18px 36px rgba(15, 143, 127, .26);
            flex: 0 0 auto;
        }

        .brand-icon svg,
        .hero-icon svg {
            width: 31px;
            height: 31px;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            height: 48px;
            padding: 0 22px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 15px;
            transition: .22s ease;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .btn-primary {
            color: white;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            box-shadow: 0 18px 36px rgba(15, 143, 127, .25);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 22px 46px rgba(15, 143, 127, .32);
        }

        .btn-outline {
            color: var(--primary-dark);
            background: rgba(255, 255, 255, .72);
            border-color: var(--border);
            backdrop-filter: blur(8px);
        }

        .btn-outline:hover {
            background: var(--white);
            transform: translateY(-2px);
        }

        .hero {
            flex: 1;
            display: grid;
            grid-template-columns: minmax(0, 1.08fr) minmax(380px, .92fr);
            align-items: center;
            gap: 64px;
        }

        .hero-content {
            max-width: 780px;
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
            font-weight: 800;
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
            font-size: clamp(48px, 6vw, 92px);
            line-height: .98;
            letter-spacing: -0.075em;
            color: #121827;
        }

        .subtitle {
            margin: 26px 0 0;
            max-width: 700px;
            font-size: clamp(18px, 2vw, 23px);
            line-height: 1.7;
            color: var(--muted);
        }

        .hero-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 34px;
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
            font-weight: 800;
            box-shadow: 0 8px 22px rgba(15, 32, 55, .05);
        }

        .stats {
            margin-top: 34px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            max-width: 680px;
        }

        .stat-card {
            min-height: 136px;
            padding: 24px;
            border-radius: var(--radius-lg);
            background: rgba(255, 255, 255, .72);
            border: 1px solid var(--border);
            box-shadow: 0 16px 40px rgba(15, 32, 55, .07);
            backdrop-filter: blur(10px);
        }

        .stat-label {
            color: var(--muted);
            font-size: 16px;
            margin-bottom: 8px;
        }

        .stat-value {
            color: #050b1a;
            font-size: 26px;
            font-weight: 900;
            letter-spacing: -0.04em;
        }

        .stat-text {
            color: var(--muted);
            margin-top: 8px;
            font-size: 15px;
            line-height: 1.4;
        }

        .preview-wrap {
            position: relative;
        }

        .preview-card {
            border-radius: var(--radius-xl);
            background: rgba(255, 255, 255, .88);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            padding: 34px;
            overflow: hidden;
            backdrop-filter: blur(16px);
        }

        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 30px;
        }

        .preview-title {
            margin-top: 18px;
        }

        .preview-title h2 {
            margin: 0;
            font-size: 34px;
            letter-spacing: -0.05em;
        }

        .preview-title p {
            color: var(--muted);
            line-height: 1.6;
            margin: 10px 0 0;
        }

        .status-badge {
            padding: 10px 14px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-weight: 900;
            font-size: 13px;
            white-space: nowrap;
        }

        .dashboard {
            display: grid;
            gap: 14px;
        }

        .dash-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .dash-box {
            border-radius: 22px;
            background: #ffffff;
            border: 1px solid var(--border);
            padding: 20px;
            min-height: 112px;
        }

        .dash-box small {
            color: var(--muted);
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .dash-box strong {
            display: block;
            font-size: 25px;
            letter-spacing: -0.04em;
        }

        .progress {
            height: 10px;
            background: #e9eef6;
            border-radius: 999px;
            overflow: hidden;
            margin-top: 18px;
        }

        .progress span {
            display: block;
            height: 100%;
            width: 76%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--primary), #12b7a2);
        }

        .module-list {
            margin-top: 18px;
            display: grid;
            gap: 12px;
        }

        .module-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
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
            font-weight: 800;
        }

        .module-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary);
        }

        .module-state {
            color: var(--primary-dark);
            font-weight: 900;
            font-size: 13px;
        }

        .floating-card {
            position: absolute;
            left: -34px;
            bottom: 42px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: 0 24px 60px rgba(15, 32, 55, .16);
            padding: 18px 20px;
            max-width: 230px;
        }

        .floating-card span {
            display: block;
            color: var(--muted);
            margin-bottom: 5px;
            font-size: 14px;
        }

        .floating-card strong {
            font-size: 22px;
            letter-spacing: -0.04em;
        }

        @media (max-width: 1100px) {
            .page {
                padding: 28px 5%;
            }

            .hero {
                grid-template-columns: 1fr;
                gap: 42px;
            }

            .preview-wrap {
                max-width: 720px;
            }
        }

        @media (max-width: 720px) {
            .page {
                padding: 22px 18px 32px;
            }

            .navbar {
                align-items: flex-start;
                margin-bottom: 38px;
            }

            .brand {
                font-size: 22px;
            }

            .brand-icon {
                width: 48px;
                height: 48px;
                border-radius: 16px;
            }

            .nav-actions {
                display: none;
            }

            h1 {
                font-size: 48px;
                line-height: 1.02;
            }

            .subtitle {
                font-size: 17px;
                line-height: 1.6;
            }

            .hero-buttons {
                display: grid;
                grid-template-columns: 1fr;
            }

            .btn {
                width: 100%;
                height: 54px;
            }

            .chips {
                gap: 9px;
            }

            .chip {
                font-size: 14px;
                padding: 10px 14px;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .preview-card {
                padding: 24px;
                border-radius: 26px;
            }

            .preview-header {
                flex-direction: column;
            }

            .preview-title h2 {
                font-size: 28px;
            }

            .dash-row {
                grid-template-columns: 1fr;
            }

            .floating-card {
                position: static;
                margin-top: 16px;
                max-width: 100%;
            }
        }

        @media (max-width: 420px) {
            h1 {
                font-size: 41px;
            }

            .subtitle {
                font-size: 16px;
            }

            .eyebrow {
                font-size: 14px;
            }

            .stat-card,
            .dash-box {
                padding: 18px;
            }
        }
    </style>
</head>

<body>
    <main class="page">
        <header class="navbar">
            <a href="{{ url('/') }}" class="brand">
                <span class="brand-icon">
                    <svg viewBox="0 0 64 64" fill="none">
                        <rect x="12" y="10" width="10" height="10" rx="2" fill="white"/>
                        <rect x="27" y="10" width="10" height="10" rx="2" fill="white"/>
                        <rect x="42" y="10" width="10" height="10" rx="2" fill="white"/>
                        <rect x="12" y="25" width="10" height="10" rx="2" fill="white"/>
                        <rect x="27" y="25" width="10" height="10" rx="2" fill="white"/>
                        <rect x="42" y="25" width="10" height="10" rx="2" fill="white"/>
                        <rect x="12" y="40" width="10" height="10" rx="2" fill="white"/>
                        <rect x="27" y="40" width="10" height="10" rx="2" fill="white"/>
                        <rect x="42" y="40" width="10" height="10" rx="2" fill="white"/>
                    </svg>
                </span>
                <span>Rumika SaaS</span>
            </a>

            <nav class="nav-actions">
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary">
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
            </nav>
        </header>

        <section class="hero">
            <div class="hero-content">
                <div class="eyebrow">
                    <span class="eyebrow-dot"></span>
                    Plataforma modular para negocios de atención
                </div>

                <h1>
                    Agenda, clientes e historial en bloques.
                </h1>

                <p class="subtitle">
                    Administra citas, clientes, sucursales, tratamientos, pagos e historial desde una sola plataforma.
                    Cada empresa activa solo los módulos que necesita para trabajar de forma simple, rápida y ordenada.
                </p>

                <div class="hero-buttons">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-primary">
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

                <div class="chips">
                    <span class="chip">Agenda</span>
                    <span class="chip">Clientes</span>
                    <span class="chip">Historial</span>
                    <span class="chip">Sucursales</span>
                </div>

                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-label">Hoy</div>
                        <div class="stat-value">18 citas</div>
                        <div class="stat-text">Control diario por sucursal y servicio.</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Sucursal</div>
                        <div class="stat-value">Centro</div>
                        <div class="stat-text">Cada sede trabaja con sus propios datos.</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Cliente</div>
                        <div class="stat-value">Historial completo</div>
                        <div class="stat-text">Tratamientos, pagos, visitas y observaciones.</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-label">Módulos</div>
                        <div class="stat-value">Activables</div>
                        <div class="stat-text">Ideal para clínicas, spas, barberías y centros de belleza.</div>
                    </div>
                </div>
            </div>

            <div class="preview-wrap">
                <div class="preview-card">
                    <div class="preview-header">
                        <div>
                            <span class="hero-icon">
                                <svg viewBox="0 0 64 64" fill="none">
                                    <rect x="12" y="10" width="10" height="10" rx="2" fill="white"/>
                                    <rect x="27" y="10" width="10" height="10" rx="2" fill="white"/>
                                    <rect x="42" y="10" width="10" height="10" rx="2" fill="white"/>
                                    <rect x="12" y="25" width="10" height="10" rx="2" fill="white"/>
                                    <rect x="27" y="25" width="10" height="10" rx="2" fill="white"/>
                                    <rect x="42" y="25" width="10" height="10" rx="2" fill="white"/>
                                    <rect x="12" y="40" width="10" height="10" rx="2" fill="white"/>
                                    <rect x="27" y="40" width="10" height="10" rx="2" fill="white"/>
                                    <rect x="42" y="40" width="10" height="10" rx="2" fill="white"/>
                                </svg>
                            </span>

                            <div class="preview-title">
                                <h2>Panel inteligente</h2>
                                <p>
                                    Una vista clara para revisar agenda, clientes, ventas, servicios y actividad por sucursal.
                                </p>
                            </div>
                        </div>

                        <span class="status-badge">Activo</span>
                    </div>

                    <div class="dashboard">
                        <div class="dash-row">
                            <div class="dash-box">
                                <small>Citas de hoy</small>
                                <strong>18</strong>
                                <div class="progress"><span></span></div>
                            </div>

                            <div class="dash-box">
                                <small>Clientes nuevos</small>
                                <strong>7</strong>
                                <div class="progress"><span style="width: 52%;"></span></div>
                            </div>
                        </div>

                        <div class="module-list">
                            <div class="module-item">
                                <div class="module-name">
                                    <span class="module-dot"></span>
                                    Agenda por sucursal
                                </div>
                                <span class="module-state">Disponible</span>
                            </div>

                            <div class="module-item">
                                <div class="module-name">
                                    <span class="module-dot"></span>
                                    Historial de clientes
                                </div>
                                <span class="module-state">Disponible</span>
                            </div>

                            <div class="module-item">
                                <div class="module-name">
                                    <span class="module-dot"></span>
                                    Pagos y reportes
                                </div>
                                <span class="module-state">Disponible</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="floating-card">
                    <span>Resumen mensual</span>
                    <strong>+42% actividad</strong>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
