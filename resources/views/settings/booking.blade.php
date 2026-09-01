<x-app-layout>
    <div class="rm-shell" data-rm-shell>
        <x-app-sidebar active="booking" />

        <section class="rm-workspace">
            <header class="rm-topbar">
                <livewire:app.branch-switcher />
                <div class="rm-top-actions">
                    <a class="rm-button rm-button-outline" href="{{ route('settings.system') }}">Mi sistema</a>
                </div>
            </header>

            <livewire:booking.booking-page-manager />
        </section>
    </div>
</x-app-layout>
