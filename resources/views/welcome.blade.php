<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rumika SaaS | Agenda, ventas, caja e inventario</title>
    <meta name="description" content="Rumika SaaS es un sistema modular para clínicas, spas, centros de belleza, barberías, dentistas, farmacias y tiendas. Agenda, caja, inventario, ventas, reportes, CRM y WhatsApp en una sola plataforma.">

    <link rel="icon" type="image/svg+xml" href="{{ asset('rumika-favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900" rel="stylesheet" />

    <style>
        :root {
            --primary: #008b7f;
            --primary-dark: #006b63;
            --primary-soft: #e5f7f3;
            --primary-pale: #f1fbf9;
            --ink: #07152f;
            --text: #1b2940;
            --muted: #647187;
            --border: #d7e1ee;
            --line: #e7edf5;
            --white: #ffffff;
            --bg: #f4f7fb;
            --blue: #eaf4ff;
            --amber: #fff6e4;
            --violet: #f2edff;
            --shadow: 0 24px 70px rgba(15, 23, 42, .1);
            --shadow-soft: 0 14px 40px rgba(15, 23, 42, .07);
            --radius: 28px;
            --container: 1180px;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: 'Figtree', system-ui, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 8% 4%, rgba(0, 139, 127, .12), transparent 24%),
                radial-gradient(circle at 92% 12%, rgba(50, 120, 255, .08), transparent 22%),
                linear-gradient(180deg, #fff 0%, var(--bg) 38%, #fff 100%);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        img,
        svg {
            display: block;
        }

        .page {
            width: min(var(--container), calc(100% - 32px));
            margin: 0 auto;
        }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 20;
            border-bottom: 1px solid rgba(215, 225, 238, .72);
            background: rgba(255, 255, 255, .9);
            backdrop-filter: blur(18px);
        }

        .nav {
            min-height: 78px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .brand img {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            box-shadow: 0 14px 28px rgba(0, 139, 127, .18);
        }

        .brand strong {
            display: block;
            color: var(--ink);
            font-size: clamp(22px, 4vw, 30px);
            font-weight: 900;
            line-height: 1;
        }

        .brand span span {
            display: none;
            margin-top: 4px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
        }

        .nav-links {
            display: none;
            align-items: center;
            gap: 24px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 800;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            min-height: 46px;
            padding: 0 18px;
            border: 1px solid transparent;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            font: inherit;
            font-size: 14px;
            font-weight: 900;
            white-space: nowrap;
            cursor: pointer;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            color: var(--white);
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            box-shadow: 0 18px 34px rgba(0, 139, 127, .24);
        }

        .btn-soft {
            color: var(--primary-dark);
            background: var(--white);
            border-color: var(--border);
        }

        .btn-ghost {
            color: var(--primary-dark);
            background: var(--primary-soft);
            border-color: rgba(0, 139, 127, .14);
        }

        .hero {
            padding: 34px 0 28px;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 24px;
            align-items: center;
        }

        h1,
        h2,
        h3,
        p {
            margin: 0;
        }

        h1 {
            max-width: 760px;
            color: var(--ink);
            font-size: clamp(42px, 11vw, 82px);
            font-weight: 900;
            line-height: .94;
            letter-spacing: 0;
        }

        .hero-lead {
            max-width: 660px;
            margin-top: 22px;
            color: var(--muted);
            font-size: clamp(17px, 4vw, 21px);
            font-weight: 700;
            line-height: 1.55;
        }

        .hero-actions {
            margin-top: 28px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .proof-row {
            margin-top: 26px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .proof,
        .module,
        .feature,
        .plan,
        .panel {
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, .88);
            box-shadow: var(--shadow-soft);
        }

        .proof {
            min-height: 82px;
            padding: 16px;
            border-radius: 18px;
        }

        .proof strong {
            display: block;
            color: var(--ink);
            font-size: 20px;
            font-weight: 900;
        }

        .proof span {
            display: block;
            margin-top: 4px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
            line-height: 1.35;
        }

        .product-stage {
            position: relative;
            min-height: 560px;
            display: grid;
            place-items: center;
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 38px;
            background:
                radial-gradient(circle at 60% 30%, rgba(0, 139, 127, .16), transparent 32%),
                linear-gradient(145deg, rgba(255, 255, 255, .94), rgba(229, 247, 243, .64));
            box-shadow: var(--shadow);
        }

        .phone {
            width: min(330px, 78vw);
            padding: 12px;
            border-radius: 40px;
            background: #101828;
            box-shadow: 0 30px 70px rgba(7, 21, 47, .24);
        }

        .phone-screen {
            overflow: hidden;
            border-radius: 30px;
            background: #f8fbfd;
            min-height: 590px;
        }

        .phone-top {
            padding: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--white);
            border-bottom: 1px solid var(--line);
        }

        .phone-brand {
            display: flex;
            align-items: center;
            gap: 9px;
            min-width: 0;
        }

        .phone-brand img {
            width: 34px;
            height: 34px;
            border-radius: 11px;
        }

        .phone-brand strong {
            color: var(--ink);
            font-size: 16px;
            font-weight: 900;
        }

        .phone-body {
            padding: 18px;
        }

        .phone-hello {
            display: grid;
            grid-template-columns: 46px 1fr;
            gap: 12px;
            align-items: center;
        }

        .avatar {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-soft), #fff);
            display: grid;
            place-items: center;
            color: var(--primary-dark);
            font-weight: 900;
        }

        .phone-hello strong,
        .phone-list-head {
            color: var(--ink);
            font-weight: 900;
        }

        .phone-hello span {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }

        .phone-kpis {
            margin-top: 18px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .phone-kpi {
            padding: 13px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: var(--white);
        }

        .phone-kpi b {
            display: block;
            color: var(--ink);
            font-size: 20px;
            font-weight: 900;
        }

        .phone-kpi span {
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
        }

        .phone-list {
            margin-top: 18px;
            padding: 14px;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: var(--white);
        }

        .phone-list-head {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
        }

        .phone-row {
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--line);
        }

        .phone-row:last-child {
            border-bottom: 0;
        }

        .phone-row time {
            color: var(--primary-dark);
            font-weight: 900;
            font-size: 13px;
        }

        .phone-row strong,
        .phone-row span {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .phone-row strong {
            color: var(--ink);
            font-size: 13px;
            font-weight: 900;
        }

        .phone-row span {
            margin-top: 2px;
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
        }

        .status {
            padding: 6px 9px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-size: 10px;
            font-weight: 900;
        }

        .rumi-card {
            position: absolute;
            right: 18px;
            bottom: 18px;
            width: min(255px, calc(100% - 36px));
            padding: 14px;
            border: 1px solid rgba(0, 139, 127, .15);
            border-radius: 24px;
            display: grid;
            grid-template-columns: 58px 1fr;
            gap: 12px;
            align-items: center;
            background: rgba(255, 255, 255, .94);
            box-shadow: var(--shadow-soft);
        }

        .alpaca {
            width: 58px;
            height: 58px;
        }

        .rumi-card strong {
            display: block;
            color: var(--ink);
            font-size: 14px;
            font-weight: 900;
        }

        .rumi-card span {
            display: block;
            margin-top: 3px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            line-height: 1.35;
        }

        .section {
            padding: 46px 0;
        }

        .section-head {
            max-width: 780px;
            margin-bottom: 22px;
        }

        .section-head.center {
            margin-left: auto;
            margin-right: auto;
            text-align: center;
        }

        .section-label {
            display: block;
            margin-bottom: 12px;
            color: var(--primary-dark);
            font-size: 13px;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .section-title {
            color: var(--ink);
            font-size: clamp(30px, 6vw, 50px);
            font-weight: 900;
            line-height: 1.04;
        }

        .section-text {
            margin-top: 14px;
            color: var(--muted);
            font-size: 17px;
            font-weight: 700;
            line-height: 1.6;
        }

        .module-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .module {
            min-height: 178px;
            padding: 18px;
            border-radius: 24px;
        }

        .icon-box {
            width: 46px;
            height: 46px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            color: var(--primary-dark);
            background: var(--primary-soft);
        }

        .icon-box svg {
            width: 23px;
            height: 23px;
        }

        .module:nth-child(2n) .icon-box,
        .feature:nth-child(2n) .icon-box {
            background: var(--blue);
            color: #1261a6;
        }

        .module:nth-child(3n) .icon-box,
        .feature:nth-child(3n) .icon-box {
            background: var(--amber);
            color: #a86500;
        }

        .module h3,
        .feature h3,
        .plan h3 {
            margin-top: 16px;
            color: var(--ink);
            font-size: 18px;
            font-weight: 900;
            line-height: 1.2;
        }

        .module p,
        .feature p,
        .plan p {
            margin-top: 10px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
            line-height: 1.5;
        }

        .split {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 18px;
            align-items: stretch;
        }

        .panel {
            overflow: hidden;
            border-radius: var(--radius);
        }

        .panel-pad {
            padding: clamp(22px, 5vw, 34px);
        }

        .chat-preview {
            min-height: 430px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            background: linear-gradient(145deg, rgba(229, 247, 243, .74), rgba(255, 255, 255, .9));
        }

        .chat-head {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px;
            border-radius: 20px;
            background: var(--white);
        }

        .chat-head img {
            width: 42px;
            height: 42px;
            border-radius: 14px;
        }

        .chat-head strong {
            display: block;
            color: var(--ink);
            font-weight: 900;
        }

        .chat-head span {
            display: block;
            margin-top: 2px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
        }

        .bubble {
            max-width: 86%;
            margin-top: 14px;
            padding: 13px 15px;
            border-radius: 18px;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.45;
        }

        .bubble.in {
            background: var(--white);
            color: var(--text);
            border-top-left-radius: 6px;
        }

        .bubble.out {
            margin-left: auto;
            background: #dff8ef;
            color: #0f3f39;
            border-top-right-radius: 6px;
        }

        .appointment-card {
            margin-top: 14px;
            padding: 16px;
            border-radius: 20px;
            background: var(--white);
            box-shadow: 0 12px 28px rgba(15, 23, 42, .08);
        }

        .appointment-card strong {
            color: var(--ink);
            font-weight: 900;
        }

        .appointment-card ul {
            margin: 10px 0 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 7px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 12px;
        }

        .feature {
            padding: 22px;
            border-radius: 24px;
        }

        .business-strip {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding: 2px 2px 14px;
            scrollbar-width: thin;
        }

        .business-pill {
            min-width: max-content;
            padding: 14px 18px;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: var(--white);
            color: var(--text);
            font-size: 14px;
            font-weight: 900;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .05);
        }

        .plans {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 14px;
        }

        .plan {
            position: relative;
            padding: 24px;
            border-radius: 28px;
        }

        .plan.featured {
            border-color: rgba(0, 139, 127, .36);
            box-shadow: 0 26px 70px rgba(0, 139, 127, .14);
        }

        .plan-tag {
            position: absolute;
            top: 18px;
            right: 18px;
            padding: 8px 12px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 900;
        }

        .price {
            margin-top: 18px;
            color: var(--ink);
            font-size: 36px;
            font-weight: 900;
            line-height: 1;
        }

        .price small {
            color: var(--muted);
            font-size: 14px;
            font-weight: 800;
        }

        .plan ul {
            margin: 20px 0 22px;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 10px;
        }

        .plan li {
            display: flex;
            gap: 9px;
            color: var(--text);
            font-size: 14px;
            font-weight: 800;
            line-height: 1.35;
        }

        .plan li::before {
            content: "";
            width: 8px;
            height: 8px;
            margin-top: 6px;
            flex: 0 0 auto;
            border-radius: 50%;
            background: var(--primary);
        }

        .cta {
            margin: 36px 0 54px;
            overflow: hidden;
            border-radius: 34px;
            background:
                radial-gradient(circle at 14% 88%, rgba(0, 139, 127, .2), transparent 25%),
                linear-gradient(135deg, #08263f, #043f3a 58%, #008b7f);
            color: var(--white);
            box-shadow: var(--shadow);
        }

        .cta-inner {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 22px;
            padding: clamp(28px, 7vw, 54px);
            align-items: center;
        }

        .cta h2 {
            max-width: 680px;
            font-size: clamp(30px, 7vw, 54px);
            font-weight: 900;
            line-height: 1.02;
        }

        .cta p {
            max-width: 620px;
            margin-top: 14px;
            color: rgba(255, 255, 255, .78);
            font-size: 17px;
            font-weight: 700;
            line-height: 1.6;
        }

        .footer {
            padding: 26px 0 34px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
        }

        .footer-inner {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
            text-align: center;
        }

        .whatsapp-float {
            position: fixed;
            right: 18px;
            bottom: 18px;
            z-index: 30;
            width: 58px;
            height: 58px;
            border-radius: 20px;
            display: grid;
            place-items: center;
            color: var(--white);
            background: #18a981;
            box-shadow: 0 18px 38px rgba(24, 169, 129, .34);
        }

        .icon {
            fill: none;
            stroke: currentColor;
            stroke-width: 2.2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        @media (min-width: 760px) {
            .page {
                width: min(var(--container), calc(100% - 52px));
            }

            .brand span span {
                display: block;
            }

            .proof-row,
            .module-grid,
            .feature-grid,
            .plans {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .split {
                grid-template-columns: minmax(0, .95fr) minmax(360px, 1.05fr);
            }

            .footer-inner {
                flex-direction: row;
                text-align: left;
            }
        }

        @media (min-width: 980px) {
            .nav-links {
                display: flex;
            }

            .hero {
                padding: 62px 0 42px;
            }

            .hero-grid {
                grid-template-columns: minmax(0, .98fr) minmax(420px, .82fr);
                gap: 38px;
            }

            .cta-inner {
                grid-template-columns: minmax(0, 1fr) auto;
            }
        }

        @media (max-width: 520px) {
            .page {
                width: min(100% - 22px, 480px);
            }

            .site-header {
                position: relative;
            }

            .nav {
                min-height: 72px;
            }

            .brand img {
                width: 42px;
                height: 42px;
            }

            .nav-actions .btn-soft {
                display: none;
            }

            .btn {
                width: 100%;
                min-height: 48px;
            }

            .nav-actions .btn {
                width: auto;
                padding: 0 15px;
            }

            .hero {
                padding-top: 16px;
            }

            .hero-actions {
                display: grid;
                grid-template-columns: 1fr;
            }

            .proof-row,
            .module-grid {
                grid-template-columns: 1fr;
            }

            .product-stage {
                min-height: 510px;
                border-radius: 28px;
            }

            .phone {
                width: min(300px, 84vw);
                transform: translateY(-20px);
            }

            .phone-screen {
                min-height: 520px;
            }

            .rumi-card {
                left: 14px;
                right: 14px;
                bottom: 14px;
                width: auto;
            }

            .section {
                padding: 34px 0;
            }

            .module,
            .feature,
            .plan {
                border-radius: 22px;
            }

            .whatsapp-float {
                right: 14px;
                bottom: 14px;
                width: 52px;
                height: 52px;
                border-radius: 18px;
            }
        }
    </style>
</head>

<body>
    <header class="site-header">
        <div class="page nav">
            <a href="{{ url('/') }}" class="brand" aria-label="Rumika SaaS">
                <img src="{{ asset('rumika-favicon.svg') }}" alt="Rumika">
                <span>
                    <strong>Rumika SaaS</strong>
                    <span>Sistema modular para negocios</span>
                </span>
            </a>

            <nav class="nav-links" aria-label="Principal">
                <a href="#modulos">Módulos</a>
                <a href="#whatsapp">WhatsApp</a>
                <a href="#planes">Planes</a>
                <a href="#reportes">Reportes</a>
            </nav>

            <div class="nav-actions">
                @auth
                    <a class="btn btn-soft" href="{{ route('dashboard') }}">Panel</a>
                    <a class="btn btn-primary" href="{{ route('dashboard') }}">Entrar</a>
                @else
                    <a class="btn btn-soft" href="{{ route('login') }}">Ingresar</a>
                    @if (Route::has('register'))
                        <a class="btn btn-primary" href="{{ route('register') }}">Probar</a>
                    @endif
                @endauth
            </div>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="page hero-grid">
                <div>
                    <h1>Rumika SaaS para negocios de atención.</h1>
                    <p class="hero-lead">
                        Agenda, caja, inventario, ventas, reportes, CRM con WhatsApp y permisos por rol en una sola plataforma preparada para clínicas, spas, barberías, dentistas, farmacias y tiendas.
                    </p>

                    <div class="hero-actions">
                        <a href="#planes" class="btn btn-primary">
                            Ver planes
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                        <a href="https://wa.me/59177348087?text=Hola%2C%20quiero%20una%20demo%20de%20Rumika%20SaaS." target="_blank" rel="noopener" class="btn btn-soft">
                            Solicitar demo
                        </a>
                    </div>

                    <div class="proof-row" aria-label="Beneficios principales">
                        <div class="proof"><strong>Multi-sucursal</strong><span>Stock, caja, ventas y reportes separados por sede.</span></div>
                        <div class="proof"><strong>Por roles</strong><span>Cada usuario ve solo lo que tiene permitido.</span></div>
                        <div class="proof"><strong>Mobile-first</strong><span>Hecho para trabajar desde teléfono o computadora.</span></div>
                        <div class="proof"><strong>Con Rumi</strong><span>Asistente para ubicar pantallas y consultar datos permitidos.</span></div>
                    </div>
                </div>

                <div class="product-stage" aria-label="Vista previa de Rumika">
                    <div class="phone">
                        <div class="phone-screen">
                            <div class="phone-top">
                                <div class="phone-brand">
                                    <img src="{{ asset('rumika-favicon.svg') }}" alt="">
                                    <strong>Rumika</strong>
                                </div>
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M5 7h14M5 12h14M5 17h14" stroke="#647187" stroke-width="2" stroke-linecap="round" />
                                </svg>
                            </div>

                            <div class="phone-body">
                                <div class="phone-hello">
                                    <div class="avatar">R</div>
                                    <div><strong>Resumen de hoy</strong><span>Sucursal Centro</span></div>
                                </div>

                                <div class="phone-kpis">
                                    <div class="phone-kpi"><b>18</b><span>Citas</span></div>
                                    <div class="phone-kpi"><b>Bs 4,250</b><span>Caja</span></div>
                                    <div class="phone-kpi"><b>142</b><span>Productos</span></div>
                                    <div class="phone-kpi"><b>7</b><span>Bajo stock</span></div>
                                </div>

                                <div class="phone-list">
                                    <div class="phone-list-head"><span>Agenda de hoy</span><span>Ver</span></div>
                                    <div class="phone-row">
                                        <time>09:00</time>
                                        <div><strong>Maria Lopez</strong><span>Limpieza facial</span></div>
                                        <span class="status">Asistió</span>
                                    </div>
                                    <div class="phone-row">
                                        <time>10:30</time>
                                        <div><strong>Juan Perez</strong><span>Consulta dermatológica</span></div>
                                        <span class="status">Pendiente</span>
                                    </div>
                                    <div class="phone-row">
                                        <time>12:00</time>
                                        <div><strong>Ana Torres</strong><span>Producto + servicio</span></div>
                                        <span class="status">Pagado</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rumi-card">
                        <svg class="alpaca" viewBox="0 0 80 80" fill="none" aria-hidden="true">
                            <rect x="19" y="18" width="42" height="50" rx="21" fill="#F7F2E8" />
                            <path d="M24 22c-5-8-1-15 6-10 2 2 3 6 2 10M56 22c5-8 1-15-6-10-2 2-3 6-2 10" fill="#F7F2E8" stroke="#008b7f" stroke-width="2" stroke-linecap="round" />
                            <circle cx="31" cy="39" r="3" fill="#07152F" />
                            <circle cx="49" cy="39" r="3" fill="#07152F" />
                            <path d="M38 45h4m-2 0v5m-7 1c5 4 9 4 14 0" stroke="#07152F" stroke-width="2.4" stroke-linecap="round" />
                            <path d="M22 63h36v9H22z" fill="#008B7F" />
                            <path d="M36 65h8" stroke="white" stroke-width="2.4" stroke-linecap="round" />
                        </svg>
                        <div><strong>Rumi te acompaña</strong><span>Consulta reportes, caja, clientes o datos según tu rol.</span></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="modulos">
            <div class="page">
                <div class="section-head center">
                    <span class="section-label">Módulos incluidos</span>
                    <h2 class="section-title">Todo lo operativo, listo para ordenar tu negocio.</h2>
                    <p class="section-text">Activa módulos según el tipo de sucursal: clínica, spa, barbería, centro de belleza, farmacia, tienda o consultorio.</p>
                </div>

                <div class="module-grid">
                    <article class="module"><div class="icon-box"><svg class="icon" viewBox="0 0 24 24"><path d="M8 2v4M16 2v4M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z" /></svg></div><h3>Agenda e historial</h3><p>Citas, asistencia, reagendado, servicios agregados, pagos parciales e historial.</p></article>
                    <article class="module"><div class="icon-box"><svg class="icon" viewBox="0 0 24 24"><path d="M7 3h7l4 4v14H7V3Z" /><path d="M14 3v5h5M9 13h6M9 17h6" /></svg></div><h3>Historia clínica</h3><p>Fichas, archivos, recetas, plantillas y accesos por doctor o profesional.</p></article>
                    <article class="module"><div class="icon-box"><svg class="icon" viewBox="0 0 24 24"><path d="M4 7h16v12H4z" /><path d="M16 11h4M7 7V5h10v2" /></svg></div><h3>Caja y tickets</h3><p>Apertura y cierre por turno, QR/efectivo, gastos e impresión con QZ Tray.</p></article>
                    <article class="module"><div class="icon-box"><svg class="icon" viewBox="0 0 24 24"><path d="m12 3 8 4-8 4-8-4 8-4Z" /><path d="M4 7v10l8 4 8-4V7M12 11v10" /></svg></div><h3>Inventario</h3><p>Productos, lotes, proveedores, marcas, zonas, activos y movimientos.</p></article>
                    <article class="module"><div class="icon-box"><svg class="icon" viewBox="0 0 24 24"><path d="M6 8h12l-1 13H7L6 8Z" /><path d="M9 8a3 3 0 0 1 6 0" /></svg></div><h3>Ventas directas</h3><p>Venta de productos sin cita, comprador opcional, NIT y stock por sucursal.</p></article>
                    <article class="module"><div class="icon-box"><svg class="icon" viewBox="0 0 24 24"><path d="M4 19V5M9 19v-7M14 19V8M19 19v-4" /></svg></div><h3>Reportes</h3><p>Ingresos, egresos, deudas, comisiones, asistencia y desempeño.</p></article>
                    <article class="module"><div class="icon-box"><svg class="icon" viewBox="0 0 24 24"><path d="M12 3 5 6v5c0 5 3 8 7 10 4-2 7-5 7-10V6l-7-3Z" /><path d="m9 12 2 2 4-5" /></svg></div><h3>Roles y planes</h3><p>Permisos por vista, crear, editar, eliminar y límites por plan SaaS.</p></article>
                    <article class="module"><div class="icon-box"><svg class="icon" viewBox="0 0 24 24"><path d="M4 5h16v11H8l-4 4V5Z" /><path d="M8 9h8M8 13h5" /></svg></div><h3>Centro de mensajes</h3><p>WhatsApp API por empresa, canales por sucursal y agenda desde conversaciones.</p></article>
                </div>
            </div>
        </section>

        <section class="section" id="whatsapp">
            <div class="page split">
                <div class="panel panel-pad">
                    <span class="section-label">WhatsApp integrado</span>
                    <h2 class="section-title">Convierte mensajes en citas, sin perder conversaciones.</h2>
                    <p class="section-text">Cada empresa puede conectar sus propios números, asignarlos a usuarios y responder desde una bandeja preparada para agendar clientes directo al calendario.</p>
                    <div class="hero-actions">
                        <a href="https://wa.me/59177348087?text=Hola%2C%20quiero%20ver%20el%20CRM%20con%20WhatsApp%20de%20Rumika." target="_blank" rel="noopener" class="btn btn-primary">Ver WhatsApp API</a>
                        <a href="#planes" class="btn btn-soft">Planes con CRM</a>
                    </div>
                </div>

                <div class="panel chat-preview">
                    <div class="chat-head">
                        <img src="{{ asset('rumika-favicon.svg') }}" alt="">
                        <div><strong>Centro de mensajes</strong><span>WhatsApp Central conectado</span></div>
                    </div>
                    <div class="bubble in">Hola, quisiera agendar una evaluación para esta semana.</div>
                    <div class="bubble out">Claro, te ayudo. ¿Qué día y horario te queda mejor?</div>
                    <div class="bubble in">El jueves a las 10:00 me queda bien.</div>
                    <div class="appointment-card">
                        <strong>Cita agendada</strong>
                        <ul><li>Jueves, 10:00</li><li>Evaluación inicial</li><li>Sucursal seleccionada</li></ul>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="reportes">
            <div class="page">
                <div class="section-head">
                    <span class="section-label">Control administrativo</span>
                    <h2 class="section-title">Datos claros para saber qué pasa en cada sucursal.</h2>
                    <p class="section-text">Rumika separa operación, finanzas e inventario para que gerencia pueda revisar lo importante sin entrar al detalle de cada pantalla.</p>
                </div>

                <div class="feature-grid">
                    <article class="feature"><div class="icon-box"><svg class="icon" viewBox="0 0 24 24"><path d="M4 19V5M8 17l4-5 3 3 5-7" /></svg></div><h3>Indicadores</h3><p>Ventas por productos, servicios, gastos, asistencia, deudas y neto por fecha.</p></article>
                    <article class="feature"><div class="icon-box"><svg class="icon" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-8 0v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM20 8v6M17 11h6" /></svg></div><h3>Comisiones</h3><p>Control semanal, quincenal, mensual o personalizado para ventas y servicios.</p></article>
                    <article class="feature"><div class="icon-box"><svg class="icon" viewBox="0 0 24 24"><path d="M8 4h8M9 2h6v4H9zM6 5h12v16H6z" /><path d="M9 12h6M9 16h6" /></svg></div><h3>Bitácora</h3><p>Histórico de acciones por usuario, fechas y movimientos sensibles.</p></article>
                    <article class="feature"><div class="icon-box"><svg class="icon" viewBox="0 0 24 24"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /><path d="M19.4 15a8 8 0 0 0 .1-2l2-1.5-2-3.4-2.4 1a7 7 0 0 0-1.7-1L15 5h-4l-.4 3.1a7 7 0 0 0-1.7 1l-2.4-1-2 3.4 2 1.5a8 8 0 0 0 .1 2l-2 1.5 2 3.4 2.4-1a7 7 0 0 0 1.7 1l.4 3.1h4l.4-3.1a7 7 0 0 0 1.7-1l2.4 1 2-3.4-2.2-1.5Z" /></svg></div><h3>Planes SaaS</h3><p>Demo, bloqueo, renovación, fechas de pago y límites por plan contratado.</p></article>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="page">
                <div class="section-head center">
                    <span class="section-label">Rubros compatibles</span>
                    <h2 class="section-title">Un sistema base para distintos tipos de negocio.</h2>
                </div>
                <div class="business-strip" aria-label="Tipos de negocio">
                    <span class="business-pill">Clínicas</span>
                    <span class="business-pill">Spas</span>
                    <span class="business-pill">Centros de belleza</span>
                    <span class="business-pill">Barberías</span>
                    <span class="business-pill">Dentistas</span>
                    <span class="business-pill">Farmacias</span>
                    <span class="business-pill">Tiendas</span>
                    <span class="business-pill">Consultorios</span>
                </div>
            </div>
        </section>

        <section class="section" id="planes">
            <div class="page">
                <div class="section-head center">
                    <span class="section-label">Planes flexibles</span>
                    <h2 class="section-title">Empieza pequeño y escala cuando tu operación lo pida.</h2>
                    <p class="section-text">Los planes controlan módulos, usuarios, sucursales y acceso a CRM. El plan superior queda sin límites operativos.</p>
                </div>

                <div class="plans">
                    <article class="plan"><h3>Free</h3><p>Demo completo por tiempo limitado.</p><div class="price">0 <small>/ 3 días</small></div><ul><li>Acceso inicial para probar</li><li>Configuración de empresa</li><li>Bloqueo al vencer demo</li></ul><a href="{{ Route::has('register') ? route('register') : route('login') }}" class="btn btn-soft">Probar gratis</a></article>
                    <article class="plan"><h3>Básico</h3><p>Para negocios pequeños que necesitan orden.</p><div class="price">$30 <small>/ mes</small></div><ul><li>Agenda y clientes</li><li>Caja y servicios</li><li>Inventario básico</li><li>Reportes esenciales</li></ul><a href="https://wa.me/59177348087?text=Hola%2C%20quiero%20el%20plan%20Basico%20de%20Rumika." target="_blank" rel="noopener" class="btn btn-soft">Solicitar</a></article>
                    <article class="plan featured"><span class="plan-tag">Más elegido</span><h3>Profesional</h3><p>Para equipos con ventas, caja y CRM.</p><div class="price">$60 <small>/ mes</small></div><ul><li>Todo lo del Básico</li><li>Ventas directas de productos</li><li>Centro de mensajes WhatsApp</li><li>Roles y permisos avanzados</li></ul><a href="https://wa.me/59177348087?text=Hola%2C%20quiero%20el%20plan%20Profesional%20de%20Rumika." target="_blank" rel="noopener" class="btn btn-primary">Solicitar</a></article>
                    <article class="plan"><h3>Empresa</h3><p>Para negocios con varias sucursales.</p><div class="price">$90 <small>/ mes</small></div><ul><li>Todos los módulos</li><li>Sin límites operativos</li><li>Reportes, comisiones y auditoría</li><li>Soporte para crecimiento</li></ul><a href="https://wa.me/59177348087?text=Hola%2C%20quiero%20el%20plan%20Empresa%20de%20Rumika." target="_blank" rel="noopener" class="btn btn-soft">Solicitar</a></article>
                </div>
            </div>
        </section>

        <section class="cta">
            <div class="page cta-inner">
                <div>
                    <h2>Tu negocio puede operar con más orden desde hoy.</h2>
                    <p>Registra tu empresa, configura sucursales y activa solo los módulos que necesitas. Rumika crece contigo.</p>
                </div>
                <div class="hero-actions">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">Entrar al sistema</a>
                    @else
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn btn-primary">Crear cuenta</a>
                        @endif
                        <a href="https://wa.me/59177348087?text=Hola%2C%20quiero%20una%20demo%20de%20Rumika%20SaaS." target="_blank" rel="noopener" class="btn btn-ghost">Hablar por WhatsApp</a>
                    @endauth
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="page footer-inner">
            <span>© {{ date('Y') }} Rumika SaaS. Sistema modular para negocios de atención.</span>
            <span>Desarrollado por DigitBol</span>
        </div>
    </footer>

    <a href="https://wa.me/59177348087?text=Hola%2C%20quiero%20m%C3%A1s%20informaci%C3%B3n%20sobre%20Rumika%20SaaS." target="_blank" rel="noopener" class="whatsapp-float" aria-label="Contactar por WhatsApp">
        <svg width="28" height="28" viewBox="0 0 32 32" fill="none" aria-hidden="true">
            <path fill="currentColor" d="M16.02 4C9.4 4 4.02 9.28 4.02 15.78c0 2.08.56 4.1 1.62 5.88L4 28l6.52-1.68a12.2 12.2 0 0 0 5.5 1.32C22.64 27.64 28 22.36 28 15.86C28.02 9.36 22.64 4 16.02 4Zm0 21.62c-1.78 0-3.52-.46-5.04-1.34l-.36-.22l-3.86 1l1.02-3.68l-.24-.38a9.66 9.66 0 0 1-1.5-5.18c0-5.38 4.48-9.76 9.98-9.76S26 10.44 26 15.82c0 5.4-4.48 9.8-9.98 9.8Zm5.48-7.32c-.3-.14-1.78-.86-2.06-.96c-.28-.1-.48-.14-.68.14c-.2.3-.78.96-.96 1.16c-.18.2-.36.22-.66.08c-.3-.14-1.26-.46-2.4-1.46c-.88-.78-1.48-1.74-1.66-2.04c-.18-.3-.02-.46.14-.6c.14-.14.3-.36.46-.54c.16-.18.2-.3.3-.5c.1-.2.06-.38-.02-.54c-.08-.14-.68-1.62-.94-2.22c-.24-.58-.5-.5-.68-.52h-.58c-.2 0-.52.08-.8.38c-.28.3-1.04 1-1.04 2.44s1.06 2.84 1.2 3.04c.14.2 2.08 3.12 5.04 4.38c.7.3 1.26.48 1.68.62c.7.22 1.34.18 1.84.12c.56-.08 1.78-.72 2.04-1.42c.26-.7.26-1.3.18-1.42c-.08-.14-.28-.22-.58-.36Z" />
        </svg>
    </a>
</body>

</html>
