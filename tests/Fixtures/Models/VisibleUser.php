<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests\Fixtures\Models;

final class VisibleUser extends User
{
    /**
     * @var list<string>
     */
    protected $visible = ['name', 'email'];
}
