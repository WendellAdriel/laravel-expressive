<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests\Fixtures;

enum UserRole: string
{
    case Admin = 'admin';
    case User = 'user';
}
