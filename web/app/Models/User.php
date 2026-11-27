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

    public function displayName(): string
    {
        $clean = preg_replace('/\s*\((?:Admin(?:\s*&\s*Peneliti)?|Administrator(?:\s*&\s*Peneliti)?|Tenaga Medis(?:\s*\/\s*Klinisi)?|Penilai Klinis(?:\s*\d+)?|Pengguna Umum|Pengguna)\)\s*$/iu', '', trim((string) $this->name));
        return $clean !== '' ? $clean : (string) $this->name;
    }

    public function initials(): string
    {
        $name = preg_replace('/^(?:dr\.|Ns\.|Prof\.|Dr\.)\s+/iu', '', $this->displayName());
        $parts = preg_split('/[\s,]+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($parts)) {
            return 'DX';
        }
        $first = mb_substr($parts[0], 0, 1);
        $second = count($parts) > 1 ? mb_substr($parts[1], 0, 1) : mb_substr($parts[0], 1, 1);
        return mb_strtoupper($first . $second);
    }

    public function roleBadgeLabel(): string
    {
        return match ($this->role) {
            'admin' => 'Admin & Peneliti',
            'nakes' => 'Tenaga Medis',
            default => 'Pengguna Umum',
        };
    }

    public function roleBadgeClasses(): string
    {
        return match ($this->role) {
            'admin' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'nakes' => 'bg-sky-50 text-sky-700 border-sky-200',
            default => 'bg-emerald-50 text-emerald-700 border-emerald-200',
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
