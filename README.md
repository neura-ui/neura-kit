# Neura Kit

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Version](https://img.shields.io/badge/version-2.0.0-green.svg)](CHANGELOG.md)

Open-source UI component library for **Laravel**, **Livewire**, and **Alpine.js** — forms, tables, modals, toasts, dark mode, and 60+ Blade components with a consistent design system.

| Resource | URL |
|----------|-----|
| **Documentation** | [docs.neuraui.dev](https://docs.neuraui.dev/) |
| **Website** | [neuraui.dev](https://neuraui.dev/) |
| **Changelog** | [CHANGELOG.md](CHANGELOG.md) |

> **2.0** is fully open source (MIT). No license key, activation, or vendor dashboard required.

---

## Documentation

The README covers a quick start. For installation steps, component props, layouts, theming, and live examples, use the official docs:

**[https://docs.neuraui.dev/](https://docs.neuraui.dev/)**

| Section | What you will find |
|---------|-------------------|
| [Getting started](https://docs.neuraui.dev/getting-started/installation) | Installation, Vite, `@neuraKit`, first components |
| [Usage](https://docs.neuraui.dev/getting-started/usage) | Blade syntax, Livewire patterns |
| [Dark mode & theming](https://docs.neuraui.dev/getting-started/dark-mode) | Theme tokens and customization |
| [Components](https://docs.neuraui.dev/atoms) | Every atom with examples (Button, Table, Modal, Editor, …) |

---

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13
- Livewire 3 or 4
- Tailwind CSS 4+
- Alpine.js 3+

---

## Quick start

### 1. Install the package

```bash
composer require neura-ui/neura-kit:^2.0
```

### 2. Register the Vite plugin

In `vite.config.js`:

```js
import neuraKit from './vendor/neura-ui/neura-kit/resources/js/index.ts';

export default defineConfig({
    plugins: [
        laravel({ input: ['resources/css/app.css', 'resources/js/app.js'] }),
        neuraKit(),
    ],
});
```

Import Neura Kit styles in your main CSS (see [Installation](https://docs.neuraui.dev/getting-started/installation) for the full `app.css` setup).

### 3. Optional PHP dependencies

For Editor.js, charts, kanban, and other optional features:

```bash
php artisan neura-kit:install-deps
```

### 4. Enable managers in your layout

Add `@neuraKit` before `</body>` so modals, sideovers, and spotlight work globally:

```blade
<body>
    {{ $slot }}

    @neuraKit
</body>
```

### 5. Use components

```blade
<neura::button variant="primary">Save</neura::button>

<neura::input type="email" wire:model="email" placeholder="Email" />

<neura::card>
    <neura::heading size="lg">Dashboard</neura::heading>
    <neura::text>Build faster with Neura Kit.</neura::text>
</neura::card>
```

Run `npm run dev` (or `npm run build`) after changing Vite or CSS.

---

## What is included

| Area | Highlights |
|------|------------|
| **Forms** | Input, Select, Checkbox, Radio, Switch, OTP, DatePicker, Dropzone, Editor.js, TagsInput, … |
| **Layout** | Container, Grid, Stack, Card, Sidebar patterns |
| **Feedback** | Toast, Dialog, Modal, Sideover, Alert, Callout |
| **Data** | Table (Livewire), Chart, Kanban, Tree, Flow |
| **UX** | Command palette, Spotlight, dark mode, theme switcher |

Browse the full list with interactive examples on **[docs.neuraui.dev](https://docs.neuraui.dev/)**.

---

## Modals (Livewire)

Create a modal component:

```bash
php artisan neura-kit:make-modal UserEdit
```

```php
use Neura\Kit\Support\Modal\ModalComponent;
use Neura\Kit\Concerns\InteractsWithNeuraKit;

class UserEdit extends ModalComponent
{
    use InteractsWithNeuraKit;

    public User $user;

    public function save(): void
    {
        $this->user->save();
        $this->toast('User saved!')->success();
        $this->closeModal();
    }

    public static function modalMaxWidth(): string
    {
        return 'lg';
    }

    public function render()
    {
        return view('livewire.user-edit');
    }
}
```

Open from another component:

```php
$this->openModal(UserEdit::class, ['user' => $user]);

// or fluent API
$this->modal(UserEdit::class)->with(['user' => $user])->maxWidth('xl')->open();
```

From JavaScript:

```html
<button @click="NeuraKit.modal('user-edit').with({ userId: 1 }).open()">
    Edit
</button>
```

Details: [Modal component docs](https://docs.neuraui.dev/atoms/modal).

---

## Toasts & dialogs

### Toasts (PHP)

```php
$this->toast('Saved!')->success();
$this->toast('Something went wrong')->error();
$this->toast('Processing…')->duration(6000)->info();
```

### Toasts (JavaScript)

```html
<button @click="NeuraKit.toast('Saved!').success()">Save</button>
```

### Confirm dialogs (PHP)

```php
$this->dialog('Delete user?')
    ->danger()
    ->message('This action cannot be undone.')
    ->confirmText('Delete')
    ->onConfirm('deleteUser', $userId)
    ->show();
```

See [Toast](https://docs.neuraui.dev/atoms/toast) and [Dialog](https://docs.neuraui.dev/atoms/dialog) on the docs site.

---

## Dark mode

```html
<button @click="$theme.toggle()">Toggle theme</button>
<button @click="$theme.dark()">Dark</button>
<button @click="$theme.light()">Light</button>
<button @click="$theme.system()">System</button>
```

---

## Configuration

Publish and customize defaults:

```bash
php artisan vendor:publish --tag=neura-kit-config
```

```php
// config/neura-kit.php
return [
    'component_prefix' => 'neura',

    'routes' => [
        // Default `web` works for public pages (e.g. documentation).
        // Use `web,auth` when upload/editor routes must require login.
        'middleware' => explode(',', env('NEURA_KIT_ROUTE_MIDDLEWARE', 'web')),
        'throttle' => env('NEURA_KIT_ROUTE_THROTTLE'),
    ],

    'upload' => [
        'max_size' => (int) env('NEURA_KIT_UPLOAD_MAX_SIZE', 100),
        'allowed_mimes' => env('NEURA_KIT_UPLOAD_ALLOWED_MIMES'),
    ],

    'editor' => [
        'allow_remote_image_download' => (bool) env('NEURA_KIT_EDITOR_ALLOW_REMOTE_IMAGES', false),
    ],
];
```

### Publish assets

```bash
php artisan vendor:publish --tag=neura-kit-config   # config
php artisan vendor:publish --tag=neura-kit-views    # Blade overrides
php artisan vendor:publish --tag=neura-kit-assets   # JS/CSS into resources/
php artisan vendor:publish --tag=neura-lang         # translations
```

---

## PHP & JavaScript helpers

Use the `InteractsWithNeuraKit` trait on Livewire components for `toast()`, `modal()`, `dialog()`, and `openModal()`.

| PHP | JavaScript |
|-----|------------|
| `$this->toast('…')->success()` | `NeuraKit.toast('…').success()` |
| `$this->modal(Class::class)->with([…])->open()` | `NeuraKit.modal('name').with({…}).open()` |
| `$this->dialog('Title')->danger()->show()` | `NeuraKit.dialog('Title').danger().show()` |

---

## Development

```bash
composer install
./vendor/bin/phpunit
```

---

## License

MIT License — see [LICENSE](LICENSE).
