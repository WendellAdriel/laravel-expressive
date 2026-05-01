<?php

declare(strict_types=1);

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use WendellAdriel\Expressive\Tests\Fixtures\Models\User;

beforeEach(function (): void {
    File::deleteDirectory(app_path('SyncExpressive'));
    File::deleteDirectory(base_path('stubs'));
});

function syncArguments(array $arguments = []): array
{
    return array_merge(['--namespace' => 'App\SyncExpressive'], $arguments);
}

function syncPath(string $class = 'User'): string
{
    return app_path("SyncExpressive/{$class}.php");
}

it('reports success for a valid model and expressive pair', function (): void {
    Artisan::call('make:expressive', syncArguments(['name' => 'User', '--model' => User::class]));

    $result = Artisan::call('expressive:sync', syncArguments(['name' => 'User', '--model' => User::class]));

    expect($result)->toBe(Command::SUCCESS)
        ->and(Artisan::output())->toContain('Expressive is in sync');
});

it('reports missing attributes with actionable context', function (): void {
    Artisan::call('make:expressive', syncArguments(['name' => 'User', '--model' => User::class]));
    $path = syncPath();
    File::put($path, str_replace("\n    public ?string \$uuid = null;\n", "\n", File::get($path)));

    $result = Artisan::call('expressive:sync', syncArguments(['name' => 'User', '--model' => User::class]));
    $output = Artisan::output();

    expect($result)->toBe(Command::FAILURE)
        ->and($output)->toContain('missing_attribute')
        ->and($output)->toContain(User::class)
        ->and($output)->toContain('App\SyncExpressive\User')
        ->and($output)->toContain('uuid')
        ->and($output)->toContain('expected: ?string')
        ->and($output)->toContain('Run expressive:sync --write');
});

it('reports stale attributes', function (): void {
    Artisan::call('make:expressive', syncArguments(['name' => 'User', '--model' => User::class]));
    $path = syncPath();
    File::put($path, str_replace("\n}\n", "\n\n    public ?string \$legacyCode = null;\n}\n", File::get($path)));

    $result = Artisan::call('expressive:sync', syncArguments(['name' => 'User', '--model' => User::class]));
    $output = Artisan::output();

    expect($result)->toBe(Command::FAILURE)
        ->and($output)->toContain('stale_attribute')
        ->and($output)->toContain('legacyCode')
        ->and($output)->toContain('legacy_code');
});

it('reports missing and stale relationships separately from attributes', function (): void {
    Artisan::call('make:expressive', syncArguments(['name' => 'User', '--model' => User::class]));
    $path = syncPath();
    $contents = File::get($path);
    $contents = str_replace("\n    #[Relationship]\n    public ?Address \$address = null;\n", "\n", $contents);
    $contents = str_replace("\n}\n", "\n\n    #[Relationship]\n    public ?Address \$legacyAddress = null;\n}\n", $contents);
    File::put($path, $contents);

    $result = Artisan::call('expressive:sync', syncArguments(['name' => 'User', '--model' => User::class]));
    $output = Artisan::output();

    expect($result)->toBe(Command::FAILURE)
        ->and($output)->toContain('missing_relationship')
        ->and($output)->toContain('address')
        ->and($output)->toContain('stale_relationship')
        ->and($output)->toContain('legacyAddress');
});

it('reports invalid property types with expected and actual types', function (): void {
    Artisan::call('make:expressive', syncArguments(['name' => 'User', '--model' => User::class]));
    $path = syncPath();
    File::put($path, str_replace('public string $name;', 'public ?int $name = null;', File::get($path)));

    $result = Artisan::call('expressive:sync', syncArguments(['name' => 'User', '--model' => User::class]));
    $output = Artisan::output();

    expect($result)->toBe(Command::FAILURE)
        ->and($output)->toContain('invalid_type')
        ->and($output)->toContain('name')
        ->and($output)->toContain('expected: string')
        ->and($output)->toContain('actual: ?int');
});

it('reports unresolved model and expressive mappings clearly', function (): void {
    $missingModel = Artisan::call('expressive:sync', syncArguments(['name' => 'User', '--model' => 'App\Models\Missing']));
    $missingModelOutput = Artisan::output();
    $missingExpressive = Artisan::call('expressive:sync', syncArguments(['name' => 'Missing', '--model' => User::class]));
    $missingExpressiveOutput = Artisan::output();

    expect($missingModel)->toBe(Command::FAILURE)
        ->and($missingModelOutput)->toContain('Model [App\Models\Missing] does not exist')
        ->and($missingExpressive)->toBe(Command::FAILURE)
        ->and($missingExpressiveOutput)->toContain('Expressive [App\SyncExpressive\Missing] does not exist');
});

it('does not mutate files without write mode', function (): void {
    Artisan::call('make:expressive', syncArguments(['name' => 'User', '--model' => User::class]));
    $path = syncPath();
    $contents = str_replace("\n    public ?string \$uuid = null;\n", "\n", File::get($path));
    File::put($path, $contents);

    Artisan::call('expressive:sync', syncArguments(['name' => 'User', '--model' => User::class]));

    expect(File::get($path))->toBe($contents);
});

it('writes safe generated updates when write mode is enabled', function (): void {
    Artisan::call('make:expressive', syncArguments(['name' => 'User', '--model' => User::class]));
    $path = syncPath();
    File::put($path, str_replace("\n    public ?string \$uuid = null;\n", "\n", File::get($path)));

    $result = Artisan::call('expressive:sync', syncArguments(['name' => 'User', '--model' => User::class, '--write' => true]));

    expect($result)->toBe(Command::SUCCESS)
        ->and(File::get($path))->toContain('public ?string $uuid = null;')
        ->and(Artisan::output())->toContain('Expressive updated');
});

it('refuses unsafe writes when user edits are present', function (): void {
    Artisan::call('make:expressive', syncArguments(['name' => 'User', '--model' => User::class]));
    $path = syncPath();
    $contents = str_replace("\n    public ?string \$uuid = null;\n", "\n", File::get($path));
    $contents = preg_replace('/}\s*$/', "\n\n    public function custom(): string\n    {\n        return 'custom';\n    }\n}\n", $contents);
    expect($contents)->toContain('public function custom()');
    File::put($path, $contents);

    $result = Artisan::call('expressive:sync', syncArguments(['name' => 'User', '--model' => User::class, '--write' => true]));

    expect($result)->toBe(Command::FAILURE)
        ->and(File::get($path))->toBe($contents)
        ->and(Artisan::output())->toContain('Unsafe rewrite refused');
});
