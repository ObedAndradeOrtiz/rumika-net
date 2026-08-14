<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public ?TemporaryUploadedFile $photo = null;

    public ?string $currentPhotoPath = null;

    public function mount(): void
    {
        $this->currentPhotoPath = Auth::user()->profile_photo_path;
    }

    public function updateProfilePhoto(): void
    {
        $this->validate([
            'photo' => ['required', 'image', 'max:2048'],
        ]);

        $user = Auth::user();
        $user->profile_photo_path = $this->photo->store('user-photos', 'public');
        $user->save();

        $this->currentPhotoPath = $user->profile_photo_path;
        $this->reset('photo');
        $this->dispatch('profile-photo-updated');
    }
}; ?>

<section class="rm-profile-card">
    <header>
        <span>Imagen personal</span>
        <h2>Foto de usuario</h2>
        <p>Esta imagen se usa en tu perfil del sistema y en el menu lateral.</p>
    </header>

    <form wire:submit="updateProfilePhoto" class="rm-form-stack">
        <div class="rm-profile-photo-editor">
            <span class="rm-media-preview rm-profile-photo-preview">
                @if ($photo)
                    <img src="{{ $photo->temporaryUrl() }}" alt="Nueva foto">
                @elseif ($currentPhotoPath)
                    <img src="{{ asset('storage/'.$currentPhotoPath) }}" alt="{{ auth()->user()->name }}">
                @else
                    {{ collect(explode(' ', trim(auth()->user()->name)))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('') ?: 'U' }}
                @endif
            </span>

            <div class="rm-row-main">
                <strong>{{ auth()->user()->name }}</strong>
                <span>{{ auth()->user()->email }}</span>
            </div>
        </div>

        <label class="rm-field">
            <span>Subir imagen</span>
            <input wire:model="photo" type="file" accept="image/*">
            @error('photo') <small>{{ $message }}</small> @enderror
        </label>

        <div class="rm-form-actions">
            <button class="rm-button rm-button-primary" type="submit">Guardar imagen</button>
            <x-action-message class="me-3" on="profile-photo-updated">Imagen actualizada.</x-action-message>
        </div>
    </form>
</section>
