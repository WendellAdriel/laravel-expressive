<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Support;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection;
use WendellAdriel\Expressive\Actions\ExpressiveMetadata;
use WendellAdriel\Expressive\DTOs\PropertyMetadata;
use WendellAdriel\Expressive\Exceptions\InvalidConversionValueException;
use WendellAdriel\Expressive\Exceptions\NonNullablePropertyException;
use WendellAdriel\Expressive\Exceptions\UnsupportedRelationshipPersistenceException;
use WendellAdriel\Expressive\Expressive;

final class ExpressiveMapper
{
    /**
     * @param  array<int, string>|string  $relationships
     * @param  array<int, string>|string  $attributes
     * @return Expressive<Model>
     */
    public static function fromModel(Model $model, array|string $relationships = [], array|string $attributes = []): Expressive
    {
        $relationships = self::normalizeList($relationships);
        $attributes = self::normalizeList($attributes);

        if ($relationships !== []) {
            $model->loadMissing($relationships);
        }

        if ($attributes !== []) {
            $model->append($attributes);
        }

        $class = ClassResolver::expressiveClassFor($model);
        $values = [];

        foreach ((new ExpressiveMetadata)->handle($class) as $metadata) {
            $value = self::modelValue($model, $metadata);

            if ($value === null && ! $metadata->nullable) {
                throw NonNullablePropertyException::forProperty($class, $metadata->name);
            }

            $values[$metadata->name] = self::toExpressiveValue($value);
        }

        return new $class($values);
    }

    /**
     * @param  EloquentCollection<int, Model>  $models
     * @param  array<int, string>|string  $relationships
     * @param  array<int, string>|string  $attributes
     * @return Collection<int, Expressive<Model>>
     */
    public static function fromCollection(EloquentCollection $models, array|string $relationships = [], array|string $attributes = []): Collection
    {
        $relationships = self::normalizeList($relationships);
        $attributes = self::normalizeList($attributes);

        if ($relationships !== []) {
            $models->loadMissing($relationships);
        }

        if ($attributes !== []) {
            $models->each(static function (Model $model) use ($attributes): void {
                $model->append($attributes);
            });
        }

        return $models->map(static fn (Model $model): Expressive => self::fromModel($model));
    }

    /**
     * @template TModel of Model
     *
     * @param  Expressive<TModel>  $expressive
     * @return TModel
     */
    public static function toModel(Expressive $expressive): Model
    {
        $modelClass = ClassResolver::modelClassFor($expressive);
        $model = self::newModel($modelClass);
        $attributes = [];

        foreach ((new ExpressiveMetadata)->handle($expressive::class) as $metadata) {
            if (! $metadata->property->isInitialized($expressive)) {
                continue;
            }

            $value = $metadata->property->getValue($expressive);

            if ($metadata->virtual) {
                continue;
            }

            if ($metadata->relationship) {
                if ($value !== null) {
                    $model->setRelation($metadata->name, self::toRelationshipValue($expressive::class, $metadata, $value));
                }

                continue;
            }

            if ($model->isFillable($metadata->key)) {
                $attributes[$metadata->key] = $value;
            }
        }

        $model->fill($attributes);

        return $model;
    }

    /**
     * @template TModel of Model
     *
     * @param  Expressive<TModel>  $expressive
     * @return TModel
     */
    public static function save(Expressive $expressive): Model
    {
        $model = self::toModel($expressive);
        $model->save();

        foreach ((new ExpressiveMetadata)->handle($expressive::class) as $metadata) {
            if (! $metadata->relationship || ! $metadata->property->isInitialized($expressive)) {
                continue;
            }

            $value = $metadata->property->getValue($expressive);

            if ($value === null) {
                continue;
            }

            $relation = $model->{$metadata->name}();
            $relationshipValue = self::toRelationshipValue($expressive::class, $metadata, $value);

            if ($relation instanceof BelongsTo) {
                if ($relationshipValue instanceof Model) {
                    $relationshipValue->save();
                    $relation->associate($relationshipValue);
                    $model->save();
                }

                continue;
            }

            if ($relation instanceof HasOne || $relation instanceof MorphOne) {
                if ($relationshipValue instanceof Model) {
                    $relation->save($relationshipValue);
                }

                continue;
            }

            if ($relation instanceof HasMany) {
                if ($relationshipValue instanceof EloquentCollection) {
                    $relation->saveMany($relationshipValue);
                }

                continue;
            }

            throw UnsupportedRelationshipPersistenceException::forRelation($model::class, $metadata->name, $relation);
        }

        return $model->fresh() ?? $model;
    }

    /**
     * @param  array<int, string>|string  $values
     * @return list<string>
     */
    public static function normalizeList(array|string $values): array
    {
        return is_string($values)
            ? ($values === '' ? [] : [$values])
            : array_values($values);
    }

    private static function modelValue(Model $model, PropertyMetadata $metadata): mixed
    {
        if ($metadata->relationship) {
            return $model->relationLoaded($metadata->key) ? $model->getRelation($metadata->key) : null;
        }

        if ($metadata->virtual && ! in_array($metadata->key, $model->getAppends(), true)) {
            return null;
        }

        if ($metadata->virtual || array_key_exists($metadata->key, $model->getAttributes())) {
            return $model->getAttribute($metadata->key);
        }

        return null;
    }

    private static function toExpressiveValue(mixed $value): mixed
    {
        if ($value instanceof Model) {
            return self::fromModel($value);
        }

        if ($value instanceof EloquentCollection) {
            return $value->map(static fn (Model $model): Expressive => self::fromModel($model));
        }

        return $value;
    }

    /**
     * @return Model|EloquentCollection<int, Model>
     */
    private static function toRelationshipValue(string $class, PropertyMetadata $metadata, mixed $value): Model|EloquentCollection
    {
        if ($value instanceof Expressive) {
            return self::toModel($value);
        }

        if ($value instanceof Model) {
            return $value;
        }

        if ($value instanceof Collection || is_array($value)) {
            $items = $value instanceof Collection ? $value->all() : $value;

            return new EloquentCollection(array_map(
                static fn (mixed $item): Model => self::relationshipItemToModel($class, $metadata, $item),
                $items,
            ));
        }

        throw InvalidConversionValueException::unsupportedRelationshipValue($class, $metadata->name, $value);
    }

    private static function relationshipItemToModel(string $class, PropertyMetadata $metadata, mixed $item): Model
    {
        if ($item instanceof Expressive) {
            return self::toModel($item);
        }

        if ($item instanceof Model) {
            return $item;
        }

        throw InvalidConversionValueException::unsupportedRelationshipValue($class, $metadata->name, $item);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private static function newModel(string $modelClass): Model
    {
        if (function_exists('app')) {
            /** @var Model $model */
            $model = app()->make($modelClass);

            return $model;
        }

        return new $modelClass;
    }
}
