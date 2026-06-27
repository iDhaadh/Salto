<?php

namespace App\Services;

use App\Support\BatteryStatus;
use App\Support\LockSnapshot;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Sends WhatsApp messages via the Meta WhatsApp Business Cloud API.
 *
 * Outbound alerts are not replies inside a 24h customer window, so they must
 * use a pre-approved message template. Configure the template names in
 * config/services.php (whatsapp.template_low / template_flat). Each template
 * is expected to take body parameters in this order:
 *   {{1}} lock name, {{2}} location, {{3}} battery status, {{4}} timestamp.
 */
class WhatsAppService
{
    public function isConfigured(): bool
    {
        return ! empty(config('services.whatsapp.token'))
            && ! empty(config('services.whatsapp.phone_id'));
    }

    /**
     * Send a battery template message to one recipient (E.164, no '+').
     *
     * @return array{ok:bool,error:?string,payload:array}
     */
    /**
     * @return array{ok:bool,error:?string,payload:array}
     */
    public function sendBatteryAlert(string $to, LockSnapshot $lock, BatteryStatus $status, string $reason = 'alert', ?int $alertId = null): array
    {
        $template = $status->isUrgent()
            ? config('services.whatsapp.template_flat')
            : config('services.whatsapp.template_low');

        $params = [
            $lock->name,
            $status->label(),
            now()->format('Y-m-d H:i'),
        ];

        // Include a "Resolved ✓" quick-reply button when we have a real alert ID
        // (not for test sends). The button payload carries the alert ID so the
        // webhook can resolve the correct alert when the user taps it.
        $resolveButton = ($alertId !== null && $reason !== 'test')
            ? [['type' => 'button', 'sub_type' => 'quick_reply', 'index' => '0',
                'parameters' => [['type' => 'payload', 'payload' => "RESOLVE_{$alertId}"]]]]
            : [];

        return $this->sendTemplate($to, $template, $params, $resolveButton);
    }

    /**
     * @param  array<int,string>  $bodyParams
     * @param  array<int,array>   $extraComponents  e.g. quick-reply button components
     * @return array{ok:bool,error:?string,payload:array}
     */
    public function sendTemplate(string $to, string $template, array $bodyParams, array $extraComponents = [], ?string $localeOverride = null): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'error' => 'WhatsApp Cloud API is not configured.', 'payload' => []];
        }

        $locale     = $localeOverride ?? config('services.whatsapp.template_locale', 'en');
        $components = [];

        if (! empty($bodyParams)) {
            $components[] = [
                'type'       => 'body',
                'parameters' => array_map(
                    fn ($p) => ['type' => 'text', 'text' => (string) $p],
                    $bodyParams,
                ),
            ];
        }

        $components = array_merge($components, $extraComponents);

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $this->normalizeNumber($to),
            'type'              => 'template',
            'template'          => [
                'name'       => $template,
                'language'   => ['code' => $locale],
                'components' => $components,
            ],
        ];

        try {
            $response = $this->post($payload);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage(), 'payload' => $payload];
        }

        if ($response->successful()) {
            return ['ok' => true, 'error' => null, 'payload' => $payload];
        }

        return [
            'ok' => false,
            'error' => $response->json('error.message') ?? ('HTTP '.$response->status()),
            'payload' => $payload,
        ];
    }

    protected function post(array $payload): Response
    {
        $version = config('services.whatsapp.api_version', 'v21.0');
        $phoneId = config('services.whatsapp.phone_id');
        $url = "https://graph.facebook.com/{$version}/{$phoneId}/messages";

        return Http::withToken(config('services.whatsapp.token'))
            ->timeout(20)
            ->retry(2, 500)
            ->post($url, $payload)
            ->throwIf(fn (Response $r) => $r->serverError());
    }

    /** Strip everything but digits (Cloud API wants E.164 without '+'). */
    protected function normalizeNumber(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number);

        if ($digits === '') {
            throw new RuntimeException("Invalid WhatsApp number: {$number}");
        }

        return $digits;
    }
}
