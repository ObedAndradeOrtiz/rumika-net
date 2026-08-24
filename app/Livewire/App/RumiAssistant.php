<?php

namespace App\Livewire\App;

use App\Services\RumiAiAssistant as RumiAiAssistantService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RumiAssistant extends Component
{
    public bool $open = false;

    public string $question = '';

    public array $messages = [];

    public string $notice = '';

    public function mount(): void
    {
        $this->messages = [
            [
                'role' => 'assistant',
                'text' => 'Hola, soy Rumi. Puedo explicarte como usar Rumika, ubicar pantallas, abrir acciones seguras y resumir datos permitidos por tu rol.',
                'actions' => [
                    ['label' => 'Resumen de hoy', 'prompt' => 'Dame un resumen de hoy'],
                    ['label' => 'Guia de agenda', 'prompt' => 'Explicame como funciona la agenda'],
                    ['label' => 'Guia de inventario', 'prompt' => 'Explicame inventario entradas salidas ajustes y bajas'],
                    ['label' => 'Mi rol', 'prompt' => 'Que puedo hacer con mi rol'],
                ],
            ],
        ];
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
        $this->notice = '';
    }

    public function ask(): void
    {
        $assistant = app(RumiAiAssistantService::class);
        $question = trim($this->question);

        if ($question === '') {
            return;
        }

        $this->question = '';
        $this->notice = '';
        $this->messages[] = ['role' => 'user', 'text' => $question, 'actions' => []];

        $response = $assistant->answer(Auth::user(), $question);

        $this->messages[] = [
            'role' => 'assistant',
            'text' => $response['answer'],
            'actions' => $response['actions'] ?? [],
        ];
    }

    public function usePrompt(string $prompt): void
    {
        $this->question = $prompt;
        $this->ask();
    }

    public function runAction(string $key): void
    {
        $assistant = app(RumiAiAssistantService::class);
        $this->notice = '';
        $result = $assistant->action($key, Auth::user());

        if (! $result) {
            $this->notice = 'No pude ejecutar esa accion.';

            return;
        }

        if (($result['type'] ?? null) === 'route') {
            $this->redirect($result['url'], navigate: true);

            return;
        }

        if (($result['type'] ?? null) === 'click') {
            $this->dispatch('rumi-click', selector: $result['selector']);

            return;
        }

        if (($result['type'] ?? null) === 'message') {
            $this->notice = $result['message'];
        }
    }

    public function clear(): void
    {
        $this->mount();
        $this->notice = '';
    }

    public function render()
    {
        return view('livewire.app.rumi-assistant');
    }
}
