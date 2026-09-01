<main class="rm-content rm-settings-page">
    <section class="rm-settings-hero rm-system-hero">
        <div class="rm-system-hero-copy">
            <span>Cuenta y plan</span>
            <h1>Mi sistema</h1>
            <p>Consulta el estado de tu empresa, fechas de pago, limites del plan y opciones disponibles.</p>
        </div>
        <div class="rm-system-status">
            <small>Plan actual</small>
            <strong>{{ $company->plan?->name ?? 'Free' }}</strong>
            <span>{{ $accessLabel }}</span>
        </div>
    </section>

    <section class="rm-kpi-strip rm-system-kpis">
        <article class="rm-kpi">
            <div>
                <strong>{{ $company->last_paid_at?->format('d/m/Y') ?? 'Sin pago' }}</strong>
                <span>Ultimo pago</span>
            </div>
        </article>
        <article class="rm-kpi">
            <div>
                <strong>{{ $company->next_payment_due_at?->format('d/m/Y') ?? 'Sin fecha' }}</strong>
                <span>Proximo vencimiento</span>
            </div>
        </article>
        <article class="rm-kpi">
            <div>
                <strong>{{ $company->billingPayments->count() }}</strong>
                <span>Pagos registrados</span>
            </div>
        </article>
        <article class="rm-kpi">
            <div>
                <strong>{{ ucfirst(str_replace('_', ' ', $company->billing_status ?: $company->status)) }}</strong>
                <span>Estado</span>
            </div>
        </article>
    </section>

    <section class="rm-panel rm-system-brand-panel">
        <div class="rm-panel-title">
            <div>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path d="M12 3 4 7v10l8 4 8-4V7l-8-4Z" />
                    <path d="M12 12 4 7M12 12l8-5M12 12v9" />
                </svg>
                <h2>Marca del cliente</h2>
            </div>
            <p>Personaliza el nombre y logo que vera tu equipo dentro del sistema.</p>
        </div>

        <form wire:submit="saveBrand" class="rm-system-brand-form">
            <div class="rm-system-brand-preview">
                <span class="rm-system-brand-logo">
                    @if ($companyLogo)
                        <img src="{{ $companyLogo->temporaryUrl() }}" alt="Logo temporal">
                    @elseif ($currentCompanyLogoPath)
                        <img src="{{ asset('storage/'.$currentCompanyLogoPath) }}" alt="{{ $companyName }}">
                    @else
                        <x-application-logo class="h-7 w-7 text-white" />
                    @endif
                </span>
                <div>
                    <strong>{{ $companyName ?: 'Nombre de la empresa' }}</strong>
                    <small>{{ $companyLegalName ?: 'Logo y nombre para el panel interno' }}</small>
                </div>
            </div>

            <div class="rm-system-brand-fields">
                <label class="rm-field">
                    <span>Nombre comercial</span>
                    <input type="text" wire:model="companyName" placeholder="Nombre visible en Rumika">
                    @error('companyName') <small>{{ $message }}</small> @enderror
                </label>
                <label class="rm-field">
                    <span>Razon social</span>
                    <input type="text" wire:model="companyLegalName" placeholder="Opcional">
                    @error('companyLegalName') <small>{{ $message }}</small> @enderror
                </label>
                <label class="rm-field">
                    <span>Logo</span>
                    <input type="file" wire:model="companyLogo" accept="image/*">
                    @error('companyLogo') <small>{{ $message }}</small> @enderror
                </label>
            </div>

            <button class="rm-button rm-button-primary" type="submit">Guardar marca</button>
        </form>
    </section>

    <section class="rm-panel rm-booking-admin-panel">
        <div class="rm-panel-title">
            <div>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <rect x="3" y="5" width="18" height="16" rx="3" />
                    <path d="M8 3v4M16 3v4M3 10h18" />
                </svg>
                <h2>Reservas online</h2>
            </div>
            <p>Crea un enlace publico para que tus clientes vean horarios disponibles y agenden desde el celular.</p>
        </div>

        <form wire:submit="saveBookingPage" class="rm-booking-admin-grid">
            <div class="rm-booking-admin-form">
                <div class="rm-booking-template-list">
                    @foreach ($bookingTemplates as $key => $template)
                        <label class="{{ $bookingTemplate === $key ? 'is-selected' : '' }}">
                            <input type="radio" wire:model="bookingTemplate" value="{{ $key }}">
                            <strong>{{ $template['name'] }}</strong>
                            <span>{{ $template['description'] }}</span>
                        </label>
                    @endforeach
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
                            <option value="branch">Enlaces por sucursal</option>
                        </select>
                        @error('bookingMode') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>Titulo</span>
                        <input type="text" wire:model="bookingTitle" placeholder="Agenda tu cita">
                        @error('bookingTitle') <small>{{ $message }}</small> @enderror
                    </label>
                    <label class="rm-field">
                        <span>Texto corto</span>
                        <input type="text" wire:model="bookingSubtitle" placeholder="Elige tratamiento y horario disponible">
                        @error('bookingSubtitle') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <div class="rm-booking-style-grid">
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
                        <span>Icono</span>
                        <select wire:model="bookingIconShape">
                            <option value="rounded">Redondeado</option>
                            <option value="circle">Circular</option>
                            <option value="soft">Suave</option>
                        </select>
                    </label>
                    <label class="rm-field">
                        <span>Imagen de fondo</span>
                        <input type="file" wire:model="bookingBackgroundImage" accept="image/*">
                        @error('bookingBackgroundImage') <small>{{ $message }}</small> @enderror
                    </label>
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
                    <label class="rm-checkline">
                        <input type="checkbox" wire:model="bookingShowPrices">
                        <span>Mostrar precios</span>
                    </label>
                    <label class="rm-checkline">
                        <input type="checkbox" wire:model="bookingIsActive">
                        <span>Enlace activo</span>
                    </label>
                </div>

                <button class="rm-button rm-button-primary" type="submit">Guardar reservas online</button>
            </div>

            <aside class="rm-booking-admin-preview">
                <div class="rm-booking-phone-preview"
                    style="--booking-primary: {{ $bookingPrimaryColor }}; --booking-accent: {{ $bookingAccentColor }}; --booking-bg: {{ $bookingBackgroundColor }}; --booking-font: {{ $bookingFontFamily }}, Figtree, sans-serif;">
                    <span>{{ $company->name }}</span>
                    <h3>{{ $bookingTitle ?: 'Agenda tu cita' }}</h3>
                    <p>{{ $bookingSubtitle ?: 'Elige tratamiento y horario disponible.' }}</p>
                    <div>
                        <small>Sucursal</small>
                        <strong>{{ $company->branches->first()?->name ?? 'Sucursal principal' }}</strong>
                    </div>
                    <div class="rm-booking-preview-slots">
                        <b>09:00</b><b>09:30</b><b>10:00</b>
                    </div>
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
    </section>

    <section class="rm-panel">
        <div class="rm-panel-title">
            <div>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path d="M4 6h16M4 12h16M4 18h10" />
                </svg>
                <h2>Uso actual</h2>
            </div>
        </div>

        <div class="rm-system-usage-grid">
            @foreach ([
                'Sucursales' => ['used' => $usage['branches'] ?? 0, 'limit' => $limits['branches']],
                'Usuarios' => ['used' => $usage['users'] ?? 0, 'limit' => $limits['users']],
                'Clientes' => ['used' => $usage['clients'] ?? 0, 'limit' => $limits['clients']],
                'Productos' => ['used' => $usage['products'] ?? 0, 'limit' => $limits['products']],
                'Citas del mes' => ['used' => $usage['appointments_per_month'] ?? 0, 'limit' => $limits['appointments_per_month']],
            ] as $label => $item)
                <article>
                    <strong>{{ $item['used'] }}</strong>
                    <span>{{ $label }}</span>
                    <small>Limite: {{ $item['limit'] }}</small>
                </article>
            @endforeach
        </div>
    </section>

    <section class="rm-panel">
        <div class="rm-panel-title">
            <div>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path d="M12 3 3 8l9 5 9-5-9-5Z" />
                    <path d="m3 14 9 5 9-5" />
                    <path d="m3 11 9 5 9-5" />
                </svg>
                <h2>Planes disponibles</h2>
            </div>
        </div>

        <div class="rm-system-plan-grid">
            @foreach ($plans as $card)
                <article class="rm-system-plan {{ $card['is_current'] ? 'is-current' : '' }}">
                    <div>
                        <span>{{ $card['is_current'] ? 'Plan actual' : 'Disponible' }}</span>
                        <h3>{{ $card['plan']->name }}</h3>
                        <strong>{{ $card['plan']->currency }} {{ number_format((float) $card['plan']->monthly_price, 2) }}/mes</strong>
                        <p>{{ $card['plan']->description }}</p>
                    </div>

                    <div class="rm-system-plan-list">
                        @foreach ($card['limits'] as $name => $limit)
                            <span>{{ $name }}: {{ $limit ?: 'Sin limite' }}</span>
                        @endforeach
                    </div>

                    <div class="rm-system-plan-modules">
                        @foreach (array_slice($card['modules'], 0, 8) as $module)
                            <small>{{ $module }}</small>
                        @endforeach
                    </div>

                    @if ($card['is_current'])
                        <span class="rm-system-plan-current">Plan activo</span>
                    @elseif ($card['can_request'])
                        <button class="rm-button rm-button-primary" type="button" wire:click="requestPlan('{{ $card['plan']->slug }}')">
                            Solicitar plan
                        </button>
                    @endif
                </article>
            @endforeach
        </div>
    </section>

    <section class="rm-panel">
        <div class="rm-panel-title">
            <div>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path d="M8 7h8M8 12h8M8 17h4" />
                    <rect x="4" y="3" width="16" height="18" rx="2" />
                </svg>
                <h2>Historial de pagos</h2>
            </div>
        </div>

        <div class="rm-system-payment-list">
            @forelse ($company->billingPayments as $payment)
                <article>
                    <div>
                        <strong>{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</strong>
                        <span>{{ $payment->paid_at?->format('d/m/Y') }} · {{ $payment->plan?->name ?? 'Plan' }}</span>
                    </div>
                    <small>{{ $payment->period_starts_at?->format('d/m/Y') ?? 'Sin inicio' }} - {{ $payment->period_ends_at?->format('d/m/Y') ?? 'Sin fin' }}</small>
                </article>
            @empty
                <div class="rm-dashboard-empty">Todavia no hay pagos registrados para esta empresa.</div>
            @endforelse
        </div>
    </section>

    @if ($requestedPlan)
        <div class="rm-modal-backdrop">
            <section class="rm-modal-panel rm-modal-panel-small">
                <div class="rm-modal-title">
                    <div>
                        <span>Solicitud de plan</span>
                        <h2>{{ $requestedPlan->name }}</h2>
                    </div>
                    <button type="button" wire:click="closeRequest" aria-label="Cerrar">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                            <path d="M18 6 6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <p>Tu solicitud queda lista para coordinar activacion con soporte administrativo de Rumika.</p>
                <div class="rm-form-actions">
                    <button class="rm-button rm-button-primary" type="button" wire:click="closeRequest">Entendido</button>
                </div>
            </section>
        </div>
    @endif
</main>
