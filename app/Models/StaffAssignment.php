<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAssignment extends Model
{
    protected $fillable = [
        'staff_id',
        'counter_id',
        'service_id',
        'starts_at',
        'ends_at',
        'note',
        'is_primary', // tambahin ini
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'starts_at'  => 'datetime',
        'ends_at'    => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(Counter::class, 'counter_id');
    }
}
