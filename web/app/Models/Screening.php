<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Screening extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'patient_label',
        'audience',
        'input',
        'physical_meta',
        'consent_data',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'input' => 'array',
            'physical_meta' => 'array',
            'consent_data' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prediction(): HasOne
    {
        return $this->hasOne(Prediction::class);
    }

    public function interpretations(): HasMany
    {
        return $this->hasMany(Interpretation::class);
    }

    public function latestInterpretation(): HasOne
    {
        return $this->hasOne(Interpretation::class)->latestOfMany();
    }
}

