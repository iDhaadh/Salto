<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoorOpen extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'door_name', 'salto_ap_id',
        'salto_uuid', 'success', 'error_message', 'opened_at',
    ];

    protected $casts = [
        'success'   => 'boolean',
        'opened_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
