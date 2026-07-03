<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Support;

use Illuminate\Filesystem\Filesystem;
use SplFileInfo;

abstract readonly class ClassDiscovery
{
    public function __construct(protected Filesystem $files) {}

    /**
     * @param  list<string>  $paths
     * @return list<class-string>
     */
    protected function classesFromPaths(array $paths, string $appNamespace): array
    {
        $classes = [];

        foreach ($paths as $path) {
            foreach ($this->files->allFiles($path) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $class = $this->classFromFile($file, $appNamespace);

                if ($class === null) {
                    continue;
                }

                require_once $file->getPathname();

                if (! class_exists($class)) {
                    continue;
                }

                $classes[] = $class;
            }
        }

        sort($classes);

        return array_values(array_unique($classes));
    }

    /**
     * @return class-string|null
     */
    private function classFromFile(SplFileInfo $file, string $appNamespace): ?string
    {
        $path = $file->getPathname();
        $appPath = rtrim(app_path(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (! str_starts_with($path, $appPath)) {
            return null;
        }

        $relative = substr($path, strlen($appPath));
        $class = trim($appNamespace, '\\').'\\'.str_replace(
            ['/', '\\', '.php'],
            ['\\', '\\', ''],
            $relative,
        );

        /** @var class-string $class */
        return $class;
    }
}
