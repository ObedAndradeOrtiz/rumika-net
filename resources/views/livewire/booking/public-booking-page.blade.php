@php
    $logo = $company->logo_path ? asset('storage/'.$company->logo_path) : asset('rumika-favicon.svg');
    $backgroundImage = $page->background_image_path ? asset('storage/'.$page->background_image_path) : null;
@endphp

<main class="rm-public-booking rm-booking-template-{{ $page->template }} rm-booking-shape-{{ $page->icon_shape }}"
    style="--booking-primary: {{ $page->primary_color }}; --booking-accent: {{ $page->accent_color }}; --booking-bg: {{ $page->background_color }}; --booking-font: {{ $page->font_family }}, Figtree, sans-serif; {{ $backgroundImage ? '--booking-image: url('.$backgroundImage.');' : '' }}">
    <section class="rm-booking-card">
        <header class="rm-booking-hero">
            @if ($page->show_company_logo)
            <span class="rm-booking-logo">
                <img src="{{ $logo }}" alt="{{ $company->name }}">
            </span>
            @endif
            <div>
                <small>{{ $page->hero_label ?: $company->name }}</small>
                <h1>{{ $page->title ?: 'Agenda tu cita' }}</h1>
                <p>{{ $page->subtitle ?: 'Elige tratamiento y horario disponible.' }}</p>
            </div>
        </header>

        @if ($booked)
            <div class="rm-booking-success">
                <span>Listo</span>
                <h2>Tu cita fue agendada</h2>
                <p>{{ $page->success_message ?: 'Tu cita fue agendada correctamente.' }}</p>
                <p>Te esperamos el {{ \Carbon\Carbon::parse($selectedDate.' '.$selectedTime)->format('d/m/Y H:i') }}. Si necesitas cambiarla, comunicate con la sucursal.</p>
                <strong>{{ $clientName ?: 'Cliente registrado' }}</strong>
            </div>
        @else
            <form wire:submit="book" class="rm-booking-form">
                <section class="rm-booking-step">
                    <span>1</span>
                    <div>
                        <h2>Tu numero</h2>
                        <p>Si ya eres cliente, Rumika recupera tus datos.</p>
                    </div>
                </section>

                <div class="rm-booking-phone">
                    <select wire:model="phoneCountry">
                        @foreach (\App\Support\PhoneNumber::countries() as $code => $country)
                            <option value="{{ $code }}">{{ $country['code'] }}</option>
                        @endforeach
                    </select>
                    <input type="tel" wire:model="phone" placeholder="Telefono">
                    <button type="button" wire:click="checkClient">Validar</button>
                </div>
                @error('phone') <small class="rm-booking-error">{{ $message }}</small> @enderror

                @if ($clientChecked)
                    <div class="rm-booking-client-state">
                        @if ($clientId)
                            <strong>Hola, {{ $clientName }}</strong>
                            <span>Ya encontramos tu ficha. Continua con tu reserva.</span>
                        @else
                            <strong>Primera vez por aqui</strong>
                            <span>Completa tus datos para crear tu ficha.</span>
                        @endif
                    </div>
                @endif

                @if ($clientChecked && ! $clientId)
                    <div class="rm-booking-grid">
                        <label>
                            <span>Nombre completo</span>
                            <input type="text" wire:model="clientName" placeholder="Tu nombre">
                            @error('clientName') <small>{{ $message }}</small> @enderror
                        </label>
                        <label>
                            <span>Edad</span>
                            <input type="number" wire:model="clientAge" min="1" max="120" placeholder="Opcional">
                            @error('clientAge') <small>{{ $message }}</small> @enderror
                        </label>
                        <label>
                            <span>CI / NIT{{ $page->require_identity ? '' : ' opcional' }}</span>
                            <input type="text" wire:model="clientIdentity" placeholder="{{ $page->require_identity ? 'Requerido' : 'Opcional' }}">
                            @error('clientIdentity') <small>{{ $message }}</small> @enderror
                        </label>
                        <label>
                            <span>Email{{ $page->require_email ? '' : ' opcional' }}</span>
                            <input type="email" wire:model="clientEmail" placeholder="{{ $page->require_email ? 'Requerido' : 'Opcional' }}">
                            @error('clientEmail') <small>{{ $message }}</small> @enderror
                        </label>
                    </div>
                @endif

                <section class="rm-booking-step">
                    <span>2</span>
                    <div>
                        <h2>Servicio y sucursal</h2>
                        <p>Selecciona donde y que deseas reservar.</p>
                    </div>
                </section>

                <div class="rm-booking-grid">
                    <label>
                        <span>Sucursal</span>
                        <select wire:model.live="selectedBranchId">
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('selectedBranchId') <small>{{ $message }}</small> @enderror
                    </label>
                    @if ($page->show_branch_cards && $branches->count() > 1)
                        <div class="rm-booking-branch-picker">
                            @foreach ($branches as $branch)
                                <button type="button" wire:click="$set('selectedBranchId', '{{ $branch->id }}')" class="{{ (string) $selectedBranchId === (string) $branch->id ? 'is-selected' : '' }}">
                                    <strong>{{ $branch->name }}</strong>
                                    <span>{{ $branch->address ?: 'Sucursal disponible' }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                    <label>
                        <span>Tratamiento</span>
                        <select wire:model.live="selectedServiceId">
                            <option value="">Seleccionar</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}">
                                    {{ $service->name }}{{ $page->show_service_duration && $service->duration_minutes ? ' - '.$service->duration_minutes.' min' : '' }}{{ $page->show_prices ? ' - Bs '.number_format((float) $service->price, 2) : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('selectedServiceId') <small>{{ $message }}</small> @enderror
                    </label>
                    <label>
                        <span>Fecha</span>
                        <input type="date" wire:model.live="selectedDate" min="{{ $minDate }}" max="{{ $maxDate }}">
                        @error('selectedDate') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <section class="rm-booking-step">
                    <span>3</span>
                    <div>
                        <h2>Horario disponible</h2>
                        <p>Los espacios ocupados no aparecen.</p>
                    </div>
                </section>

                <div class="rm-booking-slots">
                    @forelse ($slots as $slot)
                        <label class="{{ $selectedTime === $slot ? 'is-selected' : '' }}">
                            <input type="radio" wire:model="selectedTime" value="{{ $slot }}">
                            <span>{{ $slot }}</span>
                        </label>
                    @empty
                        <div class="rm-booking-empty">No hay horarios disponibles para esa fecha.</div>
                    @endforelse
                </div>
                @error('selectedTime') <small class="rm-booking-error">{{ $message }}</small> @enderror

                <button class="rm-booking-submit" type="submit" @disabled(! $clientChecked)>{{ $page->button_label ?: 'Agendar cita' }}</button>
            </form>
        @endif
    </section>
</main>
