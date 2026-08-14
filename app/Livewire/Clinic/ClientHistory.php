<?php

namespace App\Livewire\Clinic;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;
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

    public string $fullName = '';
    public string $identityNumber = '';
    public string $phone = '';
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
        $this->showClientModal = true;
    }

    public function editClient(int $clientId): void
    {
        $client = $this->clientQuery()->whereKey($clientId)->firstOrFail();

        $this->editingClientId = $client->id;
        $this->fullName = $client->full_name;
        $this->identityNumber = $client->identity_number ?? '';
        $this->phone = $client->phone ?? '';
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
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:140'],
            'birthDate' => ['nullable', 'date'],
            'clinicalNotes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $client = $this->editingClientId
            ? $this->clientQuery()->whereKey($this->editingClientId)->firstOrFail()
            : new Client([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
            ]);

        $client->fill([
            'full_name' => $validated['fullName'],
            'identity_number' => $validated['identityNumber'] ?: null,
            'phone' => $validated['phone'] ?: null,
            'email' => $validated['email'] ?: null,
            'birth_date' => $validated['birthDate'] ?: null,
            'clinical_notes' => $validated['clinicalNotes'] ?: null,
            'status' => $validated['status'],
        ]);
        $client->save();

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
                ->orWhere('email', 'like', "%{$search}%")));

        return view('livewire.clinic.client-history', [
            'clients' => $clientsQuery
                ->orderBy('full_name')
                ->paginate(15),
            'clientCount' => (clone $this->clientQuery())->where('status', 'active')->count(),
            'selectedClient' => $this->selectedClientId
                ? $this->clientQuery()
                    ->with(['appointments.services', 'appointments.payments', 'treatmentPlans.payments'])
                    ->whereKey($this->selectedClientId)
                    ->first()
                : null,
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

        return $company->clients()
            ->where(function ($query) use ($branch) {
                $query->whereNull('branch_id')->orWhere('branch_id', $branch->id);
            });
    }

    private function resetClientForm(): void
    {
        $this->reset([
            'editingClientId',
            'fullName',
            'identityNumber',
            'phone',
            'email',
            'birthDate',
            'clinicalNotes',
        ]);
        $this->status = 'active';
        $this->resetErrorBag();
    }
}
