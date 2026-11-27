<?php

namespace App\Providers;

use App\Models\ModelVersion;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['layouts.app', 'layouts.guest'], function ($view): void {
            $activeModel = null;
            try {
                if (Schema::hasTable('model_versions')) {
                    $activeModel = ModelVersion::where('is_active', true)->first();
                }
            } catch (\Throwable) {
                $activeModel = null;
            }
            $view->with('sharedActiveModel', $activeModel);
        });
    }
}
