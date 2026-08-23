<?php

namespace App\Http\Controllers;

use App\Livewire\Finance\ReportManager;
use App\Support\ActiveBranch;
use App\Support\Money;
use App\Support\SimpleReportPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinanceReportExportController extends Controller
{
    public function __invoke(Request $request)
    {
        $company = Auth::user()->companies()->firstOrFail();
        $branches = Auth::user()->branches()->where('company_id', $company->id)->orderBy('name')->get();
        $branches = $branches->isNotEmpty() ? $branches : $company->branches()->orderBy('name')->get();

        $report = new ReportManager();
        $report->dateFrom = $request->query('from', now()->startOfMonth()->format('Y-m-d'));
        $report->dateTo = $request->query('to', now()->format('Y-m-d'));
        $report->branchFilter = (string) $request->query('branch', '');

        $activeBranch = ActiveBranch::resolve(Auth::user(), $branches);
        $currency = Money::symbol($activeBranch);
        $data = $report->reportData($company);

        $pdf = new SimpleReportPdf(
            'Reporte gerencial',
            $company->name.' - '.$data['rangeLabel'].' - generado '.now()->format('d/m/Y H:i')
        );

        $pdf->heading('Resumen general');
        $pdf->kpis([
            ['Ingresos', $currency.' '.number_format((float) $data['kpis']['income'], 2)],
            ['Servicios', $currency.' '.number_format((float) $data['kpis']['services'], 2)],
            ['Productos', $currency.' '.number_format((float) $data['kpis']['products'], 2)],
            ['Egresos', $currency.' '.number_format((float) $data['kpis']['expenses'], 2)],
            ['Neto', $currency.' '.number_format((float) $data['kpis']['net'], 2)],
            ['Por cobrar', $currency.' '.number_format((float) $data['kpis']['debts'], 2)],
            ['Comisiones', $currency.' '.number_format((float) $data['kpis']['commissions'], 2)],
            ['Asistencia', $data['kpis']['attended'].'/'.$data['kpis']['appointments']],
        ]);

        $pdf->heading('Resumen por sucursal');
        $pdf->row(['Sucursal', 'Servicios', 'Productos', 'Gastos', 'Neto', 'Comis.', 'Asist.', 'Por cobrar'], [105, 62, 62, 58, 58, 58, 50, 72], 8);
        foreach ($data['branchRows'] as $row) {
            $pdf->row([
                $row['name'],
                $currency.' '.number_format((float) $row['services'], 2),
                $currency.' '.number_format((float) $row['products'], 2),
                $currency.' '.number_format((float) $row['expenses'], 2),
                $currency.' '.number_format((float) $row['net'], 2),
                $currency.' '.number_format((float) $row['commissions'], 2),
                $row['attended'].'/'.$row['appointments'],
                $currency.' '.number_format((float) $row['debts'], 2),
            ], [105, 62, 62, 58, 58, 58, 50, 72], 8);
        }

        $pdf->heading('Servicios mas vendidos');
        foreach ($data['serviceRows'] as $row) {
            $pdf->row([$row['name'], $row['count'].' venta(s)', $currency.' '.number_format((float) $row['total'], 2)], [330, 90, 110], 9);
        }

        $pdf->heading('Productos mas vendidos');
        foreach ($data['productRows'] as $row) {
            $pdf->row([$row['name'], number_format((float) $row['count'], 2).' unidad(es)', $currency.' '.number_format((float) $row['total'], 2)], [330, 90, 110], 9);
        }

        $pdf->heading('Rendimiento por personal');
        foreach ($data['staffRows'] as $row) {
            $pdf->row([
                $row['name'],
                'Servicios '.$currency.' '.number_format((float) $row['services'], 2),
                'Productos '.$currency.' '.number_format((float) $row['products'], 2),
                'Comision '.$currency.' '.number_format((float) $row['commission'], 2),
            ], [180, 115, 115, 115], 9);
        }

        $pdf->heading('Servicios referidos');
        if ($data['referredServiceRows']->isEmpty()) {
            $pdf->row(['Sin servicios referidos en este periodo.'], [500], 9);
        }
        foreach ($data['referredServiceRows'] as $row) {
            $pdf->row([
                $row['name'],
                $row['count'].' servicio(s)',
                $currency.' '.number_format((float) $row['total'], 2),
                $row['completed'].'/'.$row['count'].' realizado(s)',
            ], [180, 115, 100, 120], 9);
        }

        $fileName = 'reporte-gerencial-'.$report->dateFrom.'-'.$report->dateTo.'.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }
}
