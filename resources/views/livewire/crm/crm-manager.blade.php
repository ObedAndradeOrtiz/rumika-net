<main class="rm-page rm-crm-page">
    <section class="rm-hero-card rm-crm-hero">
        <div>
            <span>CRM y WhatsApp</span>
            <h1>Rumika Bot</h1>
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
                    <p>Webhook: <code>{{ $webhookUrl }}</code></p>
                </div>
                <button class="rm-button" type="button" wire:click="openChannelModal">Nuevo canal</button>
            </div>

            <div class="rm-crm-channel-list">
                @forelse ($channels as $channel)
                    <article class="rm-crm-channel">
                        <div>
                            <strong>{{ $channel->name }}</strong>
                            <p>{{ $channel->phone_number ?: 'Sin numero visible' }} · {{ $channel->phone_number_id }}</p>
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
            <section class="rm-modal rm-modal-wide">
                <button class="rm-modal-close" type="button" wire:click="$set('showChannelModal', false)">x</button>
                <span>WhatsApp API</span>
                <h2>{{ $editingChannelId ? 'Editar canal' : 'Nuevo canal' }}</h2>

                <div class="rm-form-grid">
                    <label>
                        Nombre
                        <input type="text" wire:model.defer="channelForm.name" placeholder="WhatsApp Central">
                        @error('channelForm.name') <small class="rm-field-error">{{ $message }}</small> @enderror
                    </label>
                    <label>
                        Sucursal
                        <select wire:model.defer="channelForm.branch_id">
                            <option value="">Todas las sucursales</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        Numero visible
                        <input type="text" wire:model.defer="channelForm.phone_number" placeholder="59170000000">
                    </label>
                    <label>
                        Phone Number ID
                        <input type="text" wire:model.defer="channelForm.phone_number_id">
                        @error('channelForm.phone_number_id') <small class="rm-field-error">{{ $message }}</small> @enderror
                    </label>
                    <label>
                        WABA ID
                        <input type="text" wire:model.defer="channelForm.waba_id">
                    </label>
                    <label>
                        Version API
                        <input type="text" wire:model.defer="channelForm.api_version" placeholder="v23.0">
                    </label>
                    <label class="rm-form-span">
                        Access token {{ $editingChannelId ? '(dejar vacio para no cambiar)' : '' }}
                        <textarea rows="3" wire:model.defer="channelForm.access_token"></textarea>
                        @error('channelForm.access_token') <small class="rm-field-error">{{ $message }}</small> @enderror
                    </label>
                    <label>
                        Verify token
                        <input type="text" wire:model.defer="channelForm.verify_token">
                    </label>
                    <label>
                        API key audio
                        <input type="text" wire:model.defer="channelForm.audio_converter_api_key">
                    </label>
                    <label class="rm-check-line">
                        <input type="checkbox" wire:model.defer="channelForm.is_active">
                        Canal activo
                    </label>
                </div>

                <div class="rm-modal-actions">
                    <button class="rm-button rm-button-outline" type="button" wire:click="$set('showChannelModal', false)">Cancelar</button>
                    <button class="rm-button" type="button" wire:click="saveChannel">Guardar canal</button>
                </div>
            </section>
        </div>
    @endif

    @if ($showAppointmentModal && $selectedConversation)
        <div class="rm-modal-backdrop">
            <section class="rm-modal rm-modal-wide">
                <button class="rm-modal-close" type="button" wire:click="$set('showAppointmentModal', false)">x</button>
                <span>Agenda</span>
                <h2>Crear cita desde WhatsApp</h2>
                <p class="rm-muted">Cliente: {{ $selectedConversation->contact?->name ?: $selectedConversation->contact?->phone }}</p>

                <div class="rm-form-grid">
                    <label>
                        Sucursal
                        <select wire:model.live="appointmentForm.branch_id">
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('appointmentForm.branch_id') <small class="rm-field-error">{{ $message }}</small> @enderror
                    </label>
                    <label>
                        Atendido por
                        <select wire:model.defer="appointmentForm.attended_by_user_id">
                            <option value="">Sin asignar</option>
                            @foreach ($staffUsers as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        Fecha
                        <input type="date" wire:model.defer="appointmentForm.scheduled_date">
                    </label>
                    <label>
                        Hora
                        <input type="time" wire:model.defer="appointmentForm.scheduled_time">
                    </label>
                    <label class="rm-form-span">
                        Servicios
                        <select multiple wire:model.defer="appointmentForm.service_ids" class="rm-crm-service-select">
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}">{{ $service->name }} · Bs {{ number_format((float) $service->price, 2) }}</option>
                            @endforeach
                        </select>
                        @error('appointmentForm.service_ids') <small class="rm-field-error">{{ $message }}</small> @enderror
                    </label>
                    <label class="rm-form-span">
                        Nota interna
                        <textarea rows="3" wire:model.defer="appointmentForm.notes" placeholder="Motivo, detalle o acuerdo con el cliente"></textarea>
                    </label>
                </div>

                <div class="rm-modal-actions">
                    <button class="rm-button rm-button-outline" type="button" wire:click="$set('showAppointmentModal', false)">Cancelar</button>
                    <button class="rm-button" type="button" wire:click="saveAppointmentFromCrm">Crear cita</button>
                </div>
            </section>
        </div>
    @endif
</main>
