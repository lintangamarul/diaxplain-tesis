<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_versions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('version_tag')->default('xgb-v1.0.0')->index();
            $table->string('algorithm')->default('XGBoost');
            $table->string('technique');
            $table->json('params')->nullable();
            $table->json('metrics')->nullable();
            $table->string('artifact_path')->nullable();
            $table->string('dataset_hash')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamp('trained_at')->nullable();
            $table->timestamps();
        });

        Schema::create('experiment_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_version_id')->nullable()->constrained('model_versions')->nullOnDelete();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('technique');
            $table->json('params')->nullable();
            $table->json('cv_metrics')->nullable();
            $table->json('test_metrics')->nullable();
            $table->json('per_class')->nullable();
            $table->json('confusion')->nullable();
            $table->json('roc')->nullable();
            $table->float('train_seconds')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        Schema::create('screenings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('patient_label', 80)->nullable()->index();
            $table->string('audience', 25)->default('awam');
            $table->json('input');
            $table->json('physical_meta')->nullable();
            $table->boolean('consent_data')->default(true);
            $table->string('source', 30)->default('web');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->uuid('screening_id')->unique();
            $table->foreign('screening_id')->references('id')->on('screenings')->cascadeOnDelete();
            $table->foreignId('model_version_id')->nullable()->constrained('model_versions')->nullOnDelete();
            $table->string('model_version_tag', 40)->default('xgb-v1.0.0');
            $table->string('label', 40);
            $table->string('label_id', 50)->nullable();
            $table->unsignedTinyInteger('label_code')->index();
            $table->json('probabilities');
            $table->string('confidence_note', 20)->default('sedang');
            $table->json('shap_local');
            $table->json('input_summary')->nullable();
            $table->float('base_value')->default(0);
            $table->json('warnings')->nullable();
            $table->string('status', 20)->default('done');
            $table->timestamp('predicted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('interpretations', function (Blueprint $table) {
            $table->id();
            $table->uuid('screening_id')->index();
            $table->foreign('screening_id')->references('id')->on('screenings')->cascadeOnDelete();
            $table->foreignId('prediction_id')->constrained('predictions')->cascadeOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->json('narrative')->nullable();
            $table->json('validation')->nullable();
            $table->boolean('used_fallback')->default(false);
            $table->string('provider', 50)->nullable();
            $table->string('model_name', 80)->nullable();
            $table->string('prompt_version', 20)->default('p1.0');
            $table->unsignedInteger('latency_ms')->default(0);
            $table->unsignedTinyInteger('retries')->default(0);
            $table->unsignedInteger('tokens_in')->default(0);
            $table->unsignedInteger('tokens_out')->default(0);
            $table->timestamps();
        });

        Schema::create('narrative_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interpretation_id')->constrained('interpretations')->cascadeOnDelete();
            $table->foreignId('rater_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('akurasi');
            $table->unsignedTinyInteger('kejelasan');
            $table->unsignedTinyInteger('kegunaan');
            $table->unsignedTinyInteger('keamanan');
            $table->text('komentar')->nullable();
            $table->timestamps();

            $table->unique(['interpretation_id', 'rater_id']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 80)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi', 100)->index();
            $table->string('target', 120)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('narrative_ratings');
        Schema::dropIfExists('interpretations');
        Schema::dropIfExists('predictions');
        Schema::dropIfExists('screenings');
        Schema::dropIfExists('experiment_runs');
        Schema::dropIfExists('model_versions');
    }
};

