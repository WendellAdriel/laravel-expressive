<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;
use JsonSerializable;
use WendellAdriel\Expressive\Actions\ExpressiveMetadata;
use WendellAdriel\Expressive\DTOs\PropertyMetadata;
use WendellAdriel\Expressive\Exceptions\JsonEncodingException;
use WendellAdriel\Expressive\Exceptions\StrictModeEnabledException;
use WendellAdriel\Expressive\Support\ClassResolver;
use WendellAdriel\Expressive\Support\ExpressiveMapper;

/**
 * @template-covariant TModel of Model
 *
 * @implements Arrayable<string, mixed>
 */
abstract class Expressive implements Arrayable, JsonSerializable
{
    /**
     * @param  array<string, mixed>  $values
     */
    public function __construct(array $values = [])
    {
        foreach ($values as $property => $value) {
            if (property_exists($this, $property)) {
                $this->{$property} = $value;
            }
        }
    }

    /**
     * @return TModel
     */
    public function model(): Model
    {
        /** @var TModel $model */
        $model = ExpressiveMapper::toModel($this);

        return $model;
    }

    /**
     * @return TModel
     */
    public function save(): Model
    {
        if (config('expressive.strict', false)) {
            throw StrictModeEnabledException::whenSaving($this::class);
        }

        /** @var TModel $model */
        $model = ExpressiveMapper::save($this);

        return $model;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $values = [];
        $model = $this->serializationModel();
        $visible = $model->getVisible();
        $hidden = $model->getHidden();

        foreach ((new ExpressiveMetadata)->handle($this::class) as $metadata) {
            if (! $metadata->property->isInitialized($this) || ! $this->isArrayableProperty($metadata, $visible, $hidden)) {
                continue;
            }

            $values[$this->serializationKey($metadata->name)] = $this->arrayValue($metadata->property->getValue($this));
        }

        return $values;
    }

    public function toJson(int $options = 0): string
    {
        try {
            return json_encode($this->jsonSerialize(), $options | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw JsonEncodingException::forExpressive($this::class, $exception);
        }
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    private function arrayValue(mixed $value): mixed
    {
        if ($value instanceof self) {
            return $value->toArray();
        }

        if ($value instanceof Collection) {
            return $value->map(fn (mixed $item): mixed => $this->arrayValue($item))->toArray();
        }

        return $value;
    }

    private function serializationKey(string $key): string
    {
        $case = config('expressive.serialization.case', 'preserve');

        return match ($case) {
            'preserve' => $key,
            'snake' => Str::snake($key),
            default => throw new InvalidArgumentException("Unsupported expressive serialization.case [{$case}]."),
        };
    }

    private function serializationModel(): Model
    {
        $modelClass = ClassResolver::modelClassFor($this);

        if (function_exists('app')) {
            /** @var Model $model */
            $model = app()->make($modelClass);

            return $model;
        }

        return new $modelClass;
    }

    /**
     * @param  list<string>  $visible
     * @param  list<string>  $hidden
     */
    private function isArrayableProperty(PropertyMetadata $metadata, array $visible, array $hidden): bool
    {
        if ($visible !== [] && ! in_array($metadata->key, $visible, true)) {
            return false;
        }

        return ! in_array($metadata->key, $hidden, true);
    }
}
