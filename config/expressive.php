<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Expressive Namespace
    |--------------------------------------------------------------------------
    |
    | Here you may define the namespace where Expressive classes are generated for
    | your application and resolved during implicit mapping. This default path
    | matches the standard namespace used by fresh Laravel applications.
    |
    */

    'namespace' => 'App\\Expressive',

    /*
    |--------------------------------------------------------------------------
    | Expressive Class Suffix
    |--------------------------------------------------------------------------
    |
    | Here you may specify the suffix appended to generated Expressive class names
    | and used during implicit lookups. Keep this value empty when your typed
    | objects should use the mapped Eloquent model base name for lookup.
    |
    */

    'suffix' => '',

    /*
    |--------------------------------------------------------------------------
    | Strict Mode
    |--------------------------------------------------------------------------
    |
    | Strict mode prevents Expressive objects from writing application state.
    | Keep this disabled when Expressive should persist converted models
    | and supported relationships through its save method.
    |
    */

    'strict' => false,

    /*
    |--------------------------------------------------------------------------
    | Diagnostics
    |--------------------------------------------------------------------------
    |
    | Keep runtime conversion conservative by default. When enabled, Expressive
    | throws if a non-virtual, non-relationship property maps to an attribute
    | that Eloquent will not fill because it is not fillable.
    |
    */

    'diagnostics' => [
        'throw_on_unfillable' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Serialization
    |--------------------------------------------------------------------------
    |
    | Expressive serializes public property names by default. Applications that
    | prefer Laravel-style response keys may opt in to snake-case output while
    | keeping metadata lookups based on the mapped Eloquent keys. Supported
    | values are: preserve, snake.
    |
    */

    'serialization' => [
        'case' => 'preserve',
    ],

    /*
    |--------------------------------------------------------------------------
    | Generator Defaults
    |--------------------------------------------------------------------------
    |
    | These values are used by make:expressive when the matching CLI option is
    | omitted. Explicit CLI flags always take precedence for a single run.
    |
    */

    'generator' => [
        'with_attributes' => true,
        'with_relationships' => true,
        'exclude_hidden' => false,
        'hint_morph_map' => false,
    ],

];
