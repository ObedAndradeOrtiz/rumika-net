<x-app-layout>
    <div class="rm-shell" data-rm-shell>
        <x-app-sidebar active="clients" />

        <section class="rm-workspace">
            <header class="rm-topbar">
                <livewire:app.branch-switcher />
                <div class="rm-top-actions">
                    <a class="rm-button rm-button-outline" href="{{ route('clinic.agenda') }}">Agenda</a>
                </div>
            </header>

            <livewire:clinic.client-history />
        </section>
    </div>
</x-app-layout>
