<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use WendellAdriel\Expressive\ExpressiveServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ExpressiveServiceProvider::class,
        ];
    }
}
