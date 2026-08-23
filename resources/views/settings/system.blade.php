<x-app-layout>
    <div class="rm-shell" data-rm-shell>
        <x-app-sidebar active="system" />

        <section class="rm-workspace">
            <header class="rm-topbar">
                <livewire:app.branch-switcher />

                <div class="rm-top-actions">
                    <a class="rm-button rm-button-outline" href="{{ route('settings.commerce') }}">Sucursales</a>
                </div>
            </header>

            <livewire:settings.company-system-manager />
        </section>
    </div>
</x-app-layout>
