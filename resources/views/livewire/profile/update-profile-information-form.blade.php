<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public ?TemporaryUploadedFile $photo = null;

    public string $photoCrop = '';

    public ?string $currentPhotoPath = null;

    public function mount(): void
    {
        $this->currentPhotoPath = Auth::user()->profile_photo_path;
    }

    public function updateProfilePhoto(): void
    {
        $this->validate([
            'photo' => ['required', 'image', 'max:2048'],
            'photoCrop' => ['nullable', 'string'],
        ]);

        $user = Auth::user();
        $user->profile_photo_path = $this->storeProfilePhoto();
        $user->save();

        $this->currentPhotoPath = $user->profile_photo_path;
        $this->reset('photo', 'photoCrop');
        $this->dispatch('profile-photo-updated');
    }

    private function storeProfilePhoto(): string
    {
        if (str_starts_with($this->photoCrop, 'data:image/') && str_contains($this->photoCrop, ',')) {
            [, $payload] = explode(',', $this->photoCrop, 2);
            $binary = base64_decode($payload, true);

            if ($binary !== false && strlen($binary) > 1000) {
                $path = 'user-photos/'.Str::uuid().'.jpg';
                Storage::disk('public')->put($path, $binary);

                return $path;
            }
        }

        return $this->photo->store('user-photos', 'public');
    }
}; ?>

<section class="rm-profile-card">
    <header>
        <span>Imagen personal</span>
        <h2>Foto de usuario</h2>
        <p>Esta imagen se usa en tu perfil del sistema y en el menu lateral.</p>
    </header>

    <form wire:submit="updateProfilePhoto" class="rm-form-stack">
        <div class="rm-profile-photo-editor" data-avatar-cropper>
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
            <input wire:model="photo" data-avatar-crop-input type="file" accept="image/*">
            @error('photo') <small>{{ $message }}</small> @enderror
        </label>

        <div class="rm-avatar-crop-tool" data-avatar-crop-tool wire:ignore hidden>
            <div class="rm-avatar-crop-stage" data-avatar-crop-stage>
                <img data-avatar-crop-image alt="Recorte de foto">
            </div>
            <label class="rm-field">
                <span>Acercar o alejar</span>
                <input data-avatar-crop-zoom type="range" min="1" max="3" step="0.01" value="1">
            </label>
            <small>Mueve la imagen dentro del cuadro para elegir que parte se vera como foto de perfil.</small>
        </div>
        <textarea wire:model="photoCrop" data-avatar-crop-output hidden></textarea>

        <div class="rm-form-actions">
            <button class="rm-button rm-button-primary" type="submit">Guardar imagen</button>
            <x-action-message class="me-3" on="profile-photo-updated">Imagen actualizada.</x-action-message>
        </div>
    </form>
</section>
