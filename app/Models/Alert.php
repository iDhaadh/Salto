<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alert extends Model
{
    protected $fillable = [
        'lock_id', 'severity', 'status', 'opened_at',
        'last_notified_at', 'notify_count', 'resolved_at',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'last_notified_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function lock(): BelongsTo
    {
        return $this->belongsTo(Lock::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
