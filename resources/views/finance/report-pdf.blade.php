<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte gerencial - {{ $company->name }}</title>
    <style>
        body { color: #101828; font-family: Arial, sans-serif; font-size: 12px; margin: 24px; }
        h1 { font-size: 24px; margin: 0 0 4px; }
        h2 { border-bottom: 1px solid #d0d5dd; font-size: 16px; margin: 22px 0 10px; padding-bottom: 6px; }
        .muted { color: #667085; }
        .kpis { display: grid; gap: 8px; grid-template-columns: repeat(4, 1fr); margin: 18px 0; }
        .kpi { border: 1px solid #d0d5dd; border-radius: 10px; padding: 10px; }
        .kpi strong { display: block; font-size: 16px; margin-bottom: 4px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border-bottom: 1px solid #eaecf0; padding: 8px 6px; text-align: left; }
        th { background: #f2f4f7; font-size: 11px; text-transform: uppercase; }
        .grid { display: grid; gap: 18px; grid-template-columns: repeat(2, 1fr); }
        @media print { button { display: none; } body { margin: 12mm; } }
    </style>
</head>
<body>
    <button onclick="window.print()">Guardar o imprimir PDF</button>
    <h1>Reporte gerencial</h1>
    <div class="muted">{{ $company->name }} - {{ $rangeLabel }} - generado {{ $generatedAt }}</div>

    <section class="kpis">
        <div class="kpi"><strong>{{ $currency }} {{ number_format((float) $kpis['income'], 2) }}</strong>Ingresos</div>
        <div class="kpi"><strong>{{ $currency }} {{ number_format((float) $kpis['expenses'], 2) }}</strong>Egresos</div>
        <div class="kpi"><strong>{{ $currency }} {{ number_format((float) $kpis['net'], 2) }}</strong>Neto</div>
        <div class="kpi"><strong>{{ $currency }} {{ number_format((float) $kpis['debts'], 2) }}</strong>Deudas</div>
        <div class="kpi"><strong>{{ $currency }} {{ number_format((float) $kpis['services'], 2) }}</strong>Servicios</div>
        <div class="kpi"><strong>{{ $currency }} {{ number_format((float) $kpis['products'], 2) }}</strong>Productos</div>
        <div class="kpi"><strong>{{ $currency }} {{ number_format((float) $kpis['commissions'], 2) }}</strong>Comisiones</div>
        <div class="kpi"><strong>{{ $kpis['attended'] }}/{{ $kpis['appointments'] }}</strong>Asistencia</div>
    </section>

    <h2>Resumen por sucursal</h2>
    <table>
        <thead><tr><th>Sucursal</th><th>Servicios</th><th>Productos</th><th>Gastos</th><th>Neto</th><th>Asistencia</th><th>Deudas</th></tr></thead>
        <tbody>
            @foreach ($branchRows as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $currency }} {{ number_format((float) $row['services'], 2) }}</td>
                    <td>{{ $currency }} {{ number_format((float) $row['products'], 2) }}</td>
                    <td>{{ $currency }} {{ number_format((float) $row['expenses'], 2) }}</td>
                    <td>{{ $currency }} {{ number_format((float) $row['net'], 2) }}</td>
                    <td>{{ $row['attended'] }}/{{ $row['appointments'] }}</td>
                    <td>{{ $currency }} {{ number_format((float) $row['debts'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="grid">
        <section>
            <h2>Servicios mas vendidos</h2>
            <table><tbody>
                @foreach ($serviceRows as $row)
                    <tr><td>{{ $row['name'] }}</td><td>{{ $row['count'] }}</td><td>{{ $currency }} {{ number_format((float) $row['total'], 2) }}</td></tr>
                @endforeach
            </tbody></table>
        </section>
        <section>
            <h2>Productos mas vendidos</h2>
            <table><tbody>
                @foreach ($productRows as $row)
                    <tr><td>{{ $row['name'] }}</td><td>{{ number_format((float) $row['count'], 2) }}</td><td>{{ $currency }} {{ number_format((float) $row['total'], 2) }}</td></tr>
                @endforeach
            </tbody></table>
        </section>
    </div>

    <h2>Rendimiento por personal</h2>
    <table>
        <thead><tr><th>Personal</th><th>Servicios</th><th>Productos</th><th>Comision</th></tr></thead>
        <tbody>
            @foreach ($staffRows as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $currency }} {{ number_format((float) $row['services'], 2) }}</td>
                    <td>{{ $currency }} {{ number_format((float) $row['products'], 2) }}</td>
                    <td>{{ $currency }} {{ number_format((float) $row['commission'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 350));</script>
</body>
</html>
