<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Actions;

use Illuminate\Support\Str;
use WendellAdriel\Expressive\DTOs\ExpressivePropertyShape;

final class ExtractExpressiveShape
{
    /**
     * @return array<string, ExpressivePropertyShape>
     */
    public function handle(string $contents): array
    {
        $shapes = [];
        $relationship = false;

        foreach (explode("\n", $contents) as $line) {
            if (str_contains($line, '#[Relationship]')) {
                $relationship = true;

                continue;
            }

            if (! preg_match('/^\s*public\s+([^\s]+)\s+\$(\w+)/', $line, $matches)) {
                continue;
            }

            $property = $matches[2];
            $kind = $relationship ? 'relationship' : 'attribute';
            $key = $relationship ? $property : Str::snake($property);
            $shape = new ExpressivePropertyShape($kind, $property, $key, $matches[1]);

            $shapes[$shape->identifier()] = $shape;
            $relationship = false;
        }

        return $shapes;
    }
}
