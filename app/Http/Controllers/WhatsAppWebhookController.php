<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Handles Meta WhatsApp Cloud API webhook callbacks.
 *
 * GET  /api/whatsapp/webhook  — hub verification (Meta pings this when you
 *                               register the callback URL in Business Manager)
 * POST /api/whatsapp/webhook  — incoming events (button taps, status updates)
 *
 * The webhook must be publicly reachable by Meta's servers. On the Linux
 * production server expose port 8080 (or put Nginx in front with HTTPS).
 */
class WhatsAppWebhookController extends Controller
{
    /** Hub verification handshake required by Meta when registering the webhook. */
    public function verify(Request $request): Response
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $expectedToken = Settings::waVerifyToken();

        if ($mode === 'subscribe' && $token === $expectedToken && $expectedToken !== '') {
            return response((string) $challenge, 200);
        }

        return response('Forbidden', 403);
    }

    /** Handle incoming webhook events from Meta. */
    public function handle(Request $request): Response
    {
        // Verify the request signature when App Secret is configured.
        $appSecret = Settings::get('wa_app_secret');
        if ($appSecret) {
            $signature = $request->header('X-Hub-Signature-256', '');
            $expected  = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);
            if (! hash_equals($expected, $signature)) {
                return response('Forbidden', 403);
            }
        }

        $data = $request->json()->all();

        foreach ($data['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                foreach ($value['messages'] ?? [] as $message) {
                    $this->processMessage($message);
                }
            }
        }

        // Meta requires a 200 OK immediately.
        return response('OK', 200);
    }

    private function processMessage(array $message): void
    {
        // We only care about quick-reply button taps.
        if (($message['type'] ?? '') !== 'interactive') {
            return;
        }

        $interactive = $message['interactive'] ?? [];
        if (($interactive['type'] ?? '') !== 'button_reply') {
            return;
        }

        $payload = $interactive['button_reply']['id'] ?? '';

        if (str_starts_with($payload, 'RESOLVE_')) {
            $alertId = (int) substr($payload, strlen('RESOLVE_'));
            $this->resolveAlert($alertId);
        }
    }

    private function resolveAlert(int $alertId): void
    {
        $alert = Alert::find($alertId);

        if ($alert && $alert->status === 'open') {
            $alert->update([
                'status'      => 'resolved',
                'resolved_at' => now(),
            ]);
        }
    }
}
