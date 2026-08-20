<div class="rm-login-shell">
    <div class="rm-login-header">
        <a href="{{ url('/') }}" class="rm-login-brand">
            <span class="rm-brand-mark">
                <x-application-logo class="h-7 w-7 text-white" />
            </span>

            <span>
                <span class="rm-login-brand-title">Rumika SaaS</span>
                <span class="rm-login-brand-subtitle">Configuracion inicial</span>
            </span>
        </a>

        <span class="rm-secure-badge">Plan Free</span>
    </div>

    <div class="rm-login-copy">
        <h1>Completa tu empresa</h1>
        <p>
            Antes de entrar al panel necesitamos el nombre comercial, tu primera
            sucursal y el tipo de negocio para activar los modulos correctos.
        </p>
    </div>

    <form wire:submit="save" class="rm-login-form">
        <div class="rm-field">
            <label class="rm-label" for="companyName">Nombre de empresa</label>
            <div class="rm-input-box">
                <input wire:model="companyName" id="companyName" class="rm-input" type="text" placeholder="Centro medico Bethel">
            </div>
            <x-input-error :messages="$errors->get('companyName')" class="rm-error" />
        </div>

        <div class="rm-register-grid">
            <div class="rm-field">
                <label class="rm-label" for="branchName">Primera sucursal</label>
                <div class="rm-input-box">
                    <input wire:model="branchName" id="branchName" class="rm-input" type="text" placeholder="Sucursal Centro">
                </div>
                <x-input-error :messages="$errors->get('branchName')" class="rm-error" />
            </div>

            <div class="rm-field">
                <label class="rm-label" for="businessTypeId">Tipo de negocio</label>
                <div class="rm-input-box">
                    <select wire:model="businessTypeId" id="businessTypeId" class="rm-input rm-select">
                        <option value="">Selecciona uno</option>
                        @foreach ($businessTypes as $businessType)
                            <option value="{{ $businessType->id }}">{{ $businessType->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-input-error :messages="$errors->get('businessTypeId')" class="rm-error" />
            </div>
        </div>

        <div class="rm-register-grid">
            <div class="rm-field">
                <label class="rm-label" for="phone">Telefono</label>
                <div class="rm-input-box">
                    <input wire:model="phone" id="phone" class="rm-input" type="text" placeholder="Opcional">
                </div>
                <x-input-error :messages="$errors->get('phone')" class="rm-error" />
            </div>

            <div class="rm-field">
                <label class="rm-label" for="address">Direccion</label>
                <div class="rm-input-box">
                    <input wire:model="address" id="address" class="rm-input" type="text" placeholder="Opcional">
                </div>
                <x-input-error :messages="$errors->get('address')" class="rm-error" />
            </div>
        </div>

        <button class="rm-submit-button" type="submit" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save">Entrar a Rumika</span>
            <span wire:loading wire:target="save" class="rm-loading-content">
                <span class="rm-spinner"></span>
                Guardando...
            </span>
        </button>
    </form>
</div>
