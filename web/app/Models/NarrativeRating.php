<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NarrativeRating extends Model
{
    protected $fillable = [
        'interpretation_id',
        'rater_id',
        'akurasi',
        'kejelasan',
        'kegunaan',
        'keamanan',
        'komentar',
    ];

    protected function casts(): array
    {
        return [
            'akurasi' => 'integer',
            'kejelasan' => 'integer',
            'kegunaan' => 'integer',
            'keamanan' => 'integer',
        ];
    }

    public function interpretation(): BelongsTo
    {
        return $this->belongsTo(Interpretation::class);
    }

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    public function averageScore(): float
    {
        return round(($this->akurasi + $this->kejelasan + $this->kegunaan + $this->keamanan) / 4.0, 2);
    }
}

