@if ($showRecordModal)
    <div class="rm-modal-backdrop" wire:click="closeRecordModal"></div>
    <section class="rm-modal-panel rm-modal-panel-xl rm-clinical-editor" role="dialog" aria-modal="true">
        <div class="rm-modal-title">
            <div>
                <span>Historia clinica</span>
                <h2>Nueva ficha o evolucion</h2>
            </div>
            <button type="button" wire:click="closeRecordModal">x</button>
        </div>

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

        <div class="rm-form-actions">
            <button class="rm-button rm-button-primary" type="button" wire:click="saveRecord">Guardar ficha</button>
            <button class="rm-button rm-button-outline" type="button" wire:click="closeRecordModal">Cancelar</button>
        </div>
    </section>
@endif

@if ($showDocumentModal)
    <div class="rm-modal-backdrop" wire:click="closeDocumentModal"></div>
    <section class="rm-modal-panel rm-modal-panel-xl rm-clinical-editor" role="dialog" aria-modal="true">
        <div class="rm-modal-title">
            <div>
                <span>Archivo clinico</span>
                <h2>Subir archivo del cliente</h2>
            </div>
            <button type="button" wire:click="closeDocumentModal">x</button>
        </div>

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

        <div class="rm-form-actions">
            <button class="rm-button rm-button-primary" type="button" wire:click="saveDocument">Subir archivo</button>
            <button class="rm-button rm-button-outline" type="button" wire:click="closeDocumentModal">Cancelar</button>
        </div>
    </section>
@endif

@if ($showPrescriptionModal)
    <div class="rm-modal-backdrop" wire:click="closePrescriptionModal"></div>
    <section class="rm-modal-panel rm-modal-panel-xl rm-clinical-editor" role="dialog" aria-modal="true">
        <div class="rm-modal-title">
            <div>
                <span>Receta</span>
                <h2>Nueva receta</h2>
            </div>
            <button type="button" wire:click="closePrescriptionModal">x</button>
        </div>

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

        <div class="rm-form-actions">
            <button class="rm-button rm-button-primary" type="button" wire:click="savePrescription">Guardar receta</button>
            <button class="rm-button rm-button-outline" type="button" wire:click="closePrescriptionModal">Cancelar</button>
        </div>
    </section>
@endif

@if ($showTemplateModal)
    <div class="rm-modal-backdrop" wire:click="closeTemplateModal"></div>
    <section class="rm-modal-panel rm-modal-panel-xl rm-clinical-editor" role="dialog" aria-modal="true">
        <div class="rm-modal-title">
            <div>
                <span>Plantillas clinicas</span>
                <h2>{{ $editingTemplateId ? 'Editar plantilla' : 'Nueva plantilla' }}</h2>
            </div>
            <button type="button" wire:click="closeTemplateModal">x</button>
        </div>

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

        <div class="rm-form-actions">
            <button class="rm-button rm-button-primary" type="button" wire:click="saveTemplate">Guardar plantilla</button>
            <button class="rm-button rm-button-outline" type="button" wire:click="closeTemplateModal">Cancelar</button>
        </div>
    </section>
@endif

@if ($showSpecialtyModal)
    <div class="rm-modal-backdrop" wire:click="closeSpecialtyModal"></div>
    <section class="rm-modal-panel rm-modal-panel-wide rm-clinical-editor" role="dialog" aria-modal="true">
        <div class="rm-modal-title">
            <div>
                <span>Especialidades</span>
                <h2>Nueva especialidad</h2>
            </div>
            <button type="button" wire:click="closeSpecialtyModal">x</button>
        </div>

        <div class="rm-form-grid rm-clinical-form-grid two">
            <label class="rm-field">
                <span>Nombre</span>
                <input wire:model="specialtyName" type="text" placeholder="Dermatologia, ginecologia, podologia">
            </label>
            <label class="rm-field">
                <span>Descripcion</span>
                <input wire:model="specialtyDescription" type="text" placeholder="Opcional">
            </label>
        </div>

        <div class="rm-form-actions">
            <button class="rm-button rm-button-primary" type="button" wire:click="saveSpecialty">Crear especialidad</button>
            <button class="rm-button rm-button-outline" type="button" wire:click="closeSpecialtyModal">Cancelar</button>
        </div>
    </section>
@endif

@if ($showAssignSpecialtyModal)
    <div class="rm-modal-backdrop" wire:click="closeAssignSpecialtyModal"></div>
    <section class="rm-modal-panel rm-modal-panel-wide rm-clinical-editor" role="dialog" aria-modal="true">
        <div class="rm-modal-title">
            <div>
                <span>Profesionales</span>
                <h2>Asignar especialidades</h2>
            </div>
            <button type="button" wire:click="closeAssignSpecialtyModal">x</button>
        </div>

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

        <div class="rm-form-actions">
            <button class="rm-button rm-button-primary" type="button" wire:click="assignSpecialties">Guardar especialidades</button>
            <button class="rm-button rm-button-outline" type="button" wire:click="closeAssignSpecialtyModal">Cancelar</button>
        </div>
    </section>
@endif

@if ($showPatientAccessModal)
    <div class="rm-modal-backdrop" wire:click="closePatientAccessModal"></div>
    <section class="rm-modal-panel rm-modal-panel-xl rm-clinical-editor" role="dialog" aria-modal="true">
        <div class="rm-modal-title">
            <div>
                <span>Acceso por paciente</span>
                <h2>Autorizar historial</h2>
            </div>
            <button type="button" wire:click="closePatientAccessModal">x</button>
        </div>

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

        <div class="rm-form-actions">
            <button class="rm-button rm-button-primary" type="button" wire:click="grantPatientAccess">Guardar acceso</button>
            <button class="rm-button rm-button-outline" type="button" wire:click="closePatientAccessModal">Cancelar</button>
        </div>
    </section>
@endif

@if ($confirmingRecordDeleteId || $confirmingTemplateDeleteId)
    <div class="rm-modal-backdrop" wire:click="cancelClinicalDelete"></div>
    <section class="rm-modal-panel rm-modal-panel-small" role="dialog" aria-modal="true">
        <div class="rm-modal-title">
            <div>
                <span>Confirmar eliminacion</span>
                <h2>Deseas eliminar este registro?</h2>
            </div>
            <button type="button" wire:click="cancelClinicalDelete">x</button>
        </div>
        <p class="rm-delete-copy">Esta accion quedara registrada en la bitacora del sistema.</p>
        <div class="rm-form-actions">
            @if ($confirmingRecordDeleteId)
                <button class="rm-button rm-button-danger" type="button" wire:click="deleteRecordConfirmed">Si, eliminar ficha</button>
            @else
                <button class="rm-button rm-button-danger" type="button" wire:click="deleteTemplateConfirmed">Si, eliminar plantilla</button>
            @endif
            <button class="rm-button rm-button-outline" type="button" wire:click="cancelClinicalDelete">Cancelar</button>
        </div>
    </section>
@endif
