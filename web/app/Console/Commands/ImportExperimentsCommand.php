<?php

namespace App\Console\Commands;

use App\Services\ExperimentImporter;
use Illuminate\Console\Command;

class ImportExperimentsCommand extends Command
{
    protected $signature = 'experiments:import {--dir= : Custom path to ml/artifacts directory}';
    protected $description = 'Impor hasil eksperimen E0-E6, SHAP global, Weakness Report, dan Model terbaik dari ml/artifacts';

    public function handle(ExperimentImporter $importer): int
    {
        $res = $importer->importFromArtifacts($this->option('dir') ?: null);
        $codes = implode(', ', $res['experiments_imported'] ?? []);
        $this->info("Berhasil mengimpor skenario eksperimen: [{$codes}] dan mengaktifkan model: " . ($res['model_version'] ?? '-'));
        return self::SUCCESS;
    }
}

