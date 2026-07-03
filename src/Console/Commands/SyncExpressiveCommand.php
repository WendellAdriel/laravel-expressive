<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use ReflectionClass;
use WendellAdriel\Expressive\Actions\CompareExpressiveShape;
use WendellAdriel\Expressive\Actions\ExpressiveClassBuilder;
use WendellAdriel\Expressive\Actions\ExtractExpressiveShape;
use WendellAdriel\Expressive\Actions\ShouldRewriteExpressiveClass;
use WendellAdriel\Expressive\Concerns\ResolvesExpressiveStubPath;
use WendellAdriel\Expressive\DTOs\ExpressiveShapeDifference;
use WendellAdriel\Expressive\DTOs\SyncExpressiveSummary;
use WendellAdriel\Expressive\Enums\SyncExpressiveResult;
use WendellAdriel\Expressive\Exceptions\InvalidModelClassException;
use WendellAdriel\Expressive\Exceptions\NonExistingModelClassException;
use WendellAdriel\Expressive\Exceptions\UnsupportedGenerationException;
use WendellAdriel\Expressive\Support\ClassResolver;
use WendellAdriel\Expressive\Support\ExpressiveClassDiscovery;
use WendellAdriel\Expressive\Support\ExpressiveClassTargets;

final class SyncExpressiveCommand extends Command
{
    use ResolvesExpressiveStubPath;

    private Filesystem $files;

    private ExpressiveClassBuilder $builder;

    private ExpressiveClassTargets $targets;

    private ExpressiveClassDiscovery $discovery;

    private ExtractExpressiveShape $extractShape;

    private CompareExpressiveShape $compareShape;

    private ShouldRewriteExpressiveClass $shouldRewrite;

    protected $signature = 'expressive:sync
        {name? : The Expressive class name}
        {--model= : The Eloquent model class}
        {--namespace= : Override the configured Expressive namespace}
        {--suffix= : Override the configured Expressive suffix}
        {--all : Validate or safely sync all discovered Expressive classes}
        {--write : Safely rewrite generated Expressive classes when possible}';

    protected $description = 'Validate or safely sync an Expressive class against its Eloquent model';

    /**
     * @throws FileNotFoundException
     */
    public function handle(
        Filesystem $files,
        ExpressiveClassBuilder $builder,
        ExpressiveClassTargets $targets,
        ExpressiveClassDiscovery $discovery,
        ExtractExpressiveShape $extractShape,
        CompareExpressiveShape $compareShape,
        ShouldRewriteExpressiveClass $shouldRewrite,
    ): int {
        $this->files = $files;
        $this->builder = $builder;
        $this->targets = $targets;
        $this->discovery = $discovery;
        $this->extractShape = $extractShape;
        $this->compareShape = $compareShape;
        $this->shouldRewrite = $shouldRewrite;

        if ($this->option('all')) {
            return $this->handleAll();
        }

        $modelClass = $this->modelClass();

        if ($modelClass === null) {
            return self::FAILURE;
        }

        $target = $this->targets->target(
            $this->laravel->getNamespace(),
            $modelClass,
            (string) ($this->argument('name') ?: class_basename($modelClass)),
            $this->option('namespace') === null ? null : (string) $this->option('namespace'),
            $this->option('suffix') === null ? null : (string) $this->option('suffix'),
        );

        if (! $this->files->exists($target->path)) {
            $this->components->error(
                "Expressive [{$target->expressiveClass}] does not exist at [{$target->path}].",
            );

            return self::FAILURE;
        }

        $result = $this->syncOne(
            $modelClass,
            $target->expressiveClass,
            $target->namespace,
            $target->class,
            $target->path,
        );

        if ($result === SyncExpressiveResult::Synced) {
            $this->components->info('Expressive is in sync.');

            return self::SUCCESS;
        }

        if ($result === SyncExpressiveResult::Updated) {
            $this->components->info("Expressive updated: {$target->path}");

            return self::SUCCESS;
        }

        return self::FAILURE;
    }

    /**
     * @throws FileNotFoundException
     */
    private function handleAll(): int
    {
        if ((string) $this->option('model') !== '') {
            $this->components->error('The --model option cannot be used with --all.');

            return self::FAILURE;
        }

        if ((string) $this->argument('name') !== '') {
            $this->components->error('The name argument cannot be used with --all.');

            return self::FAILURE;
        }

        $namespace = $this->option('namespace') === null
            ? $this->targets->namespace()
            : $this->targets->namespace((string) $this->option('namespace'));

        $path = $this->targets->directoryFor($this->laravel->getNamespace(), $namespace);

        if (! $this->files->isDirectory($path)) {
            $this->components->error("Expressive path [{$path}] does not exist.");

            return self::FAILURE;
        }

        $previousNamespace = config('expressive.namespace');
        $previousSuffix = config('expressive.suffix');
        config()->set('expressive.namespace', $namespace);

        if ($this->option('suffix') !== null) {
            config()->set('expressive.suffix', (string) $this->option('suffix'));
        }

        $summary = new SyncExpressiveSummary;

        try {
            foreach ($this->discovery->handle($path, $this->laravel->getNamespace()) as $expressiveClass) {
                $reflection = new ReflectionClass($expressiveClass);
                $shortName = $reflection->getShortName();

                try {
                    $modelClass = ClassResolver::modelClassFor($this->laravel->make($expressiveClass));
                } catch (InvalidModelClassException|NonExistingModelClassException $exception) {
                    $summary->recordMissing();
                    $this->components->error("{$expressiveClass}: {$exception->getMessage()}");

                    continue;
                }

                $summary->recordChecked();
                $result = $this->syncOne(
                    $modelClass,
                    $expressiveClass,
                    $reflection->getNamespaceName(),
                    $shortName,
                    $reflection->getFileName() ?: "{$path}/{$shortName}.php",
                );

                $summary->recordResult($result);
            }
        } finally {
            config()->set('expressive.namespace', $previousNamespace);
            config()->set('expressive.suffix', $previousSuffix);
        }

        $this->syncAllSummary($summary);

        return $summary->hasFailures() ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  class-string<Model>  $modelClass
     *
     * @throws FileNotFoundException
     */
    private function syncOne(
        string $modelClass,
        string $expressiveClass,
        string $namespace,
        string $class,
        string $path,
    ): SyncExpressiveResult {
        $model = $this->newModel($modelClass);

        if (! $model->getConnection()->getSchemaBuilder()->hasTable($model->getTable())) {
            throw UnsupportedGenerationException::missingTable($modelClass, $model->getTable());
        }

        $expectedContents = $this->builder->handle(
            $this->files->get($this->stubPath()),
            $namespace,
            $class,
            $model,
        );
        $currentContents = $this->files->get($path);
        $differences = $this->compareShape->handle(
            $modelClass,
            $expressiveClass,
            $this->extractShape->handle($expectedContents),
            $this->extractShape->handle($currentContents),
        );

        if ($differences === []) {
            return SyncExpressiveResult::Synced;
        }

        if (! $this->option('write')) {
            $this->reportDifferences($differences);

            return SyncExpressiveResult::Drifted;
        }

        if (! $this->shouldRewrite->handle($currentContents, $differences)) {
            $this->reportDifferences($differences);
            $this->components->error(
                'Unsafe rewrite refused. Remove user edits or update the class manually.',
            );

            return SyncExpressiveResult::Unsafe;
        }

        $this->files->put($path, $expectedContents);

        return SyncExpressiveResult::Updated;
    }

    /**
     * @return class-string<Model>|null
     */
    private function modelClass(): ?string
    {
        $model = (string) $this->option('model');

        if ($model === '') {
            $this->components->error('The --model option is required.');

            return null;
        }

        try {
            return $this->targets->modelClass($model, $this->laravel->getNamespace());
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
                "model: {$difference->modelClass}",
                "expressive: {$difference->expressiveClass}",
                "property: {$difference->property}",
                "key: {$difference->key}",
                "expected: {$difference->expectedType}",
                "actual: {$difference->actualType}",
                "suggestion: {$difference->suggestion}",
            ]));
        }
    }

    private function syncAllSummary(SyncExpressiveSummary $summary): void
    {
        $this->line("Checked: {$summary->checked}");
        $this->line("In sync: {$summary->synced}");
        $this->line("Drifted: {$summary->drifted}");
        $this->line("Missing: {$summary->missing}");
        $this->line("Updated: {$summary->updated}");
    }
}
