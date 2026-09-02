<?php

namespace App\Livewire\Booking;

use App\Models\Appointment;
use App\Models\BookingPage;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Service;
use App\Support\PhoneNumber;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PublicBookingPage extends Component
{
    public BookingPage $page;

    public string $phoneCountry = 'BO';

    public string $phone = '';

    public ?int $clientId = null;

    public bool $clientChecked = false;

    public string $clientName = '';

    public string $clientAge = '';

    public string $clientIdentity = '';

    public string $clientEmail = '';

    public string $selectedBranchId = '';

    public string $selectedServiceId = '';

    public string $serviceSearch = '';

    public string $selectedDate = '';

    public string $selectedTime = '';

    public bool $booked = false;

    public ?int $createdAppointmentId = null;

    public function mount(string $slug): void
    {
        $this->page = BookingPage::query()
            ->with('company.branches.businessType')
            ->where('slug', $slug)
            ->firstOrFail();

        abort_unless($this->page->is_active, 404);

        $company = $this->page->company;
        $branchId = request()->integer('sucursal');
        $branch = $branchId
            ? $company->branches()->where('status', 'active')->whereKey($branchId)->first()
            : null;

        $this->selectedBranchId = $branch?->id
            ? (string) $branch->id
            : (string) ($company->branches()->where('status', 'active')->orderBy('name')->value('id') ?? '');
        $this->selectedDate = now()->addDays(max(0, (int) $this->page->min_days_ahead))->toDateString();
        $this->phoneCountry = $company->branches()->whereKey($this->selectedBranchId)->value('country_code') ?? 'BO';
    }

    public function updatedSelectedBranchId(): void
    {
        $this->selectedServiceId = '';
        $this->selectedTime = '';
        $this->phoneCountry = $this->page->company->branches()->whereKey($this->selectedBranchId)->value('country_code') ?? 'BO';
    }

    public function updatedSelectedServiceId(): void
    {
        $this->selectedTime = '';
    }

    public function updatedServiceSearch(): void
    {
        $this->selectedServiceId = '';
        $this->selectedTime = '';
    }

    public function updatedSelectedDate(): void
    {
        $this->selectedTime = '';
    }

    public function checkClient(): void
    {
        $validated = $this->validate([
            'phoneCountry' => ['required', Rule::in(array_keys(PhoneNumber::countries()))],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $phone = PhoneNumber::normalize($validated['phone'], $validated['phoneCountry']);

        if (! $phone) {
            $this->addError('phone', PhoneNumber::hint($validated['phoneCountry']));
            return;
        }

        $client = $this->page->company->clients()
            ->where(function ($query) use ($phone) {
                $query->where('phone', $phone)
                    ->orWhereHas('phones', fn ($phones) => $phones->where('phone', $phone));
            })
            ->first();

        $this->phone = $phone;
        $this->clientId = $client?->id;
        $this->clientName = $client?->full_name ?? '';
        $this->clientEmail = $client?->email ?? '';
        $this->clientIdentity = $client?->identity_number ?? '';
        $this->clientChecked = true;
    }

    public function book(): void
    {
        if (! $this->canBook()) {
            $this->addError('selectedTime', 'Completa tus datos, tratamiento, fecha y horario para agendar.');
            return;
        }

        if (! $this->clientChecked) {
            $this->addError('phone', 'Primero valida tu numero para continuar.');
            return;
        }

        $company = $this->page->company;
        $branchIds = $company->branches()->where('status', 'active')->pluck('id')->all();
        $serviceIds = $this->publishedServices()->pluck('id')->all();

        $minDate = now()->addDays(max(0, (int) $this->page->min_days_ahead))->toDateString();
        $maxDate = now()->addDays(max(1, (int) $this->page->max_days_ahead))->toDateString();

        $validated = $this->validate([
            'selectedBranchId' => ['required', Rule::in($branchIds)],
            'selectedServiceId' => ['required', Rule::in($serviceIds)],
            'selectedDate' => ['required', 'date', 'after_or_equal:'.$minDate, 'before_or_equal:'.$maxDate],
            'selectedTime' => ['required', 'date_format:H:i'],
            'phone' => ['required', 'string', 'max:30'],
            'clientName' => [$this->clientId ? 'nullable' : 'required', 'string', 'max:160'],
            'clientAge' => ['nullable', 'integer', 'min:1', 'max:120'],
            'clientIdentity' => [$this->page->require_identity ? 'required' : 'nullable', 'string', 'max:40'],
            'clientEmail' => [$this->page->require_email ? 'required' : 'nullable', 'email', 'max:120'],
        ]);

        $normalizedPhone = PhoneNumber::normalize($validated['phone'], $this->phoneCountry);
        if (! $normalizedPhone) {
            $this->addError('phone', PhoneNumber::hint($this->phoneCountry));
            return;
        }
        $validated['phone'] = $normalizedPhone;

        if (! in_array($validated['selectedTime'], $this->availableSlots(), true)) {
            $this->addError('selectedTime', 'Ese horario ya no esta disponible. Elige otro.');
            return;
        }

        $service = $this->publishedServices()->where('services.id', (int) $validated['selectedServiceId'])->firstOrFail();
        $duration = $service->duration_minutes ?: $this->page->default_duration_minutes;
        $servicePrice = $this->promotionalPriceFor((int) $service->id) ?? $service->price;

        $appointment = DB::transaction(function () use ($company, $validated, $service, $duration, $servicePrice) {
            $client = $this->clientId
                ? $company->clients()->whereKey($this->clientId)->firstOrFail()
                : $company->clients()->create([
                    'branch_id' => (int) $validated['selectedBranchId'],
                    'full_name' => $validated['clientName'],
                    'identity_number' => $validated['clientIdentity'] ?: null,
                    'phone' => $validated['phone'],
                    'email' => $validated['clientEmail'] ?: null,
                    'clinical_notes' => $validated['clientAge'] ? 'Edad registrada desde reserva online: '.$validated['clientAge'] : null,
                ]);

            if (! $client->phones()->where('phone', $validated['phone'])->exists()) {
                $client->phones()->create([
                    'phone' => $validated['phone'],
                    'label' => 'Principal',
                    'is_primary' => ! $client->phones()->exists(),
                ]);
            }

            $appointment = Appointment::query()->create([
                'company_id' => $company->id,
                'branch_id' => (int) $validated['selectedBranchId'],
                'client_id' => $client->id,
                'scheduled_at' => Carbon::parse($validated['selectedDate'].' '.$validated['selectedTime']),
                'duration_minutes' => $duration,
                'status' => 'scheduled',
                'clinical_notes' => 'Cita creada desde enlace de reserva online.',
            ]);

            $appointment->services()->create([
                'service_id' => $service->id,
                'name' => $service->name,
                'price' => $servicePrice,
                'duration_minutes' => $service->duration_minutes,
            ]);

            return $appointment;
        });

        $this->createdAppointmentId = $appointment->id;
        $this->booked = true;
    }

    public function resetBooking(): void
    {
        $company = $this->page->company;

        $this->reset([
            'phone',
            'clientId',
            'clientChecked',
            'clientName',
            'clientAge',
            'clientIdentity',
            'clientEmail',
            'selectedServiceId',
            'serviceSearch',
            'selectedTime',
            'booked',
            'createdAppointmentId',
        ]);

        $this->selectedBranchId = (string) ($company->branches()->where('status', 'active')->orderBy('name')->value('id') ?? '');
        $this->selectedDate = now()->addDays(max(0, (int) $this->page->min_days_ahead))->toDateString();
        $this->phoneCountry = $company->branches()->whereKey($this->selectedBranchId)->value('country_code') ?? 'BO';
    }

    public function availableSlots(): array
    {
        if (! $this->selectedBranchId || ! $this->selectedDate) {
            return [];
        }

        $service = $this->selectedServiceId
            ? $this->publishedServices()->where('services.id', (int) $this->selectedServiceId)->first()
            : null;
        $duration = (int) ($service?->duration_minutes ?: $this->page->default_duration_minutes);
        $interval = max(10, (int) $this->page->slot_interval_minutes);
        $start = Carbon::parse($this->selectedDate.' '.substr((string) $this->page->available_from, 0, 5));
        $end = Carbon::parse($this->selectedDate.' '.substr((string) $this->page->available_to, 0, 5));

        $existing = Appointment::query()
            ->where('company_id', $this->page->company_id)
            ->where('branch_id', (int) $this->selectedBranchId)
            ->whereDate('scheduled_at', $this->selectedDate)
            ->whereNotIn('status', ['cancelled', 'deleted', 'rescheduled'])
            ->get(['scheduled_at', 'duration_minutes']);

        $slots = [];
        for ($cursor = $start->copy(); $cursor->copy()->addMinutes($duration)->lte($end); $cursor->addMinutes($interval)) {
            $slotEnd = $cursor->copy()->addMinutes($duration);
            $overlaps = $existing->filter(function (Appointment $appointment) use ($cursor, $slotEnd) {
                $appointmentStart = $appointment->scheduled_at;
                $appointmentEnd = $appointmentStart->copy()->addMinutes($appointment->duration_minutes ?: 60);

                return $cursor->lt($appointmentEnd) && $slotEnd->gt($appointmentStart);
            })->count();

            if ($overlaps < max(1, (int) $this->page->max_appointments_per_slot) && $cursor->isFuture()) {
                $slots[] = $cursor->format('H:i');
            }
        }

        return $slots;
    }

    public function canBook(): bool
    {
        if (! $this->clientChecked || ! $this->selectedBranchId || ! $this->selectedServiceId || ! $this->selectedDate || ! $this->selectedTime) {
            return false;
        }

        if (! in_array($this->selectedTime, $this->availableSlots(), true)) {
            return false;
        }

        if (! $this->clientId && trim($this->clientName) === '') {
            return false;
        }

        if ($this->page->require_identity && trim($this->clientIdentity) === '') {
            return false;
        }

        if ($this->page->require_email && trim($this->clientEmail) === '') {
            return false;
        }

        return true;
    }

    public function render()
    {
        $company = $this->page->company;
        $branches = $company->branches()->where('status', 'active')->orderBy('name')->get();
        $services = $this->publishedServices()
            ->when(trim($this->serviceSearch) !== '', function ($query) {
                $search = trim($this->serviceSearch);

                $query->where(function ($services) use ($search) {
                    $services->where('name', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->get();

        return view('livewire.booking.public-booking-page', [
            'company' => $company,
            'branches' => $branches,
            'services' => $services,
            'promotedServices' => $this->promotedServices(),
            'publicPromotionalPrices' => $this->publicPromotionalPrices(),
            'slots' => $this->availableSlots(),
            'canBook' => $this->canBook(),
            'minDate' => now()->addDays(max(0, (int) $this->page->min_days_ahead))->toDateString(),
            'maxDate' => now()->addDays(max(1, (int) $this->page->max_days_ahead))->toDateString(),
        ])->layout('layouts.public-booking');
    }

    private function publishedServices()
    {
        $query = $this->page->publish_all_services
            ? $this->page->company->services()
            : $this->page->services();

        return $query->whereIn('services.status', ['active', 'available']);
    }

    private function promotedServices()
    {
        return $this->page->services()
            ->wherePivot('is_promoted', true)
            ->whereIn('services.status', ['active', 'available'])
            ->orderBy('name')
            ->get();
    }

    private function publicPromotionalPrices()
    {
        return $this->page->services()
            ->whereNotNull('booking_page_services.promotional_price')
            ->pluck('booking_page_services.promotional_price', 'services.id');
    }

    private function promotionalPriceFor(int $serviceId): ?float
    {
        $price = $this->page->services()
            ->where('services.id', $serviceId)
            ->value('booking_page_services.promotional_price');

        return $price === null ? null : (float) $price;
    }
}
