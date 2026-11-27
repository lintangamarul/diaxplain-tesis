<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prediction extends Model
{
    protected $fillable = [
        'screening_id',
        'model_version_id',
        'model_version_tag',
        'label',
        'label_id',
        'label_code',
        'probabilities',
        'confidence_note',
        'shap_local',
        'input_summary',
        'base_value',
        'warnings',
        'status',
        'predicted_at',
    ];

    protected function casts(): array
    {
        return [
            'label_code' => 'integer',
            'probabilities' => 'array',
            'shap_local' => 'array',
            'input_summary' => 'array',
            'base_value' => 'float',
            'warnings' => 'array',
            'predicted_at' => 'datetime',
        ];
    }

    public function screening(): BelongsTo
    {
        return $this->belongsTo(Screening::class);
    }

    public function modelVersion(): BelongsTo
    {
        return $this->belongsTo(ModelVersion::class);
    }

    public function interpretations(): HasMany
    {
        return $this->hasMany(Interpretation::class);
    }
}

