<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<section class="rm-profile-card">
    <header>
        <span>Seguridad</span>
        <h2>Cambiar contrasena</h2>
        <p>Actualiza tu acceso cuando lo necesites. Tu administrador tambien puede restablecerlo desde Usuarios.</p>
    </header>

    <form wire:submit="updatePassword" class="rm-form-stack">
        <label class="rm-field">
            <span>Contrasena actual</span>
            <input wire:model="current_password" type="password" autocomplete="current-password">
            @error('current_password') <small>{{ $message }}</small> @enderror
        </label>

        <label class="rm-field">
            <span>Nueva contrasena</span>
            <input wire:model="password" type="password" autocomplete="new-password">
            @error('password') <small>{{ $message }}</small> @enderror
        </label>

        <label class="rm-field">
            <span>Confirmar nueva contrasena</span>
            <input wire:model="password_confirmation" type="password" autocomplete="new-password">
            @error('password_confirmation') <small>{{ $message }}</small> @enderror
        </label>

        <div class="rm-form-actions">
            <button class="rm-button rm-button-primary" type="submit">Guardar contrasena</button>
            <x-action-message class="me-3" on="password-updated">Contrasena actualizada.</x-action-message>
        </div>
    </form>
</section>
