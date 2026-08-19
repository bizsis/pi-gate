<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    protected $fillable = [
        'company_id',
        'device_uid',
        'name',
        'platform',
        'app_version',
        'last_seen_at',
        'last_sync_at',
        'active',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(DeviceSyncLog::class);
    }
}
