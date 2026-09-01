<div class="rm-staff-punch">
    @if ($enabled)
    <button class="rm-staff-punch-button {{ $openRecord ? 'is-open' : '' }}" type="button" wire:click="openPunch">
        <span class="rm-staff-punch-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                <path d="M8 2v4M16 2v4M3 10h18" />
                <rect x="3" y="4" width="18" height="18" rx="3" />
                <path d="M8 15h5" />
            </svg>
        </span>
        <span data-sidebar-label>
            <strong>{{ $openRecord ? 'Registrar salida' : 'Registrar asistencia' }}</strong>
            <small>
                @if ($openRecord)
                    Entrada {{ $openRecord->check_in_at?->format('H:i') }}
                @elseif ($todayRecord?->check_out_at)
                    Completa {{ $todayRecord->check_out_at->format('H:i') }}
                @else
                    Facial + ubicacion
                @endif
            </small>
        </span>
    </button>

    @if ($showModal)
        <div class="rm-modal-backdrop" wire:click="closePunch" data-attendance-close></div>
        <section class="rm-modal-panel rm-modal-panel-small rm-attendance-modal" role="dialog" aria-modal="true"
            data-attendance-face data-livewire-id="{{ $this->getId() }}">
            <div class="rm-modal-title">
                <div>
                    <span>Recursos humanos</span>
                    <h2>{{ $openRecord ? 'Registrar salida' : 'Registrar asistencia' }}</h2>
                </div>
                <button type="button" wire:click="closePunch" data-attendance-close aria-label="Cerrar modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="rm-attendance-face-card">
                <div class="rm-attendance-alpaca" aria-hidden="true">
                    <span>Rumi</span>
                    <i></i>
                </div>
                <div class="rm-attendance-video-wrap">
                    <video data-attendance-video autoplay muted playsinline></video>
                    <canvas data-attendance-canvas hidden></canvas>
                </div>
                <p data-attendance-status>
                    @if (! $hasFace)
                        Este usuario primero debe registrar su rostro para poder marcar asistencia.
                    @elseif (! $hasGeofence)
                        Configura ubicacion y radio en una sucursal antes de marcar.
                    @else
                        Activa la camara y valida tu rostro dentro del radio de una sucursal.
                    @endif
                </p>
                @error('attendance') <small class="rm-field-error">{{ $message }}</small> @enderror
                @if ($message)
                    <div class="rm-inline-success">
                        {{ $message }}
                        @if ($lastBranchName)
                            <span>{{ $lastBranchName }} - {{ $lastDistance }} m - {{ $lastSimilarity }}%</span>
                        @endif
                    </div>
                @endif
            </div>

            <div class="rm-form-actions rm-face-actions">
                @if (! $hasFace)
                    <a class="rm-button rm-button-primary" href="{{ route('security.face', ['mode' => 'enroll']) }}">
                        Registrar rostro
                    </a>
                @else
                    <button class="rm-button rm-button-outline" type="button" data-attendance-start>Activar camara</button>
                    <button class="rm-button rm-button-primary" type="button" data-attendance-capture
                        @disabled(! $hasGeofence)>
                        Validar y guardar
                    </button>
                @endif
            </div>
        </section>
    @endif
    @endif
</div>
