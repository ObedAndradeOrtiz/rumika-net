@if ($showExpenseModal)
    <div class="rm-modal-backdrop" wire:click="$set('showExpenseModal', false)"></div>
    <section class="rm-modal-panel rm-modal-panel-wide" role="dialog" aria-modal="true">
        <div class="rm-modal-title"><div><span>Gasto</span><h2>{{ $editingExpenseId ? 'Editar gasto' : 'Registrar gasto' }}</h2></div><button type="button" wire:click="$set('showExpenseModal', false)">x</button></div>
        <form wire:submit="saveExpense" class="rm-form-stack">
            <label class="rm-field"><span>Tipo</span><select wire:model.live="expenseTypeId"><option value="">Seleccionar tipo</option>@foreach ($activeTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select>@error('expenseTypeId')<small>{{ $message }}</small>@enderror</label>
            <div class="rm-form-row">
                <label class="rm-field"><span>Origen</span><select wire:model="expenseSource"><option value="cashbox">Caja</option><option value="external">Externo</option></select>@error('expenseSource')<small>{{ $message }}</small>@enderror</label>
                <label class="rm-field"><span>Monto</span><input wire:model="expenseAmount" type="number" min="0.01" step="0.01">@error('expenseAmount')<small>{{ $message }}</small>@enderror</label>
            </div>
            <div class="rm-form-row">
                <label class="rm-field"><span>Fecha</span><input wire:model="expenseSpentAt" type="date">@error('expenseSpentAt')<small>{{ $message }}</small>@enderror</label>
                @if ($selectedExpenseTypeRequiresStaff)
                    <label class="rm-field"><span>Personal</span><select wire:model="staffUserId"><option value="">Seleccionar personal</option>@foreach ($staffUsers as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select>@error('staffUserId')<small>{{ $message }}</small>@enderror</label>
                @else
                    <label class="rm-field"><span>Responsable</span><input type="text" value="{{ $expenseResponsibleName }}" disabled></label>
                @endif
            </div>
            <label class="rm-field"><span>Referencia</span><input wire:model="expenseReference" type="text" placeholder="Recibo, planilla, observacion corta"></label>
            <label class="rm-field"><span>Descripcion</span><input wire:model="expenseDescription" type="text" placeholder="Detalle del gasto"></label>
            <div class="rm-form-actions"><button class="rm-button rm-button-primary" type="submit">Guardar gasto</button></div>
        </form>
    </section>
@endif

@if ($showTypeModal)
    <div class="rm-modal-backdrop" wire:click="$set('showTypeModal', false)"></div>
    <section class="rm-modal-panel rm-modal-panel-wide" role="dialog" aria-modal="true">
        <div class="rm-modal-title"><div><span>Tipo de gasto</span><h2>{{ $editingTypeId ? 'Editar tipo' : 'Nuevo tipo' }}</h2></div><button type="button" wire:click="$set('showTypeModal', false)">x</button></div>
        <form wire:submit="saveType" class="rm-form-stack">
            <label class="rm-field"><span>Nombre</span><input wire:model="typeName" type="text" placeholder="Adelanto, pago al personal, limpieza">@error('typeName')<small>{{ $message }}</small>@enderror</label>
            <div class="rm-form-row">
                <label class="rm-field"><span>Origen por defecto</span><select wire:model="typeDefaultSource"><option value="cashbox">Caja</option><option value="external">Externo</option></select></label>
                <label class="rm-field"><span>Estado</span><select wire:model="typeStatus"><option value="active">Activo</option><option value="inactive">Inactivo</option></select></label>
            </div>
            <label class="rm-check-option">
                <input wire:model="typeRequiresStaff" type="checkbox">
                <span>Este tipo va dirigido a personal</span>
                <small>Adelantos, pagos, liquidaciones y otros conceptos por trabajador.</small>
            </label>
            <label class="rm-field"><span>Descripcion</span><input wire:model="typeDescription" type="text"></label>
            <div class="rm-form-actions"><button class="rm-button rm-button-primary" type="submit">Guardar tipo</button></div>
        </form>
    </section>
@endif

@if ($confirmingExpenseDeleteId)
    <div class="rm-modal-backdrop" wire:click="$set('confirmingExpenseDeleteId', null)"></div>
    <section class="rm-modal-panel rm-modal-panel-small" role="dialog" aria-modal="true">
        <div class="rm-confirm-icon">!</div>
        <h2>Eliminar gasto</h2>
        <p>Se eliminara este gasto del historial mensual.</p>
        <div class="rm-form-actions"><button class="rm-button rm-button-danger" type="button" wire:click="deleteExpense({{ $confirmingExpenseDeleteId }})">Eliminar</button><button class="rm-button rm-button-outline" type="button" wire:click="$set('confirmingExpenseDeleteId', null)">Cancelar</button></div>
    </section>
@endif

@if ($confirmingTypeDeleteId)
    <div class="rm-modal-backdrop" wire:click="$set('confirmingTypeDeleteId', null)"></div>
    <section class="rm-modal-panel rm-modal-panel-small" role="dialog" aria-modal="true">
        <div class="rm-confirm-icon">!</div>
        <h2>Eliminar tipo</h2>
        <p>Solo se puede eliminar si no tiene gastos registrados.</p>
        @error('deleteType')<p class="rm-inline-error">{{ $message }}</p>@enderror
        <div class="rm-form-actions"><button class="rm-button rm-button-danger" type="button" wire:click="deleteType({{ $confirmingTypeDeleteId }})">Eliminar</button><button class="rm-button rm-button-outline" type="button" wire:click="$set('confirmingTypeDeleteId', null)">Cancelar</button></div>
    </section>
@endif
