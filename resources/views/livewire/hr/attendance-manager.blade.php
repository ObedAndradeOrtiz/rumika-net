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
            <article class="is-warning">
                <span>Faltas sin registro</span>
                <strong>{{ $summary['missing'] }}</strong>
            </article>
        </div>
    </section>

    <div class="rm-hr-grid">
        <section class="rm-panel">
            <div class="rm-panel-title">
                <div>
                    <h2>Resumen por personal</h2>
                    <p>Los dias sin registro no cuentan domingos. Si no hay horario configurado, Rumika usa lunes a sabado.</p>
                </div>
            </div>

            <div class="rm-hr-staff-list">
                @forelse ($rows as $row)
                    <article class="rm-hr-staff-row">
                        <div>
                            <strong>{{ $row['user']->name }}</strong>
                            <span>{{ $row['user']->email }}</span>
                            @if ($row['missing_count'] > 0)
                                <small>Sin registro: {{ $row['missing_dates'] }}</small>
                            @endif
                        </div>
                        <div class="rm-hr-staff-metrics">
                            <span><b>{{ $row['present_count'] }}</b> entradas</span>
                            <span><b>{{ $row['completed_count'] }}</b> completas</span>
                            <span class="{{ $row['missing_count'] > 0 ? 'is-danger' : '' }}"><b>{{ $row['missing_count'] }}</b> faltas</span>
                        </div>
                        <button class="rm-button rm-button-outline" type="button"
                            wire:click="$set('scheduleUserId', {{ $row['user']->id }})">
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

        <section class="rm-panel">
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
        </section>
    </div>

    <section class="rm-panel">
        <div class="rm-panel-title">
            <div>
                <h2>Ultimas marcaciones</h2>
                <p>Incluye sucursal, distancia, parecido facial y estado de salida.</p>
            </div>
        </div>

        <div class="rm-hr-record-table">
            <div class="rm-hr-record-head">
                <span>Fecha</span>
                <span>Personal</span>
                <span>Entrada</span>
                <span>Salida</span>
                <span>Validacion</span>
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
                    <span>
                        Entrada {{ $record->check_in_face_similarity ?? '-' }}%
                        <small>Salida {{ $record->check_out_face_similarity ?? '-' }}%</small>
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
</div>
