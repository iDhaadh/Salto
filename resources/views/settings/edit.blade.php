@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<h1 class="h3 mb-3">Settings</h1>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

{{-- Tab navigation --}}
<ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $activeTab === 'monitoring' ? 'active' : '' }}"
                data-bs-toggle="tab" data-bs-target="#pane-monitoring" type="button" role="tab">
            <i class="bi bi-bell me-1"></i> Monitoring &amp; Alerts
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $activeTab === 'email' ? 'active' : '' }}"
                data-bs-toggle="tab" data-bs-target="#pane-email" type="button" role="tab">
            <i class="bi bi-envelope me-1"></i> Email / SMTP
            @if($smtpConfigured)
                @if($emailEnabled)
                    <span class="badge bg-success ms-1">Enabled</span>
                @else
                    <span class="badge bg-secondary ms-1">Disabled</span>
                @endif
            @else
                <span class="badge bg-warning text-dark ms-1">Not set</span>
            @endif
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $activeTab === 'whatsapp' ? 'active' : '' }}"
                data-bs-toggle="tab" data-bs-target="#pane-whatsapp" type="button" role="tab">
            <i class="bi bi-whatsapp me-1"></i> WhatsApp API
            @if($waConfigured)
                @if($whatsappEnabled)
                    <span class="badge bg-success ms-1">Enabled</span>
                @else
                    <span class="badge bg-secondary ms-1">Disabled</span>
                @endif
            @else
                <span class="badge bg-warning text-dark ms-1">Not set</span>
            @endif
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $activeTab === 'connection' ? 'active' : '' }}"
                data-bs-toggle="tab" data-bs-target="#pane-connection" type="button" role="tab">
            <i class="bi bi-database me-1"></i> SALTO Database
            @if($saltoConfigured)
                @if($saltoMonitoringEnabled)
                    <span class="badge bg-success ms-1">Enabled</span>
                @else
                    <span class="badge bg-secondary ms-1">Disabled</span>
                @endif
            @else
                <span class="badge bg-warning text-dark ms-1">Not set</span>
            @endif
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link {{ $activeTab === 'sms' ? 'active' : '' }}"
                data-bs-toggle="tab" data-bs-target="#pane-sms" type="button" role="tab">
            <i class="bi bi-phone me-1"></i> SMS
            @if($smsConfigured)
                @if($smsEnabled)
                    <span class="badge bg-success ms-1">Enabled</span>
                @else
                    <span class="badge bg-secondary ms-1">Disabled</span>
                @endif
            @else
                <span class="badge bg-warning text-dark ms-1">Not set</span>
            @endif
        </button>
    </li>
</ul>

<div class="tab-content" id="settingsTabContent">

{{-- ══════════════════════════════════════════════════════════════════════
     TAB 1 — Monitoring & Alerts
════════════════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade {{ $activeTab === 'monitoring' ? 'show active' : '' }}"
     id="pane-monitoring" role="tabpanel">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf @method('PUT')

                        <h2 class="h6 text-uppercase text-muted">Monitoring</h2>
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <label class="form-label">Poll interval (minutes)</label>
                                <input type="number" min="1" max="59" name="poll_minutes"
                                       value="{{ old('poll_minutes', $pollMinutes) }}" class="form-control">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Reminder every (hours)</label>
                                <input type="number" min="1" max="168" name="reminder_hours"
                                       value="{{ old('reminder_hours', $reminderHours) }}" class="form-control">
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="notify_on_recovery"
                                           id="recov" {{ $notifyOnRecovery ? 'checked' : '' }}>
                                    <label class="form-check-label" for="recov">Notify when a battery recovers to normal</label>
                                </div>
                            </div>
                        </div>

                        <h2 class="h6 text-uppercase text-muted">Email recipients</h2>
                        <div class="mb-4">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="email_enabled"
                                       id="email_enabled" {{ $emailEnabled ? 'checked' : '' }}>
                                <label class="form-check-label" for="email_enabled">Send email alerts</label>
                            </div>
                            <label class="form-label">Recipients (comma separated)</label>
                            <textarea name="emails" rows="2" class="form-control"
                                      placeholder="ops@example.com, engineering@example.com">{{ old('emails', $emails) }}</textarea>
                        </div>

                        <h2 class="h6 text-uppercase text-muted">WhatsApp recipients</h2>
                        <div class="mb-4">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="whatsapp_enabled"
                                       id="wa_enabled" {{ $whatsappEnabled ? 'checked' : '' }}>
                                <label class="form-check-label" for="wa_enabled">Send WhatsApp alerts</label>
                            </div>
                            <label class="form-label">Numbers in E.164 format (comma separated)</label>
                            <textarea name="whatsapp" rows="2" class="form-control"
                                      placeholder="+9607712345, +9609998888">{{ old('whatsapp', $whatsapp) }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Save settings</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h2 class="h6">Send test notification</h2>
                    <p class="text-muted small">Dispatches a sample low-battery alert through all enabled channels to the recipients above.</p>
                    <form method="POST" action="{{ route('settings.test') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-send"></i> Send test notification
                        </button>
                    </form>
                    <hr>
                    <p class="text-muted small mb-0">Delivery is via the queue worker — ensure <code>queue:work</code> is running.</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     TAB 2 — Email / SMTP
════════════════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade {{ $activeTab === 'email' ? 'show active' : '' }}"
     id="pane-email" role="tabpanel">

    <form method="POST" action="{{ route('settings.email.update') }}">
        @csrf @method('PUT')
        <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-body-tertiary rounded border">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" role="switch"
                       name="email_enabled" id="email_enabled_toggle"
                       {{ $emailEnabled ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="email_enabled_toggle">
                    Email notifications enabled
                </label>
            </div>
            <span class="text-muted small">Toggle off to pause all email alerts without removing your settings.</span>
        </div>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header fw-semibold">
                        <i class="bi bi-server me-1"></i> SMTP server
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-8">
                                <label class="form-label">SMTP host <span class="text-danger">*</span></label>
                                <input type="text" name="smtp_host" class="form-control @error('smtp_host') is-invalid @enderror"
                                       value="{{ old('smtp_host', $smtpHost) }}"
                                       placeholder="smtp.office365.com">
                                @error('smtp_host')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label">Port</label>
                                <input type="number" name="smtp_port" class="form-control @error('smtp_port') is-invalid @enderror"
                                       value="{{ old('smtp_port', $smtpPort) }}" min="1" max="65535">
                                @error('smtp_port')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Username</label>
                                <input type="text" name="smtp_username" autocomplete="username"
                                       class="form-control @error('smtp_username') is-invalid @enderror"
                                       value="{{ old('smtp_username', $smtpUsername) }}"
                                       placeholder="alerts@yourdomain.com">
                                @error('smtp_username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">
                                    Password
                                    @if($smtpPasswordSet)<span class="badge bg-secondary ms-1">saved</span>@endif
                                </label>
                                <input type="password" name="smtp_password" autocomplete="new-password"
                                       class="form-control"
                                       placeholder="{{ $smtpPasswordSet ? 'Leave blank to keep current' : 'Enter password' }}">
                                <div class="form-text">Leave blank to keep the existing password.</div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Encryption</label>
                                <select name="smtp_encryption" class="form-select">
                                    <option value="tls"  {{ $smtpEncryption === 'tls'  ? 'selected' : '' }}>TLS / STARTTLS (port 587)</option>
                                    <option value="ssl"  {{ $smtpEncryption === 'ssl'  ? 'selected' : '' }}>SSL (port 465)</option>
                                    <option value="none" {{ $smtpEncryption === 'none' ? 'selected' : '' }}>None (port 25)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header fw-semibold">
                        <i class="bi bi-person-lines-fill me-1"></i> Sender identity
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-7">
                                <label class="form-label">From address <span class="text-danger">*</span></label>
                                <input type="email" name="mail_from_address"
                                       class="form-control @error('mail_from_address') is-invalid @enderror"
                                       value="{{ old('mail_from_address', $mailFromAddress) }}"
                                       placeholder="noreply@yourdomain.com">
                                @error('mail_from_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-5">
                                <label class="form-label">From name <span class="text-danger">*</span></label>
                                <input type="text" name="mail_from_name"
                                       class="form-control @error('mail_from_name') is-invalid @enderror"
                                       value="{{ old('mail_from_name', $mailFromName) }}"
                                       placeholder="SALTO Battery Monitor">
                                @error('mail_from_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Save email settings
                </button>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="h6">Test email delivery</h2>
                        <p class="text-muted small">Sends a plain-text test message directly over SMTP. Save your settings first.</p>
                        <div class="mb-2">
                            <label class="form-label small">Send test to</label>
                            <input type="email" id="test-email-addr" class="form-control form-control-sm"
                                   placeholder="you@yourdomain.com">
                        </div>
                        <button type="button" id="btn-test-email" class="btn btn-outline-primary w-100">
                            <i class="bi bi-send me-1"></i> Send test email
                        </button>
                        <div id="email-test-result" class="mt-2" hidden></div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     TAB 3 — WhatsApp API
════════════════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade {{ $activeTab === 'whatsapp' ? 'show active' : '' }}"
     id="pane-whatsapp" role="tabpanel">

    <form method="POST" action="{{ route('settings.whatsapp.update') }}">
        @csrf @method('PUT')
        <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-body-tertiary rounded border">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" role="switch"
                       name="whatsapp_enabled" id="whatsapp_enabled_toggle"
                       {{ $whatsappEnabled ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="whatsapp_enabled_toggle">
                    WhatsApp notifications enabled
                </label>
            </div>
            <span class="text-muted small">Toggle off to pause all WhatsApp alerts without removing your settings.</span>
        </div>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header fw-semibold">
                        <i class="bi bi-whatsapp me-1"></i> Meta WhatsApp Cloud API credentials
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">
                                    API Token (permanent access token)
                                    @if($waTokenSet)<span class="badge bg-secondary ms-1">saved</span>@endif
                                </label>
                                <input type="password" name="wa_token" autocomplete="new-password"
                                       class="form-control font-monospace"
                                       placeholder="{{ $waTokenSet ? 'Leave blank to keep current token' : 'EAAxxxxx…' }}">
                                <div class="form-text">Leave blank to keep the existing token.</div>
                            </div>
                            <div class="col-sm-7">
                                <label class="form-label">Phone Number ID <span class="text-danger">*</span></label>
                                <input type="text" name="wa_phone_id"
                                       class="form-control font-monospace @error('wa_phone_id') is-invalid @enderror"
                                       value="{{ old('wa_phone_id', $waPhoneId) }}"
                                       placeholder="556164050925397">
                                <div class="form-text">Found in Meta Business → WhatsApp → API Setup.</div>
                                @error('wa_phone_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-5">
                                <label class="form-label">API version</label>
                                <input type="text" name="wa_api_version"
                                       class="form-control @error('wa_api_version') is-invalid @enderror"
                                       value="{{ old('wa_api_version', $waApiVersion) }}"
                                       placeholder="v21.0">
                                @error('wa_api_version')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-7">
                                <label class="form-label">
                                    Verify Token
                                    <span class="text-muted fw-normal small">(webhook setup)</span>
                                </label>
                                <input type="text" name="wa_verify_token"
                                       class="form-control"
                                       value="{{ old('wa_verify_token', $waVerifyToken) }}"
                                       placeholder="e.g. salto2024secret">
                                <div class="form-text">A short secret <strong>you create</strong> — paste the same value into Meta's webhook Verify Token field.</div>
                            </div>
                            <div class="col-sm-5">
                                <label class="form-label">
                                    Facebook App Secret
                                    @if($waAppSecretSet)<span class="badge bg-secondary ms-1">saved</span>@endif
                                </label>
                                <input type="password" name="wa_app_secret" autocomplete="new-password"
                                       class="form-control"
                                       placeholder="{{ $waAppSecretSet ? 'Leave blank to keep current' : 'App secret from Meta dashboard' }}">
                                <div class="form-text">Leave blank to keep existing. Used to verify webhook payloads.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Webhook Callback URL</label>
                                <div class="input-group">
                                    <input type="text" name="wa_webhook_url" id="wa-webhook-url"
                                           class="form-control font-monospace"
                                           value="{{ old('wa_webhook_url', $waWebhookUrl) }}"
                                           placeholder="https://salto.yourdomain.com/api/whatsapp/webhook">
                                    <button type="button" class="btn btn-outline-secondary" id="btn-copy-webhook"
                                            onclick="navigator.clipboard.writeText(document.getElementById('wa-webhook-url').value).then(()=>{this.innerHTML='<i class=\'bi bi-check-lg\'></i> Copied';setTimeout(()=>{this.innerHTML='<i class=\'bi bi-clipboard\'></i> Copy'},2000)})" title="Copy to clipboard">
                                        <i class="bi bi-clipboard"></i> Copy
                                    </button>
                                </div>
                                <div class="form-text">Paste this as the <strong>Callback URL</strong> in Meta → WhatsApp → Configuration → Webhook.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header fw-semibold">
                        <i class="bi bi-file-text me-1"></i> Approved message templates
                        <span class="text-muted fw-normal small ms-2">Must be pre-approved in Meta Business Manager.</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label">Template — Low battery <span class="text-danger">*</span></label>
                                <input type="text" name="wa_template_low"
                                       class="form-control @error('wa_template_low') is-invalid @enderror"
                                       value="{{ old('wa_template_low', $waTemplateLow) }}"
                                       placeholder="battery_low_alert">
                                @error('wa_template_low')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Template — Flat / Dead <span class="text-danger">*</span></label>
                                <input type="text" name="wa_template_flat"
                                       class="form-control @error('wa_template_flat') is-invalid @enderror"
                                       value="{{ old('wa_template_flat', $waTemplateFlat) }}"
                                       placeholder="battery_flat_alert">
                                @error('wa_template_flat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Template — Normal / Recovered</label>
                                <input type="text" name="wa_template_normal"
                                       class="form-control @error('wa_template_normal') is-invalid @enderror"
                                       value="{{ old('wa_template_normal', $waTemplateNormal ?? '') }}"
                                       placeholder="battery_normal_alert">
                                <div class="form-text">Sent when a battery recovers to Normal. Uses {{1}} lock name, {{2}} timestamp.</div>
                                @error('wa_template_normal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label">Template locale</label>
                                <input type="text" name="wa_template_locale"
                                       class="form-control @error('wa_template_locale') is-invalid @enderror"
                                       value="{{ old('wa_template_locale', $waTemplateLocale) }}"
                                       placeholder="en">
                                @error('wa_template_locale')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Save WhatsApp settings
                </button>
            </div>

            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <h2 class="h6">Test WhatsApp delivery</h2>
                        <p class="text-muted small">
                            Sends a test message. If your battery templates are still
                            <strong>In review</strong>, it falls back to the built-in
                            <code>hello_world</code> template to verify your credentials work.
                        </p>
                        <div class="mb-2">
                            <label class="form-label small">Send test to (E.164)</label>
                            <input type="text" id="test-wa-phone" class="form-control form-control-sm"
                                   placeholder="+9607712345">
                        </div>
                        <button type="button" id="btn-test-wa" class="btn btn-outline-success w-100">
                            <i class="bi bi-whatsapp me-1"></i> Send test message
                        </button>
                        <div id="wa-test-result" class="mt-2" hidden></div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     TAB 4 — SALTO Database
════════════════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade {{ $activeTab === 'connection' ? 'show active' : '' }}"
     id="pane-connection" role="tabpanel">

    <form method="POST" action="{{ route('settings.connection.update') }}">
        @csrf @method('PUT')
        <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-body-tertiary rounded border">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" role="switch"
                       name="salto_monitoring_enabled" id="salto_monitoring_toggle"
                       {{ $saltoMonitoringEnabled ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="salto_monitoring_toggle">
                    Battery monitoring enabled
                </label>
            </div>
            <span class="text-muted small">Toggle off to pause all battery scans without removing your connection settings.</span>
        </div>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header fw-semibold">
                        <i class="bi bi-hdd-network me-1"></i> MS SQL Server connection
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-8">
                                <label class="form-label">Host / IP address <span class="text-danger">*</span></label>
                                <input type="text" name="salto_host" class="form-control @error('salto_host') is-invalid @enderror"
                                       value="{{ old('salto_host', $saltoHost) }}" placeholder="192.168.1.100">
                                @error('salto_host')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label">Port</label>
                                <input type="number" name="salto_port" class="form-control @error('salto_port') is-invalid @enderror"
                                       value="{{ old('salto_port', $saltoPort) }}" min="1" max="65535">
                                @error('salto_port')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Database name <span class="text-danger">*</span></label>
                                <input type="text" name="salto_database" class="form-control @error('salto_database') is-invalid @enderror"
                                       value="{{ old('salto_database', $saltoDatabase) }}" placeholder="ProAccessSpace">
                                @error('salto_database')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" name="salto_username" autocomplete="username"
                                       class="form-control @error('salto_username') is-invalid @enderror"
                                       value="{{ old('salto_username', $saltoUsername) }}" placeholder="salto_readonly">
                                @error('salto_username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">
                                    Password
                                    @if($saltoPasswordSet)<span class="badge bg-secondary ms-1">saved</span>@endif
                                </label>
                                <input type="password" name="salto_password" autocomplete="new-password" class="form-control"
                                       placeholder="{{ $saltoPasswordSet ? 'Leave blank to keep current' : 'Enter password' }}">
                                <div class="form-text">Leave blank to keep the existing password.</div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Encryption</label>
                                <select name="salto_encrypt" class="form-select">
                                    <option value="yes"    {{ $saltoEncrypt === 'yes'    ? 'selected' : '' }}>yes (required)</option>
                                    <option value="no"     {{ $saltoEncrypt === 'no'     ? 'selected' : '' }}>no (disable)</option>
                                    <option value="strict" {{ $saltoEncrypt === 'strict' ? 'selected' : '' }}>strict</option>
                                </select>
                            </div>
                            <div class="col-sm-6 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="salto_trust_cert"
                                           id="trust_cert" {{ $saltoTrustCert ? 'checked' : '' }}>
                                    <label class="form-check-label" for="trust_cert">Trust server certificate</label>
                                    <div class="form-text">Enable for self-signed / internal certs.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header fw-semibold">
                        <i class="bi bi-table me-1"></i> Schema mapping
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label">Lock table</label>
                                <input type="text" name="salto_lock_table" class="form-control"
                                       value="{{ old('salto_lock_table', $saltoTable) }}" placeholder="tb_DOOR">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">ID column</label>
                                <input type="text" name="salto_col_id" class="form-control"
                                       value="{{ old('salto_col_id', $saltoColId) }}" placeholder="ID">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Name column</label>
                                <input type="text" name="salto_col_name" class="form-control"
                                       value="{{ old('salto_col_name', $saltoColName) }}" placeholder="NAME">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Location column</label>
                                <input type="text" name="salto_col_location" class="form-control"
                                       value="{{ old('salto_col_location', $saltoColLocation) }}" placeholder="NAME">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Battery status column</label>
                                <input type="text" name="salto_col_battery" class="form-control"
                                       value="{{ old('salto_col_battery', $saltoColBattery) }}" placeholder="BATTERY_STATUS">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Last seen column</label>
                                <input type="text" name="salto_col_lastseen" class="form-control"
                                       value="{{ old('salto_col_lastseen', $saltoColLastSeen) }}" placeholder="LAST_UPDATE">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Raw SQL override <span class="text-muted">(optional)</span></label>
                                <textarea name="salto_raw_sql" rows="3" class="form-control font-monospace"
                                          placeholder="SELECT id_lock as id, Description as name ...">{{ old('salto_raw_sql', $saltoRawSql) }}</textarea>
                                <div class="form-text">Must alias columns as <code>id, name, location, battery, last_seen</code>. Overrides table/column fields above.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Save connection settings
                </button>
            </div>

            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <h2 class="h6">Test connection</h2>
                        <p class="text-muted small">Opens a live connection and counts rows in the configured table.</p>
                        <button type="button" id="btn-test-conn" class="btn btn-outline-primary w-100">
                            <i class="bi bi-plug me-1"></i> Test connection
                        </button>
                        <div id="conn-test-result" class="mt-2" hidden></div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     TAB 5 — SMS
════════════════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade {{ $activeTab === 'sms' ? 'show active' : '' }}"
     id="pane-sms" role="tabpanel">

    <form method="POST" action="{{ route('settings.sms.update') }}">
        @csrf @method('PUT')

        {{-- Header --}}
        <div class="d-flex align-items-center gap-2 mb-1">
            <h2 class="h5 mb-0"><i class="bi bi-phone me-1"></i> SMS Integration</h2>
            <span class="badge {{ $smsEnabled ? 'bg-success' : 'bg-secondary' }}">
                {{ $smsEnabled ? 'Active' : 'Inactive' }}
            </span>
        </div>
        <p class="text-muted small mb-4">Generic HTTP API — use <code>&#123;&#123;to&#125;&#125;</code> <code>&#123;&#123;message&#125;&#125;</code> <code>&#123;&#123;from&#125;&#125;</code> placeholders in the endpoint URL and body template.</p>

        {{-- Enable toggle --}}
        <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-body-tertiary rounded border">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" role="switch"
                       name="sms_enabled" id="sms_enabled_toggle"
                       {{ $smsEnabled ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="sms_enabled_toggle">
                    Enable SMS notifications
                </label>
            </div>
            <span class="text-muted small">Toggle off to pause all SMS alerts without removing your settings.</span>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">

                {{-- Provider info --}}
                <div class="card mb-4">
                    <div class="card-header fw-semibold">
                        <i class="bi bi-info-circle me-1"></i> Provider
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label">Provider Name</label>
                                <input type="text" name="sms_provider_name" class="form-control"
                                       value="{{ old('sms_provider_name', $smsProviderName) }}"
                                       placeholder="e.g. Twilio, ClickSend, Dhiraagu SMS">
                                <div class="form-text">Label only — for your reference.</div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Sender ID / From Number</label>
                                <input type="text" name="sms_sender_id" class="form-control"
                                       value="{{ old('sms_sender_id', $smsSenderId) }}"
                                       placeholder="+9607XXXXXXX or PASSFLOW">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Endpoint --}}
                <div class="card mb-4">
                    <div class="card-header fw-semibold">
                        <i class="bi bi-link-45deg me-1"></i> API Endpoint
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-9">
                                <label class="form-label">API Endpoint URL <span class="text-danger">*</span></label>
                                <input type="url" name="sms_endpoint"
                                       class="form-control @error('sms_endpoint') is-invalid @enderror"
                                       value="{{ old('sms_endpoint', $smsEndpoint) }}"
                                       placeholder="https://api.example.com/sms/send">
                                @error('sms_endpoint')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">Use <code>&#123;&#123;to&#125;&#125;</code>, <code>&#123;&#123;from&#125;&#125;</code>, <code>&#123;&#123;message&#125;&#125;</code> as placeholders.</div>
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label">HTTP Method</label>
                                <select name="sms_method" class="form-select">
                                    <option value="POST" {{ old('sms_method', $smsMethod) === 'POST' ? 'selected' : '' }}>POST</option>
                                    <option value="GET"  {{ old('sms_method', $smsMethod) === 'GET'  ? 'selected' : '' }}>GET</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Authentication --}}
                <div class="card mb-4">
                    <div class="card-header fw-semibold">
                        <i class="bi bi-shield-lock me-1"></i> Authentication
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Auth Type</label>
                            <select name="sms_auth_type" class="form-select" id="sms_auth_type">
                                <option value="none"   {{ old('sms_auth_type', $smsAuthType) === 'none'   ? 'selected' : '' }}>None</option>
                                <option value="bearer" {{ old('sms_auth_type', $smsAuthType) === 'bearer' ? 'selected' : '' }}>Bearer Token</option>
                                <option value="basic"  {{ old('sms_auth_type', $smsAuthType) === 'basic'  ? 'selected' : '' }}>Basic Auth</option>
                                <option value="apikey" {{ old('sms_auth_type', $smsAuthType) === 'apikey' ? 'selected' : '' }}>API Key Header</option>
                            </select>
                        </div>

                        {{-- Bearer --}}
                        <div id="sms-auth-bearer" class="{{ old('sms_auth_type', $smsAuthType) === 'bearer' ? '' : 'd-none' }}">
                            <label class="form-label">Bearer Token</label>
                            <div class="input-group">
                                <input type="password" name="sms_bearer_token" class="form-control" id="sms-bearer-input"
                                       placeholder="{{ $smsBearerTokenSet ? '(saved — leave blank to keep)' : 'Enter token' }}">
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="const i=document.getElementById('sms-bearer-input');i.type=i.type==='password'?'text':'password'">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Basic --}}
                        <div id="sms-auth-basic" class="{{ old('sms_auth_type', $smsAuthType) === 'basic' ? '' : 'd-none' }}">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="sms_basic_username" class="form-control"
                                           value="{{ old('sms_basic_username', $smsBasicUsername) }}">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="sms_basic_password" class="form-control"
                                           placeholder="(saved — leave blank to keep)">
                                </div>
                            </div>
                        </div>

                        {{-- API Key --}}
                        <div id="sms-auth-apikey" class="{{ old('sms_auth_type', $smsAuthType) === 'apikey' ? '' : 'd-none' }}">
                            <div class="row g-3">
                                <div class="col-sm-5">
                                    <label class="form-label">Header Name</label>
                                    <input type="text" name="sms_apikey_header" class="form-control"
                                           value="{{ old('sms_apikey_header', $smsApiKeyHeader) }}"
                                           placeholder="X-API-Key">
                                </div>
                                <div class="col-sm-7">
                                    <label class="form-label">API Key Value</label>
                                    <input type="password" name="sms_apikey_value" class="form-control"
                                           placeholder="(saved — leave blank to keep)">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Body template --}}
                <div class="card mb-4">
                    <div class="card-header fw-semibold">
                        <i class="bi bi-code-square me-1"></i> Request Body Template
                        <span class="text-muted fw-normal small ms-2">Leave blank for GET requests or APIs that use URL params.</span>
                    </div>
                    <div class="card-body">
                        <textarea name="sms_body_template" class="form-control font-monospace" rows="5"
                                  placeholder='{"to":"@{{to}}","from":"@{{from}}","message":"@{{message}}"}'>{{ old('sms_body_template', $smsBodyTemplate) }}</textarea>
                        <div class="form-text">JSON or plain-text body sent with POST requests. Placeholders: <code>&#123;&#123;to&#125;&#125;</code> <code>&#123;&#123;from&#125;&#125;</code> <code>&#123;&#123;message&#125;&#125;</code>.</div>
                    </div>
                </div>

                {{-- Recipients --}}
                <div class="card mb-4">
                    <div class="card-header fw-semibold">
                        <i class="bi bi-people me-1"></i> SMS Recipients
                    </div>
                    <div class="card-body">
                        <label class="form-label">Phone numbers (E.164 format)</label>
                        <textarea name="sms_recipients" class="form-control" rows="2"
                                  placeholder="+9607712345, +9609876543">{{ old('sms_recipients', $smsRecipients) }}</textarea>
                        <div class="form-text">Comma or newline separated. Use E.164 format (+9607...).</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-floppy me-1"></i> Save SMS settings
                </button>
            </div>

            {{-- Sidebar: test --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header fw-semibold">
                        <i class="bi bi-send me-1"></i> Send test SMS
                    </div>
                    <div class="card-body">
                        <label class="form-label">Phone number</label>
                        <input type="tel" id="test-sms-phone" class="form-control mb-2"
                               placeholder="+9607712345">
                        <button type="button" id="btn-test-sms" class="btn btn-outline-primary w-100">
                            <i class="bi bi-phone me-1"></i> Send test SMS
                        </button>
                        <div id="sms-test-result" class="mt-2" hidden></div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

</div>{{-- /tab-content --}}

@push('scripts')
<script>
function ajaxTest(btnId, resultId, url, bodyFn) {
    const btn = document.getElementById(btnId);
    const result = document.getElementById(resultId);
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    btn.addEventListener('click', function () {
        btn.disabled = true;
        const origHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Testing…';
        result.hidden = true;

        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(bodyFn()),
        })
        .then(r => r.json())
        .then(data => {
            result.className = 'alert alert-' + (data.ok ? 'success' : 'danger') + ' small mb-0';
            result.innerHTML = (data.ok ? '<i class="bi bi-check-circle me-1"></i>' : '<i class="bi bi-x-circle me-1"></i>') + data.message;
            result.hidden = false;
        })
        .catch(() => {
            result.className = 'alert alert-danger small mb-0';
            result.innerHTML = '<i class="bi bi-x-circle me-1"></i> Request failed.';
            result.hidden = false;
        })
        .finally(() => { btn.disabled = false; btn.innerHTML = origHtml; });
    });
}

ajaxTest('btn-test-email', 'email-test-result', '{{ route('settings.email.test') }}',
    () => ({ test_email: document.getElementById('test-email-addr').value }));

ajaxTest('btn-test-wa', 'wa-test-result', '{{ route('settings.whatsapp.test') }}',
    () => ({ test_phone: document.getElementById('test-wa-phone').value }));

ajaxTest('btn-test-conn', 'conn-test-result', '{{ route('settings.connection.test') }}',
    () => ({}));

ajaxTest('btn-test-sms', 'sms-test-result', '{{ route('settings.sms.test') }}',
    () => ({ test_phone: document.getElementById('test-sms-phone').value }));

// Auth type toggle for SMS tab
(function () {
    const sel = document.getElementById('sms_auth_type');
    if (! sel) return;
    const panels = {
        bearer: document.getElementById('sms-auth-bearer'),
        basic:  document.getElementById('sms-auth-basic'),
        apikey: document.getElementById('sms-auth-apikey'),
    };
    function show(val) {
        Object.entries(panels).forEach(([k, el]) => el && el.classList.toggle('d-none', k !== val));
    }
    sel.addEventListener('change', () => show(sel.value));
    show(sel.value);
})();
</script>
@endpush
@endsection
