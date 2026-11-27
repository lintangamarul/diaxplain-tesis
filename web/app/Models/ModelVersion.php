<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModelVersion extends Model
{
    protected $fillable = [
        'name',
        'version_tag',
        'algorithm',
        'technique',
        'params',
        'metrics',
        'artifact_path',
        'dataset_hash',
        'is_active',
        'trained_at',
    ];

    protected function casts(): array
    {
        return [
            'params' => 'array',
            'metrics' => 'array',
            'is_active' => 'boolean',
            'trained_at' => 'datetime',
        ];
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
    }

    public function experimentRuns(): HasMany
    {
        return $this->hasMany(ExperimentRun::class);
    }
}

