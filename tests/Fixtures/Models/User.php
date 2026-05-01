<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use WendellAdriel\Expressive\Concerns\IsExpressive;
use WendellAdriel\Expressive\Tests\Fixtures\UserRole;

class User extends Model
{
    use IsExpressive;

    protected $table = 'expressive_users';

    protected $fillable = ['name', 'email', 'role', 'password', 'email_verified_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'email_verified_at' => 'datetime',
        ];
    }

    public function address(): HasOne
    {
        return $this->hasOne(Address::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function unsupportedPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'expressive_post_user');
    }

    public function postAddresses(): HasManyThrough
    {
        return $this->hasManyThrough(Address::class, Post::class, 'user_id', 'user_id', 'id', 'user_id');
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => "{$this->name} ({$this->role->value})",
        );
    }
}
