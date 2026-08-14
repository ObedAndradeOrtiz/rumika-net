<x-app-layout>
    <div class="rm-shell" data-rm-shell>
        <x-app-sidebar active="cashbox" />

        <section class="rm-workspace">
            <header class="rm-topbar">
                <livewire:app.branch-switcher />
                <div class="rm-top-actions">
                    <livewire:clinic.quick-cashbox />
                    <a class="rm-button rm-button-outline" href="{{ route('clinic.agenda') }}">Agenda</a>
                </div>
            </header>

            <livewire:clinic.cashbox-summary />
        </section>
    </div>
</x-app-layout>
