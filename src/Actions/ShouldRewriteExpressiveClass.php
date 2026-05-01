<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Actions;

use WendellAdriel\Expressive\DTOs\ExpressiveShapeDifference;

final class ShouldRewriteExpressiveClass
{
    /**
     * @param  list<ExpressiveShapeDifference>  $differences
     */
    public function handle(string $contents, array $differences): bool
    {
        if (preg_match('/\bfunction\s+\w+\s*\(/', $contents) === 1) {
            return false;
        }

        foreach ($differences as $difference) {
            if (str_starts_with($difference->kind, 'stale_')) {
                return false;
            }
        }

        return true;
    }
}
