@php
    $dashboardUrl = Route::has('dashboard') ? route('dashboard') : url('/');
    $homeUrl = auth()->check() ? $dashboardUrl : url('/');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso no autorizado | Rumika</title>
    <link rel="icon" href="{{ asset('rumika-favicon.svg') }}" type="image/svg+xml">
    <style>
        :root {
            --rumika-bg: #f4f7fb;
            --rumika-border: #d7e0ec;
            --rumika-ink: #0f172a;
            --rumika-muted: #667085;
            --rumika-teal: #008b7d;
            --rumika-teal-dark: #006b61;
            --rumika-soft: #e8f7f4;
            --rumika-danger: #be123c;
        }

        * {
            box-sizing: border-box;
        }

        body {
            align-items: center;
            background:
                radial-gradient(circle at top right, rgba(0, 139, 125, 0.14), transparent 28rem),
                linear-gradient(180deg, #ffffff 0%, var(--rumika-bg) 100%);
            color: var(--rumika-ink);
            display: flex;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            justify-content: center;
            margin: 0;
            min-height: 100vh;
            padding: 22px;
        }

        .error-card {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid var(--rumika-border);
            border-radius: 28px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.12);
            max-width: 720px;
            overflow: hidden;
            width: 100%;
        }

        .error-head {
            align-items: center;
            border-bottom: 1px solid #edf2f7;
            display: flex;
            gap: 14px;
            padding: 22px 24px;
        }

        .error-logo {
            align-items: center;
            background: var(--rumika-teal);
            border-radius: 16px;
            display: flex;
            flex: 0 0 auto;
            height: 54px;
            justify-content: center;
            width: 54px;
        }

        .error-logo img {
            display: block;
            height: 34px;
            width: 34px;
        }

        .error-brand strong {
            display: block;
            font-size: 20px;
            font-weight: 950;
        }

        .error-brand span {
            color: var(--rumika-muted);
            display: block;
            font-size: 14px;
            font-weight: 700;
            margin-top: 2px;
        }

        .error-body {
            display: grid;
            gap: 22px;
            padding: 34px;
        }

        .error-code {
            align-items: center;
            background: var(--rumika-soft);
            border: 1px solid #bde8df;
            border-radius: 999px;
            color: var(--rumika-teal-dark);
            display: inline-flex;
            font-size: 14px;
            font-weight: 950;
            justify-content: center;
            letter-spacing: 0.04em;
            padding: 8px 14px;
            width: max-content;
        }

        h1 {
            font-size: clamp(32px, 5vw, 52px);
            letter-spacing: 0;
            line-height: 1.02;
            margin: 0;
        }

        p {
            color: var(--rumika-muted);
            font-size: 17px;
            font-weight: 650;
            line-height: 1.55;
            margin: 0;
            max-width: 58ch;
        }

        .error-note {
            background: #f8fafc;
            border: 1px dashed var(--rumika-border);
            border-radius: 18px;
            color: #475467;
            font-size: 15px;
            line-height: 1.5;
            padding: 16px;
        }

        .error-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .error-actions a,
        .error-actions button {
            align-items: center;
            border: 1px solid var(--rumika-border);
            border-radius: 16px;
            cursor: pointer;
            display: inline-flex;
            font: inherit;
            font-size: 15px;
            font-weight: 900;
            justify-content: center;
            min-height: 52px;
            padding: 0 18px;
            text-decoration: none;
        }

        .error-actions a {
            background: var(--rumika-teal);
            border-color: var(--rumika-teal);
            color: #ffffff;
        }

        .error-actions button {
            background: #ffffff;
            color: var(--rumika-ink);
        }

        .error-help {
            color: var(--rumika-muted);
            font-size: 13px;
            font-weight: 750;
        }

        @media (max-width: 560px) {
            body {
                align-items: flex-start;
                padding: 14px;
                padding-top: 34px;
            }

            .error-card {
                border-radius: 22px;
            }

            .error-head {
                padding: 18px;
            }

            .error-body {
                padding: 24px 18px;
            }

            .error-actions {
                display: grid;
                grid-template-columns: 1fr;
            }

            .error-actions a,
            .error-actions button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="error-card">
        <header class="error-head">
            <span class="error-logo">
                <img src="{{ asset('rumika-favicon.svg') }}" alt="Rumika">
            </span>
            <div class="error-brand">
                <strong>Rumika SaaS</strong>
                <span>Control de acceso</span>
            </div>
        </header>

        <section class="error-body">
            <span class="error-code">403</span>
            <div>
                <h1>No tienes acceso a esta pantalla</h1>
                <p>
                    Tu usuario esta activo, pero tu rol no tiene permiso para abrir esta seccion.
                    Si necesitas usarla, pide a administracion que revise tus permisos en Usuarios y roles.
                </p>
            </div>

            <div class="error-note">
                Rumika protege la informacion de caja, clientes, inventario, reportes e historia clinica segun el rol de cada persona.
            </div>

            <div class="error-actions">
                <a href="{{ $homeUrl }}">Ir al inicio</a>
                <button type="button" onclick="history.length > 1 ? history.back() : window.location.href='{{ $homeUrl }}'">Volver atras</button>
            </div>

            <div class="error-help">
                Si crees que esto es un error, solicita acceso a un administrador de tu empresa.
            </div>
        </section>
    </main>
</body>
</html>
