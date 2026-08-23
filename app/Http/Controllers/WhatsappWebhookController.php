<?php

namespace App\Http\Controllers;

use App\Models\CrmContact;
use App\Models\CrmConversation;
use App\Models\CrmMessage;
use App\Models\WhatsappChannel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class WhatsappWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        if ($mode !== 'subscribe' || blank($token)) {
            return response('Invalid verification request', 403);
        }

        $matches = WhatsappChannel::query()
            ->where('is_active', true)
            ->get()
            ->contains(fn (WhatsappChannel $channel) => hash_equals((string) $channel->verify_token, (string) $token));

        return $matches
            ? response((string) $challenge, 200)->header('Content-Type', 'text/plain')
            : response('Verification token mismatch', 403);
    }

    public function receive(Request $request): Response
    {
        foreach (data_get($request->all(), 'entry', []) as $entry) {
            foreach (data_get($entry, 'changes', []) as $change) {
                $value = data_get($change, 'value', []);
                $phoneNumberId = data_get($value, 'metadata.phone_number_id');
                $channel = WhatsappChannel::query()
                    ->where('phone_number_id', $phoneNumberId)
                    ->where('is_active', true)
                    ->first();

                if (! $channel) {
                    continue;
                }

                $contacts = collect(data_get($value, 'contacts', []))->keyBy('wa_id');

                foreach (data_get($value, 'messages', []) as $messagePayload) {
                    $this->storeIncomingMessage($channel, $messagePayload, $contacts->get(data_get($messagePayload, 'from'), []));
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }

    private function storeIncomingMessage(WhatsappChannel $channel, array $messagePayload, array $contactPayload = []): void
    {
        $wamid = data_get($messagePayload, 'id');

        if ($wamid && CrmMessage::query()->where('wamid', $wamid)->exists()) {
            return;
        }

        $phone = preg_replace('/\D+/', '', (string) data_get($messagePayload, 'from'));

        if (blank($phone)) {
            return;
        }

        $name = data_get($contactPayload, 'profile.name');
        $type = (string) data_get($messagePayload, 'type', 'text');
        $body = $this->extractBody($messagePayload, $type);
        $messageAt = Carbon::createFromTimestamp((int) data_get($messagePayload, 'timestamp', time()));

        $contact = CrmContact::query()->updateOrCreate(
            ['company_id' => $channel->company_id, 'phone' => $phone],
            [
                'name' => $name ?: null,
                'last_interaction_at' => $messageAt,
            ],
        );

        $conversation = CrmConversation::query()->firstOrCreate(
            [
                'company_id' => $channel->company_id,
                'whatsapp_channel_id' => $channel->id,
                'crm_contact_id' => $contact->id,
            ],
            [
                'client_id' => $contact->client_id,
                'status' => 'open',
            ],
        );

        $conversation->messages()->create([
            'company_id' => $channel->company_id,
            'whatsapp_channel_id' => $channel->id,
            'crm_contact_id' => $contact->id,
            'wamid' => $wamid ?: 'local-in-' . Str::uuid(),
            'direction' => 'in',
            'type' => $type,
            'body' => $body,
            'status' => 'received',
            'media_id' => data_get($messagePayload, "{$type}.id"),
            'media_mime_type' => data_get($messagePayload, "{$type}.mime_type"),
            'media_filename' => data_get($messagePayload, "{$type}.filename"),
            'raw_payload' => $messagePayload,
            'message_at' => $messageAt,
            'is_read' => false,
        ]);

        $conversation->update([
            'client_id' => $contact->client_id,
            'unread_count' => $conversation->unread_count + 1,
            'last_message' => Str::limit($body ?: strtoupper($type), 180),
            'last_message_at' => $messageAt,
            'last_customer_message_at' => $messageAt,
            'status' => 'open',
        ]);
    }

    private function extractBody(array $messagePayload, string $type): string
    {
        return match ($type) {
            'text' => (string) data_get($messagePayload, 'text.body', ''),
            'button' => (string) data_get($messagePayload, 'button.text', ''),
            'interactive' => (string) (data_get($messagePayload, 'interactive.button_reply.title')
                ?: data_get($messagePayload, 'interactive.list_reply.title')
                ?: ''),
            'image', 'video', 'document', 'audio' => (string) data_get($messagePayload, "{$type}.caption", strtoupper($type)),
            default => strtoupper($type),
        };
    }
}
