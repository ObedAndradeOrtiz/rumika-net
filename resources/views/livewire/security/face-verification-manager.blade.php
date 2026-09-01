<div class="rm-login-shell rm-face-shell" data-face-security data-face-mode="{{ $mode }}" data-livewire-id="{{ $this->getId() }}">
    <div class="rm-login-header">
        <a class="rm-login-brand" href="{{ url('/') }}">
            <span class="rm-brand-mark">
                <img src="{{ asset('rumika-favicon.svg') }}" alt="Rumika" style="width: 34px; height: 34px;">
            </span>
            <span>
                <span class="rm-login-brand-title">Rumika</span>
                <span class="rm-login-brand-subtitle">Seguridad de ingreso</span>
            </span>
        </a>
        <span class="rm-secure-badge">Camara activa</span>
    </div>

    <div class="rm-login-copy">
        <h1>{{ $mode === 'enroll' ? 'Registra tu rostro' : 'Valida tu ingreso' }}</h1>
        <p>
            {{ $mode === 'enroll'
                ? 'Este usuario tiene verificacion visual activada. La primera vez se registra una huella facial para validar futuros accesos.'
                : 'Detectamos una nueva sesion o IP. Mira a la camara para confirmar que eres el usuario autorizado con un parecido minimo de 60%.' }}
        </p>
    </div>

    <div class="rm-face-card">
        <div class="rm-face-mascot" aria-hidden="true">
            <span class="rm-face-alpaca">Rumi</span>
            <span class="rm-face-mouth"></span>
        </div>
        <div class="rm-face-video-wrap">
            <video data-face-video autoplay muted playsinline></video>
            <canvas data-face-canvas hidden></canvas>
        </div>
        <div class="rm-face-status" data-face-status>
            Activa la camara y ubica tu rostro de frente, con buena luz.
        </div>
        @error('face')
            <div class="rm-inline-error">{{ $message }}</div>
        @enderror
        @if ($lastSimilarity)
            <div class="rm-face-score">Parecido detectado: {{ $lastSimilarity }}%</div>
        @endif
    </div>

    <div class="rm-form-actions rm-face-actions">
        <button class="rm-button rm-button-outline" type="button" data-face-start>Activar camara</button>
        <button class="rm-button rm-button-primary" type="button" data-face-capture>
            {{ $mode === 'enroll' ? 'Registrar rostro' : 'Validar ingreso' }}
        </button>
    </div>

    <p class="rm-auth-switch">
        Rumika guarda una captura de seguridad por intento y una huella numerica para comparar el acceso.
    </p>

    <style>
        .rm-face-shell { max-width: 600px; margin: 0 auto; }
        .rm-face-card {
            padding: 18px;
            border-radius: 24px;
            border: 1px solid var(--rm-border);
            background: rgba(255, 255, 255, .88);
            box-shadow: var(--rm-shadow-md);
            display: grid;
            gap: 14px;
        }
        .rm-face-video-wrap {
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            background: #0f172a;
            aspect-ratio: 4 / 3;
            display: grid;
            place-items: center;
        }
        .rm-face-video-wrap video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
        }
        .rm-face-mascot {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #087568;
            font-weight: 900;
        }
        .rm-face-alpaca {
            width: 54px;
            height: 54px;
            border-radius: 20px;
            display: grid;
            place-items: center;
            background: #e7f7f5;
            border: 1px solid #cfeee8;
            font-size: 13px;
        }
        .rm-face-mouth {
            width: 34px;
            height: 12px;
            border-radius: 999px;
            background: #cfeee8;
            transform-origin: left center;
        }
        .rm-face-shell.is-reading .rm-face-mouth { animation: rmChew .44s ease-in-out infinite alternate; }
        .rm-face-status,
        .rm-face-score {
            color: #667085;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.5;
        }
        .rm-face-score { color: #087568; }
        .rm-face-actions { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        @keyframes rmChew {
            from { transform: scaleX(.72); opacity: .64; }
            to { transform: scaleX(1.18); opacity: 1; }
        }
        @media (max-width: 560px) {
            .rm-face-actions { grid-template-columns: 1fr; }
        }
    </style>
</div>
