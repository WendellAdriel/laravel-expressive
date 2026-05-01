<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

final class Tag extends Model
{
    protected $table = 'expressive_tags';

    protected $guarded = ['id'];

    public function posts(): MorphToMany
    {
        return $this->morphedByMany(Post::class, 'taggable', 'expressive_taggables');
    }
}
