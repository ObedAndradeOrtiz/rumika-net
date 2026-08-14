<x-app-layout>
    <div class="rm-shell" data-rm-shell>
        <x-app-sidebar active="profile" />

        <section class="rm-workspace">
            <header class="rm-topbar">
                <livewire:app.branch-switcher />
                <div class="rm-top-actions"></div>
            </header>

            <div class="rm-content rm-settings-page">
                <div class="rm-settings-hero">
                    <div>
                        <span>Cuenta personal</span>
                        <h1>Mi perfil</h1>
                        <p>Cambia tu imagen de usuario y actualiza tu contrasena cuando lo necesites.</p>
                    </div>
                </div>

                <div class="rm-profile-grid">
                    <section class="rm-panel">
                        <livewire:profile.update-profile-information-form />
                    </section>

                    <section class="rm-panel">
                        <livewire:profile.update-password-form />
                    </section>
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
