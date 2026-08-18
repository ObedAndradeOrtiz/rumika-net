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
                    'answer' => 'La API del asistente todavía no está configurada.',
                ]);
            }

            $model = config('services.google_ai.model', 'gemini-3.5-flash');

            $knowledgePath = public_path('rumika-knowledge.txt');

            $knowledge = File::exists($knowledgePath)
                ? File::get($knowledgePath)
                : 'Rumika SaaS es un sistema base modular creado por DigitBol para clínicas, spas, centros de belleza, barberías, dentistas y negocios que necesitan agenda, clientes, inventario, pagos, sucursales y reportes.';

            $userMessage = $request->input('message');

            $prompt = trim("
Eres el asistente virtual oficial de Rumika SaaS.

Responde siempre en español.
Responde de forma clara, profesional, amable y breve.
No inventes precios, planes ni datos que no estén en la base de conocimiento.
Si el usuario pregunta algo que no está en la base de conocimiento, indica que puede comunicarse con un representante por WhatsApp al 59177348087.

Base de conocimiento:
{$knowledge}

Pregunta del usuario:
{$userMessage}
");

            $response = Http::withoutVerifying()
                ->timeout(20)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                [
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('Error Google AI Rumika Bot', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'model' => $model,
                ]);

                return response()->json([
                    'ok' => false,
                    'answer' => 'El asistente no pudo responder en este momento. Puedes comunicarte con un representante por WhatsApp al 59177348087.',
                    'debug' => app()->environment('local') ? $response->body() : null,
                ]);
            }

            $answer = trim((string) data_get(
                $response->json(),
                'candidates.0.content.parts.0.text'
            ));

            return response()->json([
                'ok' => true,
                'answer' => $answer ?: 'Puedes comunicarte con un representante por WhatsApp al 59177348087.',
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
