<x-app-layout>
    <div class="rm-shell" data-rm-shell>
        <x-app-sidebar active="debts" />

        <section class="rm-workspace">
            <header class="rm-topbar">
                <livewire:app.branch-switcher />
                <div class="rm-top-actions">
                    <a class="rm-button rm-button-outline" href="{{ route('finance.reports') }}">Reportes</a>
                </div>
            </header>

            <livewire:finance.debt-manager />
        </section>
    </div>
</x-app-layout>
