<x-app-layout>
    <div class="rm-shell" data-rm-shell>
        <x-app-sidebar active="finance-summary" />

        <section class="rm-workspace">
            <header class="rm-topbar">
                <livewire:app.branch-switcher />

                <div class="rm-top-actions">
                    <a class="rm-button rm-button-outline" href="{{ route('dashboard') }}">Panel</a>
                </div>
            </header>

            <livewire:finance.income-summary />
        </section>
    </div>
</x-app-layout>
