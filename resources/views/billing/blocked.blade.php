<x-app-layout>
    <div class="rm-billing-blocked">
        <section>
            <span class="rm-brand-mark">
                <x-application-logo class="h-7 w-7 text-white" />
            </span>
            <h1>Acceso pausado</h1>
            <p>
                Tu demo o periodo pagado finalizo. Para reactivar Rumika, comunicate con administracion y solicita la activacion del siguiente mes.
            </p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="rm-button rm-button-primary" type="submit">Cerrar sesion</button>
            </form>
        </section>
    </div>
</x-app-layout>
