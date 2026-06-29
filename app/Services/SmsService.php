<?php

namespace App\Services;

use App\Support\Settings;
use Illuminate\Support\Facades\Http;

class SmsService
{
    public function send(string $to, string $message): array
    {
        $endpoint = Settings::smsEndpoint();
        if (! $endpoint) {
            return ['ok' => false, 'error' => 'No SMS endpoint configured.'];
        }

        $from   = Settings::smsSenderId();
        $method = strtolower(Settings::smsMethod());

        $url  = $this->fill($endpoint, $to, $message, $from);
        $body = $this->fill(Settings::smsBodyTemplate(), $to, $message, $from);

        try {
            $request = Http::timeout(15);

            match (Settings::smsAuthType()) {
                'bearer' => $request = $request->withToken((string) Settings::get('sms_bearer_token', '')),
                'basic'  => $request = $request->withBasicAuth(
                                (string) Settings::get('sms_basic_username', ''),
                                (string) Settings::get('sms_basic_password', '')
                            ),
                'apikey' => $request = $request->withHeaders([
                                (string) Settings::get('sms_apikey_header', 'X-API-Key') => (string) Settings::get('sms_apikey_value', ''),
                            ]),
                default  => null,
            };

            if ($method === 'post' && $body !== '') {
                $decoded = json_decode($body, true);
                $response = (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
                    ? $request->asJson()->post($url, $decoded)
                    : $request->withBody($body, 'text/plain')->post($url);
            } else {
                $response = $request->get($url);
            }

            return $response->successful()
                ? ['ok' => true]
                : ['ok' => false, 'error' => "HTTP {$response->status()}: " . substr($response->body(), 0, 300)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function fill(string $template, string $to, string $message, string $from): string
    {
        return str_replace(['{{to}}', '{{message}}', '{{from}}'], [$to, $message, $from], $template);
    }
}
