<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use WendellAdriel\Expressive\DTOs\ExpressiveClassTarget;
use WendellAdriel\Expressive\Exceptions\InvalidModelClassException;
use WendellAdriel\Expressive\Exceptions\NonExistingModelClassException;

final class ExpressiveClassTargets
{
    /**
     * @return class-string<Model>
     */
    public function modelClass(string $model, string $appNamespace): string
    {
        $class = str_replace('/', '\\', trim($model, '\\/'));

        if (! str_contains($class, '\\')) {
            $class = $appNamespace.'Models\\'.$class;
        }

        if (! class_exists($class)) {
            throw NonExistingModelClassException::forClass($class);
        }

        if (! is_subclass_of($class, Model::class)) {
            throw InvalidModelClassException::forClass($class);
        }

        return $class;
    }

    public function className(string $name, ?string $suffix = null): string
    {
        $class = Str::studly(class_basename(str_replace('/', '\\', $name)));
        $suffix ??= (string) config('expressive.suffix', '');

        if ($suffix !== '' && ! str_ends_with($class, $suffix)) {
            $class .= $suffix;
        }

        return $class;
    }

    public function namespace(?string $namespace = null): string
    {
        return trim((string) ($namespace ?: config('expressive.namespace', 'App\\Expressive')), '\\');
    }

    public function pathFor(string $appNamespace, string $namespace, string $class): string
    {
        $root = trim($appNamespace, '\\');

        if (str_starts_with($namespace, $root)) {
            $relative = Str::after($namespace, $root);

            return app_path(str_replace('\\', '/', $relative).'/'.$class.'.php');
        }

        if (str_starts_with($namespace, 'App\\')) {
            return app_path(str_replace('\\', '/', Str::after($namespace, 'App\\')).'/'.$class.'.php');
        }

        return base_path(str_replace('\\', '/', $namespace).'/'.$class.'.php');
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function target(
        string $appNamespace,
        string $modelClass,
        string $name,
        ?string $namespace = null,
        ?string $suffix = null,
    ): ExpressiveClassTarget {
        $class = $this->className($name, $suffix);
        $resolvedNamespace = $this->namespace($namespace);

        return new ExpressiveClassTarget(
            modelClass: $modelClass,
            namespace: $resolvedNamespace,
            class: $class,
            expressiveClass: $resolvedNamespace.'\\'.$class,
            path: $this->pathFor($appNamespace, $resolvedNamespace, $class),
        );
    }
}
