<?php

declare(strict_types=1);

namespace Sifrious\PigeonPost;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;
use Sifrious\Aleph\Connector\ConnectorRegistry;

final class PigeonPostServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            LoftCursors::class,
            fn ($app): LoftCursors => new LoftCursors($app->make(DatabaseManager::class)->connection()),
        );

        $this->app->singleton(PigeonPostConnector::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->app->make(ConnectorRegistry::class)
            ->register($this->app->make(PigeonPostConnector::class));
    }
}
