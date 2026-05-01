<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Actions;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEncryptedCollection;
use Illuminate\Database\Eloquent\Casts\AsEnumArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Casts\AsFluent;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Casts\AsUri;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use WendellAdriel\Expressive\DTOs\GeneratedPropertyType;

final class CastTypeResolver
{
    /**
     * @param  array<string, mixed>  $column
     */
    public function handle(string $name, array $column, Model $model): GeneratedPropertyType
    {
        $cast = $model->getCasts()[$name] ?? null;

        return is_string($cast)
            ? $this->resolveCast($cast)
            : $this->resolveColumn($column);
    }

    private function resolveCast(string $cast): GeneratedPropertyType
    {
        $baseCast = Str::before($cast, ':');
        $normalizedCast = strtolower($cast);
        $normalizedBaseCast = strtolower($baseCast);

        if (enum_exists($baseCast)) {
            return new GeneratedPropertyType(class_basename($baseCast), [$baseCast]);
        }

        return match (true) {
            in_array($normalizedBaseCast, ['date', 'datetime', 'immutable_date', 'immutable_datetime'], true) => $this->carbon(),
            in_array($normalizedCast, ['array', 'json', 'json:unicode', 'encrypted:array', 'encrypted:json'], true) => new GeneratedPropertyType('array'),
            in_array($normalizedCast, ['collection', 'encrypted:collection'], true) => new GeneratedPropertyType('Collection', ['Illuminate\Support\Collection']),
            in_array($normalizedCast, ['object', 'encrypted:object'], true) => new GeneratedPropertyType('object'),
            in_array($normalizedBaseCast, ['bool', 'boolean'], true) => new GeneratedPropertyType('bool'),
            in_array($normalizedBaseCast, ['int', 'integer'], true) => new GeneratedPropertyType('int'),
            in_array($normalizedBaseCast, ['real', 'float', 'double'], true) => new GeneratedPropertyType('float'),
            $normalizedBaseCast === 'decimal' => new GeneratedPropertyType('string'),
            in_array($normalizedBaseCast, ['encrypted', 'hashed', 'string'], true) => new GeneratedPropertyType('string'),
            $normalizedBaseCast === 'timestamp' => new GeneratedPropertyType('int'),
            $baseCast === AsArrayObject::class => new GeneratedPropertyType('ArrayObject', ['ArrayObject']),
            $baseCast === AsEncryptedArrayObject::class => new GeneratedPropertyType('ArrayObject', ['ArrayObject']),
            $baseCast === AsCollection::class => $this->collectionCast($cast),
            $baseCast === AsEncryptedCollection::class => $this->collectionCast($cast),
            $baseCast === AsEnumArrayObject::class => $this->enumContainerCast($cast, 'ArrayObject', ['ArrayObject']),
            $baseCast === AsEnumCollection::class => $this->enumContainerCast($cast, 'Collection', ['Illuminate\Support\Collection']),
            $baseCast === AsFluent::class => new GeneratedPropertyType('Fluent', ['Illuminate\Support\Fluent']),
            $baseCast === AsStringable::class => new GeneratedPropertyType('Stringable', ['Illuminate\Support\Stringable']),
            $baseCast === AsUri::class => new GeneratedPropertyType('Uri', ['Illuminate\Support\Uri']),
            default => $this->customCast($baseCast),
        };
    }

    private function collectionCast(string $cast): GeneratedPropertyType
    {
        [$collectionClass, $itemClass] = array_pad($this->castArguments($cast), 2, '');
        $type = 'Collection';
        $imports = ['Illuminate\Support\Collection'];

        if ($collectionClass !== '' && class_exists($collectionClass) && is_subclass_of($collectionClass, Collection::class)) {
            $type = class_basename($collectionClass);
            $imports = [$collectionClass];
        }

        if ($itemClass === '' || str_contains($itemClass, '@') || ! class_exists($itemClass)) {
            return new GeneratedPropertyType($type, $imports);
        }

        $imports[] = $itemClass;

        return new GeneratedPropertyType($type, $imports, "{$type}<int, ".class_basename($itemClass).'>');
    }

    /**
     * @param  list<string>  $imports
     */
    private function enumContainerCast(string $cast, string $type, array $imports): GeneratedPropertyType
    {
        $enumClass = $this->castArguments($cast)[0] ?? '';

        if (! enum_exists($enumClass)) {
            return new GeneratedPropertyType($type, $imports);
        }

        $imports[] = $enumClass;

        return new GeneratedPropertyType($type, $imports, "{$type}<int, ".class_basename($enumClass).'>');
    }

    /**
     * @return list<string>
     */
    private function castArguments(string $cast): array
    {
        if (! str_contains($cast, ':')) {
            return [];
        }

        return array_map(trim(...), explode(',', Str::after($cast, ':')));
    }

    private function customCast(string $cast): GeneratedPropertyType
    {
        if (! class_exists($cast) || ! is_subclass_of($cast, CastsAttributes::class)) {
            return new GeneratedPropertyType('mixed');
        }

        /** @var ReflectionClass<CastsAttributes<mixed, mixed>> $reflection */
        $reflection = new ReflectionClass($cast);
        $docComment = $reflection->getDocComment() ?: '';

        if (! preg_match('/@implements\s+(?:[\\\\\w]+\\\\)?CastsAttributes<([^,>]+),\s*[^>]+>/', $docComment, $matches)) {
            return new GeneratedPropertyType('mixed');
        }

        return $this->customCastType(trim($matches[1]), $reflection);
    }

    /**
     * @param  ReflectionClass<CastsAttributes<mixed, mixed>>  $reflection
     */
    private function customCastType(string $type, ReflectionClass $reflection): GeneratedPropertyType
    {
        $scalarTypes = ['array', 'bool', 'float', 'int', 'object', 'string'];

        if ($type === 'mixed' || str_contains($type, '|') || str_contains($type, '&')) {
            return new GeneratedPropertyType('mixed');
        }

        if (in_array($type, $scalarTypes, true)) {
            return new GeneratedPropertyType($type);
        }

        $class = $this->resolveCustomCastClass($type, $reflection);

        if ($class === null) {
            return new GeneratedPropertyType('mixed');
        }

        return new GeneratedPropertyType(class_basename($class), [$class]);
    }

    /**
     * @param  ReflectionClass<CastsAttributes<mixed, mixed>>  $reflection
     */
    private function resolveCustomCastClass(string $type, ReflectionClass $reflection): ?string
    {
        $class = ltrim($type, '\\');

        if (str_contains($class, '\\') && class_exists($class)) {
            return $class;
        }

        foreach ($this->importsFor($reflection) as $import) {
            if (class_basename($import) === $class && class_exists($import)) {
                return $import;
            }
        }

        $namespaced = $reflection->getNamespaceName().'\\'.$class;

        return class_exists($namespaced) ? $namespaced : null;
    }

    /**
     * @param  ReflectionClass<CastsAttributes<mixed, mixed>>  $reflection
     * @return list<class-string>
     */
    private function importsFor(ReflectionClass $reflection): array
    {
        $filename = $reflection->getFileName();

        if ($filename === false) {
            return [];
        }

        $contents = file_get_contents($filename);

        if ($contents === false) {
            return [];
        }

        preg_match_all('/^use\s+([^;]+);/m', $contents, $matches);

        return array_values(array_filter(
            $matches[1],
            static fn (string $import): bool => class_exists($import),
        ));
    }

    /**
     * @param  array<string, mixed>  $column
     */
    private function resolveColumn(array $column): GeneratedPropertyType
    {
        $type = strtolower((string) ($column['type_name'] ?? $column['type'] ?? 'string'));

        return match (true) {
            str_contains($type, 'int') => new GeneratedPropertyType('int'),
            str_contains($type, 'bool') => new GeneratedPropertyType('bool'),
            str_contains($type, 'float'), str_contains($type, 'double'), str_contains($type, 'real') => new GeneratedPropertyType('float'),
            str_contains($type, 'date'), str_contains($type, 'time') => $this->carbon(),
            default => new GeneratedPropertyType('string'),
        };
    }

    private function carbon(): GeneratedPropertyType
    {
        return new GeneratedPropertyType('CarbonInterface', ['Carbon\CarbonInterface']);
    }
}
