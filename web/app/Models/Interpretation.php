<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Interpretation extends Model
{
    protected $fillable = [
        'screening_id',
        'prediction_id',
        'status',
        'narrative',
        'validation',
        'used_fallback',
        'provider',
        'model_name',
        'prompt_version',
        'latency_ms',
        'retries',
        'tokens_in',
        'tokens_out',
    ];

    protected function casts(): array
    {
        return [
            'narrative' => 'array',
            'validation' => 'array',
            'used_fallback' => 'boolean',
            'latency_ms' => 'integer',
            'retries' => 'integer',
            'tokens_in' => 'integer',
            'tokens_out' => 'integer',
        ];
    }

    public function screening(): BelongsTo
    {
        return $this->belongsTo(Screening::class);
    }

    public function prediction(): BelongsTo
    {
        return $this->belongsTo(Prediction::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(NarrativeRating::class);
    }
}

