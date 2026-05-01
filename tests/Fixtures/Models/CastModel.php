<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEncryptedCollection;
use Illuminate\Database\Eloquent\Casts\AsEnumArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Casts\AsFluent;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Casts\AsUri;
use Illuminate\Database\Eloquent\Model;
use WendellAdriel\Expressive\Tests\Fixtures\Casts\CustomCast;
use WendellAdriel\Expressive\Tests\Fixtures\Casts\MalformedCustomCast;
use WendellAdriel\Expressive\Tests\Fixtures\Casts\TypedCustomCast;
use WendellAdriel\Expressive\Tests\Fixtures\Collections\CustomCollection;
use WendellAdriel\Expressive\Tests\Fixtures\UserRole;
use WendellAdriel\Expressive\Tests\Fixtures\ValueObjects\CastValueObject;

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
            'encrypted_array_object_value' => AsEncryptedArrayObject::class,
            'enum_array_object_value' => AsEnumArrayObject::of(UserRole::class),
            'enum_collection_value' => AsEnumCollection::of(UserRole::class),
            'custom_collection_value' => AsCollection::using(CustomCollection::class),
            'collection_of_value_objects' => AsCollection::of(CastValueObject::class),
            'encrypted_custom_collection_value' => AsEncryptedCollection::using(CustomCollection::class),
            'encrypted_collection_of_value_objects' => AsEncryptedCollection::of(CastValueObject::class),
            'enum_value' => UserRole::class,
            'custom_value' => CustomCast::class,
            'typed_custom_value' => TypedCustomCast::class,
            'malformed_custom_value' => MalformedCustomCast::class,
            'castable_value' => CastValueObject::class,
        ];
    }
}
