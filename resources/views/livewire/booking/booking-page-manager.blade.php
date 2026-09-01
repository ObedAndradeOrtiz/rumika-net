<div class="rm-booking-editor-page">
    <section class="rm-booking-editor-hero">
        <div>
            <span>Reservas online</span>
            <h1>Editor del enlace de agenda</h1>
            <p>Personaliza la pagina publica, revisa los tratamientos visibles y descarga QR para compartir con tus clientes.</p>
        </div>
        <div class="rm-booking-editor-status">
            <strong>{{ $bookingIsActive ? 'Publicado' : 'Pausado' }}</strong>
            <small>{{ $bookingGeneralUrl ?: 'Configura el enlace' }}</small>
        </div>
    </section>

    <form wire:submit="saveBookingPage" class="rm-booking-editor-layout">
        <section class="rm-booking-editor-main">
            <article class="rm-booking-editor-panel">
                <div class="rm-booking-editor-heading">
                    <span>Plantilla</span>
                    <h2>Elige una base visual</h2>
                </div>
                <div class="rm-booking-template-list rm-booking-template-list-large">
                    @foreach ($bookingTemplates as $key => $template)
                        <label class="{{ $bookingTemplate === $key ? 'is-selected' : '' }}">
                            <input type="radio" wire:model="bookingTemplate" value="{{ $key }}">
                            <strong>{{ $template['name'] }}</strong>
                            <span>{{ $template['description'] }}</span>
                            <button type="button" wire:click="applyTemplate('{{ $key }}')">Aplicar estilo</button>
                        </label>
                    @endforeach
                </div>
            </article>

            <article class="rm-booking-editor-panel">
                <div class="rm-booking-editor-heading">
                    <span>Contenido</span>
                    <h2>Textos y enlace publico</h2>
                </div>
                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>Enlace unico</span>
                        <input type="text" wire:model.live.debounce.500ms="bookingSlug" placeholder="central-bethel">
                        @if ($bookingSlugAvailable === true)
                            <small class="rm-field-success">Disponible: {{ $bookingGeneralUrl }}</small>
                        @elseif ($bookingSlugAvailable === false)
                            <small>Ese enlace no esta disponible.</small>
                        @endif
                        @error('bookingSlug') <small>{{ $message }}</small> @enderror
                    </label>
                    <label class="rm-field">
                        <span>Modo de enlace</span>
                        <select wire:model="bookingMode">
                            <option value="general">Un solo enlace para todo</option>
                            <option value="branch">Tambien QR por sucursal</option>
                        </select>
                        @error('bookingMode') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>Etiqueta superior</span>
                        <input type="text" wire:model="bookingHeroLabel" placeholder="Reserva online">
                        @error('bookingHeroLabel') <small>{{ $message }}</small> @enderror
                    </label>
                    <label class="rm-field">
                        <span>Boton principal</span>
                        <input type="text" wire:model="bookingButtonLabel" placeholder="Agendar cita">
                        @error('bookingButtonLabel') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <label class="rm-field">
                    <span>Titulo</span>
                    <input type="text" wire:model="bookingTitle" placeholder="Agenda tu cita">
                    @error('bookingTitle') <small>{{ $message }}</small> @enderror
                </label>
                <label class="rm-field">
                    <span>Texto corto</span>
                    <textarea wire:model="bookingSubtitle" rows="3" placeholder="Elige tu sucursal, tratamiento y horario disponible."></textarea>
                    @error('bookingSubtitle') <small>{{ $message }}</small> @enderror
                </label>
                <label class="rm-field">
                    <span>Mensaje al agendar</span>
                    <textarea wire:model="bookingSuccessMessage" rows="3" placeholder="Tu cita fue agendada correctamente."></textarea>
                    @error('bookingSuccessMessage') <small>{{ $message }}</small> @enderror
                </label>
            </article>

            <article class="rm-booking-editor-panel">
                <div class="rm-booking-editor-heading">
                    <span>Diseno</span>
                    <h2>Colores, logo y forma</h2>
                </div>
                <div class="rm-booking-style-grid rm-booking-style-grid-expanded">
                    <label class="rm-field">
                        <span>Color principal</span>
                        <input type="color" wire:model="bookingPrimaryColor">
                    </label>
                    <label class="rm-field">
                        <span>Color suave</span>
                        <input type="color" wire:model="bookingAccentColor">
                    </label>
                    <label class="rm-field">
                        <span>Fondo</span>
                        <input type="color" wire:model="bookingBackgroundColor">
                    </label>
                    <label class="rm-field">
                        <span>Letra</span>
                        <select wire:model="bookingFontFamily">
                            <option value="Figtree">Figtree</option>
                            <option value="Inter">Inter</option>
                            <option value="Nunito">Nunito</option>
                            <option value="Poppins">Poppins</option>
                        </select>
                    </label>
                    <label class="rm-field">
                        <span>Forma del logo</span>
                        <select wire:model="bookingIconShape">
                            <option value="rounded">Redondeado</option>
                            <option value="circle">Circular</option>
                            <option value="soft">Suave</option>
                        </select>
                    </label>
                    <label class="rm-field">
                        <span>Imagen de fondo</span>
                        <input type="file" wire:model="bookingBackgroundImage" accept="image/*">
                        @if ($currentBookingBackgroundPath)
                            <small>Imagen cargada actualmente.</small>
                        @endif
                        @error('bookingBackgroundImage') <small>{{ $message }}</small> @enderror
                    </label>
                </div>
            </article>

            <article class="rm-booking-editor-panel">
                <div class="rm-booking-editor-heading">
                    <span>Reglas</span>
                    <h2>Campos, agenda y visibilidad</h2>
                </div>
                <div class="rm-booking-style-grid">
                    <label class="rm-field">
                        <span>Desde</span>
                        <input type="time" wire:model="bookingAvailableFrom">
                    </label>
                    <label class="rm-field">
                        <span>Hasta</span>
                        <input type="time" wire:model="bookingAvailableTo">
                    </label>
                    <label class="rm-field">
                        <span>Intervalo</span>
                        <input type="number" min="10" max="120" wire:model="bookingSlotIntervalMinutes">
                    </label>
                    <label class="rm-field">
                        <span>Duracion base</span>
                        <input type="number" min="10" max="480" wire:model="bookingDefaultDurationMinutes">
                    </label>
                    <label class="rm-field">
                        <span>Dias minimo</span>
                        <input type="number" min="0" max="365" wire:model="bookingMinDaysAhead">
                    </label>
                    <label class="rm-field">
                        <span>Dias maximo</span>
                        <input type="number" min="1" max="365" wire:model="bookingMaxDaysAhead">
                        @error('bookingMaxDaysAhead') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <div class="rm-booking-switch-grid">
                    <label class="rm-checkline"><input type="checkbox" wire:model="bookingShowPrices"><span>Mostrar precios</span></label>
                    <label class="rm-checkline"><input type="checkbox" wire:model="bookingShowServiceDuration"><span>Mostrar duracion</span></label>
                    <label class="rm-checkline"><input type="checkbox" wire:model="bookingShowBranchCards"><span>Mostrar tarjetas de sucursal</span></label>
                    <label class="rm-checkline"><input type="checkbox" wire:model="bookingShowCompanyLogo"><span>Mostrar logo</span></label>
                    <label class="rm-checkline"><input type="checkbox" wire:model="bookingRequireIdentity"><span>CI/NIT obligatorio</span></label>
                    <label class="rm-checkline"><input type="checkbox" wire:model="bookingRequireEmail"><span>Email obligatorio</span></label>
                    <label class="rm-checkline"><input type="checkbox" wire:model="bookingIsActive"><span>Enlace activo</span></label>
                </div>
            </article>
        </section>

        <aside class="rm-booking-editor-preview">
            <div class="rm-booking-editor-actions">
                <button class="rm-button rm-button-primary" type="submit">Guardar editor</button>
                @if ($bookingGeneralUrl)
                    <a class="rm-button rm-button-outline" href="{{ $bookingGeneralUrl }}" target="_blank" rel="noopener">Ver pagina</a>
                @endif
            </div>

            <div class="rm-booking-phone-preview rm-booking-phone-preview-rich"
                style="--booking-primary: {{ $bookingPrimaryColor }}; --booking-accent: {{ $bookingAccentColor }}; --booking-bg: {{ $bookingBackgroundColor }}; --booking-font: {{ $bookingFontFamily }}, Figtree, sans-serif;">
                <div class="rm-booking-preview-head">
                    @if ($bookingShowCompanyLogo)
                        <span class="rm-booking-preview-logo">
                            <img src="{{ $company->logo_path ? asset('storage/'.$company->logo_path) : asset('rumika-favicon.svg') }}" alt="{{ $company->name }}">
                        </span>
                    @endif
                    <div>
                        <small>{{ $bookingHeroLabel ?: 'Reserva online' }}</small>
                        <h3>{{ $bookingTitle ?: 'Agenda tu cita' }}</h3>
                    </div>
                </div>
                <p>{{ $bookingSubtitle ?: 'Elige tratamiento y horario disponible.' }}</p>

                @if ($bookingShowBranchCards)
                    <div class="rm-booking-preview-branch-card">
                        <small>Sucursal</small>
                        <strong>{{ $company->branches->first()?->name ?? 'Sucursal principal' }}</strong>
                    </div>
                @endif

                <div class="rm-booking-preview-services">
                    <small>Tratamientos visibles</small>
                    @forelse ($previewServices as $service)
                        <b>
                            <span>{{ $service->name }}</span>
                            <em>
                                @if ($bookingShowServiceDuration && $service->duration_minutes)
                                    {{ $service->duration_minutes }} min
                                @endif
                                @if ($bookingShowPrices)
                                    Bs {{ number_format((float) $service->price, 2) }}
                                @endif
                            </em>
                        </b>
                    @empty
                        <b><span>No hay tratamientos activos aun.</span></b>
                    @endforelse
                </div>

                <div class="rm-booking-preview-slots">
                    <b>09:00</b><b>09:30</b><b>10:00</b>
                </div>
                <button type="button">{{ $bookingButtonLabel ?: 'Agendar cita' }}</button>
            </div>

            @if ($bookingGeneralUrl)
                <div class="rm-booking-qr-card" data-booking-qr data-qr-url="{{ $bookingGeneralUrl }}" data-qr-name="{{ $company->name }}" data-qr-logo="{{ $company->logo_path ? asset('storage/'.$company->logo_path) : asset('rumika-favicon.svg') }}">
                    <canvas width="220" height="220"></canvas>
                    <strong>QR general</strong>
                    <a href="{{ $bookingGeneralUrl }}" target="_blank" rel="noopener">{{ $bookingGeneralUrl }}</a>
                    <button class="rm-button rm-button-outline" type="button" data-qr-download>Descargar QR</button>
                </div>
            @endif

            @if ($bookingMode === 'branch')
                <div class="rm-booking-branch-links">
                    @foreach ($bookingBranchLinks as $link)
                        <div data-booking-qr data-qr-url="{{ $link['url'] }}" data-qr-name="{{ $link['name'] }}" data-qr-logo="{{ $company->logo_path ? asset('storage/'.$company->logo_path) : asset('rumika-favicon.svg') }}">
                            <canvas width="160" height="160"></canvas>
                            <span>{{ $link['name'] }}</span>
                            <button class="rm-button rm-button-outline" type="button" data-qr-download>QR</button>
                        </div>
                    @endforeach
                </div>
            @endif
        </aside>
    </form>
</div>
