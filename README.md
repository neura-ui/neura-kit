# Neura Kit

Open-source UI component library for Laravel, Livewire, and Alpine.js.

## Requirements

- PHP 8.2+
- Laravel 11+
- Livewire 3+
- Tailwind CSS 4+
- Alpine.js 3+

## Installation

```bash
composer require neura-ui/neura-kit
```

### Vite Configuration

Add the plugin to your `vite.config.js`:

```js
import neuraKit from './vendor/neura-ui/neura-kit/resources/js/index.ts';

export default defineConfig({
    plugins: [
        laravel({ input: ['resources/css/app.css', 'resources/js/app.js'] }),
        neuraKit(),
    ],
});
```

### Install Optional Dependencies

```bash
php artisan neura-kit:install-deps
```

### Add Managers Directive

Add `@neuraKit` to your layout before `</body>`:

```blade
<body>
    {{ $slot }}
    
    @neuraKit
</body>
```

## Components

### 60+ UI Components

| Category | Components |
|----------|------------|
| **Forms** | Input, Textarea, Select, Checkbox, Radio, Switch, OTP, DatePicker, TagsInput, Dropzone, Editor |
| **Layout** | Container, Grid, Stack, Box, Card, Section, Sidebar, Navbar |
| **Feedback** | Toast, Dialog, Modal, Alert, Callout, Skeleton |
| **Navigation** | Tabs, Breadcrumbs, Dropdown, Command, ContextMenu, Navlist |
| **Data** | Table, Calendar, Kanban, Chart, ImageGallery |
| **Display** | Avatar, Badge, Icon, Lottie, Accordion, Popover |

### Usage

```blade
<neura::button>Click me</neura::button>

<neura::input type="email" wire:model="email" placeholder="Email" />

<neura::card>
    <neura::heading>Title</neura::heading>
    <neura::text>Content here</neura::text>
</neura::card>
```

## Modals

### Creating a Modal

```bash
php artisan neura-kit:make-modal UserEdit
```

### Modal Component

```php
use Neura\Kit\Support\Modal\ModalComponent;

class UserEdit extends ModalComponent
{
    public User $user;
    
    public function mount(User $user)
    {
        $this->user = $user;
    }
    
    public function save()
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

### Opening Modals

```php
use Neura\Kit\Concerns\InteractsWithNeuraKit;

class UsersIndex extends Component
{
    use InteractsWithNeuraKit;
    
    public function editUser(User $user)
    {
        // Simple
        $this->openModal(UserEdit::class, ['user' => $user]);
        
        // Fluent
        $this->modal(UserEdit::class)
            ->with(['user' => $user])
            ->maxWidth('xl')
            ->open();
    }
}
```

### JavaScript

```html
<button @click="NeuraKit.modal('user-edit').with({userId: 1}).open()">
    Edit User
</button>
```

## Toasts

### PHP (Livewire)

```php
use Neura\Kit\Concerns\InteractsWithNeuraKit;

class MyComponent extends Component
{
    use InteractsWithNeuraKit;
    
    public function save()
    {
        // Simple
        $this->toast('Saved!')->success();
        
        // With duration
        $this->toast('Processing...')->duration(6000)->info();
        
        // Types: success, error, warning, info
        $this->toast('Error occurred')->error();
    }
}
```

### JavaScript

```html
<button @click="NeuraKit.toast('Saved!').success()">Save</button>
<button @click="NeuraKit.toast('Error').duration(6000).error()">Error</button>
```

## Dialogs

### PHP (Livewire)

```php
public function confirmDelete(int $userId)
{
    $this->dialog('Delete user?')
        ->danger()
        ->message('This action cannot be undone.')
        ->confirmText('Delete')
        ->onConfirm('deleteUser', $userId)
        ->show();
}

public function deleteUser(int $userId)
{
    User::destroy($userId);
    $this->toast('User deleted')->success();
}
```

### JavaScript

```html
<button @click="
    NeuraKit.dialog('Delete user?')
        .danger()
        .message('This cannot be undone.')
        .confirmText('Delete')
        .onConfirm(() => $wire.deleteUser(userId))
        .show()
">
    Delete
</button>
```

## Theme

### Dark Mode

```html
<!-- Toggle -->
<button @click="$theme.toggle()">Toggle Theme</button>

<!-- Set specific -->
<button @click="$theme.light()">Light</button>
<button @click="$theme.dark()">Dark</button>
<button @click="$theme.system()">System</button>

<!-- Check state -->
<span x-show="$theme.isDark">Dark mode</span>
<span x-show="$theme.isLight">Light mode</span>
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=neura-kit-config
```

```php
// config/neura-kit.php
return [
    'component_prefix' => 'neura',

    'modal' => [
        'component_defaults' => [
            'modal_max_width' => 'lg',
            'close_modal_on_click_away' => true,
            'close_modal_on_escape' => true,
            'close_modal_on_escape_is_forceful' => true,
            'dispatch_close_event' => false,
            'destroy_on_close' => false,
        ],
    ],
];
```

## Publishing Assets

```bash
# Config
php artisan vendor:publish --tag=neura-kit-config

# Views (for customization)
php artisan vendor:publish --tag=neura-kit-views

# JS & CSS
php artisan vendor:publish --tag=neura-kit-assets
```

## API Reference

### InteractsWithNeuraKit Trait

| Method | Description |
|--------|-------------|
| `toast(?string $content)` | Create toast builder |
| `modal(?string $component)` | Create modal builder |
| `dialog(?string $title)` | Create dialog builder |
| `openModal(string $component, array $args, array $attrs)` | Open modal directly |
| `closeModal(bool $force)` | Close current modal |

### Toast Builder

| Method | Description |
|--------|-------------|
| `duration(int $ms)` | Set duration in milliseconds |
| `success(?string $content)` | Show success toast |
| `error(?string $content)` | Show error toast |
| `warning(?string $content)` | Show warning toast |
| `info(?string $content)` | Show info toast |

### Modal Builder

| Method | Description |
|--------|-------------|
| `with(array $args)` | Pass arguments to modal |
| `attrs(array $attrs)` | Set modal attributes |
| `maxWidth(string $width)` | Set max width (sm, md, lg, xl, 2xl...) |
| `open(?array $args)` | Open the modal |
| `close(bool $force)` | Close current modal |

### Dialog Builder

| Method | Description |
|--------|-------------|
| `title(string $title)` | Set dialog title |
| `message(string $message)` | Set dialog message |
| `info()` / `success()` / `warning()` / `danger()` | Set dialog type |
| `confirmText(string $text)` | Set confirm button text |
| `cancelText(string $text)` | Set cancel button text |
| `hideCancel()` | Hide cancel button |
| `size(string $size)` | Set dialog size |
| `onConfirm(string $method, ...$params)` | Set confirm callback |
| `onCancel(string $method, ...$params)` | Set cancel callback |
| `show()` | Show the dialog |

### NeuraKit JavaScript API

```js
// Toast
NeuraKit.toast('Message').success()
NeuraKit.toast('Message').duration(5000).error()

// Modal
NeuraKit.modal('component-name').with({id: 1}).open()
NeuraKit.modal().close()

// Dialog
NeuraKit.dialog('Title').danger().message('...').onConfirm(() => {}).show()
```

## License

MIT License. See [LICENSE](LICENSE) for details.

