<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use WendellAdriel\Expressive\Actions\ExpressiveClassBuilder;
use WendellAdriel\Expressive\Exceptions\UnsupportedGenerationException;
use WendellAdriel\Expressive\Support\ExpressiveClassTargets;

use function Laravel\Prompts\text;

final class MakeExpressiveCommand extends Command
{
    protected $signature = 'make:expressive
        {name? : The Expressive class name}
        {--model= : The Eloquent model class}
        {--namespace= : Override the configured Expressive namespace}
        {--suffix= : Override the configured Expressive suffix}
        {--with-attributes : Generate all attribute properties and ignore attribute generator defaults}
        {--without-attributes : Do not generate attribute properties}
        {--attributes= : Generate only the given comma-separated attribute properties}
        {--with-relationships : Generate all relationship properties and ignore relationship generator defaults}
        {--without-relationships : Do not generate relationship properties}
        {--relationships= : Generate only the given comma-separated relationship properties}
        {--include-hidden : Include hidden model attributes and ignore hidden generator defaults}
        {--exclude-hidden : Exclude hidden model attributes from generated properties}
        {--hint-morph-map : Enrich MorphTo PHPDoc with confident morph-map Expressive classes}
        {--dry-run : Print the generated class without writing it}
        {--force : Overwrite the Expressive class if it already exists}';

    protected $description = 'Create a new Expressive class from an Eloquent model';

    /**
     * @throws FileNotFoundException
     */
    public function handle(Filesystem $files, ExpressiveClassBuilder $builder, ExpressiveClassTargets $targets): int
    {
        $modelClass = $targets->modelClass($this->modelOption(), $this->laravel->getNamespace());
        $model = $this->newModel($modelClass);
        $target = $targets->target(
            $this->laravel->getNamespace(),
            $modelClass,
            (string) ($this->argument('name') ?: class_basename($modelClass)),
            $this->option('namespace') === null ? null : (string) $this->option('namespace'),
            $this->option('suffix') === null ? null : (string) $this->option('suffix'),
        );

        if (! $this->option('dry-run') && $files->exists($target->path) && ! $this->option('force')) {
            $this->components->error('Expressive already exists.');

            return self::FAILURE;
        }

        if (! $model->getConnection()->getSchemaBuilder()->hasTable($model->getTable())) {
            throw UnsupportedGenerationException::missingTable($modelClass, $model->getTable());
        }

        $contents = $builder->handle(
            $files->get($this->stubPath($files)),
            $target->namespace,
            $target->class,
            $model,
            $this->generatorOptions(),
        );

        if ($this->option('dry-run')) {
            $this->line($contents);

            return self::SUCCESS;
        }

        $files->ensureDirectoryExists(dirname($target->path));
        $files->put($target->path, $contents);

        $this->components->info("Expressive [{$target->path}] created successfully.");

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function listOption(string $option): array
    {
        return collect(explode(',', (string) $this->option($option)))
            ->map(static fn (string $value): string => trim($value))
            ->filter()
            ->values()
            ->all();
    }

    private function modelOption(): string
    {
        return (string) ($this->option('model') ?: text('Which model should this Expressive class map to?', default: (string) ($this->argument('name') ?: 'User')));
    }

    /**
     * @return array{without_attributes: bool, attributes: list<string>|null, without_relationships: bool, relationships: list<string>|null, exclude_hidden: bool, hint_morph_map: bool}
     */
    private function generatorOptions(): array
    {
        $config = config('expressive.generator', []);

        $options = [
            'without_attributes' => ! (bool) ($config['with_attributes'] ?? true),
            'attributes' => null,
            'without_relationships' => ! (bool) ($config['with_relationships'] ?? true),
            'relationships' => null,
            'exclude_hidden' => (bool) ($config['exclude_hidden'] ?? false),
            'hint_morph_map' => (bool) ($config['hint_morph_map'] ?? false),
        ];

        if ($this->optionWasPassed('without-attributes')) {
            $options['without_attributes'] = true;
            $options['attributes'] = null;
        }

        if ($this->optionWasPassed('with-attributes')) {
            $options['without_attributes'] = false;
            $options['attributes'] = null;
        }

        if ($this->optionWasPassed('attributes')) {
            $options['without_attributes'] = false;
            $options['attributes'] = $this->listOption('attributes');
        }

        if ($this->optionWasPassed('without-relationships')) {
            $options['without_relationships'] = true;
            $options['relationships'] = null;
        }

        if ($this->optionWasPassed('with-relationships')) {
            $options['without_relationships'] = false;
            $options['relationships'] = null;
        }

        if ($this->optionWasPassed('relationships')) {
            $options['without_relationships'] = false;
            $options['relationships'] = $this->listOption('relationships');
        }

        if ($this->optionWasPassed('exclude-hidden')) {
            $options['exclude_hidden'] = true;
        }

        if ($this->optionWasPassed('include-hidden')) {
            $options['exclude_hidden'] = false;
        }

        if ($this->optionWasPassed('hint-morph-map')) {
            $options['hint_morph_map'] = true;
        }

        return $options;
    }

    private function optionWasPassed(string $option): bool
    {
        return $this->input->hasParameterOption('--'.$option);
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
