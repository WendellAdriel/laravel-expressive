<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Actions;

use WendellAdriel\Expressive\DTOs\ExpressivePropertyShape;
use WendellAdriel\Expressive\DTOs\ExpressiveShapeDifference;

final class CompareExpressiveShape
{
    /**
     * @param  array<string, ExpressivePropertyShape>  $expected
     * @param  array<string, ExpressivePropertyShape>  $actual
     * @return list<ExpressiveShapeDifference>
     */
    public function handle(
        string $modelClass,
        string $expressiveClass,
        array $expected,
        array $actual,
    ): array {
        $differences = [];

        foreach ($expected as $key => $shape) {
            if (! isset($actual[$key])) {
                $differences[] = $this->difference(
                    'missing_'.$shape->kind,
                    $modelClass,
                    $expressiveClass,
                    $shape,
                    'missing',
                );

                continue;
            }

            if ($actual[$key]->type !== $shape->type) {
                $differences[] = $this->difference(
                    'invalid_type',
                    $modelClass,
                    $expressiveClass,
                    $shape,
                    $actual[$key]->type,
                );
            }
        }

        foreach ($actual as $key => $shape) {
            if (! isset($expected[$key])) {
                $differences[] = $this->difference(
                    'stale_'.$shape->kind,
                    $modelClass,
                    $expressiveClass,
                    $shape,
                    $shape->type,
                    'Remove or remap this property.',
                );
            }
        }

        return $differences;
    }

    private function difference(
        string $kind,
        string $modelClass,
        string $expressiveClass,
        ExpressivePropertyShape $shape,
        string $actualType,
        string $suggestion = 'Run expressive:sync --write or update the class manually.',
    ): ExpressiveShapeDifference {
        return new ExpressiveShapeDifference(
            $kind,
            $modelClass,
            $expressiveClass,
            $shape->property,
            $shape->key,
            $shape->type,
            $actualType,
            $suggestion,
        );
    }
}
