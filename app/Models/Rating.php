<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'counter_id',
        'service_id',
        'staff_id',
        'score',
        'comment',
        'flags', // kalau nanti diisi
    ];

    protected $casts = [
        'counter_id' => 'integer',
        'service_id' => 'integer',
        'staff_id'   => 'integer',
        'score'      => 'integer',
        'flags'      => 'array',   // penting: kolom json jadi array
    ];

    // relasi yang dipakai di table
    public function counter() { return $this->belongsTo(\App\Models\Counter::class); }
    public function service() { return $this->belongsTo(\App\Models\Service::class); }
    public function staff()   { return $this->belongsTo(\App\Models\Staff::class); }
}
