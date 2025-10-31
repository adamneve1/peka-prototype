<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// ⬇️ Tambahan penting buat Filament v4
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ⬇️ WAJIB: izinkan user akses panel (sementara true dulu biar ngetes)
    public function canAccessPanel(Panel $panel): bool
    {
        return true;

        // Setelah bisa login, ganti ke aturan bener:
        // return $this->email === 'admin@example.com';
        // atau kalau pakai spatie/permission: return $this->hasRole('admin');
    }
}
