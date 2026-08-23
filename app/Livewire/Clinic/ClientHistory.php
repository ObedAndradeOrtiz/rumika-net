<?php

namespace App\Livewire\Clinic;

use App\Models\Branch;
use App\Models\Client;
use App\Models\ClientCharge;
use App\Models\Company;
use App\Models\TreatmentPaymentItem;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ClientHistory extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'active';
    public ?int $selectedClientId = null;
    public ?int $editingClientId = null;
    public ?int $confirmingInactiveClientId = null;
    public bool $showClientModal = false;
    public bool $showHistoryModal = false;
    public string $historyTab = 'appointments';

    public string $fullName = '';
    public string $identityNumber = '';
    public string $phone = '';
    public string $phoneCountry = 'BO';
    public array $phones = [['phone' => '', 'label' => 'Principal', 'is_primary' => true]];
    public string $email = '';
    public string $birthDate = '';
    public string $clinicalNotes = '';
    public string $status = 'active';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function createClient(): void
    {
        $this->resetClientForm();
        $this->phoneCountry = $this->activeBranch()->country_code ?? 'BO';
        $this->showClientModal = true;
    }

    public function editClient(int $clientId): void
    {
        $client = $this->clientQuery()->with('phones')->whereKey($clientId)->firstOrFail();

        $this->editingClientId = $client->id;
        $this->phoneCountry = $this->activeBranch()->country_code ?? 'BO';
        $this->fullName = $client->full_name;
        $this->identityNumber = $client->identity_number ?? '';
        $this->phone = $client->phone ?? '';
        $this->phones = $client->phones
            ->sortByDesc('is_primary')
            ->map(fn ($phone) => [
                'phone' => $phone->phone,
                'label' => $phone->label ?: '',
                'is_primary' => (bool) $phone->is_primary,
            ])
            ->values()
            ->all() ?: [['phone' => $client->phone ?? '', 'label' => 'Principal', 'is_primary' => true]];
        $this->ensureSinglePrimaryPhone();
        $this->email = $client->email ?? '';
        $this->birthDate = $client->birth_date?->format('Y-m-d') ?? '';
        $this->clinicalNotes = $client->clinical_notes ?? '';
        $this->status = $client->status;
        $this->showClientModal = true;
    }

    public function saveClient(): void
    {
        $company = $this->company();
        $branch = $this->activeBranch();

        $validated = $this->validate([
            'fullName' => ['required', 'string', 'max:160'],
            'identityNumber' => ['nullable', 'string', 'max:40'],
            'phoneCountry' => ['required', Rule::in(array_keys(PhoneNumber::countries()))],
            'phones' => ['array', 'max:6'],
            'phones.*.phone' => ['nullable', 'string', 'max:60'],
            'phones.*.label' => ['nullable', 'string', 'max:40'],
            'phones.*.is_primary' => ['nullable', 'boolean'],
            'email' => ['nullable', 'email', 'max:140'],
            'birthDate' => ['nullable', 'date'],
            'clinicalNotes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $phoneRows = $this->normalizedPhoneRows($validated['phones'] ?? [], $validated['phoneCountry']);

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $client = DB::transaction(function () use ($company, $branch, $validated, $phoneRows) {
            $client = $this->editingClientId
                ? $this->clientQuery()->whereKey($this->editingClientId)->firstOrFail()
                : new Client([
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                ]);

            $client->fill([
                'full_name' => $validated['fullName'],
                'identity_number' => $validated['identityNumber'] ?: null,
                'phone' => collect($phoneRows)->firstWhere('is_primary', true)['phone'] ?? ($phoneRows[0]['phone'] ?? null),
                'email' => $validated['email'] ?: null,
                'birth_date' => $validated['birthDate'] ?: null,
                'clinical_notes' => $validated['clinicalNotes'] ?: null,
                'status' => $validated['status'],
            ]);
            $client->save();

            $this->syncClientPhones($client, $phoneRows);

            return $client;
        });

        $this->selectedClientId = $client->id;
        $this->closeClientModal();
    }

    public function confirmInactivateClient(int $clientId): void
    {
        $this->confirmingInactiveClientId = $clientId;
    }

    public function cancelInactivateClient(): void
    {
        $this->confirmingInactiveClientId = null;
    }

    public function inactivateClient(int $clientId): void
    {
        $client = $this->clientQuery()->whereKey($clientId)->firstOrFail();
        $client->update(['status' => 'inactive']);

        if ($this->selectedClientId === $client->id) {
            $this->selectedClientId = null;
        }

        $this->confirmingInactiveClientId = null;
    }

    public function selectClient(int $clientId): void
    {
        $this->selectedClientId = $this->clientQuery()->whereKey($clientId)->value('id');
        $this->historyTab = 'appointments';
        $this->showHistoryModal = (bool) $this->selectedClientId;
    }

    public function closeHistoryModal(): void
    {
        $this->showHistoryModal = false;
        $this->selectedClientId = null;
    }

    public function setHistoryTab(string $tab): void
    {
        if (in_array($tab, ['appointments', 'products', 'service_debts', 'product_debts'], true)) {
            $this->historyTab = $tab;
        }
    }

    public function addPhone(): void
    {
        if (count($this->phones) >= 6) {
            return;
        }

        $this->phones[] = ['phone' => '', 'label' => '', 'is_primary' => false];
    }

    public function removePhone(int $index): void
    {
        unset($this->phones[$index]);
        $this->phones = array_values($this->phones);

        if ($this->phones === []) {
            $this->phones = [['phone' => '', 'label' => 'Principal', 'is_primary' => true]];
        }

        $this->ensureSinglePrimaryPhone();
    }

    public function setPrimaryPhone(int $index): void
    {
        foreach ($this->phones as $phoneIndex => $phoneRow) {
            $this->phones[$phoneIndex]['is_primary'] = $phoneIndex === $index;
        }
    }

    public function closeClientModal(): void
    {
        $this->showClientModal = false;
        $this->resetClientForm();
    }

    public function render()
    {
        $company = $this->company();
        $search = trim($this->search);
        $clientsQuery = $this->clientQuery()
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested
                ->where('full_name', 'like', "%{$search}%")
                ->orWhere('identity_number', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhereHas('phones', fn ($phoneQuery) => $phoneQuery->where('phone', 'like', "%{$search}%"))
                ->orWhere('email', 'like', "%{$search}%")));

        return view('livewire.clinic.client-history', [
            'clients' => $clientsQuery
                ->with(['phones', 'primaryPhone'])
                ->orderBy('full_name')
                ->paginate(15),
            'clientCount' => (clone $this->clientQuery())->where('status', 'active')->count(),
            'selectedClient' => $this->selectedClientId
                ? $this->clientQuery()
                    ->with(['phones', 'appointments.services', 'appointments.payments', 'appointments.attendedBy', 'treatmentPlans.payments'])
                    ->whereKey($this->selectedClientId)
                    ->first()
                : null,
            'historyProductItems' => $this->historyProductItems($company),
            'historyPendingServiceCharges' => $this->historyPendingCharges($company, 'service'),
            'historyPendingProductCharges' => $this->historyPendingCharges($company, 'product'),
            'phoneCountries' => PhoneNumber::countries(),
        ]);
    }

    private function company(): Company
    {
        return Auth::user()->companies()->firstOrFail();
    }

    private function activeBranch(): Branch
    {
        $company = $this->company();
        $branches = Auth::user()->branches()->where('company_id', $company->id)->orderBy('name')->get();
        $branches = $branches->isNotEmpty() ? $branches : $company->branches()->orderBy('name')->get();

        return $branches->firstWhere('id', session('active_branch_id'))
            ?? $branches->first()
            ?? $company->branches()->firstOrFail();
    }

    private function clientQuery()
    {
        $company = $this->company();
        $branch = $this->activeBranch();

        return $company->clients();
    }

    private function resetClientForm(): void
    {
        $this->reset([
            'editingClientId',
            'fullName',
            'identityNumber',
            'phone',
            'phones',
            'email',
            'birthDate',
            'clinicalNotes',
        ]);
        $this->phones = [['phone' => '', 'label' => 'Principal', 'is_primary' => true]];
        $this->phoneCountry = $this->activeBranch()->country_code ?? 'BO';
        $this->status = 'active';
        $this->resetErrorBag();
    }

    private function normalizedPhoneRows(array $phones, string $country): array
    {
        $rows = [];

        foreach ($phones as $index => $phone) {
            $rawPhone = trim((string) ($phone['phone'] ?? ''));

            if ($rawPhone === '') {
                continue;
            }

            $normalized = PhoneNumber::normalize($rawPhone, $country);

            if (! $normalized) {
                $this->addError("phones.{$index}.phone", PhoneNumber::hint($country));

                continue;
            }

            $rows[] = [
                'phone' => $normalized,
                'label' => trim((string) ($phone['label'] ?? '')),
                'is_primary' => filter_var($phone['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        $rows = collect($rows)->unique('phone')->values()->all();

        if ($rows !== [] && ! collect($rows)->contains(fn (array $row) => $row['is_primary'])) {
            $rows[0]['is_primary'] = true;
        }

        return $rows;
    }

    private function syncClientPhones(Client $client, array $phones): void
    {
        $client->phones()->delete();

        foreach ($phones as $index => $phone) {
            $client->phones()->create([
                'phone' => $phone['phone'],
                'label' => $phone['label'] ?: ($phone['is_primary'] ? 'Principal' : null),
                'is_primary' => (bool) $phone['is_primary'],
            ]);
        }
    }

    private function ensureSinglePrimaryPhone(): void
    {
        $primaryFound = false;

        foreach ($this->phones as $index => $phoneRow) {
            $isPrimary = filter_var($phoneRow['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $this->phones[$index]['is_primary'] = $isPrimary && ! $primaryFound;
            $primaryFound = $primaryFound || $this->phones[$index]['is_primary'];
        }

        if (! $primaryFound && isset($this->phones[0])) {
            $this->phones[0]['is_primary'] = true;
        }
    }

    private function historyProductItems(Company $company)
    {
        if (! $this->selectedClientId) {
            return collect();
        }

        return TreatmentPaymentItem::query()
            ->with(['payment', 'product', 'batch', 'soldBy'])
            ->where('type', 'product')
            ->whereHas('payment', fn ($query) => $query
                ->where('company_id', $company->id)
                ->where('client_id', $this->selectedClientId))
            ->latest()
            ->get();
    }

    private function historyPendingCharges(Company $company, string $type)
    {
        if (! $this->selectedClientId) {
            return collect();
        }

        return ClientCharge::query()
            ->with('soldBy')
            ->where('company_id', $company->id)
            ->where('client_id', $this->selectedClientId)
            ->where('type', $type)
            ->whereIn('status', ['pending', 'partial'])
            ->orderBy('charged_at')
            ->get();
    }
}
