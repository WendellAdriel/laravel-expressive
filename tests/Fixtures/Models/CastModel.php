<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsFluent;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Casts\AsUri;
use Illuminate\Database\Eloquent\Model;
use WendellAdriel\Expressive\Tests\Fixtures\Casts\CustomCast;
use WendellAdriel\Expressive\Tests\Fixtures\UserRole;

final class CastModel extends Model
{
    protected $table = 'expressive_cast_models';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'array_value' => 'array',
            'fluent_value' => AsFluent::class,
            'stringable_value' => AsStringable::class,
            'uri_value' => AsUri::class,
            'boolean_value' => 'boolean',
            'collection_value' => 'collection',
            'date_value' => 'date:Y-m-d',
            'datetime_value' => 'datetime:Y-m-d H:i:s',
            'immutable_date_value' => 'immutable_date',
            'immutable_datetime_value' => 'immutable_datetime',
            'decimal_value' => 'decimal:2',
            'double_value' => 'double',
            'encrypted_value' => 'encrypted',
            'encrypted_array_value' => 'encrypted:array',
            'encrypted_collection_value' => 'encrypted:collection',
            'encrypted_object_value' => 'encrypted:object',
            'float_value' => 'float',
            'hashed_value' => 'hashed',
            'integer_value' => 'integer',
            'object_value' => 'object',
            'real_value' => 'real',
            'string_value' => 'string',
            'timestamp_value' => 'timestamp',
            'array_object_value' => AsArrayObject::class,
            'enum_value' => UserRole::class,
            'custom_value' => CustomCast::class,
        ];
    }
}
