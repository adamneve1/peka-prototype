<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['name'];

    // Relasi many-to-many ke Counter
    public function counters()
    {
        return $this->belongsToMany(Counter::class, 'counter_service')
                    ->withTimestamps();
    }
}
