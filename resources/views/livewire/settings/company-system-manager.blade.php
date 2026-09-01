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
