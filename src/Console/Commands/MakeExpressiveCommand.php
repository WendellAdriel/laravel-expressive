<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use WendellAdriel\Expressive\Actions\ExpressiveClassBuilder;
use WendellAdriel\Expressive\Exceptions\InvalidModelClassException;
use WendellAdriel\Expressive\Exceptions\NonExistingModelClassException;
use WendellAdriel\Expressive\Exceptions\UnsupportedGenerationException;

use function Laravel\Prompts\text;

final class MakeExpressiveCommand extends Command
{
    protected $signature = 'make:expressive
        {name? : The Expressive class name}
        {--model= : The Eloquent model class}
        {--namespace= : Override the configured Expressive namespace}
        {--suffix= : Override the configured Expressive suffix}
        {--force : Overwrite the Expressive class if it already exists}';

    protected $description = 'Create a new Expressive class from an Eloquent model';

    /**
     * @throws FileNotFoundException
     */
    public function handle(Filesystem $files, ExpressiveClassBuilder $builder): int
    {
        $modelClass = $this->resolveModelClass();
        $model = $this->newModel($modelClass);
        $class = $this->className((string) ($this->argument('name') ?: class_basename($modelClass)));
        $namespace = trim((string) ($this->option('namespace') ?: config('expressive.namespace', 'App\\Expressive')), '\\');
        $path = $this->pathFor($namespace, $class);

        if ($files->exists($path) && ! $this->option('force')) {
            $this->components->error('Expressive already exists.');

            return self::FAILURE;
        }

        if (! $model->getConnection()->getSchemaBuilder()->hasTable($model->getTable())) {
            throw UnsupportedGenerationException::missingTable($modelClass, $model->getTable());
        }

        $stub = $files->get($this->stubPath($files));

        $files->ensureDirectoryExists(dirname($path));
        $files->put($path, $builder->handle($stub, $namespace, $class, $model));

        $this->components->info("Expressive [{$path}] created successfully.");

        return self::SUCCESS;
    }

    /**
     * @return class-string<Model>
     */
    private function resolveModelClass(): string
    {
        $model = (string) ($this->option('model') ?: text('Which model should this Expressive class map to?', default: (string) ($this->argument('name') ?: 'User')));
        $class = str_replace('/', '\\', trim($model, '\\/'));

        if (! str_contains($class, '\\')) {
            $class = $this->laravel->getNamespace().'Models\\'.$class;
        }

        if (! class_exists($class)) {
            throw NonExistingModelClassException::forClass($class);
        }

        if (! is_subclass_of($class, Model::class)) {
            throw InvalidModelClassException::forClass($class);
        }

        return $class;
    }

    private function className(string $name): string
    {
        $class = Str::studly(class_basename(str_replace('/', '\\', $name)));
        $suffix = (string) ($this->option('suffix') ?? config('expressive.suffix', ''));

        if ($suffix !== '' && ! str_ends_with($class, $suffix)) {
            $class .= $suffix;
        }

        return $class;
    }

    private function pathFor(string $namespace, string $class): string
    {
        $root = trim($this->laravel->getNamespace(), '\\');

        if (str_starts_with($namespace, $root)) {
            $relative = Str::after($namespace, $root);

            return app_path(str_replace('\\', '/', $relative).'/'.$class.'.php');
        }

        if (str_starts_with($namespace, 'App\\')) {
            return app_path(str_replace('\\', '/', Str::after($namespace, 'App\\')).'/'.$class.'.php');
        }

        return base_path(str_replace('\\', '/', $namespace).'/'.$class.'.php');
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function newModel(string $modelClass): Model
    {
        /** @var Model $model */
        $model = $this->laravel->make($modelClass);

        return $model;
    }

    private function stubPath(Filesystem $files): string
    {
        $publishedStub = base_path('stubs/expressive.stub');

        return $files->exists($publishedStub)
            ? $publishedStub
            : __DIR__.'/../../../stubs/expressive.stub';
    }
}
