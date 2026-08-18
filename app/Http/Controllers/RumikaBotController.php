<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class RumikaBotController extends Controller
{
    public function ask(Request $request)
    {
        $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $apiKey = config('services.google_ai.key');

        if (!$apiKey) {
            return response()->json([
                'ok' => false,
                'answer' => 'La API del asistente todavía no está configurada.',
            ], 500);
        }

        $knowledgePath = public_path('rumika-knowledge.txt');

        $knowledge = File::exists($knowledgePath)
            ? File::get($knowledgePath)
            : 'Rumika SaaS es un sistema modular creado por DigitBol.';

        $userMessage = $request->input('message');

        $prompt = "
Eres el asistente virtual oficial de Rumika SaaS.

Debes responder en español, de forma clara, profesional, amable y breve.

Solo responde usando la siguiente base de conocimiento.
Si el usuario pregunta algo que no está en la base de conocimiento, dile que puede comunicarse con un representante por WhatsApp al 59177348087.

Base de conocimiento:
{$knowledge}

Pregunta del usuario:
{$userMessage}
";

        try {
            $response = Http::timeout(25)->post(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey,
                [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $prompt
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'maxOutputTokens' => 350,
                    ],
                ]
            );

            if (!$response->successful()) {
                return response()->json([
                    'ok' => false,
                    'answer' => 'No pude obtener respuesta del asistente.',
                ], 500);
            }

            $answer = data_get(
                $response->json(),
                'candidates.0.content.parts.0.text',
                'No pude generar una respuesta en este momento.'
            );

            return response()->json([
                'ok' => true,
                'answer' => trim($answer),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'answer' => 'Hubo un problema al conectar con el asistente.',
            ], 500);
        }
    }
}
