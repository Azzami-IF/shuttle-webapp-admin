<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteTemplate extends Model
{
    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'origin',
        'destination',
        'departure_time',
        'price',
        'active_days',
        'generate_days_ahead',
        'is_active'
    ];

    protected $casts = [
        'active_days' => 'array',
        'is_active' => 'boolean',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
