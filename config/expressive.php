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

];
