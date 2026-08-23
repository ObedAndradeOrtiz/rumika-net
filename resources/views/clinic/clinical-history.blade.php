<x-app-layout>
    <div class="rm-shell" data-rm-shell>
        <x-app-sidebar active="clinical-history" />

        <section class="rm-workspace">
            <header class="rm-topbar">
                <livewire:app.branch-switcher />
                <div class="rm-top-actions">
                    <a class="rm-button rm-button-outline" href="{{ route('clinic.clients') }}">Clientes</a>
                </div>
            </header>

            <livewire:clinic.clinical-history-manager />
        </section>
    </div>
</x-app-layout>
