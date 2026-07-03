<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Support;

use Illuminate\Database\Eloquent\Model;

final readonly class ModelClassDiscovery extends ClassDiscovery
{
    /**
     * @param  list<string>  $paths
     * @return list<class-string<Model>>
     */
    public function handle(array $paths, string $appNamespace): array
    {
        return collect($this->classesFromPaths($paths, $appNamespace))
            ->filter(static fn (string $class): bool => is_subclass_of($class, Model::class))
            ->values()
            ->all();
    }
}
