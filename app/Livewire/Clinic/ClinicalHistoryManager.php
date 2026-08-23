<?php

namespace App\Livewire\Clinic;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Client;
use App\Models\ClinicalDocument;
use App\Models\ClinicalPatientAccess;
use App\Models\ClinicalPrescription;
use App\Models\ClinicalRecord;
use App\Models\ClinicalSpecialty;
use App\Models\ClinicalTemplate;
use App\Models\Company;
use App\Models\Service;
use App\Models\User;
use App\Support\RumikaAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ClinicalHistoryManager extends Component
{
    use WithFileUploads;

    public string $tab = 'records';
    public string $clientSearch = '';
    public ?int $selectedClientId = null;
    public bool $lockedToClient = false;

    public ?int $templateId = null;
    public string $recordTitle = '';
    public string $recordType = 'ficha';
    public ?int $recordAppointmentId = null;
    public ?int $recordAppointmentServiceId = null;
    public ?int $recordServiceId = null;
    public string $recordContent = '';
    public array $recordData = [];

    public string $documentTitle = '';
    public ?int $documentAppointmentId = null;
    public ?int $documentAppointmentServiceId = null;
    public ?int $documentServiceId = null;
    public string $documentNotes = '';
    public ?TemporaryUploadedFile $documentFile = null;

    public string $prescriptionTitle = 'Receta';
    public ?int $prescriptionAppointmentId = null;
    public ?int $prescriptionAppointmentServiceId = null;
    public string $prescriptionIssuedAt = '';
    public string $prescriptionIndications = '';

    public ?int $editingTemplateId = null;
    public string $templateName = '';
    public string $templateCategory = 'ficha_inicial';
    public string $templateBody = '';
    public string $templateFieldsText = '';
    public bool $templateIsActive = true;
    public ?int $confirmingRecordDeleteId = null;
    public ?int $confirmingTemplateDeleteId = null;

    public string $specialtyName = '';
    public string $specialtyDescription = '';
    public ?int $specialtyUserId = null;
    public array $specialtyIds = [];

    public ?int $accessClientId = null;
    public ?int $accessUserId = null;
    public bool $accessCanView = true;
    public bool $accessCanCreate = true;
    public string $accessExpiresAt = '';
    public string $accessReason = '';

    public bool $showRecordModal = false;
    public bool $showDocumentModal = false;
    public bool $showPrescriptionModal = false;
    public bool $showTemplateModal = false;
    public bool $showSpecialtyModal = false;
    public bool $showAssignSpecialtyModal = false;
    public bool $showPatientAccessModal = false;

    public function mount(): void
    {
        $this->prescriptionIssuedAt = now()->format('Y-m-d');
        $requestedClientId = (int) request()->query('cliente', 0);

        if ($requestedClientId > 0) {
            $this->selectedClientId = $this->visibleClientsQuery()->whereKey($requestedClientId)->value('id');
            abort_unless($this->selectedClientId, 403);
            $this->lockedToClient = true;
        }

        $this->selectedClientId ??= $this->visibleClientsQuery()->oldest('full_name')->value('id');
        $this->accessClientId = $this->selectedClientId;
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['records', 'documents', 'prescriptions', 'templates', 'access'], true)) {
            return;
        }

        if (in_array($tab, ['templates', 'access'], true)) {
            $this->authorizeClinical($tab === 'access' ? 'manage_access' : 'edit');
        }

        $this->tab = $tab;
    }

    public function selectClient(int $clientId): void
    {
        if ($this->lockedToClient && $clientId !== $this->selectedClientId) {
            return;
        }

        $this->selectedClientId = $this->visibleClientsQuery()->whereKey($clientId)->value('id');
        $this->accessClientId = $this->selectedClientId;
        $this->recordAppointmentId = null;
        $this->recordAppointmentServiceId = null;
        $this->documentAppointmentId = null;
        $this->documentAppointmentServiceId = null;
        $this->prescriptionAppointmentId = null;
        $this->prescriptionAppointmentServiceId = null;
    }

    public function openRecordModal(): void
    {
        $this->authorizeClinical('create');
        abort_unless($this->canCreateForSelectedClient(), 403);

        $this->showRecordModal = true;
    }

    public function closeRecordModal(): void
    {
        $this->showRecordModal = false;
    }

    public function openDocumentModal(): void
    {
        $this->authorizeClinical('create');
        abort_unless($this->canCreateForSelectedClient(), 403);

        $this->showDocumentModal = true;
    }

    public function closeDocumentModal(): void
    {
        $this->showDocumentModal = false;
    }

    public function openPrescriptionModal(): void
    {
        $this->authorizeClinical('create');
        abort_unless($this->canCreateForSelectedClient(), 403);

        $this->showPrescriptionModal = true;
    }

    public function closePrescriptionModal(): void
    {
        $this->showPrescriptionModal = false;
    }

    public function openTemplateModal(): void
    {
        $this->authorizeClinical('create');
        $this->resetTemplateForm();
        $this->showTemplateModal = true;
    }

    public function closeTemplateModal(): void
    {
        $this->showTemplateModal = false;
        $this->resetTemplateForm();
    }

    public function openSpecialtyModal(): void
    {
        $this->authorizeClinical('manage_access');
        $this->showSpecialtyModal = true;
    }

    public function closeSpecialtyModal(): void
    {
        $this->showSpecialtyModal = false;
    }

    public function openAssignSpecialtyModal(): void
    {
        $this->authorizeClinical('manage_access');
        $this->showAssignSpecialtyModal = true;
    }

    public function closeAssignSpecialtyModal(): void
    {
        $this->showAssignSpecialtyModal = false;
    }

    public function openPatientAccessModal(): void
    {
        $this->authorizeClinical('manage_access');
        $this->accessClientId = $this->selectedClientId;
        $this->showPatientAccessModal = true;
    }

    public function closePatientAccessModal(): void
    {
        $this->showPatientAccessModal = false;
    }

    public function updatedTemplateId(): void
    {
        if (! $this->templateId) {
            return;
        }

        $template = $this->company()->clinicalTemplates()->whereKey($this->templateId)->first();

        if (! $template) {
            return;
        }

        $this->recordTitle = $this->recordTitle ?: $template->name;
        $this->recordType = $template->category ?: 'ficha';
        $this->recordContent = $this->recordContent ?: (string) $template->body;
        $this->recordData = collect($template->fields ?? [])
            ->mapWithKeys(fn (array|string $field) => [
                is_array($field) ? ($field['key'] ?? $field['label'] ?? '') : (string) $field => '',
            ])
            ->filter(fn ($value, string $key) => $key !== '')
            ->all();
    }

    public function saveRecord(): void
    {
        $this->authorizeClinical('create');
        $client = $this->selectedClientForWrite();
        $company = $this->company();

        $validated = $this->validate([
            'templateId' => ['nullable', Rule::exists('clinical_templates', 'id')->where('company_id', $company->id)],
            'recordTitle' => ['required', 'string', 'max:180'],
            'recordType' => ['required', 'string', 'max:60'],
            'recordAppointmentId' => ['nullable', Rule::exists('appointments', 'id')->where('company_id', $company->id)->where('client_id', $client->id)],
            'recordAppointmentServiceId' => ['nullable', 'integer'],
            'recordServiceId' => ['nullable', Rule::exists('services', 'id')->where('company_id', $company->id)],
            'recordContent' => ['nullable', 'string'],
            'recordData' => ['array'],
        ]);

        $this->validateAppointmentService($validated['recordAppointmentServiceId'] ?? null, $validated['recordAppointmentId'] ?? null);

        ClinicalRecord::query()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'appointment_id' => $validated['recordAppointmentId'] ?: null,
            'appointment_service_id' => $validated['recordAppointmentServiceId'] ?: null,
            'service_id' => $validated['recordServiceId'] ?: null,
            'clinical_template_id' => $validated['templateId'] ?: null,
            'created_by_user_id' => Auth::id(),
            'title' => $validated['recordTitle'],
            'type' => $validated['recordType'],
            'content' => $validated['recordContent'] ?: null,
            'data' => array_filter($validated['recordData'] ?? [], fn ($value) => trim((string) $value) !== ''),
        ]);

        $this->reset(['templateId', 'recordTitle', 'recordAppointmentId', 'recordAppointmentServiceId', 'recordServiceId', 'recordContent', 'recordData']);
        $this->recordType = 'ficha';
        $this->showRecordModal = false;
        $this->dispatch('clinical-record-saved');
    }

    public function saveDocument(): void
    {
        $this->authorizeClinical('create');
        $client = $this->selectedClientForWrite();
        $company = $this->company();

        $validated = $this->validate([
            'documentTitle' => ['required', 'string', 'max:180'],
            'documentAppointmentId' => ['nullable', Rule::exists('appointments', 'id')->where('company_id', $company->id)->where('client_id', $client->id)],
            'documentAppointmentServiceId' => ['nullable', 'integer'],
            'documentServiceId' => ['nullable', Rule::exists('services', 'id')->where('company_id', $company->id)],
            'documentNotes' => ['nullable', 'string', 'max:1000'],
            'documentFile' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx', 'max:10240'],
        ]);

        $this->validateAppointmentService($validated['documentAppointmentServiceId'] ?? null, $validated['documentAppointmentId'] ?? null);

        $path = $this->documentFile?->store("clinical-documents/{$company->id}/{$client->id}", 'public');

        ClinicalDocument::query()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'appointment_id' => $validated['documentAppointmentId'] ?: null,
            'appointment_service_id' => $validated['documentAppointmentServiceId'] ?: null,
            'service_id' => $validated['documentServiceId'] ?: null,
            'uploaded_by_user_id' => Auth::id(),
            'title' => $validated['documentTitle'],
            'file_path' => $path,
            'file_name' => $this->documentFile?->getClientOriginalName(),
            'mime_type' => $this->documentFile?->getMimeType(),
            'file_size' => $this->documentFile?->getSize(),
            'notes' => $validated['documentNotes'] ?: null,
        ]);

        $this->reset(['documentTitle', 'documentAppointmentId', 'documentAppointmentServiceId', 'documentServiceId', 'documentNotes', 'documentFile']);
        $this->showDocumentModal = false;
        $this->dispatch('clinical-document-saved');
    }

    public function savePrescription(): void
    {
        $this->authorizeClinical('create');
        $client = $this->selectedClientForWrite();
        $company = $this->company();

        $validated = $this->validate([
            'prescriptionTitle' => ['required', 'string', 'max:180'],
            'prescriptionAppointmentId' => ['nullable', Rule::exists('appointments', 'id')->where('company_id', $company->id)->where('client_id', $client->id)],
            'prescriptionAppointmentServiceId' => ['nullable', 'integer'],
            'prescriptionIssuedAt' => ['required', 'date'],
            'prescriptionIndications' => ['required', 'string'],
        ]);

        $this->validateAppointmentService($validated['prescriptionAppointmentServiceId'] ?? null, $validated['prescriptionAppointmentId'] ?? null);

        ClinicalPrescription::query()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'appointment_id' => $validated['prescriptionAppointmentId'] ?: null,
            'appointment_service_id' => $validated['prescriptionAppointmentServiceId'] ?: null,
            'issued_by_user_id' => Auth::id(),
            'title' => $validated['prescriptionTitle'],
            'indications' => $validated['prescriptionIndications'],
            'issued_at' => $validated['prescriptionIssuedAt'],
        ]);

        $this->reset(['prescriptionAppointmentId', 'prescriptionAppointmentServiceId', 'prescriptionIndications']);
        $this->prescriptionTitle = 'Receta';
        $this->prescriptionIssuedAt = now()->format('Y-m-d');
        $this->showPrescriptionModal = false;
        $this->dispatch('clinical-prescription-saved');
    }

    public function saveTemplate(): void
    {
        $this->authorizeClinical($this->editingTemplateId ? 'edit' : 'create');
        $company = $this->company();

        $validated = $this->validate([
            'templateName' => ['required', 'string', 'max:160'],
            'templateCategory' => ['required', 'string', 'max:60'],
            'templateBody' => ['nullable', 'string'],
            'templateFieldsText' => ['nullable', 'string'],
            'templateIsActive' => ['boolean'],
        ]);

        $fields = collect(preg_split('/\r\n|\r|\n/', $validated['templateFieldsText'] ?: ''))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->map(fn (string $line) => ['label' => $line, 'key' => str($line)->slug('_')->toString()])
            ->values()
            ->all();

        $template = $this->editingTemplateId
            ? $company->clinicalTemplates()->whereKey($this->editingTemplateId)->firstOrFail()
            : new ClinicalTemplate(['company_id' => $company->id]);

        $template->fill([
            'name' => $validated['templateName'],
            'category' => $validated['templateCategory'],
            'body' => $validated['templateBody'] ?: null,
            'fields' => $fields,
            'is_active' => $validated['templateIsActive'],
        ])->save();

        $this->resetTemplateForm();
        $this->showTemplateModal = false;
        $this->dispatch('clinical-template-saved');
    }

    public function editTemplate(int $templateId): void
    {
        $this->authorizeClinical('edit');
        $template = $this->company()->clinicalTemplates()->whereKey($templateId)->firstOrFail();

        $this->editingTemplateId = $template->id;
        $this->templateName = $template->name;
        $this->templateCategory = $template->category;
        $this->templateBody = $template->body ?? '';
        $this->templateFieldsText = collect($template->fields ?? [])->map(fn ($field) => $field['label'] ?? '')->filter()->implode(PHP_EOL);
        $this->templateIsActive = $template->is_active;
        $this->tab = 'templates';
        $this->showTemplateModal = true;
    }

    public function deleteTemplate(int $templateId): void
    {
        $this->authorizeClinical('delete');
        $this->confirmingTemplateDeleteId = $templateId;
    }

    public function deleteTemplateConfirmed(): void
    {
        $this->authorizeClinical('delete');
        $template = $this->company()->clinicalTemplates()->whereKey($this->confirmingTemplateDeleteId)->firstOrFail();

        if ($template->records()->exists()) {
            $template->update(['is_active' => false]);
        } else {
            $template->delete();
        }

        $this->confirmingTemplateDeleteId = null;
    }

    public function confirmDeleteRecord(int $recordId): void
    {
        $this->authorizeClinical('delete');
        $this->confirmingRecordDeleteId = $recordId;
    }

    public function deleteRecordConfirmed(): void
    {
        $this->authorizeClinical('delete');
        $record = $this->company()->clinicalRecords()
            ->whereKey($this->confirmingRecordDeleteId)
            ->where('client_id', $this->selectedClientId)
            ->firstOrFail();

        $record->delete();
        $this->confirmingRecordDeleteId = null;
    }

    public function cancelClinicalDelete(): void
    {
        $this->confirmingRecordDeleteId = null;
        $this->confirmingTemplateDeleteId = null;
    }

    public function resetTemplateForm(): void
    {
        $this->reset(['editingTemplateId', 'templateName', 'templateBody', 'templateFieldsText']);
        $this->templateCategory = 'ficha_inicial';
        $this->templateIsActive = true;
        $this->resetErrorBag();
    }

    public function saveSpecialty(): void
    {
        $this->authorizeClinical('manage_access');
        $company = $this->company();

        $validated = $this->validate([
            'specialtyName' => ['required', 'string', 'max:120', Rule::unique('clinical_specialties', 'name')->where('company_id', $company->id)],
            'specialtyDescription' => ['nullable', 'string', 'max:500'],
        ]);

        $company->clinicalSpecialties()->create([
            'name' => $validated['specialtyName'],
            'description' => $validated['specialtyDescription'] ?: null,
        ]);

        $this->reset(['specialtyName', 'specialtyDescription']);
        $this->showSpecialtyModal = false;
    }

    public function assignSpecialties(): void
    {
        $this->authorizeClinical('manage_access');
        $company = $this->company();

        $validated = $this->validate([
            'specialtyUserId' => ['required', Rule::exists('users', 'id')],
            'specialtyIds' => ['array'],
            'specialtyIds.*' => [Rule::exists('clinical_specialties', 'id')->where('company_id', $company->id)],
        ]);

        $user = $company->users()->whereKey($validated['specialtyUserId'])->firstOrFail();
        $user->clinicalSpecialties()->sync($validated['specialtyIds']);

        $this->reset(['specialtyUserId', 'specialtyIds']);
        $this->showAssignSpecialtyModal = false;
    }

    public function grantPatientAccess(): void
    {
        $this->authorizeClinical('manage_access');
        $company = $this->company();

        $validated = $this->validate([
            'accessClientId' => ['required', Rule::exists('clients', 'id')->where('company_id', $company->id)],
            'accessUserId' => ['required', Rule::exists('users', 'id')],
            'accessCanView' => ['boolean'],
            'accessCanCreate' => ['boolean'],
            'accessExpiresAt' => ['nullable', 'date'],
            'accessReason' => ['nullable', 'string', 'max:500'],
        ]);

        $company->users()->whereKey($validated['accessUserId'])->firstOrFail();

        ClinicalPatientAccess::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'client_id' => $validated['accessClientId'],
                'user_id' => $validated['accessUserId'],
            ],
            [
                'granted_by_user_id' => Auth::id(),
                'can_view' => $validated['accessCanView'],
                'can_create' => $validated['accessCanCreate'],
                'expires_at' => $validated['accessExpiresAt'] ?: null,
                'reason' => $validated['accessReason'] ?: null,
            ],
        );

        $this->reset(['accessUserId', 'accessExpiresAt', 'accessReason']);
        $this->accessCanView = true;
        $this->accessCanCreate = true;
        $this->showPatientAccessModal = false;
    }

    public function revokePatientAccess(int $accessId): void
    {
        $this->authorizeClinical('manage_access');
        $this->company()->clinicalPatientAccesses()->whereKey($accessId)->delete();
    }

    public function render()
    {
        $company = $this->company();
        $selectedClient = $this->selectedClientId
            ? $this->visibleClientsQuery()->with('phones')->whereKey($this->selectedClientId)->first()
            : null;

        if (! $selectedClient) {
            $this->selectedClientId = $this->visibleClientsQuery()->oldest('full_name')->value('id');
            $selectedClient = $this->selectedClientId
                ? $this->visibleClientsQuery()->with('phones')->whereKey($this->selectedClientId)->first()
                : null;
        }

        return view('livewire.clinic.clinical-history-manager', [
            'company' => $company,
            'clients' => $this->visibleClientsQuery()
                ->with('phones')
                ->when(trim($this->clientSearch) !== '', function (Builder $query) {
                    $search = '%' . trim($this->clientSearch) . '%';
                    $query->where(fn (Builder $nested) => $nested
                        ->where('full_name', 'like', $search)
                        ->orWhere('identity_number', 'like', $search)
                        ->orWhere('phone', 'like', $search)
                        ->orWhereHas('phones', fn (Builder $phoneQuery) => $phoneQuery->where('phone', 'like', $search)));
                })
                ->orderBy('full_name')
                ->limit(40)
                ->get(),
            'selectedClient' => $selectedClient,
            'templates' => $company->clinicalTemplates()->orderByDesc('is_active')->orderBy('name')->get(),
            'activeTemplates' => $company->clinicalTemplates()->where('is_active', true)->orderBy('name')->get(),
            'records' => $selectedClient
                ? $selectedClient->clinicalRecords()->with(['template', 'createdBy', 'appointmentService'])->latest()->get()
                : collect(),
            'documents' => $selectedClient
                ? $selectedClient->clinicalDocuments()->with(['uploadedBy', 'appointmentService'])->latest()->get()
                : collect(),
            'prescriptions' => $selectedClient
                ? $selectedClient->clinicalPrescriptions()->with(['issuedBy', 'appointmentService'])->latest('issued_at')->get()
                : collect(),
            'appointments' => $selectedClient
                ? $selectedClient->appointments()->with('services')->latest('scheduled_at')->limit(60)->get()
                : collect(),
            'services' => $company->services()->where('status', 'active')->orderBy('name')->get(),
            'staff' => $company->users()->with('clinicalSpecialties')->orderBy('name')->get(),
            'specialties' => $company->clinicalSpecialties()->with('users')->orderBy('name')->get(),
            'accesses' => $company->clinicalPatientAccesses()->with(['client', 'user', 'grantedBy'])->latest()->get(),
            'canCreateClinical' => $this->canClinical('create') && $this->canCreateForSelectedClient(),
            'canEditClinical' => $this->canClinical('edit'),
            'canDeleteClinical' => $this->canClinical('delete'),
            'canManageAccess' => $this->canClinical('manage_access'),
            'canViewFullHistory' => $this->canClinical('view_full'),
        ]);
    }

    private function company(): Company
    {
        return Auth::user()->companies()->firstOrFail();
    }

    private function authorizeClinical(string $action = 'view'): void
    {
        abort_unless($this->canClinical($action), 403);
    }

    private function canClinical(string $action = 'view'): bool
    {
        return RumikaAccess::can(Auth::user(), 'historia_clinica', $action, company: $this->company());
    }

    private function selectedClientForWrite(): Client
    {
        $client = $this->selectedClientId
            ? $this->visibleClientsQuery()->whereKey($this->selectedClientId)->first()
            : null;

        abort_unless($client && $this->canCreateForSelectedClient(), 403);

        return $client;
    }

    private function canCreateForSelectedClient(): bool
    {
        if (! $this->selectedClientId) {
            return false;
        }

        if ($this->canClinical('view_full') || $this->canClinical('manage_access')) {
            return true;
        }

        return $this->company()->clinicalPatientAccesses()
            ->where('client_id', $this->selectedClientId)
            ->where('user_id', Auth::id())
            ->where('can_create', true)
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists()
            || $this->company()->appointments()
                ->where('client_id', $this->selectedClientId)
                ->where(function (Builder $query) {
                    $query->where('attended_by_user_id', Auth::id())
                        ->orWhereHas('services', fn (Builder $serviceQuery) => $serviceQuery->where('performed_by_user_id', Auth::id()));
                })
                ->exists();
    }

    private function visibleClientsQuery()
    {
        $company = $this->company();

        $query = $company->clients()->where('status', 'active');

        if ($this->canClinical('view_full') || $this->canClinical('manage_access')) {
            return $query;
        }

        $userId = Auth::id();

        return $query->where(function (Builder $nested) use ($userId) {
            $nested
                ->whereHas('clinicalAccesses', fn (Builder $accessQuery) => $accessQuery
                    ->where('user_id', $userId)
                    ->where('can_view', true)
                    ->where(fn (Builder $dateQuery) => $dateQuery->whereNull('expires_at')->orWhere('expires_at', '>', now())))
                ->orWhereHas('appointments', fn (Builder $appointmentQuery) => $appointmentQuery
                    ->where('attended_by_user_id', $userId)
                    ->orWhereHas('services', fn (Builder $serviceQuery) => $serviceQuery->where('performed_by_user_id', $userId)));
        });
    }

    private function validateAppointmentService(?int $appointmentServiceId, ?int $appointmentId): void
    {
        if (! $appointmentServiceId) {
            return;
        }

        $exists = AppointmentService::query()
            ->whereKey($appointmentServiceId)
            ->when($appointmentId, fn (Builder $query) => $query->where('appointment_id', $appointmentId))
            ->whereHas('appointment', fn (Builder $query) => $query->where('company_id', $this->company()->id))
            ->exists();

        if (! $exists) {
            $this->addError('recordAppointmentServiceId', 'El servicio seleccionado no pertenece a esta cita.');
            abort(422);
        }
    }
}
