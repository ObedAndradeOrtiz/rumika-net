<?php

namespace App\Services;

use App\Models\CrmMessage;
use Illuminate\Support\Facades\Http;

class WhatsappMessageSender
{
    public function sendText(CrmMessage $message): bool
    {
        $message->loadMissing(['channel', 'contact']);

        $channel = $message->channel;

        if (! $channel?->access_token || ! $channel?->phone_number_id) {
            $message->update(['status' => 'channel_error']);

            return false;
        }

        $response = Http::withoutVerifying()
            ->timeout(20)
            ->withToken($channel->access_token)
            ->post("https://graph.facebook.com/{$channel->api_version}/{$channel->phone_number_id}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $message->contact->phone,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message->body,
                ],
            ]);

        $payload = $response->json();
        $wamid = data_get($payload, 'messages.0.id');

        $message->update([
            'wamid' => $wamid ?: $message->wamid,
            'status' => $response->successful() ? 'sent' : 'failed',
            'raw_payload' => $payload ?: ['body' => $response->body()],
        ]);

        return $response->successful();
    }
}
