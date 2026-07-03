<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Concerns;

trait ResolvesExpressiveStubPath
{
    private function stubPath(): string
    {
        $publishedStub = base_path('stubs/expressive.stub');

        return $this->files->exists($publishedStub)
            ? $publishedStub
            : dirname(__DIR__, 2).'/stubs/expressive.stub';
    }
}
