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

    'icon-stack' => Config::iconStack(),

    'icon' => Config::icon(),

    'orb' => Config::orb(),

    'preloader' => Config::preloader(),

    'contribution-graph' => Config::contributionGraph(),

    'input' => Config::input(),

    'filters' => Config::filters(),

    'textarea' => Config::input(),

    'select' => Config::input(),

    'otp' => Config::input(),

    'autocomplete' => Config::input(),

    'rich-editor' => Config::input(),

    'dropzone' => Config::dropzone(),

    'file-manager' => Config::fileManager(),

    'fieldset' => Config::fieldset(),

    'popup' => Config::popup(),

    'modal' => Config::modal(),

    'sideover' => Config::sideover(),

    'card' => Config::card(),

    'dropdown' => Config::dropdown(),

    'context-menu' => Config::dropdown(),

    'command' => Config::command(),

    'spotlight' => Config::spotlight(),

    'composer' => Config::composer(),

    'checkbox' => Config::checkbox(),

    'radio' => Config::radio(),

    'toggle' => Config::toggle(),

    'navlist' => Config::navlist(),

    'navbar' => Config::navbar(),

    'tabs' => Config::tabs(),

    'link' => Config::link(),

    'callout' => Config::callout(),

    'dialog' => Config::dialog(),

    'toast' => Config::toast(),

    'empty-state' => Config::emptyState(),

    'progress' => Config::progress(),

    'skeleton' => Config::skeleton(),

    'lottie' => Config::lottie(),

    'chart' => Config::chart(),

    'tree' => Config::tree(),

    'flow' => Config::flow(),

    'kanban' => Config::kanban(),

    'event-calendar' => Config::eventCalendar(),

    'image-gallery' => Config::imageGallery(),

    'popover' => Config::popup(),

    'table' => Config::table(),

    'wizard' => Config::wizard(),

    /*
    |--------------------------------------------------------------------------
    | HTTP Routes (uploads, editor)
    |--------------------------------------------------------------------------
    |
    | Middleware applied to Neura Kit utility routes. Defaults require an
    | authenticated session. Override in your app if a route must stay public.
    |
    | Default is web-only (CSRF + session) so demos and docs work without login.
    | For production back office, set: NEURA_KIT_ROUTE_MIDDLEWARE=web,auth
    |
    | Example: NEURA_KIT_ROUTE_MIDDLEWARE=web,auth,throttle:uploads
    |
    */

    'routes' => [
        'middleware' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('NEURA_KIT_ROUTE_MIDDLEWARE', 'web'))
        ))),
        'throttle' => env('NEURA_KIT_ROUTE_THROTTLE', '60,1'),
    ],

    'upload' => [
        'max_size' => env('NEURA_KIT_UPLOAD_MAX_SIZE', 100), // MB
        'chunk_size' => env('NEURA_KIT_UPLOAD_CHUNK_SIZE', 1), // MB
        'disk' => env('LIVEWIRE_DISK', 'local'),
        /*
         * Server-side MIME allowlist for assembled chunk uploads.
         * null = no MIME enforcement (client accept only).
         * Example: image/jpeg,image/png,application/pdf
         */
        'allowed_mimes' => env('NEURA_KIT_UPLOAD_ALLOWED_MIMES')
            ? array_values(array_filter(array_map('trim', explode(',', (string) env('NEURA_KIT_UPLOAD_ALLOWED_MIMES')))))
            : null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Editor Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for rich text editors (Tiptap, Editor.js)
    |
    */

    'editor' => [
        'default_variant' => env('NEURA_KIT_EDITOR_VARIANT', 'tiptap'), // 'tiptap' or 'editorjs'
        'max_image_size' => env('NEURA_KIT_EDITOR_MAX_IMAGE_SIZE', 10240), // KB (10MB default)
        'image_disk' => env('NEURA_KIT_EDITOR_IMAGE_DISK', 'public'), // 'public', 'local', 's3', etc.
        'image_path' => env('NEURA_KIT_EDITOR_IMAGE_PATH', 'editor/images'),
        /*
         * Allow Editor.js Livewire to download remote images (SSRF risk if enabled).
         * Prefer uploading files via the authenticated upload endpoint instead.
         */
        'allow_remote_image_download' => (bool) env('NEURA_KIT_EDITOR_ALLOW_REMOTE_IMAGES', false),
        'remote_image_max_bytes' => (int) env('NEURA_KIT_EDITOR_REMOTE_IMAGE_MAX_BYTES', 10_485_760), // 10 MB
    ],
];
