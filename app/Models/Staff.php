<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $table = 'staff';
    

    protected $fillable = [
        'name',
        'photo_path', // harusnya ini, karena kita simpan path file
    ];

    protected $appends = ['photo_url']; // biar auto ikut ke array/json

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path
            ? asset('storage/' . $this->photo_path)
            : null;
    }
    public function ratings()
{
    return $this->hasMany(\App\Models\Rating::class);
}
public function counter()
{
    return $this->belongsTo(\App\Models\Counter::class);
}

}
