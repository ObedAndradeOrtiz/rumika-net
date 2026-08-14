<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Rumika SaaS') }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('rumika-favicon.svg') }}?v=2">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <main class="rm-auth-shell">
            <div class="rm-auth-layout">
                <section class="rm-auth-preview" aria-label="Resumen de Rumika SaaS">
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-3" wire:navigate>
                        <span class="rm-brand-mark">
                            <x-application-logo class="h-7 w-7 text-white" />
                        </span>
                        <span class="text-2xl font-black text-slate-950">Rumika SaaS</span>
                    </a>

                    <div>
                        <h1>Agenda, clientes e historial en bloques.</h1>
                        <p>
                            Una base modular para clinicas, spas, centros de belleza, barberias y dentistas.
                            Cada sucursal podra activar sus modulos segun su tipo de negocio.
                        </p>
                    </div>

                    <div class="rm-module-strip" aria-label="Modulos iniciales">
                        <span class="rm-module-chip">Agenda</span>
                        <span class="rm-module-chip">Clientes</span>
                        <span class="rm-module-chip">Historial</span>
                        <span class="rm-module-chip">Sucursales</span>
                    </div>

                    <div class="rm-preview-grid">
                        <article class="rm-preview-card">
                            <span>Hoy</span>
                            <strong>18 citas</strong>
                            <span>4 tipos de servicio activos</span>
                        </article>
                        <article class="rm-preview-card">
                            <span>Sucursal</span>
                            <strong>Centro</strong>
                            <span>Clinica estetica</span>
                        </article>
                        <article class="rm-preview-card">
                            <span>Cliente</span>
                            <strong>Historial visible</strong>
                            <span>Desde cada cita en agenda</span>
                        </article>
                        <article class="rm-preview-card">
                            <span>Marca</span>
                            <strong>Logo propio</strong>
                            <span>Nombre y sucursales configurables</span>
                        </article>
                    </div>
                </section>

                <section class="rm-auth-card rm-auth-form-panel">
                    {{ $slot }}
                </section>
            </div>
        </main>
    </body>
</html>
