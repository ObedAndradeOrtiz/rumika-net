<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RumikaBotController extends Controller
{
    public function ask(Request $request)
    {
        try {
            $request->validate([
                'message' => ['required', 'string', 'max:1000'],
            ]);

            $apiKey = config('services.google_ai.key');

            if (!$apiKey) {
                return response()->json([
                    'ok' => false,
                    'answer' => 'La API del asistente todavía no está configurada. Falta GOOGLE_AI_API_KEY en el archivo .env.',
                ]);
            }

            $knowledgePath = public_path('rumika-knowledge.txt');

            $knowledge = File::exists($knowledgePath)
                ? File::get($knowledgePath)
                : 'Rumika SaaS es un sistema base modular creado por DigitBol para clínicas, spas, centros de belleza, barberías, dentistas y negocios que necesitan agenda, clientes, inventario, pagos, sucursales y reportes.';

            $userMessage = $request->input('message');

            $prompt = "
Eres el asistente virtual oficial de Rumika SaaS.

Responde siempre en español.
Responde de forma clara, profesional, amable y breve.
No inventes precios ni datos que no estén en la base de conocimiento.
Si no sabes algo, indica que el usuario puede comunicarse con un representante por WhatsApp al 59177348087.

Base de conocimiento:
{$knowledge}

Pregunta del usuario:
{$userMessage}
";

            $response = Http::timeout(30)->post(
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey,
                [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'maxOutputTokens' => 350,
                    ],
                ]
            );

            if (!$response->successful()) {
                Log::error('Error Google AI Rumika Bot', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response()->json([
                    'ok' => false,
                    'answer' => 'El asistente no pudo responder en este momento. Puedes comunicarte con un representante por WhatsApp al 59177348087.',
                    'debug' => app()->environment('local') ? $response->body() : null,
                ]);
            }

            $answer = data_get(
                $response->json(),
                'candidates.0.content.parts.0.text'
            );

            if (!$answer) {
                return response()->json([
                    'ok' => false,
                    'answer' => 'No pude generar una respuesta en este momento. Puedes escribirnos por WhatsApp al 59177348087.',
                ]);
            }

            return response()->json([
                'ok' => true,
                'answer' => trim($answer),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error interno Rumika Bot', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'ok' => false,
                'answer' => 'Hubo un problema interno al conectar con el asistente. Puedes comunicarte por WhatsApp al 59177348087.',
                'debug' => app()->environment('local') ? $e->getMessage() : null,
            ]);
        }
    }
}
