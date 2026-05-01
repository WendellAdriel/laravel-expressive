<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Support;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Str;
use ReflectionClass;
use WendellAdriel\Expressive\Attributes\Expressive as ExpressiveAttribute;
use WendellAdriel\Expressive\Attributes\Model as ModelAttribute;
use WendellAdriel\Expressive\Exceptions\InvalidExpressiveClassException;
use WendellAdriel\Expressive\Exceptions\InvalidModelClassException;
use WendellAdriel\Expressive\Exceptions\NonExistingExpressiveClassException;
use WendellAdriel\Expressive\Exceptions\NonExistingModelClassException;
use WendellAdriel\Expressive\Expressive;

final class ClassResolver
{
    /**
     * @return class-string<Expressive<EloquentModel>>
     */
    public static function expressiveClassFor(EloquentModel $model): string
    {
        $reflection = new ReflectionClass($model);
        $attribute = $reflection->getAttributes(ExpressiveAttribute::class)[0] ?? null;

        if ($attribute !== null) {
            $class = $attribute->newInstance()->class;

            return self::validateExpressiveClass($class);
        }

        $class = self::expressiveNamespace().'\\'.class_basename($model).self::suffix();

        if (! class_exists($class)) {
            throw NonExistingExpressiveClassException::forClass($class);
        }

        return self::validateExpressiveClass($class);
    }

    /**
     * @param  Expressive<EloquentModel>  $expressive
     * @return class-string<EloquentModel>
     */
    public static function modelClassFor(Expressive $expressive): string
    {
        $reflection = new ReflectionClass($expressive);
        $attribute = $reflection->getAttributes(ModelAttribute::class)[0] ?? null;

        if ($attribute !== null) {
            $class = $attribute->newInstance()->class;

            return self::validateModelClass($class);
        }

        $relative = str($reflection->getName())
            ->after(self::expressiveNamespace().'\\')
            ->toString();

        $parts = explode('\\', $relative);
        $suffix = self::suffix();
        $basename = (string) end($parts);
        $parts[array_key_last($parts)] = $suffix !== '' && str_ends_with($basename, $suffix)
            ? Str::beforeLast($basename, $suffix)
            : $basename;

        $class = self::appNamespace().'Models\\'.implode('\\', $parts);

        if (! class_exists($class)) {
            throw NonExistingModelClassException::forClass($class);
        }

        return self::validateModelClass($class);
    }

    private static function expressiveNamespace(): string
    {
        return trim((string) config('expressive.namespace', 'App\\Expressive'), '\\');
    }

    private static function suffix(): string
    {
        return (string) config('expressive.suffix', '');
    }

    private static function appNamespace(): string
    {
        return function_exists('app')
            ? app()->getNamespace()
            : 'App\\';
    }

    /**
     * @return class-string<Expressive<EloquentModel>>
     */
    private static function validateExpressiveClass(string $class): string
    {
        if (! class_exists($class)) {
            throw NonExistingExpressiveClassException::forClass($class);
        }

        if (! is_subclass_of($class, Expressive::class)) {
            throw InvalidExpressiveClassException::forClass($class);
        }

        return $class;
    }

    /**
     * @return class-string<EloquentModel>
     */
    private static function validateModelClass(string $class): string
    {
        if (! class_exists($class)) {
            throw NonExistingModelClassException::forClass($class);
        }

        if (! is_subclass_of($class, EloquentModel::class)) {
            throw InvalidModelClassException::forClass($class);
        }

        return $class;
    }
}
