<?php

namespace App\Livewire\Statistics;

use App\Models\Company;
use App\Support\SimpleReportPdf;
use App\Support\RumikaAccess;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatisticsSummary extends Component
{
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $branchFilter = '';
    public string $year = '';
    public bool $showNewPatientsModal = false;
    public bool $showProfessionalModal = false;
    public string $selectedProfessionalKey = '';
    public string $professionalServiceFilter = '';
    public string $topServiceSearch = '';

    public function mount(): void
    {
        abort_unless($this->canView(), 403);

        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->year = now()->format('Y');
    }

    public function render()
    {
        abort_unless($this->canView(), 403);

        $this->validate([
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date', 'after_or_equal:dateFrom'],
            'branchFilter' => ['nullable', 'integer'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'topServiceSearch' => ['nullable', 'string', 'max:120'],
        ]);

        $company = $this->company();
        $from = Carbon::parse($this->dateFrom)->startOfDay();
        $to = Carbon::parse($this->dateTo)->endOfDay();
        $year = (int) $this->year;
        $yearStart = Carbon::create($year, 1, 1)->startOfYear();
        $yearEnd = $yearStart->copy()->endOfYear();
        $branches = $company->branches()->with('businessType')->orderBy('name')->get();
        $branchIds = $this->selectedBranchIds($branches);

        $appointments = $company->appointments()
            ->with(['branch', 'client.primaryPhone', 'services', 'payments.items', 'attendedBy'])
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('scheduled_at', [$from, $to])
            ->get();
        $payments = $company->treatmentPayments()
            ->with(['branch', 'items.soldBy', 'splits'])
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('paid_at', [$from, $to])
            ->get();
        $expenses = $company->expenses()
            ->with(['branch', 'type'])
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('spent_at', [$from->toDateString(), $to->toDateString()])
            ->get();
        $newPatientRows = $this->newPatientRows($company, $branchIds, $from, $to);
        $annualAppointments = $company->appointments()
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('scheduled_at', [$yearStart, $yearEnd])
            ->get();
        $annualPayments = $company->treatmentPayments()
            ->with(['items', 'splits'])
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('paid_at', [$yearStart, $yearEnd])
            ->get();
        $annualExpenses = $company->expenses()
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('spent_at', [$yearStart->toDateString(), $yearEnd->toDateString()])
            ->get();

        $scheduled = $appointments->count();
        $attended = $appointments->where('attended', true)->count();
        $noShow = $appointments->where('status', 'no_show')->count();
        $pending = max(0, $scheduled - $attended - $noShow);
        $webScheduled = $appointments->where('booking_source', 'web')->count();
        $whatsappScheduled = $appointments->where('booking_source', 'whatsapp')->count();
        $manualScheduled = max(0, $scheduled - $webScheduled - $whatsappScheduled);
        $attendanceRate = $scheduled > 0 ? round(($attended / $scheduled) * 100) : 0;
        $servicesIncome = (float) $payments->flatMap->items->where('type', 'service')->sum('total');
        $productsIncome = (float) $payments->flatMap->items->where('type', 'product')->sum('total');
        $expensesTotal = (float) $expenses->sum('amount');

        return view('livewire.statistics.statistics-summary', [
            'branches' => $branches,
            'dateLabel' => $from->format('d/m/Y').' - '.$to->format('d/m/Y'),
            'attendance' => [
                'scheduled' => $scheduled,
                'attended' => $attended,
                'no_show' => $noShow,
                'pending' => $pending,
                'web' => $webScheduled,
                'whatsapp' => $whatsappScheduled,
                'manual' => $manualScheduled,
                'rate' => $attendanceRate,
            ],
            'patients' => [
                'new' => $newPatientRows->count(),
            ],
            'newPatientRows' => $newPatientRows,
            'finance' => [
                'services' => $servicesIncome,
                'products' => $productsIncome,
                'income' => $servicesIncome + $productsIncome,
                'expenses' => $expensesTotal,
                'net' => $servicesIncome + $productsIncome - $expensesTotal,
                'cash' => (float) $payments->flatMap->splits->where('method', 'cash')->sum('amount'),
                'qr' => (float) $payments->flatMap->splits->where('method', 'qr')->sum('amount'),
                'payments_count' => $payments->count(),
                'average_ticket' => $payments->count() > 0 ? ($servicesIncome + $productsIncome) / $payments->count() : 0,
            ],
            'branchRows' => $this->branchRows($branches, $appointments, $payments, $expenses),
            'dailyRows' => $this->dailyRows($appointments, $payments, $expenses, $from, $to),
            'topServices' => $this->topServices($appointments, $payments, $this->topServiceSearch),
            'topServiceSearchSummary' => $this->topServiceSearchSummary($appointments, $payments, $this->topServiceSearch),
            'topProfessionals' => $this->topProfessionals($appointments),
            'professionalRows' => $this->showProfessionalModal
                ? $this->professionalRows($appointments, $this->selectedProfessionalKey, $this->professionalServiceFilter)
                : collect(),
            'professionalIncome' => $this->showProfessionalModal
                ? $this->professionalIncome($appointments, $this->selectedProfessionalKey, $this->professionalServiceFilter)
                : 0,
            'professionalTreatmentOptions' => $this->showProfessionalModal
                ? $this->professionalTreatmentOptions($appointments, $this->selectedProfessionalKey)
                : collect(),
            'selectedProfessionalName' => $this->selectedProfessionalName($appointments, $this->selectedProfessionalKey),
            'topSellers' => $this->topSellers($payments),
            'topProducts' => $this->topProducts($payments),
            'annualRows' => $this->annualRows($annualAppointments, $annualPayments, $annualExpenses, $year),
            'yearOptions' => range(now()->year + 1, now()->year - 4),
        ]);
    }

    private function branchRows($branches, $appointments, $payments, $expenses)
    {
        return $branches
            ->when($this->branchFilter !== '', fn ($items) => $items->where('id', (int) $this->branchFilter))
            ->map(function ($branch) use ($appointments, $payments, $expenses) {
                $branchAppointments = $appointments->where('branch_id', $branch->id);
                $branchPayments = $payments->where('branch_id', $branch->id);
                $services = (float) $branchPayments->flatMap->items->where('type', 'service')->sum('total');
                $products = (float) $branchPayments->flatMap->items->where('type', 'product')->sum('total');
                $expenseTotal = (float) $expenses->where('branch_id', $branch->id)->sum('amount');
                $scheduled = $branchAppointments->count();
                $attended = $branchAppointments->where('attended', true)->count();

                return [
                    'name' => $branch->name,
                    'type' => $branch->businessType?->name ?? 'Sin tipo',
                    'scheduled' => $scheduled,
                    'web' => $branchAppointments->where('booking_source', 'web')->count(),
                    'attended' => $attended,
                    'rate' => $scheduled > 0 ? round(($attended / $scheduled) * 100) : 0,
                    'income' => $services + $products,
                    'services' => $services,
                    'products' => $products,
                    'expenses' => $expenseTotal,
                    'net' => $services + $products - $expenseTotal,
                ];
            })
            ->values();
    }

    public function exportNewPatientsExcel(): StreamedResponse
    {
        abort_unless($this->canView(), 403);

        [$company, $from, $to, $branchIds] = $this->exportContext();
        $rows = $this->newPatientRows($company, $branchIds, $from, $to);
        $xml = $this->newPatientsExcelXml($rows, $from, $to);
        $fileName = 'pacientes-nuevos-'.$from->format('Ymd').'-'.$to->format('Ymd').'.xls';

        return response()->streamDownload(fn () => print($xml), $fileName, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    public function exportNewPatientsPdf(): StreamedResponse
    {
        abort_unless($this->canView(), 403);

        [$company, $from, $to, $branchIds] = $this->exportContext();
        $rows = $this->newPatientRows($company, $branchIds, $from, $to);
        $pdf = new SimpleReportPdf(
            'Pacientes nuevos',
            $from->format('d/m/Y').' - '.$to->format('d/m/Y')
        );

        $pdf->heading('Detalle');
        $pdf->row(['Paciente', 'Contacto', 'Sucursal', 'Registro', 'Tratamientos'], [135, 82, 92, 72, 170], 8);

        foreach ($rows as $patient) {
            $treatments = collect($patient['appointments'])
                ->flatMap(fn (array $appointment) => $appointment['services'])
                ->filter()
                ->unique()
                ->implode(', ');

            $pdf->row([
                $patient['name'],
                $patient['phone'] ?: 'Sin telefono',
                $patient['branch'],
                $patient['registered_at'],
                $treatments !== '' ? $treatments : 'Sin tratamientos registrados',
            ], [135, 82, 92, 72, 170], 8);
        }

        if ($rows->isEmpty()) {
            $pdf->row(['Sin pacientes nuevos en el rango seleccionado.'], [500], 9);
        }

        $fileName = 'pacientes-nuevos-'.$from->format('Ymd').'-'.$to->format('Ymd').'.pdf';

        return response()->streamDownload(fn () => print($pdf->output()), $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function dailyRows($appointments, $payments, $expenses, Carbon $from, Carbon $to)
    {
        $days = collect();
        $cursor = $from->copy()->startOfDay();

        while ($cursor <= $to) {
            $dayStart = $cursor->copy()->startOfDay();
            $dayEnd = $cursor->copy()->endOfDay();
            $dayAppointments = $appointments->filter(fn ($appointment) => $appointment->scheduled_at->between($dayStart, $dayEnd));
            $dayPayments = $payments->filter(fn ($payment) => $payment->paid_at->between($dayStart, $dayEnd));
            $dayExpenses = $expenses->filter(fn ($expense) => $expense->spent_at->between($dayStart, $dayEnd));

            $days->push([
                'date' => $cursor->format('d/m'),
                'scheduled' => $dayAppointments->count(),
                'web' => $dayAppointments->where('booking_source', 'web')->count(),
                'attended' => $dayAppointments->where('attended', true)->count(),
                'income' => (float) $dayPayments->flatMap->items->sum('total'),
                'expenses' => (float) $dayExpenses->sum('amount'),
            ]);

            $cursor->addDay();
        }

        return $days;
    }

    private function topServices($appointments, $payments, string $search = '')
    {
        $search = trim($search);
        $serviceIncome = $payments
            ->flatMap->items
            ->where('type', 'service')
            ->groupBy(fn ($item) => $this->normalizeServiceName((string) $item->name))
            ->map(fn ($items) => (float) $items->sum('total'));

        return $appointments
            ->flatMap(function ($appointment) {
                return $appointment->services->map(fn ($service) => [
                    'name' => $service->name,
                    'attended' => (bool) $appointment->attended,
                ]);
            })
            ->filter(fn (array $service) => filled($service['name']))
            ->when($search !== '', fn ($services) => $services->filter(
                fn (array $service) => str_contains(
                    Str::of($service['name'])->lower()->ascii()->toString(),
                    Str::of($search)->lower()->ascii()->toString()
                )
            ))
            ->groupBy('name')
            ->map(fn ($services, $name) => [
                'name' => $name,
                'count' => $services->count(),
                'attended' => $services->where('attended', true)->count(),
                'income' => (float) ($serviceIncome->get($this->normalizeServiceName((string) $name), 0) ?? 0),
            ])
            ->sortByDesc('count')
            ->take($search !== '' ? 20 : 8)
            ->values();
    }

    private function topServiceSearchSummary($appointments, $payments, string $search): array
    {
        $rows = $this->topServices($appointments, $payments, $search);

        return [
            'scheduled' => (int) $rows->sum('count'),
            'attended' => (int) $rows->sum('attended'),
            'income' => (float) $rows->sum('income'),
        ];
    }

    private function normalizeServiceName(string $name): string
    {
        return Str::of($name)->trim()->lower()->ascii()->toString();
    }

    private function topProfessionals($appointments)
    {
        $attendedAppointments = $appointments->where('attended', true);
        $totalAttended = max(1, $attendedAppointments->count());

        return $attendedAppointments
            ->groupBy(fn ($appointment) => $appointment->attended_by_user_id ?: 'none')
            ->map(function ($items) use ($totalAttended) {
                $first = $items->first();
                $count = $items->count();

                return [
                    'key' => (string) ($first->attended_by_user_id ?: 'none'),
                    'name' => $first->attendedBy?->name ?? 'Sin profesional asignado',
                    'count' => $count,
                    'percentage' => round(($count / $totalAttended) * 100),
                ];
            })
            ->sortByDesc('count')
            ->take(10)
            ->values();
    }

    private function professionalRows($appointments, string $professionalKey, string $serviceFilter)
    {
        return $this->professionalAppointments($appointments, $professionalKey)
            ->flatMap(function ($appointment) {
                $services = $appointment->services->isNotEmpty()
                    ? $appointment->services
                    : collect([(object) ['name' => 'Sin tratamiento registrado']]);

                return $services->map(fn ($service) => [
                    'patient' => $appointment->client?->full_name ?? 'Sin paciente',
                    'phone' => $appointment->client?->displayContact(),
                    'branch' => $appointment->branch?->name ?? 'Sin sucursal',
                    'date' => $appointment->scheduled_at?->format('d/m/Y H:i') ?? 'Sin fecha',
                    'service' => $service->name ?? 'Sin tratamiento registrado',
                    'status' => $this->appointmentStatusLabel((string) $appointment->status),
                ]);
            })
            ->when($serviceFilter !== '', fn ($rows) => $rows->where('service', $serviceFilter))
            ->values();
    }

    private function professionalIncome($appointments, string $professionalKey, string $serviceFilter): float
    {
        $appointmentIds = $this->professionalAppointments($appointments, $professionalKey)->pluck('id');

        return round((float) $appointments
            ->whereIn('id', $appointmentIds)
            ->flatMap->payments
            ->flatMap->items
            ->where('type', 'service')
            ->when($serviceFilter !== '', fn ($items) => $items->where('name', $serviceFilter))
            ->sum('total'), 2);
    }

    private function professionalTreatmentOptions($appointments, string $professionalKey)
    {
        return $this->professionalAppointments($appointments, $professionalKey)
            ->flatMap->services
            ->pluck('name')
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    private function selectedProfessionalName($appointments, string $professionalKey): string
    {
        if ($professionalKey === '') {
            return 'Profesional';
        }

        $appointment = $this->professionalAppointments($appointments, $professionalKey)->first();

        return $appointment?->attendedBy?->name ?? 'Sin profesional asignado';
    }

    private function professionalAppointments($appointments, string $professionalKey)
    {
        return $appointments
            ->where('attended', true)
            ->filter(fn ($appointment) => (string) ($appointment->attended_by_user_id ?: 'none') === $professionalKey)
            ->sortBy('scheduled_at')
            ->values();
    }

    private function topSellers($payments)
    {
        return $payments
            ->flatMap->items
            ->where('type', 'product')
            ->groupBy(fn ($item) => $item->sold_by_user_id ?: 'none')
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'name' => $first->soldBy?->name ?? 'Sin vendedor',
                    'quantity' => (float) $items->sum('quantity'),
                    'total' => (float) $items->sum('total'),
                    'count' => $items->count(),
                ];
            })
            ->sortByDesc('total')
            ->take(8)
            ->values();
    }

    private function topProducts($payments)
    {
        return $payments
            ->flatMap->items
            ->where('type', 'product')
            ->groupBy('name')
            ->map(fn ($items, $name) => [
                'name' => $name,
                'quantity' => (float) $items->sum('quantity'),
                'total' => (float) $items->sum('total'),
            ])
            ->sortByDesc('total')
            ->take(8)
            ->values();
    }

    private function annualRows($appointments, $payments, $expenses, int $year)
    {
        $months = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        return collect(range(1, 12))->map(function ($month) use ($appointments, $payments, $expenses, $months, $year) {
            $monthStart = Carbon::create($year, $month, 1)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();
            $monthAppointments = $appointments->filter(fn ($appointment) => $appointment->scheduled_at->between($monthStart, $monthEnd));
            $monthPayments = $payments->filter(fn ($payment) => $payment->paid_at->between($monthStart, $monthEnd));
            $monthExpenses = $expenses->filter(fn ($expense) => $expense->spent_at->between($monthStart, $monthEnd));
            $services = (float) $monthPayments->flatMap->items->where('type', 'service')->sum('total');
            $products = (float) $monthPayments->flatMap->items->where('type', 'product')->sum('total');
            $expensesTotal = (float) $monthExpenses->sum('amount');

            return [
                'month' => $months[$month],
                'scheduled' => $monthAppointments->count(),
                'attended' => $monthAppointments->where('attended', true)->count(),
                'services' => $services,
                'products' => $products,
                'income' => $services + $products,
                'expenses' => $expensesTotal,
                'net' => $services + $products - $expensesTotal,
            ];
        });
    }

    private function newPatientRows(Company $company, $branchIds, Carbon $from, Carbon $to)
    {
        return $company->clients()
            ->with([
                'branch',
                'primaryPhone',
                'appointments' => fn ($query) => $query
                    ->with('services')
                    ->whereIn('branch_id', $branchIds)
                    ->whereBetween('scheduled_at', [$from, $to])
                    ->orderBy('scheduled_at'),
            ])
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($client) => [
                'name' => $client->full_name,
                'phone' => $client->displayContact(),
                'branch' => $client->branch?->name ?? 'Sin sucursal',
                'registered_at' => $client->created_at?->format('d/m/Y H:i') ?? 'Sin fecha',
                'appointments' => $client->appointments
                    ->map(fn ($appointment) => [
                        'date' => $appointment->scheduled_at?->format('d/m/Y H:i') ?? 'Sin fecha',
                        'status' => $this->appointmentStatusLabel((string) $appointment->status),
                        'services' => $appointment->services->pluck('name')->filter()->values()->all(),
                    ])
                    ->values()
                    ->all(),
            ])
            ->values();
    }

    private function exportContext(): array
    {
        $this->validate([
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date', 'after_or_equal:dateFrom'],
            'branchFilter' => ['nullable', 'integer'],
        ]);

        $company = $this->company();
        $from = Carbon::parse($this->dateFrom)->startOfDay();
        $to = Carbon::parse($this->dateTo)->endOfDay();
        $branches = $company->branches()->orderBy('name')->get();

        return [$company, $from, $to, $this->selectedBranchIds($branches)];
    }

    private function selectedBranchIds($branches)
    {
        return $this->branchFilter !== ''
            ? collect([(int) $this->branchFilter])
            : $branches->pluck('id');
    }

    private function newPatientsExcelXml($rows, Carbon $from, Carbon $to): string
    {
        $body = '<Row>'
            .$this->excelCell('Paciente', 'header')
            .$this->excelCell('Telefono / CI', 'header')
            .$this->excelCell('Sucursal', 'header')
            .$this->excelCell('Fecha registro', 'header')
            .$this->excelCell('Fecha cita', 'header')
            .$this->excelCell('Estado cita', 'header')
            .$this->excelCell('Tratamientos', 'header')
            .'</Row>';

        foreach ($rows as $patient) {
            if (count($patient['appointments']) === 0) {
                $body .= '<Row>'
                    .$this->excelCell($patient['name'])
                    .$this->excelCell($patient['phone'] ?: 'Sin telefono')
                    .$this->excelCell($patient['branch'])
                    .$this->excelCell($patient['registered_at'])
                    .$this->excelCell('Sin citas')
                    .$this->excelCell('Sin estado')
                    .$this->excelCell('Sin tratamientos registrados')
                    .'</Row>';

                continue;
            }

            foreach ($patient['appointments'] as $appointment) {
                $body .= '<Row>'
                    .$this->excelCell($patient['name'])
                    .$this->excelCell($patient['phone'] ?: 'Sin telefono')
                    .$this->excelCell($patient['branch'])
                    .$this->excelCell($patient['registered_at'])
                    .$this->excelCell($appointment['date'])
                    .$this->excelCell($appointment['status'])
                    .$this->excelCell(implode(', ', $appointment['services']) ?: 'Sin tratamientos registrados')
                    .'</Row>';
            }
        }

        if ($rows->isEmpty()) {
            $body .= '<Row>'.$this->excelCell('Sin pacientes nuevos en el rango seleccionado.').'</Row>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<?mso-application progid="Excel.Sheet"?>'
            .'<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
            .'<Styles><Style ss:ID="header"><Font ss:Bold="1"/><Interior ss:Color="#E9F6F4" ss:Pattern="Solid"/></Style></Styles>'
            .'<Worksheet ss:Name="Pacientes nuevos">'
            .'<Table>'
            .'<Row>'.$this->excelCell('Rango', 'header').$this->excelCell($from->format('d/m/Y').' - '.$to->format('d/m/Y')).'</Row>'
            .$body
            .'</Table>'
            .'</Worksheet>'
            .'</Workbook>';
    }

    private function excelCell(string $value, ?string $style = null): string
    {
        $styleAttr = $style ? ' ss:StyleID="'.$style.'"' : '';

        return '<Cell'.$styleAttr.'><Data ss:Type="String">'.htmlspecialchars($value, ENT_XML1, 'UTF-8').'</Data></Cell>';
    }

    public function openNewPatientsModal(): void
    {
        $this->showNewPatientsModal = true;
    }

    public function closeNewPatientsModal(): void
    {
        $this->showNewPatientsModal = false;
    }

    public function openProfessionalModal(string $professionalKey): void
    {
        $this->selectedProfessionalKey = $professionalKey;
        $this->professionalServiceFilter = '';
        $this->showProfessionalModal = true;
    }

    public function closeProfessionalModal(): void
    {
        $this->showProfessionalModal = false;
        $this->selectedProfessionalKey = '';
        $this->professionalServiceFilter = '';
    }

    private function appointmentStatusLabel(string $status): string
    {
        return match ($status) {
            'attended' => 'Asistio',
            'no_show' => 'No asistio',
            'rescheduled' => 'Reagendada',
            'cancelled' => 'Cancelada',
            default => 'Programada',
        };
    }

    private function canView(): bool
    {
        return RumikaAccess::can(Auth::user(), 'estadisticas');
    }

    private function company(): Company
    {
        return Auth::user()->companies()->firstOrFail();
    }
}
