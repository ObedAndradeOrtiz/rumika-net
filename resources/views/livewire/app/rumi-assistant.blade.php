<div class="rm-rumi" data-rumi-assistant>
    <button class="rm-rumi-launcher" type="button" wire:click="toggle" aria-label="Abrir Rumi IA" wire:loading.class="is-thinking">
        <span class="rm-rumi-alpaca" aria-hidden="true">
            <i class="ear left"></i>
            <i class="ear right"></i>
            <i class="wool one"></i>
            <i class="wool two"></i>
            <i class="wool three"></i>
            <b class="eye left"></b>
            <b class="eye right"></b>
            <em></em>
        </span>
        <strong>Rumi IA</strong>
    </button>

    @if ($open)
        <section class="rm-rumi-panel" aria-label="Rumi IA">
            <div class="rm-rumi-head">
                <div>
                    <span>Asistente interno</span>
                    <h2><span class="rm-rumi-alpaca is-small" aria-hidden="true"><i class="ear left"></i><i class="ear right"></i><i class="wool one"></i><i class="wool two"></i><i class="wool three"></i><b class="eye left"></b><b class="eye right"></b><em></em></span> Rumi IA</h2>
                </div>
                <div class="rm-rumi-head-actions">
                    <button type="button" wire:click="clear">Limpiar</button>
                    <button type="button" wire:click="toggle" aria-label="Cerrar Rumi IA">x</button>
                </div>
            </div>

            <div class="rm-rumi-messages">
                @foreach ($messages as $message)
                    <article class="rm-rumi-message is-{{ $message['role'] }}">
                        <p>{!! nl2br(e($message['text'])) !!}</p>
                        @if (! empty($message['actions']))
                            <div class="rm-rumi-actions">
                                @foreach ($message['actions'] as $action)
                                    @if (! empty($action['prompt']))
                                        <button type="button" wire:click="usePrompt(@js($action['prompt']))">
                                            {{ $action['label'] }}
                                        </button>
                                    @elseif (! empty($action['key']))
                                        <button type="button" wire:click="runAction(@js($action['key']))">
                                            {{ $action['label'] }}
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>

            @if ($notice)
                <div class="rm-rumi-notice">{{ $notice }}</div>
            @endif

            <form class="rm-rumi-form" wire:submit="ask">
                <input type="text" wire:model="question" placeholder="Preguntale algo a Rumi IA">
                <button type="submit">Enviar</button>
            </form>

            <p class="rm-rumi-foot">Rumi solo muestra datos permitidos por tu rol.</p>
        </section>
    @endif
</div>
