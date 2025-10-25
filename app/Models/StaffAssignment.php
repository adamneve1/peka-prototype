<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAssignment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'staff_id',
        'counter_id', // <-- INI YANG PERLU DITAMBAHKAN
        'service_id',
        'starts_at',
        'ends_at',
    ];

    // ... (relasi-relasi yang sudah kamu buat tadi) ...

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