<div class="rm-content rm-settings-page">
    <section class="rm-settings-hero rm-clinical-hero">
        <div>
            <span>Historia clinica</span>
            <h1>Fichas, documentos y recetas</h1>
            <p>Administra plantillas, archivos del cliente, recetas y accesos por doctor o profesional.</p>
        </div>
        <div class="rm-settings-summary rm-clinical-summary">
            <strong>{{ $records->count() + $documents->count() + $prescriptions->count() }}</strong>
            <span>Registros del paciente</span>
        </div>
    </section>

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
                <section class="rm-panel rm-clinical-editor">
                    <header>
                        <h2>Nueva ficha o evolucion</h2>
                        <span>Asociable a cita, tratamiento o servicio.</span>
                    </header>

                    @if ($canCreateClinical)
                        <div class="rm-form-grid rm-clinical-form-grid two">
                            <label class="rm-field">
                                <span>Plantilla</span>
                                <select wire:model.live="templateId">
                                    <option value="">Sin plantilla</option>
                                    @foreach ($activeTemplates as $template)
                                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="rm-field">
                                <span>Titulo</span>
                                <input wire:model="recordTitle" type="text" placeholder="Ficha inicial, control, evolucion">
                            </label>
                            <label class="rm-field">
                                <span>Tipo</span>
                                <select wire:model="recordType">
                                    <option value="ficha">Ficha clinica</option>
                                    <option value="evolucion">Evolucion</option>
                                    <option value="laboratorio">Laboratorio</option>
                                    <option value="consentimiento">Consentimiento</option>
                                    <option value="observacion">Observacion</option>
                                </select>
                            </label>
                            <label class="rm-field">
                                <span>Cita</span>
                                <select wire:model.live="recordAppointmentId">
                                    <option value="">Sin cita</option>
                                    @foreach ($appointments as $appointment)
                                        <option value="{{ $appointment->id }}">{{ $appointment->scheduled_at->format('d/m/Y H:i') }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="rm-field">
                                <span>Tratamiento de la cita</span>
                                <select wire:model="recordAppointmentServiceId">
                                    <option value="">Sin tratamiento</option>
                                    @foreach ($appointments as $appointment)
                                        @foreach ($appointment->services as $line)
                                            <option value="{{ $line->id }}">{{ $appointment->scheduled_at->format('d/m') }} - {{ $line->name }}</option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </label>
                            <label class="rm-field">
                                <span>Servicio general</span>
                                <select wire:model="recordServiceId">
                                    <option value="">Sin servicio</option>
                                    @foreach ($services as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        @if ($recordData)
                            <div class="rm-form-grid rm-clinical-form-grid two">
                                @foreach ($recordData as $field => $value)
                                    <label class="rm-field">
                                        <span>{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $field)) }}</span>
                                        <input wire:model="recordData.{{ $field }}" type="text">
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        <label class="rm-field">
                            <span>Detalle clinico</span>
                            <textarea wire:model="recordContent" rows="7" placeholder="Escribe signos, antecedentes, diagnostico, indicaciones o notas del caso."></textarea>
                        </label>

                        <button class="rm-button rm-button-primary" type="button" wire:click="saveRecord">Guardar ficha</button>
                    @else
                        <div class="rm-empty-state">Tu rol puede ver esta historia, pero no crear fichas para este paciente.</div>
                    @endif
                </section>

                <section class="rm-clinical-list">
                    @forelse ($records as $record)
                        <article class="rm-panel rm-clinical-card">
                            <div>
                                <strong>{{ $record->title }}</strong>
                                <span>{{ ucfirst(str_replace('_', ' ', $record->type)) }} · {{ $record->created_at->format('d/m/Y H:i') }}</span>
                                @if ($record->appointmentService)
                                    <small>{{ $record->appointmentService->name }}</small>
                                @elseif ($record->service)
                                    <small>{{ $record->service->name }}</small>
                                @endif
                            </div>
                            <p>{{ \Illuminate\Support\Str::limit($record->content ?: collect($record->data ?? [])->map(fn($v, $k) => "$k: $v")->implode(' · '), 240) }}</p>
                            <footer>{{ $record->createdBy?->name ?? 'Sin responsable' }}</footer>
                        </article>
                    @empty
                        <div class="rm-panel rm-empty-state">Este paciente aun no tiene fichas clinicas.</div>
                    @endforelse
                </section>
            @elseif ($tab === 'documents')
                <section class="rm-panel rm-clinical-editor">
                    <header>
                        <h2>Subir archivo del cliente</h2>
                        <span>Imagenes, PDF o documentos externos.</span>
                    </header>
                    @if ($canCreateClinical)
                        <div class="rm-form-grid rm-clinical-form-grid two">
                            <label class="rm-field">
                                <span>Titulo</span>
                                <input wire:model="documentTitle" type="text" placeholder="Ecografia, foto antes, consentimiento">
                            </label>
                            <label class="rm-field">
                                <span>Archivo</span>
                                <input wire:model="documentFile" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx">
                            </label>
                            <label class="rm-field">
                                <span>Cita</span>
                                <select wire:model="documentAppointmentId">
                                    <option value="">Sin cita</option>
                                    @foreach ($appointments as $appointment)
                                        <option value="{{ $appointment->id }}">{{ $appointment->scheduled_at->format('d/m/Y H:i') }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="rm-field">
                                <span>Servicio general</span>
                                <select wire:model="documentServiceId">
                                    <option value="">Sin servicio</option>
                                    @foreach ($services as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                        <label class="rm-field">
                            <span>Notas</span>
                            <textarea wire:model="documentNotes" rows="3" placeholder="De que trata el archivo o quien lo entrego."></textarea>
                        </label>
                        <button class="rm-button rm-button-primary" type="button" wire:click="saveDocument">Subir archivo</button>
                    @endif
                </section>

                <section class="rm-clinical-list">
                    @forelse ($documents as $document)
                        <article class="rm-panel rm-clinical-card">
                            <div>
                                <strong>{{ $document->title }}</strong>
                                <span>{{ $document->created_at->format('d/m/Y H:i') }} · {{ $document->file_name }}</span>
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
                <section class="rm-panel rm-clinical-editor">
                    <header>
                        <h2>Nueva receta</h2>
                        <span>Puede asociarse a una cita o tratamiento.</span>
                    </header>
                    @if ($canCreateClinical)
                        <div class="rm-form-grid rm-clinical-form-grid two">
                            <label class="rm-field">
                                <span>Titulo</span>
                                <input wire:model="prescriptionTitle" type="text">
                            </label>
                            <label class="rm-field">
                                <span>Fecha</span>
                                <input wire:model="prescriptionIssuedAt" type="date">
                            </label>
                            <label class="rm-field">
                                <span>Cita</span>
                                <select wire:model="prescriptionAppointmentId">
                                    <option value="">Sin cita</option>
                                    @foreach ($appointments as $appointment)
                                        <option value="{{ $appointment->id }}">{{ $appointment->scheduled_at->format('d/m/Y H:i') }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="rm-field">
                                <span>Tratamiento</span>
                                <select wire:model="prescriptionAppointmentServiceId">
                                    <option value="">Sin tratamiento</option>
                                    @foreach ($appointments as $appointment)
                                        @foreach ($appointment->services as $line)
                                            <option value="{{ $line->id }}">{{ $appointment->scheduled_at->format('d/m') }} - {{ $line->name }}</option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </label>
                        </div>
                        <label class="rm-field">
                            <span>Indicaciones</span>
                            <textarea wire:model="prescriptionIndications" rows="7" placeholder="Medicamentos, dosis, frecuencia, cuidados y controles."></textarea>
                        </label>
                        <button class="rm-button rm-button-primary" type="button" wire:click="savePrescription">Guardar receta</button>
                    @endif
                </section>

                <section class="rm-clinical-list">
                    @forelse ($prescriptions as $prescription)
                        <article class="rm-panel rm-clinical-card">
                            <div>
                                <strong>{{ $prescription->title }}</strong>
                                <span>{{ $prescription->issued_at->format('d/m/Y') }} · {{ $prescription->issuedBy?->name ?? 'Sin doctor' }}</span>
                            </div>
                            <p>{{ \Illuminate\Support\Str::limit($prescription->indications, 260) }}</p>
                        </article>
                    @empty
                        <div class="rm-panel rm-empty-state">Este paciente aun no tiene recetas.</div>
                    @endforelse
                </section>
            @elseif ($tab === 'templates')
                <section class="rm-panel rm-clinical-editor">
                    <header>
                        <h2>{{ $editingTemplateId ? 'Editar plantilla' : 'Nueva plantilla' }}</h2>
                        <span>La plantilla crea campos reutilizables para fichas clinicas, evoluciones, recetas o consentimientos.</span>
                    </header>
                    <div class="rm-form-grid rm-clinical-form-grid two">
                        <label class="rm-field">
                            <span>Nombre</span>
                            <input wire:model="templateName" type="text" placeholder="Ficha dermatologica, evaluacion inicial">
                        </label>
                        <label class="rm-field">
                            <span>Categoria</span>
                            <select wire:model="templateCategory">
                                <option value="ficha_inicial">Ficha inicial</option>
                                <option value="evolucion">Evolucion</option>
                                <option value="receta">Receta</option>
                                <option value="consentimiento">Consentimiento</option>
                                <option value="laboratorio">Laboratorio</option>
                            </select>
                        </label>
                    </div>
                    <label class="rm-field">
                        <span>Campos, uno por linea</span>
                        <textarea wire:model="templateFieldsText" rows="5" placeholder="Ejemplo:&#10;Tipo de sangre&#10;Alergias&#10;Antecedentes&#10;Diagnostico"></textarea>
                    </label>
                    <label class="rm-field">
                        <span>Texto base de la hoja</span>
                        <textarea wire:model="templateBody" rows="6" placeholder="Texto opcional que aparecera precargado al crear una ficha con esta plantilla. Puedes dejarlo vacio."></textarea>
                    </label>
                    <label class="rm-check-row rm-template-active-row">
                        <input wire:model="templateIsActive" type="checkbox">
                        <span>
                            <strong>Plantilla activa</strong>
                            <small>Si esta activa, aparecera como opcion al crear una nueva ficha.</small>
                        </span>
                    </label>
                    <div class="rm-modal-actions">
                        <button class="rm-button rm-button-primary" type="button" wire:click="saveTemplate">Guardar plantilla</button>
                        @if ($editingTemplateId)
                            <button class="rm-button rm-button-outline" type="button" wire:click="resetTemplateForm">Cancelar</button>
                        @endif
                    </div>
                </section>

                <section class="rm-clinical-list">
                    @foreach ($templates as $template)
                        <article class="rm-panel rm-clinical-card">
                            <div>
                                <strong>{{ $template->name }}</strong>
                                <span>{{ ucfirst(str_replace('_', ' ', $template->category)) }} · {{ $template->is_active ? 'Activa' : 'Inactiva' }}</span>
                            </div>
                            <p>{{ collect($template->fields ?? [])->map(fn($field) => $field['label'] ?? '')->filter()->implode(' · ') ?: 'Sin campos definidos' }}</p>
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
                <section class="rm-panel rm-clinical-editor">
                    <header>
                        <h2>Especialidades medicas</h2>
                        <span>Un doctor puede tener varias especialidades.</span>
                    </header>
                    <div class="rm-form-grid rm-clinical-form-grid two">
                        <label class="rm-field">
                            <span>Nueva especialidad</span>
                            <input wire:model="specialtyName" type="text" placeholder="Dermatologia, ginecologia, podologia">
                        </label>
                        <label class="rm-field">
                            <span>Descripcion</span>
                            <input wire:model="specialtyDescription" type="text" placeholder="Opcional">
                        </label>
                    </div>
                    <button class="rm-button rm-button-primary" type="button" wire:click="saveSpecialty">Crear especialidad</button>
                </section>

                <section class="rm-panel rm-clinical-editor">
                    <header>
                        <h2>Asignar especialidades</h2>
                        <span>Selecciona profesional y sus especialidades.</span>
                    </header>
                    <div class="rm-form-grid rm-clinical-form-grid two">
                        <label class="rm-field">
                            <span>Profesional</span>
                            <select wire:model="specialtyUserId">
                                <option value="">Seleccionar</option>
                                @foreach ($staff as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="rm-field">
                            <span>Especialidades</span>
                            <select wire:model="specialtyIds" multiple size="4">
                                @foreach ($specialties as $specialty)
                                    <option value="{{ $specialty->id }}">{{ $specialty->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <button class="rm-button rm-button-primary" type="button" wire:click="assignSpecialties">Guardar especialidades</button>
                </section>

                <section class="rm-panel rm-clinical-editor">
                    <header>
                        <h2>Autorizar historial por paciente</h2>
                        <span>Para doctores que no deben ver todo el historial completo.</span>
                    </header>
                    <div class="rm-form-grid rm-clinical-form-grid two">
                        <label class="rm-field">
                            <span>Paciente</span>
                            <select wire:model="accessClientId">
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->full_name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="rm-field">
                            <span>Doctor / profesional</span>
                            <select wire:model="accessUserId">
                                <option value="">Seleccionar</option>
                                @foreach ($staff as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="rm-field">
                            <span>Vence</span>
                            <input wire:model="accessExpiresAt" type="date">
                        </label>
                        <label class="rm-field">
                            <span>Motivo</span>
                            <input wire:model="accessReason" type="text" placeholder="Interconsulta, apoyo, seguimiento">
                        </label>
                    </div>
                    <div class="rm-inline-checks">
                        <label><input wire:model="accessCanView" type="checkbox"> Puede ver</label>
                        <label><input wire:model="accessCanCreate" type="checkbox"> Puede registrar</label>
                    </div>
                    <button class="rm-button rm-button-primary" type="button" wire:click="grantPatientAccess">Guardar acceso</button>
                </section>

                <section class="rm-clinical-list">
                    @foreach ($accesses as $access)
                        <article class="rm-panel rm-clinical-card">
                            <div>
                                <strong>{{ $access->client?->full_name }}</strong>
                                <span>{{ $access->user?->name }} · {{ $access->can_create ? 'Ver y registrar' : 'Solo ver' }}</span>
                            </div>
                            <p>{{ $access->reason ?: 'Sin motivo' }} {{ $access->expires_at ? '· Vence ' . $access->expires_at->format('d/m/Y') : '' }}</p>
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
</div>
