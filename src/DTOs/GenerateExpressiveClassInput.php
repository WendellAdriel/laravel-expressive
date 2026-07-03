<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\DTOs;

use Illuminate\Database\Eloquent\Model;

final readonly class GenerateExpressiveClassInput
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function __construct(
        public string $appNamespace,
        public string $modelClass,
        public string $name,
        public ?string $namespace,
        public ?string $suffix,
        public GenerateExpressiveClassOptions $options = new GenerateExpressiveClassOptions,
        public bool $dryRun = false,
        public bool $force = false,
    ) {}
}
