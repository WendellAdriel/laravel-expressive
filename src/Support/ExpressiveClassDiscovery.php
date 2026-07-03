<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Support;

use Illuminate\Database\Eloquent\Model;
use WendellAdriel\Expressive\Expressive;

final readonly class ExpressiveClassDiscovery extends ClassDiscovery
{
    /**
     * @return list<class-string<Expressive<Model>>>
     */
    public function handle(string $path, string $appNamespace): array
    {
        return collect($this->classesFromPaths([$path], $appNamespace))
            ->filter(static fn (string $class): bool => is_subclass_of($class, Expressive::class))
            ->values()
            ->all();
    }
}
