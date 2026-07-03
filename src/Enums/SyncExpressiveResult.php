<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Enums;

enum SyncExpressiveResult
{
    case Synced;
    case Updated;
    case Drifted;
    case Unsafe;
}
