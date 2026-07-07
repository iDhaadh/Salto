<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Services\SmsService;
use App\Services\WhatsAppService;
use App\Support\LockSnapshot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Delivers a single SALTO alarm notification (intrusion, tamper, forced entry,
 * duress, door-left-open, hardware failure …) on one channel to one recipient.
 * Each alarm code uses its own WhatsApp template; email/SMS use tailored text.
 */
class SendAlarmNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    /**
     * @param  array{0:string,1:string,2:string,3:string}  $alarm  [key, label, template, severity]
     */
    public function __construct(
        public LockSnapshot $lock,
        public array $alarm,
        public string $when,      // formatted event date/time
        public string $channel,   // whatsapp|email|sms
        public string $recipient,
    ) {
    }

    public function handle(WhatsAppService $whatsapp, SmsService $sms): void
    {
        [$key, $label, $template, $severity] = $this->alarm;
        $error = null;
        $payload = null;

        $location = $this->lock->location ?: '—';

        try {
            if ($this->channel === 'whatsapp') {
                $result = $whatsapp->sendTemplate(
                    $this->recipient,
                    $template,
                    [$this->lock->name, $location, $this->when],
                );
                $payload = $result['payload'] ?? null;
                if (! $result['ok']) {
                    $error = $result['error'];
                }
            } elseif ($this->channel === 'email') {
                $icon    = $severity === 'critical' ? '🚨' : '⚠️';
                $subject = "{$icon} SALTO {$label} — {$this->lock->name}";
                $body    = "{$label} reported by SALTO lock \"{$this->lock->name}\".\n\n"
                         . "Location: {$location}\n"
                         . "Time: {$this->when}\n\n"
                         . "Please investigate according to your security procedures.";
                Mail::raw($body, fn ($m) => $m->to($this->recipient)->subject($subject));
            } elseif ($this->channel === 'sms') {
                $message = "SALTO {$label}: lock {$this->lock->name} ({$location}) at {$this->when}. Please investigate.";
                $result  = $sms->send($this->recipient, $message);
                if (! $result['ok']) {
                    $error = $result['error'];
                }
            } else {
                $error = "Unknown channel: {$this->channel}";
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
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
            'alert_id'  => null,
            'channel'   => $this->channel,
            'recipient' => $this->recipient,
            'status'    => $error ? 'failed' : 'sent',
            'reason'    => 'alarm:'.$this->alarm[0],
            'error'     => $error,
            'payload'   => $payload,
            'sent_at'   => $error ? null : now(),
        ]);
    }
}
