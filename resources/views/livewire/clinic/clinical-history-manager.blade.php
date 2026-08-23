<div class="rm-content rm-settings-page">
    <section class="rm-clinical-layout {{ $lockedToClient ? 'is-client-locked' : '' }}">
        @unless ($lockedToClient)
            <aside class="rm-panel rm-clinical-patients">
                <header>
                    <h2>Pacientes</h2>
                    <span>{{ $canViewFullHistory ? 'Historial completo' : 'Solo autorizados' }}</span>
                </header>

                <label class="rm-search-field">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.2">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                    <input wire:model.live.debounce.350ms="clientSearch" type="search"
                        placeholder="Buscar por nombre, CI o telefono">
                </label>

                <div class="rm-clinical-client-list" aria-label="Lista de pacientes">
                    @forelse ($clients as $client)
                        <button type="button" wire:click="selectClient({{ $client->id }})"
                            class="{{ $selectedClient?->id === $client->id ? 'is-active' : '' }}">
                            <strong>{{ $client->full_name }}</strong>
                            <span>{{ $client->displayContact() ?? 'Sin telefono ni CI' }}</span>
                        </button>
                    @empty
                        <div class="rm-empty-state">No hay pacientes visibles para tu rol.</div>
                    @endforelse
                </div>
            </aside>
        @endunless

        <div class="rm-clinical-content">
            <div class="rm-tab-switcher rm-tab-switcher-five">
                <button type="button" wire:click="setTab('records')" class="{{ $tab === 'records' ? 'is-active' : '' }}">Fichas</button>
                <button type="button" wire:click="setTab('documents')" class="{{ $tab === 'documents' ? 'is-active' : '' }}">Archivos</button>
                <button type="button" wire:click="setTab('prescriptions')" class="{{ $tab === 'prescriptions' ? 'is-active' : '' }}">Recetas</button>
                @if ($canEditClinical)
                    <button type="button" wire:click="setTab('templates')" class="{{ $tab === 'templates' ? 'is-active' : '' }}">Plantillas</button>
                @endif
                @if ($canManageAccess)
                    <button type="button" wire:click="setTab('access')" class="{{ $tab === 'access' ? 'is-active' : '' }}">Accesos</button>
                @endif
            </div>

            @if ($selectedClient)
                <section class="rm-panel rm-clinical-client-head">
                    <div>
                        <span>Paciente seleccionado</span>
                        <h2>{{ $selectedClient->full_name }}</h2>
                        <p>{{ $selectedClient->identity_number ? 'CI ' . $selectedClient->identity_number : 'Sin CI' }}</p>
                    </div>
                    <div class="rm-clinical-phone-wrap">
                        @forelse ($selectedClient->phones as $phone)
                            <span>{{ $phone->phone }}</span>
                        @empty
                            <span>{{ $selectedClient->displayContact() ?? 'Sin telefonos ni CI' }}</span>
                        @endforelse
                    </div>
                </section>
            @endif

            @if (! $selectedClient)
                <section class="rm-panel rm-empty-state">Selecciona un paciente para trabajar su historia.</section>
            @elseif ($tab === 'records')
                <section class="rm-panel rm-clinical-actionbar">
                    <div>
                        <h2>Fichas clinicas</h2>
                        <p>Evoluciones, notas y datos asociados al paciente.</p>
                    </div>
                    @if ($canCreateClinical)
                        <button class="rm-button rm-button-primary" type="button" wire:click="openRecordModal">Nueva ficha</button>
                    @endif
                </section>

                <section class="rm-clinical-list">
                    @forelse ($records as $record)
                        <article class="rm-panel rm-clinical-card">
                            <div>
                                <strong>{{ $record->title }}</strong>
                                <span>{{ ucfirst(str_replace('_', ' ', $record->type)) }} - {{ $record->created_at->format('d/m/Y H:i') }}</span>
                                @if ($record->appointmentService)
                                    <small>{{ $record->appointmentService->name }}</small>
                                @elseif ($record->service)
                                    <small>{{ $record->service->name }}</small>
                                @endif
                            </div>
                            <p>{{ \Illuminate\Support\Str::limit($record->content ?: collect($record->data ?? [])->map(fn($v, $k) => "$k: $v")->implode(' - '), 240) }}</p>
                            <footer>{{ $record->createdBy?->name ?? 'Sin responsable' }}</footer>
                            @if ($canDeleteClinical)
                                <footer>
                                    <button type="button" wire:click="confirmDeleteRecord({{ $record->id }})">Eliminar ficha</button>
                                </footer>
                            @endif
                        </article>
                    @empty
                        <div class="rm-panel rm-empty-state">Este paciente aun no tiene fichas clinicas.</div>
                    @endforelse
                </section>
            @elseif ($tab === 'documents')
                <section class="rm-panel rm-clinical-actionbar">
                    <div>
                        <h2>Archivos</h2>
                        <p>Imagenes, PDF o documentos externos del paciente.</p>
                    </div>
                    @if ($canCreateClinical)
                        <button class="rm-button rm-button-primary" type="button" wire:click="openDocumentModal">Nuevo archivo</button>
                    @endif
                </section>

                <section class="rm-clinical-list">
                    @forelse ($documents as $document)
                        <article class="rm-panel rm-clinical-card">
                            <div>
                                <strong>{{ $document->title }}</strong>
                                <span>{{ $document->created_at->format('d/m/Y H:i') }} - {{ $document->file_name }}</span>
                            </div>
                            <p>{{ $document->notes ?: 'Sin notas' }}</p>
                            <footer>
                                <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank">Ver archivo</a>
                                <span>{{ $document->uploadedBy?->name ?? 'Sin responsable' }}</span>
                            </footer>
                        </article>
                    @empty
                        <div class="rm-panel rm-empty-state">Este paciente aun no tiene archivos.</div>
                    @endforelse
                </section>
            @elseif ($tab === 'prescriptions')
                <section class="rm-panel rm-clinical-actionbar">
                    <div>
                        <h2>Recetas</h2>
                        <p>Indicaciones medicas asociadas a citas o tratamientos.</p>
                    </div>
                    @if ($canCreateClinical)
                        <button class="rm-button rm-button-primary" type="button" wire:click="openPrescriptionModal">Nueva receta</button>
                    @endif
                </section>

                <section class="rm-clinical-list">
                    @forelse ($prescriptions as $prescription)
                        <article class="rm-panel rm-clinical-card">
                            <div>
                                <strong>{{ $prescription->title }}</strong>
                                <span>{{ $prescription->issued_at->format('d/m/Y') }} - {{ $prescription->issuedBy?->name ?? 'Sin doctor' }}</span>
                            </div>
                            <p>{{ \Illuminate\Support\Str::limit($prescription->indications, 260) }}</p>
                        </article>
                    @empty
                        <div class="rm-panel rm-empty-state">Este paciente aun no tiene recetas.</div>
                    @endforelse
                </section>
            @elseif ($tab === 'templates')
                <section class="rm-panel rm-clinical-actionbar">
                    <div>
                        <h2>Plantillas</h2>
                        <p>Formatos reutilizables para fichas, evoluciones y consentimientos.</p>
                    </div>
                    <button class="rm-button rm-button-primary" type="button" wire:click="openTemplateModal">Nueva plantilla</button>
                </section>

                <section class="rm-clinical-list">
                    @foreach ($templates as $template)
                        <article class="rm-panel rm-clinical-card">
                            <div>
                                <strong>{{ $template->name }}</strong>
                                <span>{{ ucfirst(str_replace('_', ' ', $template->category)) }} - {{ $template->is_active ? 'Activa' : 'Inactiva' }}</span>
                            </div>
                            <p>{{ collect($template->fields ?? [])->map(fn($field) => $field['label'] ?? '')->filter()->implode(' - ') ?: 'Sin campos definidos' }}</p>
                            <footer>
                                <button type="button" wire:click="editTemplate({{ $template->id }})">Editar</button>
                                @if ($canDeleteClinical)
                                    <button type="button" wire:click="deleteTemplate({{ $template->id }})">Eliminar</button>
                                @endif
                            </footer>
                        </article>
                    @endforeach
                </section>
            @elseif ($tab === 'access')
                <section class="rm-panel rm-clinical-actionbar">
                    <div>
                        <h2>Accesos clinicos</h2>
                        <p>Especialidades y permisos por paciente para doctores o profesionales.</p>
                    </div>
                    <div class="rm-clinical-action-buttons">
                        <button class="rm-button rm-button-outline" type="button" wire:click="openSpecialtyModal">Nueva especialidad</button>
                        <button class="rm-button rm-button-outline" type="button" wire:click="openAssignSpecialtyModal">Asignar especialidades</button>
                        <button class="rm-button rm-button-primary" type="button" wire:click="openPatientAccessModal">Autorizar paciente</button>
                    </div>
                </section>

                <section class="rm-clinical-list">
                    @foreach ($accesses as $access)
                        <article class="rm-panel rm-clinical-card">
                            <div>
                                <strong>{{ $access->client?->full_name }}</strong>
                                <span>{{ $access->user?->name }} - {{ $access->can_create ? 'Ver y registrar' : 'Solo ver' }}</span>
                            </div>
                            <p>{{ $access->reason ?: 'Sin motivo' }} {{ $access->expires_at ? '- Vence ' . $access->expires_at->format('d/m/Y') : '' }}</p>
                            <footer>
                                <span>Autorizado por {{ $access->grantedBy?->name ?? 'Sistema' }}</span>
                                <button type="button" wire:click="revokePatientAccess({{ $access->id }})">Quitar acceso</button>
                            </footer>
                        </article>
                    @endforeach
                </section>
            @endif
        </div>
    </section>

    @include('livewire.clinic.partials.clinical-history-modals')
</div>
