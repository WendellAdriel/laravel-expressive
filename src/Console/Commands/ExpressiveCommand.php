<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Console\Commands;

use Illuminate\Console\Command;

final class ExpressiveCommand extends Command
{
    protected $signature = 'expressive:placeholder';

    protected $description = 'Placeholder Artisan command shipped by the package expressive.';

    public function handle(): int
    {
        $this->line('Expressive placeholder command executed.');

        return self::SUCCESS;
    }
}
