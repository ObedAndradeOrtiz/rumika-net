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
                            <input type="radio" wire:model.live="bookingTemplate" value="{{ $key }}">
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
                        <select wire:model.live="bookingMode">
                            <option value="general">Un solo enlace para todo</option>
                            <option value="branch">Tambien QR por sucursal</option>
                        </select>
                        @error('bookingMode') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>Etiqueta superior</span>
                        <input type="text" wire:model.live.debounce.300ms="bookingHeroLabel" placeholder="Reserva online">
                        @error('bookingHeroLabel') <small>{{ $message }}</small> @enderror
                    </label>
                    <label class="rm-field">
                        <span>Boton principal</span>
                        <input type="text" wire:model.live.debounce.300ms="bookingButtonLabel" placeholder="Agendar cita">
                        @error('bookingButtonLabel') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <label class="rm-field">
                    <span>Titulo</span>
                    <input type="text" wire:model.live.debounce.300ms="bookingTitle" placeholder="Agenda tu cita">
                    @error('bookingTitle') <small>{{ $message }}</small> @enderror
                </label>
                <label class="rm-field">
                    <span>Texto corto</span>
                    <textarea wire:model.live.debounce.300ms="bookingSubtitle" rows="3" placeholder="Elige tu sucursal, tratamiento y horario disponible."></textarea>
                    @error('bookingSubtitle') <small>{{ $message }}</small> @enderror
                </label>
                <label class="rm-field">
                    <span>Mensaje al agendar</span>
                    <textarea wire:model.live.debounce.300ms="bookingSuccessMessage" rows="3" placeholder="Tu cita fue agendada correctamente."></textarea>
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
                        <input type="color" wire:model.live="bookingPrimaryColor">
                    </label>
                    <label class="rm-field">
                        <span>Color suave</span>
                        <input type="color" wire:model.live="bookingAccentColor">
                    </label>
                    <label class="rm-field">
                        <span>Fondo</span>
                        <input type="color" wire:model.live="bookingBackgroundColor">
                    </label>
                    <label class="rm-field">
                        <span>Letra</span>
                        <select wire:model.live="bookingFontFamily">
                            <option value="Figtree">Figtree</option>
                            <option value="Inter">Inter</option>
                            <option value="Nunito">Nunito</option>
                            <option value="Poppins">Poppins</option>
                        </select>
                    </label>
                    <label class="rm-field">
                        <span>Forma del logo</span>
                        <select wire:model.live="bookingIconShape">
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
                    <span>Promocion</span>
                    <h2>Imagen cuadrada para el enlace</h2>
                    <p>Sube una imagen, muevela dentro del cuadro y ajusta el zoom. Rumika la guarda cuadrada para que se vea bien en celular.</p>
                </div>
                <div class="rm-booking-promo-editor">
                    <div data-avatar-cropper data-avatar-crop-size="500">
                        <label class="rm-field">
                            <span>Foto promocional 500 x 500</span>
                            <input type="file" accept="image/*" data-avatar-crop-input>
                        </label>
                        <div class="rm-avatar-crop-tool rm-booking-promo-crop" data-avatar-crop-tool wire:ignore hidden>
                            <div class="rm-avatar-crop-stage" data-avatar-crop-stage>
                                <img alt="Vista previa promocional" data-avatar-crop-image>
                            </div>
                            <label class="rm-field">
                                <span>Zoom</span>
                                <input type="range" min="1" max="2.6" step="0.05" value="1" data-avatar-crop-zoom>
                            </label>
                            <textarea wire:model.live="bookingPromotionalImageCropped" data-avatar-crop-output hidden></textarea>
                        </div>
                    </div>
                    @if ($currentBookingPromotionalImagePath)
                        <div class="rm-booking-current-promo">
                            <img src="{{ asset('storage/'.$currentBookingPromotionalImagePath) }}" alt="Promocion actual">
                            <span>Imagen actual</span>
                        </div>
                    @endif
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
                        <input type="time" wire:model.live="bookingAvailableFrom">
                    </label>
                    <label class="rm-field">
                        <span>Hasta</span>
                        <input type="time" wire:model.live="bookingAvailableTo">
                    </label>
                    <label class="rm-field">
                        <span>Intervalo</span>
                        <select wire:model.live="bookingSlotIntervalMinutes">
                            <option value="30">Cada 30 minutos</option>
                            <option value="60">Cada 1 hora</option>
                        </select>
                    </label>
                    <label class="rm-field">
                        <span>Maximo por horario</span>
                        <input type="number" min="1" max="50" wire:model.live.debounce.300ms="bookingMaxAppointmentsPerSlot">
                        @error('bookingMaxAppointmentsPerSlot') <small>{{ $message }}</small> @enderror
                    </label>
                    <label class="rm-field">
                        <span>Duracion base</span>
                        <input type="number" min="10" max="480" wire:model.live.debounce.300ms="bookingDefaultDurationMinutes">
                    </label>
                    <label class="rm-field">
                        <span>Dias minimo</span>
                        <input type="number" min="0" max="365" wire:model.live.debounce.300ms="bookingMinDaysAhead">
                    </label>
                    <label class="rm-field">
                        <span>Dias maximo</span>
                        <input type="number" min="1" max="365" wire:model.live.debounce.300ms="bookingMaxDaysAhead">
                        @error('bookingMaxDaysAhead') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <div class="rm-booking-switch-grid">
                    <label class="rm-checkline"><input type="checkbox" wire:model.live="bookingShowPrices"><span>Mostrar precios</span></label>
                    <label class="rm-checkline"><input type="checkbox" wire:model.live="bookingShowServiceDuration"><span>Mostrar duracion</span></label>
                    <label class="rm-checkline"><input type="checkbox" wire:model.live="bookingShowCompanyLogo"><span>Mostrar logo</span></label>
                    <label class="rm-checkline"><input type="checkbox" wire:model.live="bookingRequireIdentity"><span>CI/NIT obligatorio</span></label>
                    <label class="rm-checkline"><input type="checkbox" wire:model.live="bookingRequireEmail"><span>Email obligatorio</span></label>
                    <label class="rm-checkline"><input type="checkbox" wire:model.live="bookingIsActive"><span>Enlace activo</span></label>
                </div>
            </article>

            <article class="rm-booking-editor-panel">
                <div class="rm-booking-editor-heading">
                    <span>Tratamientos</span>
                    <h2>Servicios visibles en la reserva</h2>
                    <p>Publica todos tus servicios o elige solo los que quieres mostrar. Puedes marcar promociones y colocar un precio especial para el enlace.</p>
                </div>
                <label class="rm-checkline">
                    <input type="checkbox" wire:model.live="bookingPublishAllServices">
                    <span>Mostrar todos los tratamientos activos</span>
                </label>
                <div class="rm-booking-service-editor">
                    @forelse ($servicesForEditor as $service)
                        @php
                            $serviceKey = (string) $service->id;
                            $isVisible = $bookingPublishAllServices || in_array($serviceKey, array_map('strval', $selectedServiceIds), true);
                        @endphp
                        <article class="{{ $isVisible ? 'is-visible' : '' }}">
                            <label class="rm-checkline">
                                <input type="checkbox" value="{{ $service->id }}" wire:model.live="selectedServiceIds" @disabled($bookingPublishAllServices)>
                                <span>{{ $service->name }}</span>
                            </label>
                            <small>
                                {{ $service->duration_minutes ? $service->duration_minutes.' min' : 'Sin duracion' }}
                                - Bs {{ number_format((float) $service->price, 2) }}
                            </small>
                            <div>
                                <label class="rm-checkline">
                                    <input type="checkbox" value="{{ $service->id }}" wire:model.live="promotedServiceIds" @disabled(! $isVisible)>
                                    <span>Promocion</span>
                                </label>
                                <input type="number" min="0" step="0.01" wire:model.live.debounce.300ms="promotionalPrices.{{ $service->id }}" placeholder="Precio promo" @disabled(! $isVisible)>
                            </div>
                        </article>
                    @empty
                        <div class="rm-booking-empty">No hay tratamientos activos o disponibles. Revisa Configuracion > Servicios.</div>
                    @endforelse
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

                <div class="rm-booking-preview-branch-card">
                    <small>Sucursal</small>
                    <strong>{{ $company->branches->first()?->name ?? 'Sucursal principal' }}</strong>
                </div>

                @if ($bookingPromotionalImageCropped || $currentBookingPromotionalImagePath)
                    <img class="rm-booking-preview-promo-image" src="{{ $bookingPromotionalImageCropped ?: asset('storage/'.$currentBookingPromotionalImagePath) }}" alt="Promocion">
                @endif

                <div class="rm-booking-preview-services">
                    <small>Tratamientos visibles</small>
                    @forelse ($previewServices as $service)
                        <b>
                            <span>{{ $service->name }}</span>
                            <em>
                                @php
                                    $promoPrice = $promotionalPrices[(string) $service->id] ?? null;
                                @endphp
                                @if ($bookingShowServiceDuration && $service->duration_minutes)
                                    {{ $service->duration_minutes }} min
                                @endif
                                @if ($bookingShowPrices)
                                    Bs {{ number_format((float) ($promoPrice !== '' && $promoPrice !== null ? $promoPrice : $service->price), 2) }}
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
