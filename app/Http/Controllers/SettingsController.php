<?php

namespace App\Http\Controllers;

use App\Services\AlertNotifier;
use App\Services\SmsService;
use App\Services\WhatsAppService;
use App\Support\BatteryStatus;
use App\Support\LockSnapshot;
use App\Support\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', [
            // active tab (flash or default)
            'activeTab'        => session('active_tab', 'monitoring'),
            // Monitoring & alerts
            'pollMinutes'      => Settings::pollMinutes(),
            'reminderHours'    => Settings::reminderHours(),
            'notifyOnRecovery' => Settings::notifyOnRecovery(),
            'emailEnabled'     => Settings::emailEnabled(),
            'whatsappEnabled'  => Settings::whatsappEnabled(),
            'emails'           => implode(', ', Settings::emailRecipients()),
            'whatsapp'         => implode(', ', Settings::whatsappRecipients()),
            // Badge flags — check DB only (not .env fallbacks) so "Configured" is accurate
            'smtpConfigured'   => (bool) Settings::get('smtp_host'),
            'waConfigured'     => Settings::get('wa_token') !== null && (bool) Settings::get('wa_phone_id'),
            'saltoConfigured'  => (bool) Settings::get('salto_host'),
            'saltoMonitoringEnabled' => Settings::saltoMonitoringEnabled(),
            'smsConfigured'    => Settings::smsConfigured(),
            // SMS
            'smsEnabled'         => Settings::smsEnabled(),
            'smsProviderName'    => Settings::smsProviderName(),
            'smsSenderId'        => Settings::smsSenderId(),
            'smsEndpoint'        => Settings::smsEndpoint(),
            'smsMethod'          => Settings::smsMethod(),
            'smsAuthType'        => Settings::smsAuthType(),
            'smsBearerTokenSet'  => Settings::smsBearerTokenSet(),
            'smsBodyTemplate'    => Settings::smsBodyTemplate(),
            'smsRecipients'      => implode(', ', Settings::smsRecipients()),
            'smsBasicUsername'   => (string) Settings::get('sms_basic_username', ''),
            'smsApiKeyHeader'    => (string) Settings::get('sms_apikey_header', 'X-API-Key'),
            // Email / SMTP
            'smtpHost'         => Settings::smtpHost(),
            'smtpPort'         => Settings::smtpPort(),
            'smtpUsername'     => Settings::smtpUsername(),
            'smtpPasswordSet'  => Settings::smtpPasswordSet(),
            'smtpEncryption'   => Settings::smtpEncryption(),
            'mailFromAddress'  => Settings::mailFromAddress(),
            'mailFromName'     => Settings::mailFromName(),
            // WhatsApp
            'waPhoneId'        => Settings::waPhoneId(),
            'waApiVersion'     => Settings::waApiVersion(),
            'waTemplateLow'    => Settings::waTemplateLow(),
            'waTemplateFlat'   => Settings::waTemplateFlat(),
            'waTemplateNormal' => Settings::waTemplateNormal(),
            'waTemplateLocale' => Settings::waTemplateLocale(),
            'waTokenSet'       => Settings::waTokenSet(),
            'waVerifyToken'    => Settings::waVerifyToken(),
            'waAppSecretSet'   => Settings::waAppSecretSet(),
            'waWebhookUrl'     => Settings::waWebhookUrl(),
            // SALTO DB connection
            'saltoHost'        => Settings::saltoHost(),
            'saltoPort'        => Settings::saltoPort(),
            'saltoDatabase'    => Settings::saltoDatabase(),
            'saltoUsername'    => Settings::saltoUsername(),
            'saltoPasswordSet' => Settings::saltoPasswordSet(),
            'saltoEncrypt'     => Settings::saltoEncrypt(),
            'saltoTrustCert'   => Settings::saltoTrustCert(),
            // SALTO schema mapping
            'saltoTable'       => Settings::saltoTable(),
            'saltoColId'       => Settings::saltoColId(),
            'saltoColName'     => Settings::saltoColName(),
            'saltoColLocation' => Settings::saltoColLocation(),
            'saltoColBattery'  => Settings::saltoColBattery(),
            'saltoColLastSeen' => Settings::saltoColLastSeen(),
            'saltoRawSql'      => Settings::saltoRawSql(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'poll_minutes' => ['required', 'integer', 'min:1', 'max:59'],
            'reminder_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'emails' => ['nullable', 'string'],
            'whatsapp' => ['nullable', 'string'],
        ]);

        Settings::put('poll_minutes', $data['poll_minutes']);
        Settings::put('reminder_hours', $data['reminder_hours']);
        Settings::put('emails', $data['emails'] ?? '');
        Settings::put('whatsapp', $data['whatsapp'] ?? '');
        Settings::put('email_enabled', $request->boolean('email_enabled') ? 1 : 0);
        Settings::put('whatsapp_enabled', $request->boolean('whatsapp_enabled') ? 1 : 0);
        Settings::put('notify_on_recovery', $request->boolean('notify_on_recovery') ? 1 : 0);

        return redirect()->route('settings.edit')
            ->with('status', 'Settings saved. Note: changing the poll interval takes effect after the next scheduler reload.');
    }

    // ── Email ──────────────────────────────────────────────────────────────

    public function updateEmail(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'smtp_host'        => ['required', 'string', 'max:255'],
            'smtp_port'        => ['required', 'integer', 'min:1', 'max:65535'],
            'smtp_username'    => ['nullable', 'string', 'max:255'],
            'smtp_password'    => ['nullable', 'string', 'max:255'],
            'smtp_encryption'  => ['required', 'in:tls,ssl,none'],
            'mail_from_address'=> ['required', 'email', 'max:255'],
            'mail_from_name'   => ['required', 'string', 'max:255'],
        ]);

        Settings::put('smtp_host',          $data['smtp_host']);
        Settings::put('smtp_port',          $data['smtp_port']);
        Settings::put('smtp_username',      $data['smtp_username'] ?? '');
        Settings::put('smtp_encryption',    $data['smtp_encryption']);
        Settings::put('mail_from_address',  $data['mail_from_address']);
        Settings::put('mail_from_name',     $data['mail_from_name']);
        Settings::put('email_enabled',      $request->boolean('email_enabled') ? 1 : 0);

        if (filled($data['smtp_password'] ?? null)) {
            Settings::put('smtp_password', $data['smtp_password']);
        }

        return redirect()->route('settings.edit')
            ->with('status', 'Email settings saved.')
            ->with('active_tab', 'email');
    }

    public function testEmail(Request $request): JsonResponse
    {
        $to = trim((string) $request->input('test_email'));
        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['ok' => false, 'message' => 'Enter a valid email address.']);
        }

        $host = Settings::smtpHost();
        if (! $host) {
            return response()->json(['ok' => false, 'message' => 'No SMTP host configured. Save email settings first.']);
        }

        $enc = Settings::smtpEncryption();

        config([
            'mail.default'                 => 'smtp',
            'mail.mailers.smtp.host'       => $host,
            'mail.mailers.smtp.port'       => Settings::smtpPort(),
            'mail.mailers.smtp.username'   => Settings::smtpUsername(),
            'mail.mailers.smtp.password'   => Settings::get('smtp_password', env('MAIL_PASSWORD', '')),
            'mail.mailers.smtp.encryption' => $enc === 'none' ? null : $enc,
            'mail.from.address'            => Settings::mailFromAddress() ?: $to,
            'mail.from.name'               => Settings::mailFromName(),
        ]);

        try {
            Mail::mailer('smtp')->raw(
                'This is a test email from SALTO Battery Monitor. Your SMTP settings are working correctly.',
                fn ($msg) => $msg->to($to)->subject('[SALTO Battery Monitor] Test email')
            );
            return response()->json(['ok' => true, 'message' => "Test email sent to {$to}. Check your inbox."]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    // ── WhatsApp ───────────────────────────────────────────────────────────

    public function updateWhatsApp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'wa_token'           => ['nullable', 'string'],
            'wa_phone_id'        => ['required', 'string', 'max:64'],
            'wa_api_version'     => ['required', 'regex:/^v\d+\.\d+$/'],
            'wa_verify_token'    => ['nullable', 'string', 'max:255'],
            'wa_app_secret'      => ['nullable', 'string', 'max:255'],
            'wa_webhook_url'     => ['nullable', 'string', 'max:512'],
            'wa_template_low'    => ['required', 'string', 'max:128'],
            'wa_template_flat'   => ['required', 'string', 'max:128'],
            'wa_template_normal' => ['nullable', 'string', 'max:128'],
            'wa_template_locale' => ['required', 'string', 'max:16'],
        ]);

        Settings::put('whatsapp_enabled',   $request->boolean('whatsapp_enabled') ? 1 : 0);
        Settings::put('wa_phone_id',        $data['wa_phone_id']);
        Settings::put('wa_api_version',     $data['wa_api_version']);
        Settings::put('wa_template_low',    $data['wa_template_low']);
        Settings::put('wa_template_flat',   $data['wa_template_flat']);
        Settings::put('wa_template_normal', $data['wa_template_normal'] ?? '');
        Settings::put('wa_template_locale', $data['wa_template_locale']);
        Settings::put('wa_verify_token',    $data['wa_verify_token'] ?? '');
        Settings::put('wa_webhook_url',     $data['wa_webhook_url'] ?? '');

        if (filled($data['wa_token'] ?? null)) {
            Settings::put('wa_token', $data['wa_token']);
        }
        if (filled($data['wa_app_secret'] ?? null)) {
            Settings::put('wa_app_secret', $data['wa_app_secret']);
        }

        return redirect()->route('settings.edit')
            ->with('status', 'WhatsApp API settings saved.')
            ->with('active_tab', 'whatsapp');
    }

    public function testWhatsApp(Request $request): JsonResponse
    {
        $to = trim((string) $request->input('test_phone'));
        if (! $to) {
            return response()->json(['ok' => false, 'message' => 'Enter a phone number in E.164 format (e.g. +9607712345).']);
        }

        $token   = Settings::get('wa_token', env('WHATSAPP_TOKEN', ''));
        $phoneId = Settings::waPhoneId();

        if (! $token || ! $phoneId) {
            return response()->json(['ok' => false, 'message' => 'API Token and Phone Number ID are required. Save the settings first.']);
        }

        config([
            'services.whatsapp.token'           => $token,
            'services.whatsapp.phone_id'        => $phoneId,
            'services.whatsapp.api_version'     => Settings::waApiVersion(),
            'services.whatsapp.template_low'    => Settings::waTemplateLow(),
            'services.whatsapp.template_flat'   => Settings::waTemplateFlat(),
            'services.whatsapp.template_normal' => Settings::waTemplateNormal(),
            'services.whatsapp.template_locale' => Settings::waTemplateLocale(),
        ]);

        try {
            $wa = app(WhatsAppService::class);

            // Try the configured battery template first.
            // If it fails with 132001 (template not found / in review), fall back
            // to the built-in hello_world template so the API credentials can still
            // be verified while the battery templates are pending approval.
            $lock   = LockSnapshot::make('TEST', 'Test Lock', 'Test Location', now()->toImmutable());
            $result = $wa->sendBatteryAlert($to, $lock, BatteryStatus::Low, 'test');

            if (! $result['ok'] && str_contains($result['error'] ?? '', '132001')) {
                // hello_world is always en_US regardless of the configured locale.
                $fallback = $wa->sendTemplate($to, 'hello_world', [], [], 'en_US');
                if ($fallback['ok']) {
                    return response()->json([
                        'ok'      => true,
                        'message' => "✓ API credentials work — hello_world template delivered to {$to}. "
                                   . "Your battery alert templates are still in review; they will be used automatically once approved.",
                    ]);
                }
                return response()->json(['ok' => false, 'message' => $fallback['error'] ?? 'Unknown API error.']);
            }

            return $result['ok']
                ? response()->json(['ok' => true, 'message' => "Battery alert template sent to {$to}. Check the phone."])
                : response()->json(['ok' => false, 'message' => $result['error'] ?? 'Unknown API error.']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    // ── SALTO DB Connection ────────────────────────────────────────────────

    public function updateConnection(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'salto_host'         => ['required', 'string', 'max:255'],
            'salto_port'         => ['required', 'integer', 'min:1', 'max:65535'],
            'salto_database'     => ['required', 'string', 'max:255'],
            'salto_username'     => ['required', 'string', 'max:255'],
            'salto_password'     => ['nullable', 'string', 'max:255'],
            'salto_encrypt'      => ['required', 'in:yes,no,strict'],
            'salto_lock_table'   => ['nullable', 'string', 'max:128'],
            'salto_col_id'       => ['nullable', 'string', 'max:128'],
            'salto_col_name'     => ['nullable', 'string', 'max:128'],
            'salto_col_location' => ['nullable', 'string', 'max:128'],
            'salto_col_battery'  => ['nullable', 'string', 'max:128'],
            'salto_col_lastseen' => ['nullable', 'string', 'max:128'],
            'salto_raw_sql'      => ['nullable', 'string'],
        ]);

        Settings::put('salto_host',         $data['salto_host']);
        Settings::put('salto_port',         $data['salto_port']);
        Settings::put('salto_database',     $data['salto_database']);
        Settings::put('salto_username',     $data['salto_username']);
        Settings::put('salto_encrypt',      $data['salto_encrypt']);
        Settings::put('salto_trust_cert',         $request->boolean('salto_trust_cert') ? 'true' : 'false');
        Settings::put('salto_monitoring_enabled', $request->boolean('salto_monitoring_enabled') ? 1 : 0);

        // Only overwrite the password if the user typed one.
        if (filled($data['salto_password'] ?? null)) {
            Settings::put('salto_password', $data['salto_password']);
        }

        // Schema mapping
        foreach (['salto_lock_table', 'salto_col_id', 'salto_col_name', 'salto_col_location', 'salto_col_battery', 'salto_col_lastseen', 'salto_raw_sql'] as $key) {
            Settings::put($key, $data[$key] ?? '');
        }

        // Purge the cached connection so the next salto:check uses the new creds.
        DB::purge('salto');

        return redirect()->route('settings.edit')
            ->with('status', 'SALTO database connection saved.')
            ->with('active_tab', 'connection');
    }

    public function testConnection(): JsonResponse
    {
        // Apply current settings to the runtime config before testing.
        try {
            $host = Settings::saltoHost();
            if (! $host) {
                return response()->json(['ok' => false, 'message' => 'No host configured. Save the connection settings first.']);
            }

            config([
                'database.connections.salto.host'                     => $host,
                'database.connections.salto.port'                     => Settings::saltoPort(),
                'database.connections.salto.database'                 => Settings::saltoDatabase(),
                'database.connections.salto.username'                 => Settings::saltoUsername(),
                'database.connections.salto.password'                 => Settings::get('salto_password', env('SALTO_DB_PASSWORD', '')),
                'database.connections.salto.encrypt'                  => Settings::saltoEncrypt(),
                'database.connections.salto.trust_server_certificate' => Settings::saltoTrustCert() ? 'true' : 'false',
            ]);

            DB::purge('salto');
            DB::connection('salto')->getPdo();

            $count = DB::connection('salto')->table(Settings::saltoTable())->count();

            return response()->json([
                'ok'      => true,
                'message' => "Connected successfully. Found {$count} row(s) in [" . Settings::saltoTable() . "].",
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    // ── SMS ───────────────────────────────────────────────────────────────────

    public function updateSms(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sms_provider_name' => ['nullable', 'string', 'max:128'],
            'sms_sender_id'     => ['nullable', 'string', 'max:64'],
            'sms_endpoint'      => ['required', 'url', 'max:512'],
            'sms_method'        => ['required', 'in:GET,POST'],
            'sms_auth_type'     => ['required', 'in:none,bearer,basic,apikey'],
            'sms_bearer_token'  => ['nullable', 'string'],
            'sms_basic_username'=> ['nullable', 'string', 'max:255'],
            'sms_basic_password'=> ['nullable', 'string'],
            'sms_apikey_header' => ['nullable', 'string', 'max:128'],
            'sms_apikey_value'  => ['nullable', 'string'],
            'sms_body_template' => ['nullable', 'string'],
            'sms_recipients'    => ['nullable', 'string'],
        ]);

        Settings::put('sms_enabled',       $request->boolean('sms_enabled') ? 1 : 0);
        Settings::put('sms_provider_name', $data['sms_provider_name'] ?? '');
        Settings::put('sms_sender_id',     $data['sms_sender_id'] ?? '');
        Settings::put('sms_endpoint',      $data['sms_endpoint']);
        Settings::put('sms_method',        $data['sms_method']);
        Settings::put('sms_auth_type',     $data['sms_auth_type']);
        Settings::put('sms_basic_username',$data['sms_basic_username'] ?? '');
        Settings::put('sms_apikey_header', $data['sms_apikey_header'] ?? 'X-API-Key');
        Settings::put('sms_body_template', $data['sms_body_template'] ?? '');
        Settings::put('sms_recipients',    $data['sms_recipients'] ?? '');

        if (filled($data['sms_bearer_token'] ?? null)) {
            Settings::put('sms_bearer_token', $data['sms_bearer_token']);
        }
        if (filled($data['sms_basic_password'] ?? null)) {
            Settings::put('sms_basic_password', $data['sms_basic_password']);
        }
        if (filled($data['sms_apikey_value'] ?? null)) {
            Settings::put('sms_apikey_value', $data['sms_apikey_value']);
        }

        return redirect()->route('settings.edit')
            ->with('status', 'SMS settings saved.')
            ->with('active_tab', 'sms');
    }

    public function testSms(Request $request, SmsService $sms): JsonResponse
    {
        $to = trim((string) $request->input('test_phone'));
        if (! $to) {
            return response()->json(['ok' => false, 'message' => 'Enter a phone number in E.164 format (e.g. +9607712345).']);
        }

        if (! Settings::smsEndpoint()) {
            return response()->json(['ok' => false, 'message' => 'No SMS endpoint configured. Save the settings first.']);
        }

        $result = $sms->send($to, 'SALTO Battery Monitor — this is a test message.');

        return $result['ok']
            ? response()->json(['ok' => true, 'message' => "Test SMS sent to {$to}."])
            : response()->json(['ok' => false, 'message' => $result['error'] ?? 'Unknown error.']);
    }

    // ── Test all channels ──────────────────────────────────────────────────────

    public function test(AlertNotifier $notifier): RedirectResponse
    {
        $lock = LockSnapshot::make(
            saltoId: 'TEST-LOCK',
            name: 'Test Lock (Front Entrance)',
            location: 'Demo / Reception',
            lastSeenAt: now()->toImmutable(),
        );

        $jobs = $notifier->notify($lock, BatteryStatus::Low, 'test');

        $message = $jobs > 0
            ? "Dispatched {$jobs} test notification(s). Make sure the queue worker is running to deliver them."
            : 'No notifications sent — enable a channel and add at least one recipient first.';

        return redirect()->route('settings.edit')->with('status', $message);
    }
}
