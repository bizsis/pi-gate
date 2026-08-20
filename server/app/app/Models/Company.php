<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'name',
        'short_name',
        'tax_number',
        'registration_number',
        'email',
        'phone',
        'address',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function workAreas(): HasMany
    {
        return $this->hasMany(WorkArea::class);
    }
}
