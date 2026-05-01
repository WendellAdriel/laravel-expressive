<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use WendellAdriel\Expressive\Concerns\IsExpressive;

final class Profile extends Model
{
    use IsExpressive;

    protected $table = 'expressive_profiles';

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['uuid', 'name'];
}
