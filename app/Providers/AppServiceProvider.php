<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        try {
            $this->applySaltoSettings();
            $this->applyEmailSettings();
            $this->applyWhatsAppSettings();
        } catch (\Throwable) {
            // App DB not ready (first boot / migration) — fall back to .env config.
        }
    }

    private function applyEmailSettings(): void
    {
        $host = \App\Support\Settings::smtpHost();
        if (! $host) {
            return;
        }
        config([
            'mail.default'                    => 'smtp',
            'mail.mailers.smtp.host'          => $host,
            'mail.mailers.smtp.port'          => \App\Support\Settings::smtpPort(),
            'mail.mailers.smtp.username'      => \App\Support\Settings::smtpUsername(),
            'mail.mailers.smtp.password'      => \App\Support\Settings::get('smtp_password', env('MAIL_PASSWORD', '')),
            'mail.mailers.smtp.encryption'    => \App\Support\Settings::smtpEncryption() === 'none' ? null : \App\Support\Settings::smtpEncryption(),
            'mail.from.address'               => \App\Support\Settings::mailFromAddress(),
            'mail.from.name'                  => \App\Support\Settings::mailFromName(),
        ]);
    }

    private function applyWhatsAppSettings(): void
    {
        $token = \App\Support\Settings::get('wa_token', env('WHATSAPP_TOKEN'));
        if (! $token) {
            return;
        }
        config([
            'services.whatsapp.token'           => $token,
            'services.whatsapp.phone_id'        => \App\Support\Settings::waPhoneId(),
            'services.whatsapp.api_version'     => \App\Support\Settings::waApiVersion(),
            'services.whatsapp.template_low'    => \App\Support\Settings::waTemplateLow(),
            'services.whatsapp.template_flat'   => \App\Support\Settings::waTemplateFlat(),
            'services.whatsapp.template_locale' => \App\Support\Settings::waTemplateLocale(),
        ]);
    }

    private function applySaltoSettings(): void
    {
        $host = \App\Support\Settings::get('salto_host');

        if ($host) {
            config([
                'database.connections.salto.host'                    => $host,
                'database.connections.salto.port'                    => (int) \App\Support\Settings::get('salto_port', 1433),
                'database.connections.salto.database'                => \App\Support\Settings::get('salto_database', env('SALTO_DB_DATABASE', 'ProAccessSpace')),
                'database.connections.salto.username'                => \App\Support\Settings::get('salto_username', ''),
                'database.connections.salto.password'                => \App\Support\Settings::get('salto_password', ''),
                'database.connections.salto.encrypt'                 => \App\Support\Settings::get('salto_encrypt', 'yes'),
                'database.connections.salto.trust_server_certificate'=> \App\Support\Settings::get('salto_trust_cert', 'true'),
            ]);
        }

        // Schema mapping overrides.
        foreach (['id', 'name', 'location', 'battery', 'lastseen'] as $col) {
            if ($val = \App\Support\Settings::get("salto_col_{$col}")) {
                config(["salto.query.columns.{$col}" => $val]);
            }
        }
        if ($table = \App\Support\Settings::get('salto_lock_table')) {
            config(['salto.query.table' => $table]);
        }
        if ($raw = \App\Support\Settings::get('salto_raw_sql')) {
            config(['salto.query.raw_sql' => $raw]);
        }
    }
}
