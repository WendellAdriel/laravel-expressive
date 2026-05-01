<?php

declare(strict_types=1);

namespace WendellAdriel\Expressive\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use WendellAdriel\Expressive\ExpressiveServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ExpressiveServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('expressive_users', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique()->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('role')->default('user');
            $table->string('password')->nullable();
            $table->string('remember_token')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('expressive_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('expressive_users')->cascadeOnDelete();
            $table->string('street');
            $table->string('city');
            $table->timestamps();
        });

        Schema::create('expressive_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('expressive_users')->cascadeOnDelete();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('expressive_images', function (Blueprint $table): void {
            $table->id();
            $table->string('url');
            $table->morphs('imageable');
            $table->timestamps();
        });

        Schema::create('expressive_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('expressive_taggables', function (Blueprint $table): void {
            $table->foreignId('tag_id')->constrained('expressive_tags')->cascadeOnDelete();
            $table->morphs('taggable');
        });

        Schema::create('expressive_profiles', function (Blueprint $table): void {
            $table->string('uuid')->primary();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('expressive_cast_models', function (Blueprint $table): void {
            $table->id();
            $table->json('array_value')->nullable();
            $table->json('fluent_value')->nullable();
            $table->string('stringable_value')->nullable();
            $table->string('uri_value')->nullable();
            $table->boolean('boolean_value')->nullable();
            $table->json('collection_value')->nullable();
            $table->date('date_value')->nullable();
            $table->dateTime('datetime_value')->nullable();
            $table->date('immutable_date_value')->nullable();
            $table->dateTime('immutable_datetime_value')->nullable();
            $table->decimal('decimal_value', 8, 2)->nullable();
            $table->double('double_value')->nullable();
            $table->string('encrypted_value')->nullable();
            $table->json('encrypted_array_value')->nullable();
            $table->json('encrypted_collection_value')->nullable();
            $table->json('encrypted_object_value')->nullable();
            $table->float('float_value')->nullable();
            $table->string('hashed_value')->nullable();
            $table->integer('integer_value')->nullable();
            $table->json('object_value')->nullable();
            $table->float('real_value')->nullable();
            $table->string('string_value')->nullable();
            $table->integer('timestamp_value')->nullable();
            $table->json('array_object_value')->nullable();
            $table->string('enum_value')->nullable();
            $table->string('custom_value')->nullable();
            $table->timestamps();
        });
    }
}
