<?php

declare(strict_types=1);

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\ParallelTesting;
use WendellAdriel\Expressive\Tests\Fixtures\Models\User;

beforeEach(function (): void {
    File::deleteDirectory(app_path(syncDirectory()));
    File::deleteDirectory(app_path('Models/'.syncDirectory()));
    File::deleteDirectory(base_path('stubs'));
});

function syncArguments(array $arguments = []): array
{
    return array_merge(['--namespace' => syncNamespace()], $arguments);
}

function syncPath(string $class = 'User'): string
{
    return app_path(syncDirectory()."/{$class}.php");
}

function syncDirectory(): string
{
    return 'SyncExpressive'.syncToken();
}

function syncNamespace(): string
{
    return 'App\\'.syncDirectory();
}

function syncToken(): string
{
    $token = ParallelTesting::token();

    return $token === false ? '' : (string) $token;
}

function createSyncModel(string $class = 'User', string $extends = User::class): void
{
    $namespace = 'App\\Models\\'.syncDirectory();
    $path = app_path('Models/'.syncDirectory()."/{$class}.php");

    File::ensureDirectoryExists(dirname($path));
    File::put($path, <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

final class {$class} extends \\{$extends}
{
}
PHP);

    require_once $path;
}

function syncModelClass(string $class = 'User'): string
{
    return 'App\\Models\\'.syncDirectory().'\\'.$class;
}

function addSyncModelAttribute(string $class = 'User'): void
{
    $path = syncPath($class);
    $contents = File::get($path);
    $contents = str_replace(
        'use WendellAdriel\Expressive\Expressive;',
        'use WendellAdriel\Expressive\Attributes\Model;'."\n".'use WendellAdriel\Expressive\Expressive;',
        $contents,
    );
    $contents = str_replace(
        "final class {$class} extends Expressive",
        '#[Model(\\'.syncModelClass($class)."::class)]\nfinal class {$class} extends Expressive",
        $contents,
    );

    File::put($path, $contents);
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
        ->and($output)->toContain(syncNamespace().'\User')
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
        ->and($missingExpressiveOutput)->toContain('Expressive ['.syncNamespace().'\Missing] does not exist');
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

it('checks all discovered expressive classes and reports an in-sync summary', function (): void {
    createSyncModel();

    Artisan::call('make:expressive', syncArguments(['name' => 'User', '--model' => syncModelClass()]));
    addSyncModelAttribute();

    $result = Artisan::call('expressive:sync', syncArguments(['--all' => true]));
    $output = Artisan::output();

    expect($result)->toBe(Command::SUCCESS)
        ->and($output)->toContain('Checked: 1')
        ->and($output)->toContain('In sync: 1');
});

it('fails sync all when any discovered expressive class has drift', function (): void {
    createSyncModel();

    Artisan::call('make:expressive', syncArguments(['name' => 'User', '--model' => syncModelClass()]));
    addSyncModelAttribute();
    File::put(syncPath(), str_replace("\n    public ?string \$uuid = null;\n", "\n", File::get(syncPath())));

    $result = Artisan::call('expressive:sync', syncArguments(['--all' => true]));
    $output = Artisan::output();

    expect($result)->toBe(Command::FAILURE)
        ->and($output)->toContain('missing_attribute')
        ->and($output)->toContain(syncModelClass())
        ->and($output)->toContain(syncNamespace().'\User')
        ->and($output)->toContain('Drifted: 1');
});

it('safely writes generated classes during sync all', function (): void {
    createSyncModel();

    Artisan::call('make:expressive', syncArguments(['name' => 'User', '--model' => syncModelClass()]));
    addSyncModelAttribute();
    File::put(syncPath(), str_replace("\n    public ?string \$uuid = null;\n", "\n", File::get(syncPath())));

    $result = Artisan::call('expressive:sync', syncArguments(['--all' => true, '--write' => true]));

    expect($result)->toBe(Command::SUCCESS)
        ->and(File::get(syncPath()))->toContain('public ?string $uuid = null;')
        ->and(Artisan::output())->toContain('Updated: 1');
});

it('rejects conflicting single target options during sync all', function (): void {
    $withModel = Artisan::call('expressive:sync', syncArguments(['--all' => true, '--model' => User::class]));
    $modelOutput = Artisan::output();
    $withName = Artisan::call('expressive:sync', syncArguments(['name' => 'User', '--all' => true]));
    $nameOutput = Artisan::output();

    expect($withModel)->toBe(Command::FAILURE)
        ->and($modelOutput)->toContain('The --model option cannot be used with --all.')
        ->and($withName)->toBe(Command::FAILURE)
        ->and($nameOutput)->toContain('The name argument cannot be used with --all.');
});
