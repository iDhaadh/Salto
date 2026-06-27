<?php

namespace App\Mail;

use App\Support\BatteryStatus;
use App\Support\LockSnapshot;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BatteryAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public LockSnapshot $lock,
        public BatteryStatus $status,
        public string $reason = 'alert', // alert|reminder|recovery|test
    ) {
    }

    public function envelope(): Envelope
    {
        $prefix = match ($this->reason) {
            'recovery' => '[Resolved]',
            'test' => '[Test]',
            default => $this->status->isUrgent() ? '[URGENT]' : '[Warning]',
        };

        $subject = sprintf(
            '%s SALTO lock battery %s — %s',
            $prefix,
            $this->reason === 'recovery' ? 'recovered' : $this->status->label(),
            $this->lock->name,
        );

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.battery-alert',
            with: [
                'lock' => $this->lock,
                'status' => $this->status,
                'reason' => $this->reason,
            ],
        );
    }
}
