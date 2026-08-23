<?php

namespace App\Http\Controllers;

use App\Livewire\Finance\ReportManager;
use App\Support\ActiveBranch;
use App\Support\Money;
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

        return response()
            ->view('finance.report-pdf', [
                ...$report->reportData($company),
                'company' => $company,
                'branches' => $branches,
                'activeBranch' => ActiveBranch::resolve(Auth::user(), $branches),
                'currency' => Money::symbol(ActiveBranch::resolve(Auth::user(), $branches)),
                'generatedAt' => now()->format('d/m/Y H:i'),
            ]);
    }
}
