<?php

namespace App\Models;

use App\Support\BatteryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lock extends Model
{
    protected $fillable = [
        'salto_id', 'name', 'location', 'battery_status',
        'battery_changed_at', 'last_seen_at', 'synced_at',
    ];

    protected $casts = [
        'battery_changed_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function openAlert(): ?Alert
    {
        return $this->alerts()->where('status', 'open')->latest('opened_at')->first();
    }

    public function status(): BatteryStatus
    {
        return BatteryStatus::tryFrom($this->battery_status) ?? BatteryStatus::Unknown;
    }
}
