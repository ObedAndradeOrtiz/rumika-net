<x-app-layout>
    <div class="rm-shell" data-rm-shell>
        <x-app-sidebar active="crm" />

        <section class="rm-workspace">
            <header class="rm-topbar">
                <livewire:app.branch-switcher />
                <div class="rm-top-actions">
                    <livewire:clinic.quick-cashbox />
                </div>
            </header>

            <livewire:crm.crm-manager />
        </section>
    </div>
</x-app-layout>
