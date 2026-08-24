<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Términos y condiciones | Rumika SaaS</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('rumika-favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900" rel="stylesheet" />
    <style>
        :root { --ink:#07152f; --text:#1b2940; --muted:#667085; --border:#d7e1ee; --bg:#f4f7fb; --primary:#008b7f; --soft:#e5f7f3; }
        * { box-sizing: border-box; }
        body { margin:0; font-family:Figtree, system-ui, sans-serif; color:var(--text); background:linear-gradient(180deg,#fff 0%,var(--bg) 100%); }
        .wrap { width:min(980px, calc(100% - 28px)); margin:0 auto; padding:28px 0 54px; }
        .top { display:flex; align-items:center; justify-content:space-between; gap:14px; margin-bottom:22px; }
        .brand { display:flex; align-items:center; gap:12px; color:var(--ink); font-weight:950; font-size:22px; text-decoration:none; }
        .brand img { width:46px; height:46px; border-radius:14px; }
        .back { border:1px solid var(--border); border-radius:999px; padding:12px 16px; color:var(--ink); font-weight:900; text-decoration:none; background:#fff; }
        .card { background:#fff; border:1px solid var(--border); border-radius:28px; box-shadow:0 22px 64px rgba(15,23,42,.08); overflow:hidden; }
        .hero { padding:34px; border-bottom:1px solid #edf2f7; background:radial-gradient(circle at 95% 0%, rgba(0,139,127,.14), transparent 28rem), #fff; }
        .label { color:var(--primary); font-size:13px; font-weight:950; letter-spacing:.08em; text-transform:uppercase; }
        h1 { margin:10px 0 0; color:var(--ink); font-size:clamp(34px,6vw,56px); line-height:1; letter-spacing:0; }
        .updated { margin-top:14px; color:var(--muted); font-weight:800; }
        .content { padding:34px; display:grid; gap:24px; }
        h2 { margin:0 0 10px; color:var(--ink); font-size:22px; }
        p, li { color:var(--text); font-size:16px; line-height:1.65; font-weight:650; }
        p { margin:0; }
        ul { margin:0; padding-left:20px; display:grid; gap:8px; }
        .note { border:1px solid #bde8df; background:var(--soft); color:#075e55; border-radius:20px; padding:18px; font-weight:800; line-height:1.55; }
        @media (max-width:620px) { .top { align-items:flex-start; flex-direction:column; } .hero,.content { padding:24px 18px; } .back { width:100%; text-align:center; } }
    </style>
</head>
<body>
    <main class="wrap">
        <nav class="top">
            <a class="brand" href="{{ url('/') }}"><img src="{{ asset('rumika-favicon.svg') }}" alt="Rumika">Rumika SaaS</a>
            <a class="back" href="{{ url('/') }}">Volver al inicio</a>
        </nav>

        <article class="card">
            <header class="hero">
                <span class="label">Documento legal</span>
                <h1>Términos y condiciones</h1>
                <p class="updated">Versión 2026-08-24</p>
            </header>

            <div class="content">
                <section class="note">
                    Al crear una cuenta, ingresar al sistema o usar Rumika SaaS, aceptas estos términos. Si no estás de acuerdo, no debes usar la plataforma.
                </section>

                <section>
                    <h2>1. Servicio</h2>
                    <p>Rumika SaaS es una plataforma modular para gestionar agenda, clientes, ventas, caja, inventario, historial clínico, reportes, roles, sucursales, mensajería y otros módulos relacionados con la operación de negocios de atención, salud estética, centros médicos, farmacias, tiendas y servicios similares.</p>
                </section>

                <section>
                    <h2>2. Cuenta, empresa y usuarios</h2>
                    <ul>
                        <li>La persona que registra la empresa declara tener autorización para administrar sus datos, usuarios, sucursales y configuración.</li>
                        <li>Cada empresa es responsable de crear usuarios, asignar roles, limitar permisos y deshabilitar accesos cuando corresponda.</li>
                        <li>El usuario debe mantener protegida su cuenta y no compartir accesos con terceros.</li>
                    </ul>
                </section>

                <section>
                    <h2>3. Datos ingresados por el cliente</h2>
                    <p>La empresa usuaria es responsable de la veracidad, legalidad y autorización de los datos que ingresa, incluyendo información de clientes, pacientes, compradores, teléfonos, documentos, historial clínico, imágenes, recetas, pagos, inventario y conversaciones.</p>
                </section>

                <section>
                    <h2>4. Uso en salud, estética y atención profesional</h2>
                    <p>Rumika organiza información operativa y administrativa. No reemplaza criterio médico, odontológico, farmacéutico, contable, tributario o legal. Cada profesional o empresa es responsable de sus diagnósticos, tratamientos, recetas, procedimientos, cumplimiento normativo y atención al cliente.</p>
                </section>

                <section>
                    <h2>5. Pagos, planes y suspensión</h2>
                    <ul>
                        <li>Los planes pueden limitar módulos, usuarios, sucursales, CRM, reportes u otras funciones.</li>
                        <li>El periodo gratuito o demo puede bloquearse al vencer.</li>
                        <li>La falta de pago, uso indebido o incumplimiento de estos términos puede generar suspensión temporal o definitiva.</li>
                        <li>Los precios y condiciones comerciales pueden actualizarse para nuevas contrataciones o renovaciones.</li>
                    </ul>
                </section>

                <section>
                    <h2>6. Inventario, caja, ventas y reportes</h2>
                    <p>Rumika ayuda a registrar movimientos, cobros, tickets, cierres, deudas, comisiones y reportes. La empresa debe revisar sus cierres, facturación, impuestos, diferencias de inventario y movimientos antes de tomar decisiones contables o tributarias.</p>
                </section>

                <section>
                    <h2>7. WhatsApp, integraciones e impresiones</h2>
                    <p>Las integraciones con WhatsApp API, QZ Tray, impresoras, servicios externos o APIs de terceros dependen de configuraciones, permisos, disponibilidad del proveedor, internet y equipos del cliente. Rumika no garantiza que servicios externos estén disponibles sin interrupciones.</p>
                </section>

                <section>
                    <h2>8. Uso permitido</h2>
                    <ul>
                        <li>No se permite usar Rumika para actividades ilegales, fraude, spam, suplantación o acceso no autorizado.</li>
                        <li>No se permite intentar vulnerar, copiar, revender sin autorización o dañar la plataforma.</li>
                        <li>La empresa debe contar con autorización para contactar clientes por WhatsApp, email u otros medios.</li>
                    </ul>
                </section>

                <section>
                    <h2>9. Responsabilidad y disponibilidad</h2>
                    <p>Rumika se ofrece como herramienta de gestión. Aunque se busca mantener estabilidad y seguridad, pueden existir mantenimientos, errores, cortes de internet, fallas de servidor o servicios de terceros. En la medida permitida por ley, Rumika no será responsable por pérdidas indirectas, lucro cesante, decisiones profesionales, errores de carga de datos o incumplimientos propios de la empresa usuaria.</p>
                </section>

                <section>
                    <h2>10. Cambios</h2>
                    <p>Estos términos pueden actualizarse. Cuando existan cambios importantes, se podrá solicitar nueva aceptación o informar dentro del sistema.</p>
                </section>

                <section>
                    <h2>11. Contacto</h2>
                    <p>Para soporte o consultas comerciales, puedes escribir a Rumika/DigitBol por WhatsApp al 59177348087.</p>
                </section>
            </div>
        </article>
    </main>
</body>
</html>
