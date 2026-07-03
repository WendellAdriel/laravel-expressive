<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Console\Commands\Concerns;

use Illuminate\Database\Eloquent\Model;
use WendellAdriel\Expressive\DTOs\GeneratedExpressiveClass;
use WendellAdriel\Expressive\DTOs\GenerateExpressiveClassInput;
use WendellAdriel\Expressive\DTOs\GenerateExpressiveClassOptions;

trait ResolvesGeneratorOptions
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    private function generationInput(string $modelClass, ?string $name = null): GenerateExpressiveClassInput
    {
        return new GenerateExpressiveClassInput(
            appNamespace: $this->laravel->getNamespace(),
            modelClass: $modelClass,
            name: $name ?: class_basename($modelClass),
            namespace: $this->option('namespace') === null ? null : (string) $this->option('namespace'),
            suffix: $this->option('suffix') === null ? null : (string) $this->option('suffix'),
            options: $this->generatorOptions(),
            dryRun: (bool) $this->option('dry-run'),
            force: (bool) $this->option('force'),
        );
    }

    private function reportGeneratedExpressive(GeneratedExpressiveClass $generated): void
    {
        if ($this->option('dry-run')) {
            $this->line($generated->contents);

            return;
        }

        $this->components->info("Expressive [{$generated->target->path}] created successfully.");
    }

    private function generatorOptions(): GenerateExpressiveClassOptions
    {
        $config = config('expressive.generator', []);

        $withoutAttributes = ! (bool) ($config['with_attributes'] ?? true);
        $attributes = null;
        $withoutRelationships = ! (bool) ($config['with_relationships'] ?? true);
        $relationships = null;
        $excludeHidden = (bool) ($config['exclude_hidden'] ?? false);
        $hintMorphMap = (bool) ($config['hint_morph_map'] ?? false);

        if ($this->optionWasPassed('without-attributes')) {
            $withoutAttributes = true;
            $attributes = null;
        }

        if ($this->optionWasPassed('with-attributes')) {
            $withoutAttributes = false;
            $attributes = null;
        }

        if ($this->optionWasPassed('attributes')) {
            $withoutAttributes = false;
            $attributes = $this->listOption('attributes');
        }

        if ($this->optionWasPassed('without-relationships')) {
            $withoutRelationships = true;
            $relationships = null;
        }

        if ($this->optionWasPassed('with-relationships')) {
            $withoutRelationships = false;
            $relationships = null;
        }

        if ($this->optionWasPassed('relationships')) {
            $withoutRelationships = false;
            $relationships = $this->listOption('relationships');
        }

        if ($this->optionWasPassed('exclude-hidden')) {
            $excludeHidden = true;
        }

        if ($this->optionWasPassed('include-hidden')) {
            $excludeHidden = false;
        }

        if ($this->optionWasPassed('hint-morph-map')) {
            $hintMorphMap = true;
        }

        return new GenerateExpressiveClassOptions(
            withoutAttributes: $withoutAttributes,
            attributes: $attributes,
            withoutRelationships: $withoutRelationships,
            relationships: $relationships,
            excludeHidden: $excludeHidden,
            hintMorphMap: $hintMorphMap,
        );
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

    private function optionWasPassed(string $option): bool
    {
        return $this->input->hasParameterOption('--'.$option);
    }
}
