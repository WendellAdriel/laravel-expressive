<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Actions;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use WendellAdriel\Expressive\Concerns\ResolvesExpressiveStubPath;
use WendellAdriel\Expressive\DTOs\GeneratedExpressiveClass;
use WendellAdriel\Expressive\DTOs\GenerateExpressiveClassInput;
use WendellAdriel\Expressive\Exceptions\ExistingExpressiveClassException;
use WendellAdriel\Expressive\Exceptions\UnsupportedGenerationException;
use WendellAdriel\Expressive\Support\ExpressiveClassTargets;

final readonly class GenerateExpressiveClass
{
    use ResolvesExpressiveStubPath;

    public function __construct(
        private Filesystem $files,
        private ExpressiveClassBuilder $builder,
        private ExpressiveClassTargets $targets,
    ) {}

    /**
     * @throws FileNotFoundException
     */
    public function handle(GenerateExpressiveClassInput $input): GeneratedExpressiveClass
    {
        $model = $this->newModel($input->modelClass);
        $target = $this->targets->target(
            $input->appNamespace,
            $input->modelClass,
            $input->name,
            $input->namespace,
            $input->suffix,
        );

        if (! $input->dryRun && $this->files->exists($target->path) && ! $input->force) {
            throw ExistingExpressiveClassException::make();
        }

        if (! $model->getConnection()->getSchemaBuilder()->hasTable($model->getTable())) {
            throw UnsupportedGenerationException::missingTable($input->modelClass, $model->getTable());
        }

        $contents = $this->builder->handle(
            $this->files->get($this->stubPath()),
            $target->namespace,
            $target->class,
            $model,
            $input->options,
        );

        if (! $input->dryRun) {
            $this->files->ensureDirectoryExists(dirname($target->path));
            $this->files->put($target->path, $contents);
        }

        return new GeneratedExpressiveClass($target, $contents);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function newModel(string $modelClass): Model
    {
        /** @var Model $model */
        $model = app($modelClass);

        return $model;
    }
}
