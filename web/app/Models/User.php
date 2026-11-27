<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'consent_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'consent_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isNakes(): bool
    {
        return $this->role === 'nakes';
    }

    public function isPengguna(): bool
    {
        return $this->role === 'pengguna';
    }

    public function roleBadgeLabel(): string
    {
        return match ($this->role) {
            'admin' => 'Administrator & Peneliti',
            'nakes' => 'Tenaga Medis / Klinisi',
            default => 'Pengguna Umum',
        };
    }

    public function screenings(): HasMany
    {
        return $this->hasMany(Screening::class);
    }

    public function narrativeRatings(): HasMany
    {
        return $this->hasMany(NarrativeRating::class, 'rater_id');
    }
}
