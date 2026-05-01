<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

final readonly class ExpressiveClassBuilder
{
    public function __construct(private CastTypeResolver $castTypeResolver) {}

    public function handle(string $stub, string $namespace, string $class, Model $model): string
    {
        $properties = $this->propertiesFor($namespace, $model);

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
    private function propertiesFor(string $namespace, Model $model): array
    {
        $imports = [
            'WendellAdriel\\Expressive\\Expressive',
        ];
        $properties = [];
        $columns = $model->getConnection()->getSchemaBuilder()->getColumns($model->getTable());

        foreach ($columns as $column) {
            $properties[] = $this->columnProperty($column, $model, $imports);
        }

        foreach ($this->relationshipsFor($model) as $relationship) {
            $properties[] = $this->relationshipProperty($namespace, $relationship, $imports);
        }

        foreach ($this->virtualAttributesFor($model, array_column($columns, 'name')) as $attribute) {
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
     * @param  array{name: string, type: string, related: class-string<Model>|null}  $relationship
     * @param  array<int, string>  $imports
     */
    private function relationshipProperty(string $namespace, array $relationship, array &$imports): string
    {
        $imports[] = 'WendellAdriel\\Expressive\\Attributes\\Relationship';

        if (is_a($relationship['type'], MorphTo::class, true)) {
            $imports[] = 'Illuminate\\Database\\Eloquent\\Model';

            return "    /** @var Expressive<Model>|null */\n    #[Relationship]\n    public ?Expressive $".$relationship['name'].' = null;';
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
