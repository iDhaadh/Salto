<?php

namespace App\Jobs;

use App\Mail\BatteryAlertMail;
use App\Models\NotificationLog;
use App\Services\SmsService;
use App\Services\WhatsAppService;
use App\Support\BatteryStatus;
use App\Support\LockSnapshot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendBatteryAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public LockSnapshot $lock,
        public BatteryStatus $status,
        public string $channel,    // email|whatsapp
        public string $recipient,
        public string $reason = 'alert', // alert|reminder|recovery|test
        public ?int $alertId = null,
    ) {
    }

    public function handle(WhatsAppService $whatsapp, SmsService $sms): void
    {
        $channel = $this->channel;
        $error = null;
        $payload = null;

        try {
            if ($channel === 'email') {
                Mail::to($this->recipient)->send(
                    new BatteryAlertMail($this->lock, $this->status, $this->reason)
                );
            } elseif ($channel === 'whatsapp') {
                $result = $whatsapp->sendBatteryAlert($this->recipient, $this->lock, $this->status, $this->reason, $this->alertId);
                $payload = $result['payload'] ?? null;
                if (! $result['ok']) {
                    $error = $result['error'];
                }
            } elseif ($channel === 'sms') {
                $label   = ucfirst($this->status->value);
                $message = "SALTO Alert: {$this->lock->name} battery is {$label}. Location: {$this->lock->location}. Time: {$this->lock->lastSeenAt->format('d/m/Y H:i')}";
                $result  = $sms->send($this->recipient, $message);
                if (! $result['ok']) {
                    $error = $result['error'];
                }
            } else {
                $error = "Unknown channel: {$channel}";
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            // Re-throw so the queue retries; the failure is still logged below.
            $this->log($error, $payload);
            throw $e;
        }

        $this->log($error, $payload);
    }

    public function failed(\Throwable $e): void
    {
        $this->log('Job failed permanently: '.$e->getMessage(), null);
    }

    protected function log(?string $error, ?array $payload): void
    {
        NotificationLog::create([
            'alert_id' => $this->alertId,
            'channel' => $this->channel,
            'recipient' => $this->recipient,
            'status' => $error ? 'failed' : 'sent',
            'reason' => $this->reason,
            'error' => $error,
            'payload' => $payload,
            'sent_at' => $error ? null : now(),
        ]);
    }
}
