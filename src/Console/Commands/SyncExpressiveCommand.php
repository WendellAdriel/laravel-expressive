<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use WendellAdriel\Expressive\Actions\CompareExpressiveShape;
use WendellAdriel\Expressive\Actions\ExpressiveClassBuilder;
use WendellAdriel\Expressive\Actions\ExtractExpressiveShape;
use WendellAdriel\Expressive\Actions\ShouldRewriteExpressiveClass;
use WendellAdriel\Expressive\DTOs\ExpressiveShapeDifference;
use WendellAdriel\Expressive\Exceptions\InvalidModelClassException;
use WendellAdriel\Expressive\Exceptions\NonExistingModelClassException;
use WendellAdriel\Expressive\Exceptions\UnsupportedGenerationException;
use WendellAdriel\Expressive\Support\ExpressiveClassTargets;

final class SyncExpressiveCommand extends Command
{
    protected $signature = 'expressive:sync
        {name? : The Expressive class name}
        {--model= : The Eloquent model class}
        {--namespace= : Override the configured Expressive namespace}
        {--suffix= : Override the configured Expressive suffix}
        {--write : Safely rewrite generated Expressive classes when possible}';

    protected $description = 'Validate or safely sync an Expressive class against its Eloquent model';

    /**
     * @throws FileNotFoundException
     */
    public function handle(
        Filesystem $files,
        ExpressiveClassBuilder $builder,
        ExpressiveClassTargets $targets,
        ExtractExpressiveShape $extractShape,
        CompareExpressiveShape $compareShape,
        ShouldRewriteExpressiveClass $shouldRewrite,
    ): int {
        $modelClass = $this->modelClass($targets);

        if ($modelClass === null) {
            return self::FAILURE;
        }

        $model = $this->newModel($modelClass);
        $target = $targets->target(
            $this->laravel->getNamespace(),
            $modelClass,
            (string) ($this->argument('name') ?: class_basename($modelClass)),
            $this->option('namespace') === null ? null : (string) $this->option('namespace'),
            $this->option('suffix') === null ? null : (string) $this->option('suffix'),
        );

        if (! $files->exists($target->path)) {
            $this->components->error(
                "Expressive [{$target->expressiveClass}] does not exist at [{$target->path}].",
            );

            return self::FAILURE;
        }

        if (! $model->getConnection()->getSchemaBuilder()->hasTable($model->getTable())) {
            throw UnsupportedGenerationException::missingTable($modelClass, $model->getTable());
        }

        $expectedContents = $builder->handle(
            $files->get($this->stubPath($files)),
            $target->namespace,
            $target->class,
            $model,
        );
        $currentContents = $files->get($target->path);
        $differences = $compareShape->handle(
            $modelClass,
            $target->expressiveClass,
            $extractShape->handle($expectedContents),
            $extractShape->handle($currentContents),
        );

        if ($differences === []) {
            $this->components->info('Expressive is in sync.');

            return self::SUCCESS;
        }

        if (! $this->option('write')) {
            $this->reportDifferences($differences);

            return self::FAILURE;
        }

        if (! $shouldRewrite->handle($currentContents, $differences)) {
            $this->reportDifferences($differences);
            $this->components->error(
                'Unsafe rewrite refused. Remove user edits or update the class manually.',
            );

            return self::FAILURE;
        }

        $files->put($target->path, $expectedContents);

        $this->components->info("Expressive updated: {$target->path}");

        return self::SUCCESS;
    }

    /**
     * @return class-string<Model>|null
     */
    private function modelClass(ExpressiveClassTargets $targets): ?string
    {
        $model = (string) $this->option('model');

        if ($model === '') {
            $this->components->error('The --model option is required.');

            return null;
        }

        try {
            return $targets->modelClass($model, $this->laravel->getNamespace());
        } catch (NonExistingModelClassException $exception) {
            $this->components->error(str_replace('Model class', 'Model', $exception->getMessage()));
        } catch (InvalidModelClassException $exception) {
            $this->components->error($exception->getMessage());
        }

        return null;
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

    /**
     * @param  list<ExpressiveShapeDifference>  $differences
     */
    private function reportDifferences(array $differences): void
    {
        foreach ($differences as $difference) {
            $this->getOutput()->writeln(implode(' | ', [
                $difference->kind,
                'model: '.$difference->modelClass,
                'expressive: '.$difference->expressiveClass,
                'property: '.$difference->property,
                'key: '.$difference->key,
                'expected: '.$difference->expectedType,
                'actual: '.$difference->actualType,
                'suggestion: '.$difference->suggestion,
            ]));
        }
    }

    private function stubPath(Filesystem $files): string
    {
        $publishedStub = base_path('stubs/expressive.stub');

        return $files->exists($publishedStub)
            ? $publishedStub
            : __DIR__.'/../../../stubs/expressive.stub';
    }
}
