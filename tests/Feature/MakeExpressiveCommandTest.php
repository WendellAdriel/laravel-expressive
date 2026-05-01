<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use WendellAdriel\Expressive\Exceptions\UnsupportedGenerationException;
use WendellAdriel\Expressive\Tests\Fixtures\Models\CastModel;
use WendellAdriel\Expressive\Tests\Fixtures\Models\Image;
use WendellAdriel\Expressive\Tests\Fixtures\Models\Post;
use WendellAdriel\Expressive\Tests\Fixtures\Models\Tag;
use WendellAdriel\Expressive\Tests\Fixtures\Models\User;

beforeEach(function (): void {
    File::deleteDirectory(app_path('Expressive'));
    File::deleteDirectory(app_path('Data'));
    File::deleteDirectory(base_path('stubs'));
});

it('generates an expressive class from a model using default config', function (): void {
    Artisan::call('make:expressive', [
        'name' => 'User',
        '--model' => User::class,
    ]);

    $path = app_path('Expressive/User.php');

    expect($path)->toBeFile()
        ->and(File::get($path))->toContain('namespace App\\Expressive;')
        ->and(File::get($path))->toContain('use WendellAdriel\\Expressive\\Tests\\Fixtures\\Models\\User as UserModel;')
        ->and(File::get($path))->toContain('@extends Expressive<UserModel>')
        ->and(File::get($path))->toContain('final class User extends Expressive')
        ->and(File::get($path))->toContain('public ?string $rememberToken = null;')
        ->and(File::get($path))->not->toContain('use WendellAdriel\\Expressive\\Attributes\\Map;')
        ->and(File::get($path))->not->toContain('#[Map(')
        ->and(File::get($path))->toContain('#[Relationship]')
        ->and(File::get($path))->toContain('/** @var Collection<int, Post>|null */')
        ->and(File::get($path))->toContain('/** @var Collection<int, Address>|null */')
        ->and(File::get($path))->toContain('#[Virtual]');
});

it('respects namespace suffix and overwrite behavior', function (): void {
    config()->set('expressive.namespace', 'App\\Data');
    config()->set('expressive.suffix', 'Expressive');

    Artisan::call('make:expressive', [
        'name' => 'User',
        '--model' => User::class,
    ]);

    $path = app_path('Data/UserExpressive.php');

    expect($path)->toBeFile()
        ->and(File::get($path))->toContain('namespace App\\Data;')
        ->and(File::get($path))->toContain('final class UserExpressive extends Expressive')
        ->and(File::get($path))->toContain('/** @var Collection<int, PostExpressive>|null */');

    $result = Artisan::call('make:expressive', [
        'name' => 'User',
        '--model' => User::class,
    ]);

    expect($result)->toBe(1);

    $result = Artisan::call('make:expressive', [
        'name' => 'User',
        '--model' => User::class,
        '--force' => true,
    ]);

    expect($result)->toBe(0);
});

it('generates relationship types documented by Laravel', function (): void {
    Artisan::call('make:expressive', [
        'name' => 'Post',
        '--model' => Post::class,
    ]);
    Artisan::call('make:expressive', [
        'name' => 'Image',
        '--model' => Image::class,
    ]);
    Artisan::call('make:expressive', [
        'name' => 'Tag',
        '--model' => Tag::class,
    ]);

    $post = File::get(app_path('Expressive/Post.php'));
    $image = File::get(app_path('Expressive/Image.php'));
    $tag = File::get(app_path('Expressive/Tag.php'));

    expect($post)->toContain('public ?Image $image = null;')
        ->and($post)->toContain('/** @var Collection<int, Tag>|null */')
        ->and($post)->toContain('public ?User $user = null;')
        ->and($image)->toContain('use Illuminate\Database\Eloquent\Model;')
        ->and($image)->toContain('/** @var Expressive<Model>|null */')
        ->and($image)->toContain('public ?Expressive $imageable = null;')
        ->and($tag)->toContain('/** @var Collection<int, Post>|null */');

    Artisan::call('make:expressive', [
        'name' => 'User',
        '--model' => User::class,
        '--force' => true,
    ]);

    $user = File::get(app_path('Expressive/User.php'));

    expect($user)->toContain('public ?Address $address = null;')
        ->and($user)->toContain('/** @var Collection<int, Post>|null */')
        ->and($user)->toContain('/** @var Collection<int, Address>|null */')
        ->and($user)->toContain('public ?Address $firstPostAddress = null;')
        ->and($user)->toContain('/** @var Collection<int, Comment>|null */')
        ->and($user)->toContain('/** @var Collection<int, Post>|null */');
});

it('uses the published expressive stub when available', function (): void {
    File::ensureDirectoryExists(base_path('stubs'));
    File::put(base_path('stubs/expressive.stub'), <<<'STUB'
<?php

declare(strict_types=1);

namespace {{ namespace }};

{{ imports }}

// Published expressive stub
/**
 * @extends Expressive<{{ model }}>
 */
final class {{ class }} extends Expressive
{
{{ properties }}
}
STUB);

    Artisan::call('make:expressive', [
        'name' => 'User',
        '--model' => User::class,
    ]);

    expect(File::get(app_path('Expressive/User.php')))->toContain('// Published expressive stub');
});

it('generates without relationships when requested', function (): void {
    Artisan::call('make:expressive', [
        'name' => 'User',
        '--model' => User::class,
        '--without-relationships' => true,
    ]);

    $contents = File::get(app_path('Expressive/User.php'));

    expect($contents)->not->toContain('#[Relationship]')
        ->not->toContain('public ?Address $address = null;')
        ->not->toContain('/** @var Collection<int, Post>|null */');
});

it('generates only selected relationships when requested', function (): void {
    Artisan::call('make:expressive', [
        'name' => 'User',
        '--model' => User::class,
        '--relationships' => 'posts,address',
    ]);

    $contents = File::get(app_path('Expressive/User.php'));

    expect($contents)->toContain('public ?Address $address = null;')
        ->toContain('/** @var Collection<int, Post>|null */')
        ->not->toContain('public ?Address $firstPostAddress = null;')
        ->not->toContain('/** @var Collection<int, Comment>|null */');
});

it('fails when selected relationships do not exist', function (): void {
    expect(fn () => Artisan::call('make:expressive', [
        'name' => 'User',
        '--model' => User::class,
        '--relationships' => 'missing',
    ]))->toThrow(UnsupportedGenerationException::class, 'missing');
});

it('generates without attributes when requested', function (): void {
    Artisan::call('make:expressive', [
        'name' => 'User',
        '--model' => User::class,
        '--without-attributes' => true,
    ]);

    $contents = File::get(app_path('Expressive/User.php'));

    expect($contents)->toContain('#[Relationship]')
        ->toContain('public ?Address $address = null;')
        ->not->toContain('public ?int $id = null;')
        ->not->toContain('public string $name;')
        ->not->toContain('public string $email;')
        ->not->toContain('public ?string $displayName = null;');
});

it('generates only selected attributes when requested', function (): void {
    Artisan::call('make:expressive', [
        'name' => 'User',
        '--model' => User::class,
        '--attributes' => 'name,email,display_name',
    ]);

    $contents = File::get(app_path('Expressive/User.php'));

    expect($contents)->toContain('public string $name;')
        ->toContain('public string $email;')
        ->toContain('public ?string $displayName = null;')
        ->toContain('#[Relationship]')
        ->not->toContain('public ?int $id = null;')
        ->not->toContain('public ?string $password = null;')
        ->not->toContain('public ?string $rememberToken = null;');
});

it('fails when selected attributes do not exist', function (): void {
    expect(fn () => Artisan::call('make:expressive', [
        'name' => 'User',
        '--model' => User::class,
        '--attributes' => 'name,missing',
    ]))->toThrow(UnsupportedGenerationException::class, 'missing');
});

it('excludes hidden attributes only when requested', function (): void {
    Artisan::call('make:expressive', [
        'name' => 'User',
        '--model' => User::class,
        '--exclude-hidden' => true,
    ]);

    $contents = File::get(app_path('Expressive/User.php'));

    expect($contents)->not->toContain('public ?string $password = null;')
        ->not->toContain('public ?string $rememberToken = null;');
});

it('keeps hidden attribute generation by default', function (): void {
    Artisan::call('make:expressive', [
        'name' => 'User',
        '--model' => User::class,
    ]);

    $contents = File::get(app_path('Expressive/User.php'));

    expect($contents)->toContain('public ?string $password = null;')
        ->toContain('public ?string $rememberToken = null;');
});

it('prints dry-run output without writing a file or requiring force', function (): void {
    File::ensureDirectoryExists(app_path('Expressive'));
    File::put(app_path('Expressive/User.php'), 'existing');

    $result = Artisan::call('make:expressive', [
        'name' => 'User',
        '--model' => User::class,
        '--dry-run' => true,
    ]);

    expect($result)->toBe(0)
        ->and(Artisan::output())->toContain('final class User extends Expressive')
        ->and(File::get(app_path('Expressive/User.php')))->toBe('existing');
});

it('documents current appended accessor auto-detection by default', function (): void {
    Artisan::call('make:expressive', [
        'name' => 'User',
        '--model' => User::class,
    ]);

    expect(File::get(app_path('Expressive/User.php')))->toContain('public ?string $displayName = null;');
});

it('generates property types for documented casts and falls back to mixed for custom casts', function (): void {
    Artisan::call('make:expressive', [
        'name' => 'CastModel',
        '--model' => CastModel::class,
    ]);

    $contents = File::get(app_path('Expressive/CastModel.php'));

    expect($contents)->toContain('use ArrayObject;')
        ->and($contents)->toContain('use Carbon\CarbonInterface;')
        ->and($contents)->toContain('use Illuminate\Support\Collection;')
        ->and($contents)->toContain('use Illuminate\Support\Fluent;')
        ->and($contents)->toContain('use Illuminate\Support\Stringable;')
        ->and($contents)->toContain('use Illuminate\Support\Uri;')
        ->and($contents)->toContain('use WendellAdriel\Expressive\Tests\Fixtures\Collections\CustomCollection;')
        ->and($contents)->toContain('use WendellAdriel\Expressive\Tests\Fixtures\UserRole;')
        ->and($contents)->toContain('use WendellAdriel\Expressive\Tests\Fixtures\ValueObjects\CastValueObject;')
        ->and($contents)->toContain('public ?array $arrayValue = null;')
        ->and($contents)->toContain('public ?Fluent $fluentValue = null;')
        ->and($contents)->toContain('public ?Stringable $stringableValue = null;')
        ->and($contents)->toContain('public ?Uri $uriValue = null;')
        ->and($contents)->toContain('public ?bool $booleanValue = null;')
        ->and($contents)->toContain('public ?Collection $collectionValue = null;')
        ->and($contents)->toContain('public ?CarbonInterface $dateValue = null;')
        ->and($contents)->toContain('public ?CarbonInterface $datetimeValue = null;')
        ->and($contents)->toContain('public ?CarbonInterface $immutableDateValue = null;')
        ->and($contents)->toContain('public ?CarbonInterface $immutableDatetimeValue = null;')
        ->and($contents)->toContain('public ?string $decimalValue = null;')
        ->and($contents)->toContain('public ?float $doubleValue = null;')
        ->and($contents)->toContain('public ?string $encryptedValue = null;')
        ->and($contents)->toContain('public ?array $encryptedArrayValue = null;')
        ->and($contents)->toContain('public ?Collection $encryptedCollectionValue = null;')
        ->and($contents)->toContain('public ?object $encryptedObjectValue = null;')
        ->and($contents)->toContain('public ?float $floatValue = null;')
        ->and($contents)->toContain('public ?string $hashedValue = null;')
        ->and($contents)->toContain('public ?int $integerValue = null;')
        ->and($contents)->toContain('public ?object $objectValue = null;')
        ->and($contents)->toContain('public ?float $realValue = null;')
        ->and($contents)->toContain('public ?string $stringValue = null;')
        ->and($contents)->toContain('public ?int $timestampValue = null;')
        ->and($contents)->toContain('public ?ArrayObject $arrayObjectValue = null;')
        ->and($contents)->toContain('public ?ArrayObject $encryptedArrayObjectValue = null;')
        ->and($contents)->toContain('/** @var ArrayObject<int, UserRole>|null */')
        ->and($contents)->toContain('public ?ArrayObject $enumArrayObjectValue = null;')
        ->and($contents)->toContain('/** @var Collection<int, UserRole>|null */')
        ->and($contents)->toContain('public ?Collection $enumCollectionValue = null;')
        ->and($contents)->toContain('public ?CustomCollection $customCollectionValue = null;')
        ->and($contents)->toContain('/** @var Collection<int, CastValueObject>|null */')
        ->and($contents)->toContain('public ?Collection $collectionOfValueObjects = null;')
        ->and($contents)->toContain('public ?CustomCollection $encryptedCustomCollectionValue = null;')
        ->and($contents)->toContain('public ?Collection $encryptedCollectionOfValueObjects = null;')
        ->and($contents)->toContain('public ?UserRole $enumValue = null;')
        ->and($contents)->toContain('public mixed $customValue = null;')
        ->and($contents)->toContain('public ?CastValueObject $typedCustomValue = null;')
        ->and($contents)->toContain('public mixed $malformedCustomValue = null;')
        ->and($contents)->toContain('public mixed $castableValue = null;');
});
