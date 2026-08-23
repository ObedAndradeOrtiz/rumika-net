<?php

namespace App\Livewire\Crm;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\ClientPhone;
use App\Models\CrmConversation;
use App\Models\CrmMessage;
use App\Models\Service;
use App\Models\WhatsappChannel;
use App\Services\WhatsappMessageSender;
use App\Support\CompanyPlanLimits;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CrmManager extends Component
{
    public string $tab = 'inbox';
    public string $search = '';
    public ?int $selectedConversationId = null;
    public string $replyText = '';

    public bool $showChannelModal = false;
    public ?int $editingChannelId = null;
    public array $channelForm = [
        'branch_id' => '',
        'name' => '',
        'phone_number' => '',
        'phone_number_id' => '',
        'waba_id' => '',
        'api_version' => 'v23.0',
        'access_token' => '',
        'verify_token' => '',
        'audio_converter_api_key' => '',
        'is_active' => true,
    ];

    public bool $showAppointmentModal = false;
    public array $appointmentForm = [
        'branch_id' => '',
        'service_ids' => [],
        'scheduled_date' => '',
        'scheduled_time' => '',
        'attended_by_user_id' => '',
        'notes' => '',
    ];

    public function mount(): void
    {
        $this->appointmentForm['branch_id'] = (string) (session('active_branch_id') ?: $this->branches()->first()?->id);
        $this->appointmentForm['scheduled_date'] = now()->format('Y-m-d');
        $this->appointmentForm['scheduled_time'] = now()->addHour()->format('H:00');
    }

    public function selectConversation(int $conversationId): void
    {
        $conversation = $this->company()->crmConversations()
            ->whereKey($conversationId)
            ->firstOrFail();

        $conversation->messages()->where('direction', 'in')->update(['is_read' => true]);
        $conversation->update(['unread_count' => 0]);

        $this->selectedConversationId = $conversation->id;
        $this->replyText = '';
        $this->tab = 'inbox';
    }

    public function sendReply(WhatsappMessageSender $sender): void
    {
        $conversation = $this->selectedConversation();

        if (! $conversation) {
            return;
        }

        $validated = $this->validate([
            'replyText' => ['required', 'string', 'max:3500'],
        ]);

        if (
            $conversation->last_customer_message_at
            && $conversation->last_customer_message_at->lt(now()->subHours(24))
        ) {
            $this->addError('replyText', 'Pasaron mas de 24 horas. Para responder se necesitara una plantilla aprobada.');

            return;
        }

        $message = CrmMessage::query()->create([
            'company_id' => $conversation->company_id,
            'crm_conversation_id' => $conversation->id,
            'whatsapp_channel_id' => $conversation->whatsapp_channel_id,
            'crm_contact_id' => $conversation->crm_contact_id,
            'wamid' => 'local-out-' . Str::uuid(),
            'direction' => 'out',
            'type' => 'text',
            'body' => $validated['replyText'],
            'status' => 'queued',
            'message_at' => now(),
            'is_read' => true,
        ]);

        $sent = $sender->sendText($message);

        $conversation->update([
            'last_message' => Str::limit($validated['replyText'], 180),
            'last_message_at' => now(),
        ]);

        $this->replyText = '';
        session()->flash($sent ? 'crm_success' : 'crm_warning', $sent ? 'Mensaje enviado.' : 'No se pudo enviar. Revisa el token o el numero.');
    }

    public function openChannelModal(?int $channelId = null): void
    {
        $this->resetValidation();
        $this->editingChannelId = $channelId;

        if ($channelId) {
            $channel = $this->company()->whatsappChannels()->whereKey($channelId)->firstOrFail();
            $this->channelForm = [
                'branch_id' => (string) ($channel->branch_id ?? ''),
                'name' => $channel->name,
                'phone_number' => $channel->phone_number ?: '',
                'phone_number_id' => $channel->phone_number_id,
                'waba_id' => $channel->waba_id ?: '',
                'api_version' => $channel->api_version ?: 'v23.0',
                'access_token' => '',
                'verify_token' => '',
                'audio_converter_api_key' => '',
                'is_active' => $channel->is_active,
            ];
        } else {
            $this->channelForm = [
                'branch_id' => (string) (session('active_branch_id') ?: ''),
                'name' => '',
                'phone_number' => '',
                'phone_number_id' => '',
                'waba_id' => '',
                'api_version' => 'v23.0',
                'access_token' => '',
                'verify_token' => Str::slug($this->company()->slug ?: $this->company()->name) . '_crm_verify',
                'audio_converter_api_key' => '',
                'is_active' => true,
            ];
        }

        $this->showChannelModal = true;
        $this->tab = 'channels';
    }

    public function saveChannel(): void
    {
        $company = $this->company();

        $validated = $this->validate([
            'channelForm.branch_id' => ['nullable', Rule::exists('branches', 'id')->where('company_id', $company->id)],
            'channelForm.name' => ['required', 'string', 'max:120'],
            'channelForm.phone_number' => ['nullable', 'string', 'max:40'],
            'channelForm.phone_number_id' => [
                'required',
                'string',
                'max:80',
                Rule::unique('whatsapp_channels', 'phone_number_id')->ignore($this->editingChannelId),
            ],
            'channelForm.waba_id' => ['nullable', 'string', 'max:80'],
            'channelForm.api_version' => ['required', 'string', 'max:20'],
            'channelForm.access_token' => [$this->editingChannelId ? 'nullable' : 'required', 'string'],
            'channelForm.verify_token' => ['nullable', 'string', 'max:180'],
            'channelForm.audio_converter_api_key' => ['nullable', 'string', 'max:180'],
            'channelForm.is_active' => ['boolean'],
        ])['channelForm'];

        $payload = [
            'company_id' => $company->id,
            'branch_id' => $validated['branch_id'] ?: null,
            'name' => $validated['name'],
            'phone_number' => $validated['phone_number'] ?: null,
            'phone_number_id' => $validated['phone_number_id'],
            'waba_id' => $validated['waba_id'] ?: null,
            'api_version' => $validated['api_version'],
            'verify_token' => $validated['verify_token'] ?: null,
            'audio_converter_api_key' => $validated['audio_converter_api_key'] ?: null,
            'is_active' => (bool) $validated['is_active'],
        ];

        if (filled($validated['access_token'])) {
            $payload['access_token'] = $validated['access_token'];
        }

        $this->editingChannelId
            ? $company->whatsappChannels()->whereKey($this->editingChannelId)->firstOrFail()->update($payload)
            : WhatsappChannel::query()->create($payload);

        $this->showChannelModal = false;
        session()->flash('crm_success', 'Canal guardado.');
    }

    public function toggleChannel(int $channelId): void
    {
        $channel = $this->company()->whatsappChannels()->whereKey($channelId)->firstOrFail();
        $channel->update(['is_active' => ! $channel->is_active]);
    }

    public function openAppointmentModal(): void
    {
        if (! $this->selectedConversation()) {
            return;
        }

        $this->resetValidation();
        $this->appointmentForm['branch_id'] = (string) (session('active_branch_id') ?: $this->branches()->first()?->id);
        $this->appointmentForm['service_ids'] = [];
        $this->appointmentForm['scheduled_date'] = now()->format('Y-m-d');
        $this->appointmentForm['scheduled_time'] = now()->addHour()->format('H:00');
        $this->appointmentForm['attended_by_user_id'] = '';
        $this->appointmentForm['notes'] = '';
        $this->showAppointmentModal = true;
    }

    public function saveAppointmentFromCrm(): void
    {
        $company = $this->company();
        $conversation = $this->selectedConversation();

        if (! $conversation) {
            return;
        }

        $validated = $this->validate([
            'appointmentForm.branch_id' => ['required', Rule::exists('branches', 'id')->where('company_id', $company->id)],
            'appointmentForm.service_ids' => ['required', 'array', 'min:1'],
            'appointmentForm.service_ids.*' => ['integer', Rule::exists('services', 'id')->where('company_id', $company->id)],
            'appointmentForm.scheduled_date' => ['required', 'date'],
            'appointmentForm.scheduled_time' => ['required', 'date_format:H:i'],
            'appointmentForm.attended_by_user_id' => ['nullable', Rule::exists('users', 'id')],
            'appointmentForm.notes' => ['nullable', 'string', 'max:1000'],
        ])['appointmentForm'];

        $contact = $conversation->contact;
        $client = $contact->client;

        if (! $client) {
            CompanyPlanLimits::assertCanCreate($company, 'clients', 'clientes');

            $client = Client::query()->create([
                'company_id' => $company->id,
                'branch_id' => null,
                'full_name' => $contact->name ?: 'Cliente WhatsApp ' . $contact->phone,
                'phone' => $contact->phone,
                'email' => $contact->email,
                'status' => 'active',
            ]);

            ClientPhone::query()->create([
                'client_id' => $client->id,
                'phone' => $contact->phone,
                'label' => 'WhatsApp',
                'is_primary' => true,
            ]);

            $contact->update(['client_id' => $client->id]);
        }

        CompanyPlanLimits::assertCanCreate($company, 'appointments_per_month', 'citas del mes');

        $scheduledAt = Carbon::parse($validated['scheduled_date'] . ' ' . $validated['scheduled_time']);
        $services = Service::query()
            ->where('company_id', $company->id)
            ->whereIn('id', $validated['service_ids'])
            ->get();

        $appointment = Appointment::query()->create([
            'company_id' => $company->id,
            'branch_id' => $validated['branch_id'],
            'client_id' => $client->id,
            'attended_by_user_id' => $validated['attended_by_user_id'] ?: null,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => max(30, (int) $services->sum('duration_minutes')),
            'status' => 'scheduled',
            'attended' => false,
            'locked_by_payment' => false,
            'clinical_notes' => trim('Creada desde CRM WhatsApp. ' . ($validated['notes'] ?? '')),
        ]);

        foreach ($services as $service) {
            $appointment->services()->create([
                'service_id' => $service->id,
                'performed_by_user_id' => $validated['attended_by_user_id'] ?: null,
                'name' => $service->name,
                'price' => $service->price,
                'duration_minutes' => $service->duration_minutes,
                'status' => 'pending',
            ]);
        }

        $conversation->update(['client_id' => $client->id]);
        $this->showAppointmentModal = false;
        session()->flash('crm_success', 'Cita creada desde WhatsApp.');
    }

    public function selectedConversation(): ?CrmConversation
    {
        if (! $this->selectedConversationId) {
            return null;
        }

        return $this->company()->crmConversations()
            ->with(['contact', 'channel', 'client', 'messages'])
            ->whereKey($this->selectedConversationId)
            ->first();
    }

    public function render()
    {
        $company = $this->company();
        $conversations = $company->crmConversations()
            ->with(['contact', 'channel'])
            ->when($this->search, function ($query) {
                $query->whereHas('contact', function ($contactQuery) {
                    $contactQuery->where('name', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%");
                });
            })
            ->latest('last_message_at')
            ->latest()
            ->limit(50)
            ->get();

        if (! $this->selectedConversationId && $conversations->isNotEmpty()) {
            $this->selectedConversationId = $conversations->first()->id;
        }

        return view('livewire.crm.crm-manager', [
            'company' => $company,
            'channels' => $company->whatsappChannels()->with('branch')->latest()->get(),
            'branches' => $this->branches(),
            'conversations' => $conversations,
            'selectedConversation' => $this->selectedConversation(),
            'services' => $this->services(),
            'staffUsers' => $this->staffUsers(),
            'webhookUrl' => url('/webhook/whatsapp'),
        ]);
    }

    private function company()
    {
        return Auth::user()->companies()->firstOrFail();
    }

    private function branches()
    {
        return Auth::user()
            ->branches()
            ->where('branches.company_id', $this->company()->id)
            ->orderBy('branches.name')
            ->get();
    }

    private function services()
    {
        return Service::query()
            ->where('company_id', $this->company()->id)
            ->where('status', 'active')
            ->when($this->appointmentForm['branch_id'] ?? null, function ($query, $branchId) {
                $query->where(function ($branchQuery) use ($branchId) {
                    $branchQuery->whereNull('branch_id')->orWhere('branch_id', $branchId);
                });
            })
            ->orderBy('name')
            ->limit(80)
            ->get();
    }

    private function staffUsers()
    {
        $branchId = $this->appointmentForm['branch_id'] ?: session('active_branch_id');

        return Auth::user()
            ->companies()
            ->firstOrFail()
            ->users()
            ->when($branchId, function ($query) use ($branchId) {
                $query->whereHas('branches', fn ($branchQuery) => $branchQuery->where('branches.id', $branchId));
            })
            ->orderBy('name')
            ->get();
    }
}
