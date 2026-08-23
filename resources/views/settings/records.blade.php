<x-app-layout>
    <div class="rm-shell" data-rm-shell>
        <x-app-sidebar active="records" />

        <section class="rm-workspace">
            <header class="rm-topbar">
                <livewire:app.branch-switcher />

                <div class="rm-top-actions">
                    <a class="rm-button rm-button-outline" href="{{ route('clinic.cashbox') }}">Caja</a>
                </div>
            </header>

            <livewire:settings.audit-log-manager />
        </section>
    </div>
</x-app-layout>
