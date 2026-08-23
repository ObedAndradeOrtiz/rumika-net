<main class="rm-content rm-crm-page">
    <section class="rm-settings-hero rm-crm-hero">
        <div>
            <span>Mensajeria</span>
            <h1>Centro de mensajes</h1>
            <p>Recibe mensajes, responde desde la bandeja y agenda clientes directo al calendario.</p>
        </div>
        <button class="rm-button" type="button" wire:click="openChannelModal">
            Nuevo canal
        </button>
    </section>

    @if (session('crm_success'))
        <div class="rm-alert rm-alert-success">{{ session('crm_success') }}</div>
    @endif
    @if (session('crm_warning'))
        <div class="rm-alert rm-alert-warning">{{ session('crm_warning') }}</div>
    @endif

    <section class="rm-tabs rm-crm-tabs" aria-label="CRM">
        <button class="{{ $tab === 'inbox' ? 'is-active' : '' }}" type="button" wire:click="$set('tab', 'inbox')">
            Bandeja
        </button>
        <button class="{{ $tab === 'channels' ? 'is-active' : '' }}" type="button" wire:click="$set('tab', 'channels')">
            Canales
        </button>
    </section>

    @if ($tab === 'inbox')
        <section class="rm-crm-layout">
            <aside class="rm-crm-conversations">
                <div class="rm-crm-search">
                    <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>
                    <input type="search" wire:model.live.debounce.350ms="search" placeholder="Buscar por nombre o telefono">
                </div>

                <div class="rm-crm-conversation-list">
                    @forelse ($conversations as $conversation)
                        <button
                            class="rm-crm-conversation {{ $selectedConversationId === $conversation->id ? 'is-active' : '' }}"
                            type="button"
                            wire:click="selectConversation({{ $conversation->id }})"
                        >
                            <span class="rm-crm-avatar">{{ strtoupper(substr($conversation->contact?->name ?: $conversation->contact?->phone ?: 'W', 0, 1)) }}</span>
                            <span>
                                <strong>{{ $conversation->contact?->name ?: 'Cliente WhatsApp' }}</strong>
                                <small>{{ $conversation->contact?->phone }} · {{ $conversation->last_message ?: 'Sin mensajes' }}</small>
                            </span>
                            @if ($conversation->unread_count)
                                <i>{{ $conversation->unread_count }}</i>
                            @endif
                        </button>
                    @empty
                        <div class="rm-empty-state">
                            <strong>Sin conversaciones</strong>
                            <p>Cuando llegue un mensaje de WhatsApp aparecera aqui.</p>
                        </div>
                    @endforelse
                </div>
            </aside>

            <section class="rm-crm-thread">
                @if ($selectedConversation)
                    <header class="rm-crm-thread-head">
                        <div>
                            <span>{{ $selectedConversation->channel?->name }}</span>
                            <h2>{{ $selectedConversation->contact?->name ?: 'Cliente WhatsApp' }}</h2>
                            <p>{{ $selectedConversation->contact?->phone }}</p>
                        </div>
                        <button class="rm-button rm-button-outline" type="button" wire:click="openAppointmentModal">
                            Agendar
                        </button>
                    </header>

                    <div class="rm-crm-messages">
                        @foreach ($selectedConversation->messages as $message)
                            <article class="rm-crm-message {{ $message->direction === 'out' ? 'is-out' : 'is-in' }}">
                                <p>{{ $message->body ?: strtoupper($message->type) }}</p>
                                <small>
                                    {{ $message->message_at?->format('d/m/Y H:i') }}
                                    @if ($message->direction === 'out')
                                        · {{ $message->status === 'sent' ? 'Enviado' : ($message->status === 'failed' ? 'Error' : 'Pendiente') }}
                                    @endif
                                </small>
                            </article>
                        @endforeach
                    </div>

                    <form class="rm-crm-reply" wire:submit="sendReply">
                        <textarea wire:model.defer="replyText" rows="2" placeholder="Escribe una respuesta"></textarea>
                        @error('replyText')
                            <small class="rm-field-error">{{ $message }}</small>
                        @enderror
                        <button class="rm-button" type="submit">Enviar</button>
                    </form>
                @else
                    <div class="rm-empty-state rm-crm-empty-thread">
                        <strong>Selecciona una conversacion</strong>
                        <p>Aqui veras los mensajes y podras crear citas.</p>
                    </div>
                @endif
            </section>
        </section>
    @else
        <section class="rm-card rm-crm-channels">
            <div class="rm-section-heading">
                <div>
                    <span>Configuracion</span>
                    <h2>Numeros de WhatsApp por empresa</h2>
                    <p class="rm-crm-webhook">Webhook: <code>{{ $webhookUrl }}</code></p>
                </div>
                <button class="rm-button" type="button" wire:click="openChannelModal">Nuevo canal</button>
            </div>

            <div class="rm-crm-channel-list">
                @forelse ($channels as $channel)
                    <article class="rm-crm-channel">
                        <div>
                            <strong>{{ $channel->name }}</strong>
                        <p>{{ $channel->phone_number ?: 'Sin numero visible' }}</p>
                        <code>{{ $channel->phone_number_id }}</code>
                            <span>{{ $channel->branch?->name ?: 'Todas las sucursales' }}</span>
                        </div>
                        <div class="rm-crm-channel-actions">
                            <span class="{{ $channel->is_active ? 'is-on' : 'is-off' }}">{{ $channel->is_active ? 'Activo' : 'Pausado' }}</span>
                            <button class="rm-button rm-button-outline" type="button" wire:click="openChannelModal({{ $channel->id }})">Editar</button>
                            <button class="rm-button rm-button-outline" type="button" wire:click="toggleChannel({{ $channel->id }})">
                                {{ $channel->is_active ? 'Pausar' : 'Activar' }}
                            </button>
                        </div>
                    </article>
                @empty
                    <div class="rm-empty-state">
                        <strong>Sin canales</strong>
                        <p>Agrega el primer numero de WhatsApp Business para esta empresa.</p>
                    </div>
                @endforelse
            </div>
        </section>
    @endif

    @if ($showChannelModal)
        <div class="rm-modal-backdrop">
            <section class="rm-modal-panel rm-modal-panel-xl rm-crm-modal">
                <div class="rm-modal-title">
                    <div>
                        <span>WhatsApp API</span>
                        <h2>{{ $editingChannelId ? 'Editar canal' : 'Nuevo canal' }}</h2>
                        <p class="rm-modal-subtitle">Configura el numero de WhatsApp Business que usara esta empresa.</p>
                    </div>
                    <button type="button" wire:click="$set('showChannelModal', false)" aria-label="Cerrar">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                            <path d="M18 6 6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="rm-form-grid">
                    <label class="rm-field">
                        <span class="rm-label">Nombre</span>
                        <input class="rm-input" type="text" wire:model.defer="channelForm.name" placeholder="WhatsApp Central">
                        @error('channelForm.name') <small class="rm-field-error">{{ $message }}</small> @enderror
                    </label>
                    <label class="rm-field">
                        <span class="rm-label">Sucursal</span>
                        <select class="rm-input" wire:model.defer="channelForm.branch_id">
                            <option value="">Todas las sucursales</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="rm-field">
                        <span class="rm-label">Numero visible</span>
                        <input class="rm-input" type="text" wire:model.defer="channelForm.phone_number" placeholder="59170000000">
                    </label>
                    <label class="rm-field">
                        <span class="rm-label">Phone Number ID</span>
                        <input class="rm-input" type="text" wire:model.defer="channelForm.phone_number_id">
                        @error('channelForm.phone_number_id') <small class="rm-field-error">{{ $message }}</small> @enderror
                    </label>
                    <label class="rm-field">
                        <span class="rm-label">WABA ID</span>
                        <input class="rm-input" type="text" wire:model.defer="channelForm.waba_id">
                    </label>
                    <label class="rm-field">
                        <span class="rm-label">Version API</span>
                        <input class="rm-input" type="text" wire:model.defer="channelForm.api_version" placeholder="v23.0">
                    </label>
                    <label class="rm-field rm-form-span">
                        <span class="rm-label">Access token {{ $editingChannelId ? '(dejar vacio para no cambiar)' : '' }}</span>
                        <textarea class="rm-input rm-crm-token-input" rows="3" wire:model.defer="channelForm.access_token"></textarea>
                        @error('channelForm.access_token') <small class="rm-field-error">{{ $message }}</small> @enderror
                    </label>
                    <label class="rm-field">
                        <span class="rm-label">Verify token</span>
                        <input class="rm-input" type="text" wire:model.defer="channelForm.verify_token">
                    </label>
                    <label class="rm-field">
                        <span class="rm-label">API key audio</span>
                        <input class="rm-input" type="text" wire:model.defer="channelForm.audio_converter_api_key">
                    </label>
                    <label class="rm-check-line">
                        <input type="checkbox" wire:model.defer="channelForm.is_active">
                        Canal activo
                    </label>
                </div>

                <div class="rm-form-actions rm-crm-modal-actions">
                    <button class="rm-button rm-button-outline" type="button" wire:click="$set('showChannelModal', false)">Cancelar</button>
                    <button class="rm-button" type="button" wire:click="saveChannel">Guardar canal</button>
                </div>
            </section>
        </div>
    @endif

    @if ($showAppointmentModal && $selectedConversation)
        <div class="rm-modal-backdrop">
            <section class="rm-modal-panel rm-modal-panel-xl rm-crm-modal">
                <div class="rm-modal-title">
                    <div>
                        <span>Agenda</span>
                        <h2>Crear cita desde WhatsApp</h2>
                        <p class="rm-modal-subtitle">Cliente: {{ $selectedConversation->contact?->name ?: $selectedConversation->contact?->phone }}</p>
                    </div>
                    <button type="button" wire:click="$set('showAppointmentModal', false)" aria-label="Cerrar">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                            <path d="M18 6 6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="rm-form-grid">
                    <label class="rm-field">
                        <span class="rm-label">Sucursal</span>
                        <select class="rm-input" wire:model.live="appointmentForm.branch_id">
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('appointmentForm.branch_id') <small class="rm-field-error">{{ $message }}</small> @enderror
                    </label>
                    <label class="rm-field">
                        <span class="rm-label">Atendido por</span>
                        <select class="rm-input" wire:model.defer="appointmentForm.attended_by_user_id">
                            <option value="">Sin asignar</option>
                            @foreach ($staffUsers as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="rm-field">
                        <span class="rm-label">Fecha</span>
                        <input class="rm-input" type="date" wire:model.defer="appointmentForm.scheduled_date">
                    </label>
                    <label class="rm-field">
                        <span class="rm-label">Hora</span>
                        <input class="rm-input" type="time" wire:model.defer="appointmentForm.scheduled_time">
                    </label>
                    <label class="rm-field rm-form-span">
                        <span class="rm-label">Servicios</span>
                        <select multiple wire:model.defer="appointmentForm.service_ids" class="rm-input rm-crm-service-select">
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}">{{ $service->name }} · Bs {{ number_format((float) $service->price, 2) }}</option>
                            @endforeach
                        </select>
                        @error('appointmentForm.service_ids') <small class="rm-field-error">{{ $message }}</small> @enderror
                    </label>
                    <label class="rm-field rm-form-span">
                        <span class="rm-label">Nota interna</span>
                        <textarea class="rm-input rm-crm-token-input" rows="3" wire:model.defer="appointmentForm.notes" placeholder="Motivo, detalle o acuerdo con el cliente"></textarea>
                    </label>
                </div>

                <div class="rm-form-actions rm-crm-modal-actions">
                    <button class="rm-button rm-button-outline" type="button" wire:click="$set('showAppointmentModal', false)">Cancelar</button>
                    <button class="rm-button" type="button" wire:click="saveAppointmentFromCrm">Crear cita</button>
                </div>
            </section>
        </div>
    @endif
</main>
