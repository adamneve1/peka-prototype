<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Counter extends Model
{
    protected $fillable = ['name','location'];

    // Relasi many-to-many ke Service
    public function services()
    {
        return $this->belongsToMany(Service::class, 'counter_service')
                    ->withTimestamps();
    }
}
