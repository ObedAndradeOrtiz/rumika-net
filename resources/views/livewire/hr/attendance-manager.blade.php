<div class="rm-content rm-hr-page">
    <div class="rm-settings-hero rm-hr-hero">
        <div>
            <span>Gestion de recursos humanos</span>
            <h1>Asistencia del personal</h1>
            <p>Controla entrada, salida, horarios y faltas con validacion facial y radio por sucursal.</p>
        </div>
        <div class="rm-hr-hero-action">
            <livewire:hr.attendance-punch />
        </div>
    </div>

    <section class="rm-panel rm-hr-filters">
        <div class="rm-panel-title rm-panel-title-compact">
            <div>
                <h2>Filtro de asistencia</h2>
                <p>Revisa marcaciones por rango de fechas y por trabajador.</p>
            </div>
            <button class="rm-button rm-button-outline" type="button" wire:click="resetDateFilter">Este mes</button>
        </div>

        <div class="rm-form-row">
            <label class="rm-field">
                <span>Desde</span>
                <input type="date" wire:model.live="fromDate">
            </label>
            <label class="rm-field">
                <span>Hasta</span>
                <input type="date" wire:model.live="toDate">
            </label>
            <label class="rm-field">
                <span>Personal</span>
                <select wire:model.live="userFilter">
                    <option value="">Todo el personal</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="rm-hr-summary">
            <article>
                <span>Entradas</span>
                <strong>{{ $summary['present'] }}</strong>
            </article>
            <article>
                <span>Salidas completas</span>
                <strong>{{ $summary['completed'] }}</strong>
            </article>
            <article>
                <span>Salida pendiente</span>
                <strong>{{ $summary['open'] }}</strong>
            </article>
            <article class="is-late">
                <span>Retrasos</span>
                <strong>{{ $summary['late'] }}</strong>
            </article>
            <article class="is-warning">
                <span>Faltas sin registro</span>
                <strong>{{ $summary['missing'] }}</strong>
            </article>
        </div>
    </section>

    <div class="rm-hr-tabs" role="tablist" aria-label="Secciones de asistencia">
        <button class="{{ $activeTab === 'users' ? 'is-active' : '' }}" type="button" wire:click="setActiveTab('users')">
            Usuarios
        </button>
        <button class="{{ $activeTab === 'schedule' ? 'is-active' : '' }}" type="button" wire:click="setActiveTab('schedule')">
            Horarios
        </button>
        <button class="{{ $activeTab === 'history' ? 'is-active' : '' }}" type="button" wire:click="setActiveTab('history')">
            Historial de marcaciones
        </button>
    </div>

    @if ($activeTab === 'users')
        <section class="rm-panel rm-hr-tab-panel">
            <div class="rm-panel-title">
                <div>
                    <h2>Usuarios con asistencia</h2>
                    <p>Solo aparecen usuarios que tienen activo el control de asistencia.</p>
                </div>
            </div>

            <div class="rm-hr-staff-list">
                @forelse ($rows as $row)
                    <article class="rm-hr-staff-row">
                        <div class="rm-hr-staff-person">
                            <span class="rm-hr-staff-face">
                                @if ($row['avatar_url'])
                                    <img src="{{ $row['avatar_url'] }}" alt="{{ $row['user']->name }}">
                                @else
                                    {{ strtoupper(mb_substr($row['user']->name, 0, 1)) }}
                                @endif
                            </span>
                            <div>
                                <strong>{{ $row['user']->name }}</strong>
                                <span>{{ $row['user']->email }}</span>
                                @if ($row['missing_count'] > 0)
                                    <small>Sin registro: {{ $row['missing_dates'] }}</small>
                                @endif
                            </div>
                        </div>
                        <div class="rm-hr-staff-metrics">
                            <span><b>{{ $row['present_count'] }}</b> entradas</span>
                            <span><b>{{ $row['completed_count'] }}</b> completas</span>
                            <span class="{{ $row['late_count'] > 0 ? 'is-late' : '' }}"><b>{{ $row['late_count'] }}</b> retrasos</span>
                            <span class="{{ $row['missing_count'] > 0 ? 'is-danger' : '' }}"><b>{{ $row['missing_count'] }}</b> faltas</span>
                            @if ($row['late_minutes'] > 0)
                                <small>Retraso acumulado: {{ $row['late_minutes'] }} min</small>
                            @endif
                        </div>
                        <button class="rm-button rm-button-outline" type="button"
                            wire:click="editScheduleFor({{ $row['user']->id }})">
                            Horario
                        </button>
                    </article>
                @empty
                    <div class="rm-empty-state">
                        <strong>Sin personal</strong>
                        <span>Aun no hay usuarios para controlar asistencia.</span>
                    </div>
                @endforelse
            </div>
        </section>
    @endif

    @if ($activeTab === 'schedule')
        <section class="rm-panel rm-hr-tab-panel">
            <div class="rm-panel-title">
                <div>
                    <h2>Horario laboral</h2>
                    <p>Configura los dias y la sucursal esperada por trabajador.</p>
                </div>
            </div>

            <form wire:submit="saveSchedule" class="rm-form-stack rm-hr-schedule-form">
                <label class="rm-field">
                    <span>Personal</span>
                    <select wire:model.live="scheduleUserId">
                        <option value="">Seleccionar personal</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('scheduleUserId') <small>{{ $message }}</small> @enderror
                </label>

                <div class="rm-hr-week-list">
                    @foreach ($weekdays as $weekday => $label)
                        <article class="rm-hr-week-row">
                            <label class="rm-checkline">
                                <input type="checkbox" wire:model="scheduleForm.{{ $weekday }}.is_working_day">
                                <span>{{ $label }}</span>
                            </label>
                            <div>
                                <input type="time" wire:model="scheduleForm.{{ $weekday }}.starts_at">
                                <input type="time" wire:model="scheduleForm.{{ $weekday }}.ends_at">
                            </div>
                            <select wire:model="scheduleForm.{{ $weekday }}.branch_id">
                                <option value="">Sucursal libre</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </article>
                    @endforeach
                </div>

                <button class="rm-button rm-button-primary" type="submit">Guardar horario</button>
            </form>

            <section class="rm-hr-exemption-panel">
                <div class="rm-panel-title rm-panel-title-compact">
                    <div>
                        <h2>Feriados y exoneraciones</h2>
                        <p>Un feriado o dia exonerado no cuenta como falta ni como retraso.</p>
                    </div>
                </div>

                <form wire:submit="saveExemption" class="rm-hr-exemption-form">
                    <label class="rm-field">
                        <span>Fecha</span>
                        <input type="date" wire:model="exemptionDate">
                        @error('exemptionDate') <small>{{ $message }}</small> @enderror
                    </label>
                    <label class="rm-field">
                        <span>Tipo</span>
                        <select wire:model="exemptionType">
                            <option value="holiday">Feriado</option>
                            <option value="excused">Exonerado</option>
                        </select>
                        @error('exemptionType') <small>{{ $message }}</small> @enderror
                    </label>
                    <label class="rm-field">
                        <span>Sucursal</span>
                        <select wire:model="exemptionBranchId">
                            <option value="">Todas las sucursales</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('exemptionBranchId') <small>{{ $message }}</small> @enderror
                    </label>
                    <label class="rm-field">
                        <span>Personal</span>
                        <select wire:model="exemptionUserId">
                            <option value="">Todo el personal</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                        @error('exemptionUserId') <small>{{ $message }}</small> @enderror
                    </label>
                    <label class="rm-field">
                        <span>Motivo</span>
                        <input type="text" wire:model="exemptionReason" placeholder="Feriado local, permiso, viaje...">
                        @error('exemptionReason') <small>{{ $message }}</small> @enderror
                    </label>
                    <button class="rm-button rm-button-primary" type="submit">Guardar exoneracion</button>
                </form>

                <div class="rm-hr-exemption-list">
                    @forelse ($exemptions as $exemption)
                        <article class="rm-hr-exemption-row">
                            <div>
                                <strong>{{ $exemption->work_date?->format('d/m/Y') }} - {{ $exemption->type === 'holiday' ? 'Feriado' : 'Exonerado' }}</strong>
                                <span>
                                    {{ $exemption->branch?->name ?? 'Todas las sucursales' }}
                                    - {{ $exemption->user?->name ?? 'Todo el personal' }}
                                </span>
                                @if ($exemption->reason)
                                    <small>{{ $exemption->reason }}</small>
                                @endif
                            </div>
                            <button class="rm-button rm-button-danger rm-button-small" type="button"
                                wire:click="deleteExemption({{ $exemption->id }})"
                                wire:confirm="Deseas eliminar esta exoneracion?">
                                Eliminar
                            </button>
                        </article>
                    @empty
                        <div class="rm-empty-state">
                            <strong>Sin feriados registrados</strong>
                            <span>Agrega feriados o permisos para que no cuenten como faltas.</span>
                        </div>
                    @endforelse
                </div>
            </section>
        </section>
    @endif

    @if ($activeTab === 'history')
        <section class="rm-panel rm-hr-tab-panel">
            <div class="rm-panel-title">
                <div>
                    <h2>Historial de marcaciones</h2>
                    <p>Incluye sucursal, distancia, parecido facial y estado de salida.</p>
                </div>
            </div>

            <div class="rm-hr-record-table">
                <div class="rm-hr-record-head">
                    <span>Fecha</span>
                    <span>Personal</span>
                    <span>Entrada</span>
                    <span>Salida</span>
                    <span>Retraso</span>
                    <span>Validacion</span>
                    <span>Accion</span>
                </div>
                @forelse ($records as $record)
                    <article class="rm-hr-record-row">
                        <span>{{ $record->work_date?->format('d/m/Y') }}</span>
                        <strong>{{ $record->user?->name ?? 'Sin usuario' }}</strong>
                        <span>
                            {{ $record->check_in_at?->format('H:i') ?? '-' }}
                            <small>{{ $record->checkInBranch?->name ?? 'Sin sucursal' }} - {{ $record->check_in_distance_meters ?? 0 }} m</small>
                        </span>
                        <span>
                            {{ $record->check_out_at?->format('H:i') ?? 'Pendiente' }}
                            <small>{{ $record->checkOutBranch?->name ?? '-' }}</small>
                        </span>
                        <span class="{{ ($record->late_minutes ?? 0) > 0 ? 'is-late' : '' }}">
                            {{ ($record->late_minutes ?? 0) > 0 ? ($record->late_minutes.' min') : '-' }}
                        </span>
                        <span>
                            Entrada {{ $record->check_in_face_similarity ?? '-' }}%
                            <small>Salida {{ $record->check_out_face_similarity ?? '-' }}%</small>
                        </span>
                        <span>
                            <button class="rm-button rm-button-danger rm-button-small" type="button"
                                wire:click="deleteRecord({{ $record->id }})"
                                wire:confirm="Esta accion eliminara la validacion de asistencia y sus fotos. Deseas continuar?">
                                Eliminar validacion
                            </button>
                        </span>
                    </article>
                @empty
                    <div class="rm-empty-state">
                        <strong>Sin marcaciones</strong>
                        <span>No hay registros en el rango seleccionado.</span>
                    </div>
                @endforelse
            </div>
        </section>
    @endif
</div>
