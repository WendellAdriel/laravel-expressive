<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use WendellAdriel\Expressive\Concerns\IsExpressive;

final class Comment extends Model
{
    use IsExpressive;

    protected $table = 'expressive_comments';

    protected $guarded = ['id'];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }
}
