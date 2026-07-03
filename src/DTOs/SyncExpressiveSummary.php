<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\DTOs;

use WendellAdriel\Expressive\Enums\SyncExpressiveResult;

final class SyncExpressiveSummary
{
    public function __construct(
        public int $checked = 0,
        public int $synced = 0,
        public int $drifted = 0,
        public int $missing = 0,
        public int $updated = 0,
    ) {}

    public function recordChecked(): void
    {
        $this->checked++;
    }

    public function recordMissing(): void
    {
        $this->missing++;
    }

    public function recordResult(SyncExpressiveResult $result): void
    {
        match ($result) {
            SyncExpressiveResult::Synced => $this->synced++,
            SyncExpressiveResult::Updated => $this->updated++,
            SyncExpressiveResult::Drifted, SyncExpressiveResult::Unsafe => $this->drifted++,
        };
    }

    public function hasFailures(): bool
    {
        return $this->drifted > 0 || $this->missing > 0;
    }
}
