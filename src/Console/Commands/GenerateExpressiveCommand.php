<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use WendellAdriel\Expressive\Actions\GenerateExpressiveClass;
use WendellAdriel\Expressive\Console\Commands\Concerns\ResolvesGeneratorOptions;
use WendellAdriel\Expressive\Exceptions\ExistingExpressiveClassException;
use WendellAdriel\Expressive\Exceptions\ExpressiveException;
use WendellAdriel\Expressive\Support\ModelClassDiscovery;

final class GenerateExpressiveCommand extends Command
{
    use ResolvesGeneratorOptions;

    protected $signature = 'expressive:generate
        {--path=* : App-relative model path to scan, defaults to app/Models}
        {--namespace= : Override the configured Expressive namespace}
        {--suffix= : Override the configured Expressive suffix}
        {--exclude=* : Model basename or class name to skip}
        {--with-attributes : Generate all attribute properties and ignore attribute generator defaults}
        {--without-attributes : Do not generate attribute properties}
        {--attributes= : Generate only the given comma-separated attribute properties}
        {--with-relationships : Generate all relationship properties and ignore relationship generator defaults}
        {--without-relationships : Do not generate relationship properties}
        {--relationships= : Generate only the given comma-separated relationship properties}
        {--include-hidden : Include hidden model attributes and ignore hidden generator defaults}
        {--exclude-hidden : Exclude hidden model attributes from generated properties}
        {--hint-morph-map : Enrich MorphTo PHPDoc with confident morph-map Expressive classes}
        {--dry-run : Print generated classes without writing files}
        {--force : Overwrite Expressive classes if they already exist}';

    protected $description = 'Generate Expressive classes for discovered Eloquent models';

    /**
     * @throws FileNotFoundException
     */
    public function handle(Filesystem $files, ModelClassDiscovery $discovery, GenerateExpressiveClass $generator): int
    {
        $paths = $this->resolvedPaths($files);

        if ($paths === null) {
            return self::FAILURE;
        }

        $models = $discovery->handle($paths, $this->laravel->getNamespace());
        $excluded = collect($this->option('exclude'))->map(static fn (string $exclude): string => trim($exclude, '\\'))->filter();
        $selectedModels = collect($models)
            ->reject(static fn (string $model): bool => $excluded->contains(class_basename($model)) || $excluded->contains($model))
            ->values();

        $generated = 0;
        $failed = 0;

        foreach ($selectedModels as $modelClass) {
            try {
                $input = $this->generationInput($modelClass);
                $result = $generator->handle($input);

                $generated++;
                $this->reportGeneratedExpressive($result);
            } catch (ExistingExpressiveClassException|ExpressiveException $exception) {
                $failed++;
                $this->components->error("{$modelClass}: {$exception->getMessage()}");
            }
        }

        $this->summary(count($models), $generated, count($models) - $selectedModels->count(), $failed);

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return list<string>|null
     */
    private function resolvedPaths(Filesystem $files): ?array
    {
        $paths = $this->option('path') === [] ? ['app/Models'] : $this->option('path');
        $resolved = [];

        foreach ($paths as $path) {
            $path = (string) $path;
            $resolvedPath = $this->isAbsolutePath($path) ? $path : base_path($path);

            if (! $files->isDirectory($resolvedPath)) {
                $this->components->error("Model path [{$path}] does not exist.");

                return null;
            }

            $resolved[] = $resolvedPath;
        }

        return $resolved;
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }

    private function summary(int $discovered, int $generated, int $skipped, int $failed): void
    {
        $this->line("Discovered: {$discovered}");
        $this->line("Generated: {$generated}");
        $this->line("Skipped: {$skipped}");
        $this->line("Failed: {$failed}");
    }
}
