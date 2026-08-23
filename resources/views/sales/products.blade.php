<x-app-layout>
    <div class="rm-shell" data-rm-shell>
        <x-app-sidebar active="product-sales" />

        <section class="rm-workspace">
            <header class="rm-topbar">
                <livewire:app.branch-switcher />

                <div class="rm-top-actions">
                    <a class="rm-button rm-button-outline" href="{{ route('clinic.cashbox') }}">Caja</a>
                </div>
            </header>

            <livewire:sales.product-sales-manager />
        </section>
    </div>
</x-app-layout>
