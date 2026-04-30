<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive;

use Illuminate\Support\ServiceProvider;
use WendellAdriel\Expressive\Console\Commands\ExpressiveCommand;

final class ExpressiveServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/expressive.php', 'expressive');

        $this->app->singleton(Expressive::class);
    }

    public function boot(): void
    {
        $this->bootConfig();
        $this->bootCommands();
    }

    private function bootConfig(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/expressive.php' => config_path('expressive.php'),
        ], ['expressive', 'expressive-config']);
    }

    private function bootCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            ExpressiveCommand::class,
        ]);
    }
}
