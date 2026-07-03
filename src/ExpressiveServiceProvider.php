<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use WendellAdriel\Expressive\Console\Commands\GenerateExpressiveCommand;
use WendellAdriel\Expressive\Console\Commands\MakeExpressiveCommand;
use WendellAdriel\Expressive\Console\Commands\SyncExpressiveCommand;
use WendellAdriel\Expressive\Support\ExpressiveMapper;

final class ExpressiveServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/expressive.php', 'expressive');
    }

    public function boot(): void
    {
        $this->bootConfig();
        $this->bootMacros();
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

        $this->publishes([
            __DIR__.'/../stubs/expressive.stub' => base_path('stubs/expressive.stub'),
        ], ['expressive', 'expressive-stubs']);
    }

    private function bootCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            GenerateExpressiveCommand::class,
            MakeExpressiveCommand::class,
            SyncExpressiveCommand::class,
        ]);
    }

    private function bootMacros(): void
    {
        if (! EloquentCollection::hasMacro('expressive')) {
            EloquentCollection::macro('expressive', function (array|string $relationships = [], array|string $attributes = []): Collection {
                /** @var EloquentCollection<int, Model> $this */
                return ExpressiveMapper::fromCollection($this, $relationships, $attributes);
            });
        }

        if (! Builder::hasGlobalMacro('expressive')) {
            Builder::macro('expressive', function (array|string $relationships = [], array|string $attributes = []): Collection {
                /** @var Builder<Model> $this */
                return ExpressiveMapper::fromCollection($this->get(), $relationships, $attributes);
            });
        }

        if (! Builder::hasGlobalMacro('expressiveChunk')) {
            Builder::macro('expressiveChunk', function (int $count, callable $callback, array|string $relationships = [], array|string $attributes = []): bool {
                if ($count < 1) {
                    throw new InvalidArgumentException('The expressive chunk size must be at least 1.');
                }

                /** @var Builder<Model> $this */
                return $this->chunk($count, function (EloquentCollection $models) use ($callback, $relationships, $attributes): mixed {
                    return $callback(ExpressiveMapper::fromCollection($models, $relationships, $attributes));
                });
            });
        }
    }
}
