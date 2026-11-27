<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'aksi',
        'target',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $aksi, ?string $target = null, array $meta = [], ?int $userId = null): self
    {
        return static::query()->create([
            'user_id' => $userId ?? Auth::id(),
            'aksi' => $aksi,
            'target' => $target,
            'meta' => $meta,
        ]);
    }
}

