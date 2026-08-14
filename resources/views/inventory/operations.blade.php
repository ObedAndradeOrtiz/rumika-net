<x-app-layout>
    <div class="rm-shell" data-rm-shell>
        <x-app-sidebar active="inventory-operations" />

        <section class="rm-workspace">
            <header class="rm-topbar">
                <livewire:app.branch-switcher />

                <div class="rm-top-actions">
                    <a class="rm-button rm-button-outline" href="{{ route('inventory.index') }}">Catalogo</a>
                </div>
            </header>

            <livewire:inventory.inventory-manager screen="operations" />
        </section>
    </div>
</x-app-layout>
