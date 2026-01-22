<?php

use Neura\Kit\Enum\Packs;
use Neura\Kit\NeuraKitConfig as Config;

return [

    /*
    |--------------------------------------------------------------------------
    | Component Prefix
    |--------------------------------------------------------------------------
    |
    | This option controls the prefix for NeuraKit components.
    | By default, components are accessed via x-neura:component-name
    |
    */

    'component_prefix' => 'neura',

    /*
    |--------------------------------------------------------------------------
    | Global Styles
    |--------------------------------------------------------------------------
    |
    | This option controls the global styles for NeuraKit components.
    | These values will be used as defaults when 'global' is specified.
    |
    */

    'style' => [
        'shadow' => Packs\Shadow::SM,
        'rounded' => Packs\Rounded::LG,
        'color' => Packs\Color::PRIMARY,
    ],

    /*
    |--------------------------------------------------------------------------
    | Component Configuration
    |--------------------------------------------------------------------------
    |
    | Configure defaults and packs for each component type.
    | You can override default values and swap pack classes.
    |
    */

    'button' => Config::button(),

    'badge' => Config::badge(),

    'alert' => Config::alert(),

    'avatar' => Config::avatar(),

    'input' => Config::input(),

    'textarea' => Config::input(),

    'select' => Config::input(),

    'modal' => Config::modal(),

    'card' => Config::card(),

    'dropdown' => Config::dropdown(),

    'checkbox' => Config::checkbox(),

    'radio' => Config::radio(),

    'toggle' => Config::toggle(),

    /*
    |--------------------------------------------------------------------------
    | License API Configuration
    |--------------------------------------------------------------------------
    |
    | This option controls the licensing API endpoint for Neura Kit.
    | The license key should be set via NEURA_KIT_LICENSE_KEY environment variable.
    |
    */

    'license_api_url' => env('NEURA_KIT_LICENSE_API_URL', 'https://api.neuraui.dev'),

    /*
    |--------------------------------------------------------------------------
    | License Domains
    |--------------------------------------------------------------------------
    |
    | Additional domains to register with your license. These will be sent
    | during activation. Use this to register multiple domains for your project.
    |
    | Can be set via NEURA_KIT_DOMAINS environment variable as comma-separated:
    | NEURA_KIT_DOMAINS="myapp.com,www.myapp.com,api.myapp.com"
    |
    */

    'license_domains' => env('NEURA_KIT_DOMAINS', []),

    /*
    |--------------------------------------------------------------------------
    | Chunk Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for chunk-based file uploads
    |
    */

    'upload' => [
        'max_size' => env('NEURA_KIT_UPLOAD_MAX_SIZE', 100), // MB
        'chunk_size' => env('NEURA_KIT_UPLOAD_CHUNK_SIZE', 1), // MB
        'disk' => env('LIVEWIRE_DISK', 'local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment Detection
    |--------------------------------------------------------------------------
    |
    | Override automatic environment detection. By default, the environment
    | is detected from APP_ENV and domain patterns.
    |
    | Possible values: 'auto', 'local', 'staging', 'production'
    |
    */

    'license_environment' => env('NEURA_KIT_ENVIRONMENT', 'auto'),
];
