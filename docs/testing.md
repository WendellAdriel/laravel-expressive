# Testing

Run the full Expressive validation suite with Composer:

```bash
composer test
```

During development, you can run individual checks:

```bash
composer analyse
composer lint:check
composer test:types
composer test:unit
```

Use the bundled workbench when expressive needs to be exercised inside a real Laravel application:

```bash
composer build
composer serve
```

## Adoption Assertions

When adding Expressive to an existing model, test the observable round trip instead of package internals:

```php
it('converts users to expressive objects', function (): void {
    $user = User::factory()->create();

    expect($user->expressive())
        ->toBeInstanceOf(App\Expressive\User::class)
        ->and($user->expressive()->model())
        ->toBeInstanceOf(User::class);
});
```

Test relationship conversion explicitly when a typed object exposes relationships:

```php
it('converts requested relationships', function (): void {
    $user = User::factory()->hasPosts(2)->create();

    $expressive = $user->expressive(relationships: ['posts']);

    expect($expressive->posts)
        ->toHaveCount(2)
        ->toContainOnlyInstancesOf(App\Expressive\Post::class);
});
```

Collection and builder macros can be tested by asserting the returned collection items, and large datasets can use `expressiveChunk()`:

```php
User::query()->expressiveChunk(100, function ($users): void {
    expect($users)->toContainOnlyInstancesOf(App\Expressive\User::class);
});
```

For save behavior, assert the database writes you expect and keep many-to-many relations explicit in application code:

```php
$saved = (new App\Expressive\User([
    'name' => 'Wendell',
    'email' => 'wendell@example.com',
]))->save();

expect($saved->exists)->toBeTrue();
```

## Serialization Assertions

Assert the public boundary you return from your application, including hidden fields and configured key casing:

```php
config(['expressive.serialization.case' => 'snake']);

expect($user->toArray())
    ->toHaveKey('email_verified_at')
    ->not->toHaveKey('remember_key');

expect($user->toJson())->toBe(json_encode($user->toArray(), JSON_THROW_ON_ERROR));
```

## Drift Assertions

Use `expressive:sync` in adoption tests or CI checks when generated Expressive classes should track Eloquent model shape:

```php
$this->artisan('expressive:sync User --model="App\Models\User"')
    ->assertSuccessful();
```

Run `expressive:sync --write` only when you expect a safely generated class to be updated. If the command refuses a rewrite, update the class manually or regenerate it after reviewing user edits.
