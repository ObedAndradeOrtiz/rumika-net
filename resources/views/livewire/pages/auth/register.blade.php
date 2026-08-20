<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    //
}; ?>

<div class="rm-login-shell">
    <div class="rm-login-header">
        <a href="{{ url('/') }}" class="rm-login-brand" wire:navigate>
            <span class="rm-brand-mark">
                <x-application-logo class="h-7 w-7 text-white" />
            </span>

            <span>
                <span class="rm-login-brand-title">Rumika SaaS</span>
                <span class="rm-login-brand-subtitle">Registro con Google</span>
            </span>
        </a>

        <span class="rm-secure-badge">Plan Free</span>
    </div>

    <div class="rm-login-copy">
        <h1>Crear cuenta</h1>
        <p>
            Registrate con Google. Crearemos una empresa gratis y luego te
            pediremos los datos necesarios para activar tu panel.
        </p>
    </div>

    <div class="rm-google-error" data-google-error hidden></div>

    <button type="button" class="rm-google-button" data-firebase-google data-auth-url="{{ route('auth.firebase.google') }}">
        <span class="rm-google-icon">G</span>

        <span class="rm-google-text">
            <strong data-google-label>Continuar con Google</strong>
            <small>Solo correos verificados por Google</small>
        </span>
    </button>

    <p class="rm-auth-note">
        No necesitas crear contrasena ahora. Tu acceso queda protegido por Google
        y podras completar empresa, sucursal y tipo de negocio al entrar.
    </p>

    <p class="rm-auth-switch">
        ¿Ya tienes cuenta?
        <a href="{{ route('login') }}" wire:navigate>Iniciar sesion</a>
    </p>

    <div class="rm-digitbol-card">
        <img src="{{ asset('digitbol-logo.jpg') }}" alt="DigitBol">

        <div>
            <span>Desarrollado por</span>
            <strong>DigitBol</strong>
            <p>Sistemas web, SaaS y soluciones digitales a medida.</p>

            <a
                href="https://wa.me/59177348087?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20DigitBol%20y%20sus%20sistemas."
                target="_blank"
                rel="noopener"
            >
                Hablar por WhatsApp
            </a>
        </div>
    </div>
</div>
