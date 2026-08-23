<?php

namespace App\Livewire\Crm;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\ClientPhone;
use App\Models\CrmConversation;
use App\Models\CrmContact;
use App\Models\CrmMessage;
use App\Models\CrmQuickReply;
use App\Models\Service;
use App\Models\WhatsappChannel;
use App\Models\WhatsappTemplate;
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
    public bool $mobileListMode = true;
    public string $replyText = '';
    public string $quickReplyTitle = '';
    public string $quickReplyBody = '';
    public string $templateName = '';
    public string $templateCategory = 'utility';
    public string $templateLanguage = 'es';
    public string $templateBody = '';

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

        if ($conversationId = request()->integer('conversation')) {
            $this->selectConversation($conversationId);
        }
    }

    public function selectConversation(int $conversationId): void
    {
        $conversation = $this->company()->crmConversations()
            ->whereIn('whatsapp_channel_id', $this->accessibleChannelIds())
            ->whereKey($conversationId)
            ->firstOrFail();

        $conversation->messages()->where('direction', 'in')->update(['is_read' => true]);
        $conversation->update(['unread_count' => 0]);

        $this->selectedConversationId = $conversation->id;
        $this->mobileListMode = false;
        $this->replyText = '';
        $this->tab = 'inbox';
        $this->dispatch('crm-scroll-bottom');
    }

    public function showConversationList(): void
    {
        $this->mobileListMode = true;
    }

    public function useQuickReply(int $replyId): void
    {
        $reply = $this->company()->crmQuickReplies()
            ->where('is_active', true)
            ->whereKey($replyId)
            ->firstOrFail();

        $this->replyText = $reply->body;
        $this->dispatch('crm-scroll-bottom');
    }

    public function saveQuickReply(): void
    {
        $validated = $this->validate([
            'quickReplyTitle' => ['required', 'string', 'max:80'],
            'quickReplyBody' => ['required', 'string', 'max:1000'],
        ]);

        CrmQuickReply::query()->create([
            'company_id' => $this->company()->id,
            'title' => $validated['quickReplyTitle'],
            'body' => $validated['quickReplyBody'],
            'is_active' => true,
        ]);

        $this->quickReplyTitle = '';
        $this->quickReplyBody = '';
        session()->flash('crm_success', 'Mensaje predeterminado guardado.');
    }

    public function deleteQuickReply(int $replyId): void
    {
        $this->company()->crmQuickReplies()->whereKey($replyId)->delete();
    }

    public function saveTemplate(): void
    {
        $validated = $this->validate([
            'templateName' => ['required', 'string', 'max:120'],
            'templateCategory' => ['required', 'string', 'max:40'],
            'templateLanguage' => ['required', 'string', 'max:12'],
            'templateBody' => ['required', 'string', 'max:1200'],
        ]);

        WhatsappTemplate::query()->updateOrCreate(
            [
                'company_id' => $this->company()->id,
                'name' => Str::slug($validated['templateName'], '_'),
                'language' => $validated['templateLanguage'],
            ],
            [
                'category' => $validated['templateCategory'],
                'body' => $validated['templateBody'],
                'status' => 'draft',
            ],
        );

        $this->templateName = '';
        $this->templateCategory = 'utility';
        $this->templateLanguage = 'es';
        $this->templateBody = '';
        session()->flash('crm_success', 'Plantilla guardada como borrador.');
    }

    public function deleteTemplate(int $templateId): void
    {
        $this->company()->whatsappTemplates()->whereKey($templateId)->delete();
    }

    public function createDemoConversation(): void
    {
        $company = $this->company();
        $channel = $company->whatsappChannels()
            ->where('phone_number_id', 'rumika-demo-' . $company->id)
            ->first();

        if (! $channel) {
            $channel = WhatsappChannel::query()->create([
                'company_id' => $company->id,
                'branch_id' => null,
                'name' => 'Demo WhatsApp',
                'phone_number' => '59170000000',
                'phone_number_id' => 'rumika-demo-' . $company->id,
                'waba_id' => 'demo',
                'api_version' => 'v23.0',
                'access_token' => 'demo-token',
                'verify_token' => $this->makeVerifyToken(),
                'audio_converter_api_key' => null,
                'is_active' => false,
            ]);
        }

        $contact = CrmContact::query()->updateOrCreate(
            ['company_id' => $company->id, 'phone' => '59170000000'],
            ['name' => 'Cliente demo', 'last_interaction_at' => now()],
        );

        $conversation = CrmConversation::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'whatsapp_channel_id' => $channel->id,
                'crm_contact_id' => $contact->id,
            ],
            [
                'client_id' => null,
                'status' => 'open',
                'is_demo' => true,
                'last_message' => 'Hola, quiero agendar una cita.',
                'last_message_at' => now(),
                'last_customer_message_at' => now(),
            ],
        );

        $conversation->update(['is_demo' => true, 'last_message_at' => now()]);

        if ($conversation->messages()->doesntExist()) {
            $conversation->messages()->createMany([
                [
                    'company_id' => $company->id,
                    'whatsapp_channel_id' => $channel->id,
                    'crm_contact_id' => $contact->id,
                    'wamid' => 'demo-in-' . Str::uuid(),
                    'direction' => 'in',
                    'type' => 'text',
                    'body' => 'Hola, quiero agendar una cita para esta semana.',
                    'status' => 'received',
                    'message_at' => now()->subMinutes(8),
                    'is_read' => true,
                ],
                [
                    'company_id' => $company->id,
                    'whatsapp_channel_id' => $channel->id,
                    'crm_contact_id' => $contact->id,
                    'wamid' => 'demo-out-' . Str::uuid(),
                    'direction' => 'out',
                    'type' => 'text',
                    'body' => 'Claro, podemos ayudarte. Te comparto los horarios disponibles.',
                    'status' => 'sent',
                    'message_at' => now()->subMinutes(6),
                    'is_read' => true,
                ],
            ]);
        }

        $this->selectConversation($conversation->id);
        session()->flash('crm_success', 'Chat demo creado.');
    }

    public function deleteConversation(int $conversationId): void
    {
        $conversation = $this->company()->crmConversations()
            ->whereIn('whatsapp_channel_id', $this->accessibleChannelIds())
            ->whereKey($conversationId)
            ->firstOrFail();

        abort_unless($conversation->is_demo || $this->isCompanyAdmin(), 403);

        $conversation->delete();

        if ($this->selectedConversationId === $conversationId) {
            $this->selectedConversationId = null;
            $this->mobileListMode = true;
        }

        session()->flash('crm_success', 'Chat eliminado.');
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
        $this->dispatch('crm-scroll-bottom');
        session()->flash($sent ? 'crm_success' : 'crm_warning', $sent ? 'Mensaje enviado.' : 'No se pudo enviar. Revisa el token o el numero.');
    }

    public function openChannelModal(?int $channelId = null): void
    {
        $this->resetValidation();
        $this->editingChannelId = $channelId;

        if ($channelId) {
            $channel = $this->company()->whatsappChannels()
                ->whereIn('id', $this->accessibleChannelIds())
                ->whereKey($channelId)
                ->firstOrFail();
            $this->channelForm = [
                'branch_id' => (string) ($channel->branch_id ?? ''),
                'name' => $channel->name,
                'phone_number' => $channel->phone_number ?: '',
                'phone_number_id' => $channel->phone_number_id,
                'waba_id' => $channel->waba_id ?: '',
                'api_version' => $channel->api_version ?: 'v23.0',
                'access_token' => '',
                'verify_token' => $channel->verify_token ?: $this->makeVerifyToken(),
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
                'verify_token' => $this->makeVerifyToken(),
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

        $existingChannel = $this->editingChannelId
            ? $company->whatsappChannels()->whereKey($this->editingChannelId)->firstOrFail()
            : null;

        $payload = [
            'company_id' => $company->id,
            'branch_id' => $validated['branch_id'] ?: null,
            'name' => $validated['name'],
            'phone_number' => $validated['phone_number'] ?: null,
            'phone_number_id' => $validated['phone_number_id'],
            'waba_id' => $validated['waba_id'] ?: null,
            'api_version' => $validated['api_version'],
            'verify_token' => $existingChannel?->verify_token ?: ($validated['verify_token'] ?: $this->makeVerifyToken()),
            'audio_converter_api_key' => $validated['audio_converter_api_key'] ?: null,
            'is_active' => (bool) $validated['is_active'],
        ];

        if (filled($validated['access_token'])) {
            $payload['access_token'] = $validated['access_token'];
        }

        $this->editingChannelId
            ? $existingChannel->update($payload)
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
            ->whereIn('whatsapp_channel_id', $this->accessibleChannelIds())
            ->whereKey($this->selectedConversationId)
            ->first();
    }

    public function render()
    {
        $company = $this->company();
        $conversations = $company->crmConversations()
            ->with(['contact', 'channel'])
            ->whereIn('whatsapp_channel_id', $this->accessibleChannelIds())
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
            'channels' => $company->whatsappChannels()
                ->with('branch')
                ->whereIn('id', $this->accessibleChannelIds())
                ->latest()
                ->get(),
            'branches' => $this->branches(),
            'conversations' => $conversations,
            'selectedConversation' => $this->selectedConversation(),
            'services' => $this->services(),
            'staffUsers' => $this->staffUsers(),
            'webhookUrl' => url('/webhook/whatsapp'),
            'quickReplies' => $company->crmQuickReplies()->where('is_active', true)->latest()->get(),
            'templates' => $company->whatsappTemplates()->with('channel')->latest()->get(),
            'canManageCrm' => $this->isCompanyAdmin(),
        ]);
    }

    private function company()
    {
        return Auth::user()->companies()->firstOrFail();
    }

    private function accessibleChannelIds(): array
    {
        $user = Auth::user();
        $company = $this->company();

        if ($this->isCompanyAdmin()) {
            return $company->whatsappChannels()->pluck('id')->all();
        }

        return $user->whatsappChannels()
            ->where('whatsapp_channels.company_id', $company->id)
            ->where('whatsapp_channels.is_active', true)
            ->pluck('whatsapp_channels.id')
            ->all();
    }

    private function isCompanyAdmin(): bool
    {
        $user = Auth::user();
        $company = $this->company();
        $role = $user->companies()
            ->where('companies.id', $company->id)
            ->value('company_user.role');

        if (in_array($role, ['owner', 'super_admin', 'super-administrador', 'admin', 'administrator', 'administrador'], true)) {
            return true;
        }

        return $user->branches()
            ->where('branches.company_id', $company->id)
            ->leftJoin('roles', 'roles.id', '=', 'branch_user.role_id')
            ->whereIn('roles.slug', ['owner', 'super_admin', 'super-administrador', 'admin', 'administrator', 'administrador'])
            ->exists();
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

    private function makeVerifyToken(): string
    {
        $company = $this->company();
        $slug = Str::slug($company->slug ?: $company->name ?: 'empresa');

        return 'rumika_' . $company->id . '_' . $slug . '_verify_' . Str::lower(Str::random(10));
    }
}
