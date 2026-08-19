<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = [
        'company_id',
        'device_id',
        'employee_id',
        'card_id',
        'event_type',
        'event_at',
        'latitude',
        'longitude',
        'photo_path',
        'client_event_uuid',
        'received_at',
    ];

    protected $casts = [
        'event_at' => 'datetime',
        'received_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(EventPhoto::class);
    }
}
