<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExperimentRun extends Model
{
    protected $fillable = [
        'model_version_id',
        'code',
        'name',
        'technique',
        'params',
        'cv_metrics',
        'test_metrics',
        'per_class',
        'confusion',
        'roc',
        'train_seconds',
        'notes',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'params' => 'array',
            'cv_metrics' => 'array',
            'test_metrics' => 'array',
            'per_class' => 'array',
            'confusion' => 'array',
            'roc' => 'array',
            'train_seconds' => 'float',
            'imported_at' => 'datetime',
        ];
    }

    public function modelVersion(): BelongsTo
    {
        return $this->belongsTo(ModelVersion::class);
    }
}

