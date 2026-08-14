<x-app-layout>
    <div class="rm-shell" data-rm-shell>
        <x-app-sidebar active="home" />

        <section class="rm-workspace">
            <header class="rm-topbar">
                <livewire:app.branch-switcher />

                <div class="rm-top-actions"></div>
            </header>

            <livewire:dashboard.home-summary />
        </section>
    </div>
</x-app-layout>
