<div>
    <div class="rm-branch-switcher">
        <button class="rm-branch-switcher-main rm-branch-switcher-button" type="button" wire:click="open">
            <span class="rm-avatar">
                @if ($activeBranch?->logo_path)
                    <img src="{{ asset('storage/'.$activeBranch->logo_path) }}" alt="{{ $activeBranch->name }}">
                @else
                    {{ strtoupper(substr($activeBranch?->name ?? 'RM', 0, 1)) }}{{ strtoupper(substr($activeBranch?->businessType?->name ?? 'K', 0, 1)) }}
                @endif
            </span>
            <div>
                <strong>{{ $activeBranch?->name ?? 'Sin sucursal' }}</strong>
                <span>{{ $activeBranch?->businessType?->name ?? 'Tipo no definido' }} - {{ $activeBranch?->company?->name ?? $company?->name ?? 'Rumika' }}</span>
            </div>
            <svg class="rm-branch-caret" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m6 9 6 6 6-6"/></svg>
        </button>
        <button class="rm-branch-company-edit" type="button" wire:click="editCompanyName" aria-label="Cambiar nombre de empresa">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
        </button>
    </div>

    @if ($showBranchModal)
        <div class="rm-modal-backdrop" wire:click="close"></div>
        <section class="rm-modal-panel rm-modal-panel-wide rm-branch-modal" role="dialog" aria-modal="true" aria-labelledby="branch-modal-title">
            <div class="rm-modal-title">
                <div>
                    <span>Trabajo actual</span>
                    <h2 id="branch-modal-title">Cambiar sucursal o negocio</h2>
                </div>
                <button type="button" wire:click="close" aria-label="Cerrar modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="rm-branch-choice-list">
                @foreach ($branches as $branch)
                    <button class="rm-branch-choice {{ $activeBranch?->id === $branch->id ? 'is-selected' : '' }}" type="button" wire:click="select({{ $branch->id }})">
                        <span class="rm-commerce-icon">
                            @if ($branch->logo_path)
                                <img src="{{ asset('storage/'.$branch->logo_path) }}" alt="{{ $branch->name }}">
                            @else
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6"/></svg>
                            @endif
                        </span>
                        <span>
                            <strong>{{ $branch->name }}</strong>
                            <small>{{ $branch->businessType?->name ?? 'Sin tipo' }} - {{ $branch->company?->name }}</small>
                        </span>
                        @if ($activeBranch?->id === $branch->id)
                            <span class="rm-check">✓</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </section>
    @endif

    @if ($showCompanyModal)
        <div class="rm-modal-backdrop" wire:click="closeCompanyModal"></div>
        <section class="rm-modal-panel rm-modal-panel-small" role="dialog" aria-modal="true" aria-labelledby="company-modal-title">
            <div class="rm-modal-title">
                <div>
                    <span>Empresa</span>
                    <h2 id="company-modal-title">Cambiar nombre comercial</h2>
                </div>
                <button type="button" wire:click="closeCompanyModal" aria-label="Cerrar modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="saveCompanyName" class="rm-form-stack">
                <label class="rm-field">
                    <span>Nombre de empresa</span>
                    <input wire:model="companyName" type="text" placeholder="Bethel, Rumika, nombre comercial">
                    @error('companyName') <small>{{ $message }}</small> @enderror
                </label>
                <div class="rm-form-actions">
                    <button class="rm-button rm-button-primary" type="submit">Guardar nombre</button>
                    <button class="rm-button rm-button-outline" type="button" wire:click="closeCompanyModal">Cancelar</button>
                </div>
            </form>
        </section>
    @endif
</div>
