<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use WendellAdriel\Expressive\DTOs\GenerateExpressiveClassOptions;
use WendellAdriel\Expressive\Exceptions\UnsupportedGenerationException;

final readonly class ExpressiveClassBuilder
{
    public function __construct(private CastTypeResolver $castTypeResolver) {}

    public function handle(
        string $stub,
        string $namespace,
        string $class,
        Model $model,
        GenerateExpressiveClassOptions $options = new GenerateExpressiveClassOptions,
    ): string {
        $properties = $this->propertiesFor($namespace, $model, $options);

        $modelAlias = class_basename($model).'Model';
        $imports = collect(explode("\n", $properties['imports']))
            ->push('use '.$model::class." as {$modelAlias};")
            ->filter()
            ->unique()
            ->sort()
            ->implode("\n");

        return str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ imports }}', '{{ model }}', '{{ properties }}'],
            [$namespace, $class, $imports, $modelAlias, $properties['properties']],
            $stub,
        );
    }

    /**
     * @return array{imports: string, properties: string}
     */
    private function propertiesFor(string $namespace, Model $model, GenerateExpressiveClassOptions $options): array
    {
        $imports = [
            'WendellAdriel\\Expressive\\Expressive',
        ];
        $properties = [];
        $columns = $model->getConnection()->getSchemaBuilder()->getColumns($model->getTable());
        $hidden = $options->excludeHidden ? $model->getHidden() : [];
        $columnNames = array_column($columns, 'name');
        $virtualAttributes = $this->virtualAttributesFor($model, $columnNames);
        $selectedAttributes = $this->selectedAttributesFor($model, $columnNames, $virtualAttributes, $options);

        foreach ($columns as $column) {
            if ($selectedAttributes !== null && ! in_array($column['name'], $selectedAttributes, true)) {
                continue;
            }

            if (in_array($column['name'], $hidden, true)) {
                continue;
            }

            $properties[] = $this->columnProperty($column, $model, $imports);
        }

        foreach ($this->filteredRelationshipsFor($model, $options) as $relationship) {
            $properties[] = $this->relationshipProperty($namespace, $model, $relationship, $imports, $options->hintMorphMap);
        }

        foreach ($virtualAttributes as $attribute) {
            if ($selectedAttributes !== null && ! in_array($attribute, $selectedAttributes, true)) {
                continue;
            }

            $imports[] = 'WendellAdriel\\Expressive\\Attributes\\Virtual';
            $properties[] = "    #[Virtual]\n    public ?string $".Str::camel($attribute).' = null;';
        }

        $imports = collect($imports)->unique()->sort()->map(static fn (string $import): string => "use {$import};")->implode("\n");

        return [
            'imports' => $imports,
            'properties' => implode("\n\n", $properties),
        ];
    }

    /**
     * @param  array<string, mixed>  $column
     * @param  array<int, string>  $imports
     */
    private function columnProperty(array $column, Model $model, array &$imports): string
    {
        $name = (string) $column['name'];
        $property = Str::camel($name);
        $generatedType = $this->castTypeResolver->handle($name, $column, $model);
        $type = $generatedType->type;
        $imports = [...$imports, ...$generatedType->imports];
        $nullable = (bool) ($column['nullable'] ?? false) || (bool) ($column['auto_increment'] ?? false);
        $prefix = $nullable && $type !== 'mixed' && ! str_starts_with($type, '?') ? '?' : '';
        $default = $nullable ? ' = null' : '';
        $lines = [];

        if ($generatedType->phpDocType !== null) {
            $lines[] = "    /** @var {$generatedType->phpDocType}".($nullable ? '|null' : '').' */';
        }

        $lines[] = "    public {$prefix}{$type} $".$property.$default.';';

        return implode("\n", $lines);
    }

    /**
     * @return list<array{name: string, type: string, related: class-string<Model>|null}>
     */
    private function relationshipsFor(Model $model): array
    {
        $relationships = [];
        $reflection = new ReflectionClass($model);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $returnType = $method->getReturnType();

            if ($method->getNumberOfParameters() > 0 || ! $returnType instanceof ReflectionNamedType || ! is_subclass_of($returnType->getName(), Relation::class)) {
                continue;
            }

            /** @var Relation<Model, Model, mixed> $relation */
            $relation = $method->invoke($model);

            $relationships[] = [
                'name' => $method->getName(),
                'type' => $relation::class,
                'related' => $relation instanceof MorphTo ? null : $relation->getRelated()::class,
            ];
        }

        return $relationships;
    }

    /**
     * @return list<array{name: string, type: string, related: class-string<Model>|null}>
     */
    private function filteredRelationshipsFor(Model $model, GenerateExpressiveClassOptions $options): array
    {
        if ($options->withoutRelationships) {
            return [];
        }

        $relationships = $this->relationshipsFor($model);
        $only = $options->relationships;

        if ($only === null) {
            return $relationships;
        }

        $names = array_column($relationships, 'name');
        $missing = array_values(array_diff($only, $names));

        if ($missing !== []) {
            throw UnsupportedGenerationException::unknownRelationships($model::class, $missing);
        }

        return array_values(array_filter(
            $relationships,
            static fn (array $relationship): bool => in_array($relationship['name'], $only, true),
        ));
    }

    /**
     * @param  list<string>  $columns
     * @param  list<string>  $virtualAttributes
     * @return list<string>|null
     */
    private function selectedAttributesFor(Model $model, array $columns, array $virtualAttributes, GenerateExpressiveClassOptions $options): ?array
    {
        if ($options->withoutAttributes) {
            return [];
        }

        $only = $options->attributes;

        if ($only === null) {
            return null;
        }

        $available = [...$columns, ...$virtualAttributes];
        $missing = array_values(array_diff($only, $available));

        if ($missing !== []) {
            throw UnsupportedGenerationException::unknownAttributes($model::class, $missing);
        }

        return $only;
    }

    /**
     * @param  array{name: string, type: string, related: class-string<Model>|null}  $relationship
     * @param  array<int, string>  $imports
     */
    private function relationshipProperty(string $namespace, Model $model, array $relationship, array &$imports, bool $hintMorphMap): string
    {
        $imports[] = 'WendellAdriel\\Expressive\\Attributes\\Relationship';

        if (is_a($relationship['type'], MorphTo::class, true)) {
            $imports[] = 'Illuminate\\Database\\Eloquent\\Model';
            $types = $hintMorphMap ? $this->morphMapTypes($model, $relationship['name']) : [];
            $phpDoc = $types === []
                ? 'Expressive<Model>|null'
                : implode('|', $types).'|Expressive<Model>|null';

            return "    /** @var {$phpDoc} */\n    #[Relationship]\n    public ?Expressive $".$relationship['name'].' = null;';
        }

        /** @var class-string<Model> $related */
        $related = $relationship['related'];
        $class = class_basename($related).(string) config('expressive.suffix', '');
        $many = is_a($relationship['type'], HasMany::class, true)
            || is_a($relationship['type'], HasManyThrough::class, true)
            || is_a($relationship['type'], BelongsToMany::class, true)
            || is_a($relationship['type'], MorphMany::class, true)
            || is_a($relationship['type'], MorphToMany::class, true);

        if ($many) {
            $imports[] = 'Illuminate\\Support\\Collection';

            return "    /** @var Collection<int, {$class}>|null */\n    #[Relationship]\n    public ?Collection $".$relationship['name'].' = null;';
        }

        if ($namespace.'\\'.$class !== $namespace.'\\'.class_basename($related)) {
            $imports[] = $namespace.'\\'.$class;
        }

        return "    #[Relationship]\n    public ?{$class} $".$relationship['name'].' = null;';
    }

    /**
     * @return list<string>
     */
    private function morphMapTypes(Model $model, string $morphName): array
    {
        $types = [];

        foreach (Relation::morphMap() as $mappedModel) {
            if (! $this->hasConfidentMorphInverse($mappedModel, $model::class, $morphName)) {
                continue;
            }

            $types[] = class_basename($mappedModel).(string) config('expressive.suffix', '');
        }

        return array_values(array_unique($types));
    }

    /**
     * @param  class-string<Model>  $mappedModel
     * @param  class-string<Model>  $targetModel
     */
    private function hasConfidentMorphInverse(string $mappedModel, string $targetModel, string $morphName): bool
    {
        $instance = new $mappedModel;
        $reflection = new ReflectionClass($instance);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $returnType = $method->getReturnType();

            if ($method->getNumberOfParameters() > 0 || ! $returnType instanceof ReflectionNamedType || ! is_subclass_of($returnType->getName(), Relation::class)) {
                continue;
            }

            /** @var Relation<Model, Model, mixed> $relation */
            $relation = $method->invoke($instance);

            if (! $relation instanceof MorphOne && ! $relation instanceof MorphMany) {
                continue;
            }

            if ($relation->getRelated()::class !== $targetModel) {
                continue;
            }

            if (Str::beforeLast($relation->getMorphType(), '_type') === $morphName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $columns
     * @return list<string>
     */
    private function virtualAttributesFor(Model $model, array $columns): array
    {
        $virtual = [];
        $reflection = new ReflectionClass($model);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PROTECTED) as $method) {
            if ($model->hasAttributeMutator($method->getName())) {
                $name = Str::snake($method->getName());

                if (! in_array($name, $columns, true)) {
                    $virtual[] = $name;
                }
            }
        }

        return $virtual;
    }
}
