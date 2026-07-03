<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use WendellAdriel\Expressive\Actions\GenerateExpressiveClass;
use WendellAdriel\Expressive\Console\Commands\Concerns\ResolvesGeneratorOptions;
use WendellAdriel\Expressive\Exceptions\ExistingExpressiveClassException;
use WendellAdriel\Expressive\Support\ExpressiveClassTargets;

use function Laravel\Prompts\text;

final class MakeExpressiveCommand extends Command
{
    use ResolvesGeneratorOptions;

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
    public function handle(GenerateExpressiveClass $generator, ExpressiveClassTargets $targets): int
    {
        $modelClass = $targets->modelClass($this->modelOption(), $this->laravel->getNamespace());

        try {
            $input = $this->generationInput(
                $modelClass,
                (string) ($this->argument('name') ?: class_basename($modelClass)),
            );
            $generated = $generator->handle($input);
        } catch (ExistingExpressiveClassException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->reportGeneratedExpressive($generated);

        return self::SUCCESS;
    }

    private function modelOption(): string
    {
        return (string) ($this->option('model') ?: text(
            'Which model should this Expressive class map to?',
            default: (string) ($this->argument('name') ?: 'User'),
        ));
    }
}
