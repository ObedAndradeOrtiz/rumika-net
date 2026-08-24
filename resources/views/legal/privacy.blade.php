<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Política de privacidad | Rumika SaaS</title>
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
                <h1>Política de privacidad</h1>
                <p class="updated">Versión 2026-08-24</p>
            </header>

            <div class="content">
                <section class="note">
                    Esta política explica cómo Rumika SaaS trata datos dentro de la plataforma. Cada empresa usuaria sigue siendo responsable de obtener autorización de sus clientes, pacientes, compradores y usuarios cuando corresponda.
                </section>

                <section>
                    <h2>1. Datos que podemos tratar</h2>
                    <ul>
                        <li>Datos de usuarios del sistema: nombre, correo, foto, teléfono, rol, sucursales asignadas y estado de acceso.</li>
                        <li>Datos de empresa y sucursales: nombre comercial, país, moneda, logo, dirección, impresora, planes y pagos.</li>
                        <li>Datos operativos ingresados por la empresa: clientes, pacientes, compradores, CI/documento, NIT, teléfonos, email, citas, servicios, productos, pagos, caja, gastos, deudas, inventario, reportes y bitácora.</li>
                        <li>Datos clínicos o sensibles si la empresa los registra: fichas, documentos, imágenes, recetas, notas, diagnósticos o información asociada a una atención.</li>
                        <li>Datos técnicos: IP, navegador, eventos de seguridad, errores, inicio/cierre de sesión y aceptación de términos.</li>
                    </ul>
                </section>

                <section>
                    <h2>2. Finalidad</h2>
                    <p>Usamos los datos para operar la plataforma, autenticar usuarios, administrar permisos, mostrar información por rol, generar reportes, controlar caja e inventario, registrar historial, prestar soporte, mejorar seguridad y cumplir obligaciones técnicas o legales aplicables.</p>
                </section>

                <section>
                    <h2>3. Responsabilidad de la empresa usuaria</h2>
                    <p>La empresa que usa Rumika decide qué datos ingresa, quién puede verlos y para qué los usa. La empresa debe contar con consentimiento o base legal para registrar datos personales, clínicos, comerciales, mensajes, imágenes o documentos de sus clientes y pacientes.</p>
                </section>

                <section>
                    <h2>4. Roles y acceso</h2>
                    <p>Rumika permite limitar información por roles, sucursales y permisos. La empresa debe revisar que cada usuario tenga solo el acceso necesario. Los administradores pueden habilitar o deshabilitar usuarios y controlar vistas sensibles como caja, reportes, historia clínica, inventario, deudas y bitácora.</p>
                </section>

                <section>
                    <h2>5. WhatsApp, integraciones y terceros</h2>
                    <p>Cuando se usan WhatsApp API, Firebase/Google, QZ Tray, impresoras u otros servicios externos, esos proveedores pueden tratar datos conforme a sus propias políticas. La empresa debe asegurarse de que sus comunicaciones cumplan las reglas aplicables de consentimiento, privacidad y mensajería.</p>
                </section>

                <section>
                    <h2>6. Conservación y eliminación</h2>
                    <p>Los datos se conservan mientras la cuenta esté activa o mientras sean necesarios para operación, respaldo, soporte, auditoría o cumplimiento. La empresa puede solicitar revisión, exportación o eliminación conforme al alcance técnico y legal permitido. Algunas acciones pueden conservarse en bitácora para seguridad y auditoría.</p>
                </section>

                <section>
                    <h2>7. Seguridad</h2>
                    <p>Rumika aplica medidas razonables de seguridad, autenticación, permisos, separación por empresa/sucursal y registros de actividad. Ningún sistema es completamente infalible, por lo que la empresa debe proteger sus credenciales, equipos, impresoras, redes y accesos de personal.</p>
                </section>

                <section>
                    <h2>8. Transferencias y soporte</h2>
                    <p>Para prestar soporte, mantenimiento o alojamiento, ciertos datos pueden ser procesados por proveedores tecnológicos o personal autorizado. El acceso se limita a lo necesario para resolver incidencias, mejorar el servicio o mantener la plataforma.</p>
                </section>

                <section>
                    <h2>9. Derechos y consultas</h2>
                    <p>Los titulares de datos deben contactar primero a la empresa que registró su información. Para soporte técnico o consultas sobre Rumika, puedes escribir a DigitBol/Rumika por WhatsApp al 59177348087.</p>
                </section>

                <section>
                    <h2>10. Cambios</h2>
                    <p>Esta política puede actualizarse. Si los cambios son relevantes, se podrá informar dentro del sistema o solicitar nueva aceptación.</p>
                </section>
            </div>
        </article>
    </main>
</body>
</html>
