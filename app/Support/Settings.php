<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Runtime settings, editable from the dashboard. Values live in the
 * `settings` table and fall back to config/monitor.php defaults.
 */
class Settings
{
    private const CACHE_KEY = 'app_settings';

    /** @return array<string,string> */
    public static function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return Setting::query()->pluck('value', 'key')->all();
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        Setting::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
        Cache::forget(self::CACHE_KEY);
    }

    public static function pollMinutes(): int
    {
        return (int) self::get('poll_minutes', config('monitor.poll_minutes'));
    }

    public static function reminderHours(): int
    {
        return (int) self::get('reminder_hours', config('monitor.reminder_hours'));
    }

    public static function notifyOnRecovery(): bool
    {
        return (bool) (int) self::get('notify_on_recovery', (int) config('monitor.notify_on_recovery'));
    }

    public static function emailEnabled(): bool
    {
        return (bool) (int) self::get('email_enabled', (int) config('monitor.email_enabled'));
    }

    public static function whatsappEnabled(): bool
    {
        return (bool) (int) self::get('whatsapp_enabled', (int) config('monitor.whatsapp_enabled'));
    }

    /** @return array<int,string> */
    public static function emailRecipients(): array
    {
        return self::splitList(self::get('emails', config('monitor.emails')));
    }

    /** @return array<int,string> */
    public static function whatsappRecipients(): array
    {
        return self::splitList(self::get('whatsapp', config('monitor.whatsapp')));
    }

    // ── Email / SMTP ─────────────────────────────────────────────────────────
    public static function smtpHost(): string      { return (string) self::get('smtp_host',         env('MAIL_HOST',         '')); }
    public static function smtpPort(): int         { return (int)    self::get('smtp_port',         env('MAIL_PORT',         587)); }
    public static function smtpUsername(): string  { return (string) self::get('smtp_username',     env('MAIL_USERNAME',     '')); }
    public static function smtpPasswordSet(): bool { return self::get('smtp_password') !== null || (env('MAIL_PASSWORD') !== null && env('MAIL_PASSWORD') !== ''); }
    public static function smtpEncryption(): string{ return (string) self::get('smtp_encryption',   env('MAIL_ENCRYPTION',   'tls')); }
    public static function mailFromAddress(): string{ return (string) self::get('mail_from_address', env('MAIL_FROM_ADDRESS', '')); }
    public static function mailFromName(): string  { return (string) self::get('mail_from_name',    env('MAIL_FROM_NAME',    config('app.name'))); }

    // ── WhatsApp Cloud API ───────────────────────────────────────────────────
    public static function waPhoneId(): string     { return (string) self::get('wa_phone_id',       env('WHATSAPP_PHONE_ID',       '')); }
    public static function waApiVersion(): string  { return (string) self::get('wa_api_version',    env('WHATSAPP_API_VERSION',    'v21.0')); }
    public static function waTemplateLow(): string    { return (string) self::get('wa_template_low',    env('WHATSAPP_TEMPLATE_LOW',    'battery_low_alert')); }
    public static function waTemplateFlat(): string   { return (string) self::get('wa_template_flat',   env('WHATSAPP_TEMPLATE_FLAT',   'battery_flat_alert')); }
    public static function waTemplateNormal(): string { return (string) self::get('wa_template_normal', env('WHATSAPP_TEMPLATE_NORMAL', 'battery_normal_alert')); }
    public static function waTemplateLocale(): string { return (string) self::get('wa_template_locale', env('WHATSAPP_TEMPLATE_LOCALE', 'en')); }
    public static function waTokenSet(): bool        { return self::get('wa_token') !== null || (env('WHATSAPP_TOKEN') !== null && env('WHATSAPP_TOKEN') !== ''); }
    public static function waVerifyToken(): string   { return (string) self::get('wa_verify_token', env('WHATSAPP_VERIFY_TOKEN', '')); }
    public static function waAppSecretSet(): bool    { return self::get('wa_app_secret') !== null; }
    public static function waWebhookUrl(): string    { return (string) self::get('wa_webhook_url', env('APP_URL', '') . '/api/whatsapp/webhook'); }

    // ── SALTO MS SQL ─────────────────────────────────────────────────────────
    public static function saltoHost(): string
    {
        return (string) self::get('salto_host', env('SALTO_DB_HOST', ''));
    }

    public static function saltoPort(): int
    {
        return (int) self::get('salto_port', env('SALTO_DB_PORT', 1433));
    }

    public static function saltoDatabase(): string
    {
        return (string) self::get('salto_database', env('SALTO_DB_DATABASE', 'ProAccessSpace'));
    }

    public static function saltoUsername(): string
    {
        return (string) self::get('salto_username', env('SALTO_DB_USERNAME', ''));
    }

    public static function saltoPasswordSet(): bool
    {
        return self::get('salto_password') !== null || env('SALTO_DB_PASSWORD') !== null;
    }

    public static function saltoEncrypt(): string
    {
        return (string) self::get('salto_encrypt', env('SALTO_DB_ENCRYPT', 'yes'));
    }

    public static function saltoTrustCert(): bool
    {
        return filter_var(self::get('salto_trust_cert', env('SALTO_DB_TRUST_CERT', 'true')), FILTER_VALIDATE_BOOLEAN);
    }

    // Schema mapping
    public static function saltoTable(): string
    {
        return (string) self::get('salto_lock_table', env('SALTO_LOCK_TABLE', 'tb_DOOR'));
    }

    public static function saltoColId(): string      { return (string) self::get('salto_col_id',       env('SALTO_COL_ID',       'ID')); }
    public static function saltoColName(): string    { return (string) self::get('salto_col_name',     env('SALTO_COL_NAME',     'NAME')); }
    public static function saltoColLocation(): string{ return (string) self::get('salto_col_location', env('SALTO_COL_LOCATION', 'NAME')); }
    public static function saltoColBattery(): string { return (string) self::get('salto_col_battery',  env('SALTO_COL_BATTERY',  'BATTERY_STATUS')); }
    public static function saltoColLastSeen(): string{ return (string) self::get('salto_col_lastseen', env('SALTO_COL_LASTSEEN', 'LAST_UPDATE')); }
    public static function saltoRawSql(): string     { return (string) self::get('salto_raw_sql',      env('SALTO_RAW_SQL',      '')); }

    /** @return array<int,string> */
    public static function splitList(?string $value): array
    {
        return collect(preg_split('/[\s,;]+/', (string) $value))
            ->map(fn ($v) => trim($v))
            ->filter()
            ->values()
            ->all();
    }
}
