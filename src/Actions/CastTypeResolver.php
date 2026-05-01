<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Actions;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\AsFluent;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Casts\AsUri;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
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
            $baseCast === AsCollection::class => new GeneratedPropertyType('Collection', ['Illuminate\Support\Collection']),
            $baseCast === AsFluent::class => new GeneratedPropertyType('Fluent', ['Illuminate\Support\Fluent']),
            $baseCast === AsStringable::class => new GeneratedPropertyType('Stringable', ['Illuminate\Support\Stringable']),
            $baseCast === AsUri::class => new GeneratedPropertyType('Uri', ['Illuminate\Support\Uri']),
            default => new GeneratedPropertyType('mixed'),
        };
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
