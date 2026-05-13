<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Narration\Contracts\NarrationProvider;
use App\Services\Narration\ElevenLabsNarrationProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(NarrationProvider::class, function ($app) {
            return match (config('poi_narration.provider')) {
                'elevenlabs' => $app->make(ElevenLabsNarrationProvider::class),
                default => throw new RuntimeException('Unknown narration provider: '.(string) config('poi_narration.provider')),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('pagination.unbeaten');
        Paginator::defaultSimpleView('pagination.unbeaten-simple');

        Gate::define('superAdmin', fn (User $user): bool => $user->isSuperAdmin());
    }
}
