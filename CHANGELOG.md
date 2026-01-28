# Changelog


All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.41] - 2026-01-27

### Improved
- **Dropdown Submenu**: Complete redesign for better UX
  - Added chevron arrow icon to indicate submenu presence
  - Added open/close delay timers to prevent accidental closing
  - Improved hover behavior with `keepOpen()` function
  - Added `icon` and `iconVariant` props to submenu trigger
  - Added `position` prop (default: `right-start`) for flexible positioning
  - Added slide animation (`translate-x`) instead of scale
  - Better accessibility with `aria-haspopup` and `aria-expanded`
  - Added backdrop blur effect
  - Higher z-index (60) to ensure submenu appears above parent

- **Dropdown Menu**: Custom class merging now works properly
  - Menu slot now uses `$menu->attributes->merge()` for proper class merging
  - Custom classes passed to `<x-slot:menu class="...">` are now correctly applied
  - Added `shadow-lg` for better visual depth
  - Added `role="menu"` for accessibility

### Example Usage
```blade
<neura::dropdown>
    <x-slot:button>Open</x-slot:button>
    <x-slot:menu class="w-64 bg-neutral-50 dark:bg-neutral-800">
        <neura::dropdown.item>Item 1</neura::dropdown.item>
        <neura::dropdown.submenu label="More Options" icon="cog-6-tooth">
            <neura::dropdown.item>Sub Item 1</neura::dropdown.item>
            <neura::dropdown.item>Sub Item 2</neura::dropdown.item>
        </neura::dropdown.submenu>
    </x-slot:menu>
</neura::dropdown>
```

## [1.0.40] - 2026-01-27

### Fixed
- **Clipboard**: Fixed copy not working on Mac/Safari
  - Improved fallback mechanism when Clipboard API fails (common on Safari without user gesture)
  - Added iOS/iPad detection for proper selection handling
  - Textarea element now properly styled for Safari compatibility
  - Added `contentEditable` and `readOnly` attributes required for iOS
  - Removed console.log statements for cleaner production output

## [1.0.39] - 2026-01-27

### Fixed
- **Badge Component**: Fixed Tailwind colors not working (red, orange, amber, yellow, lime, green, etc.)
  - Replaced dynamic template strings `bg-${color}-500` with explicit class names
  - All 17 Tailwind colors now work correctly with all variants (solid, outline, soft)
  - Classes are properly detected by Tailwind CSS v4 during build
  - Colors: red, orange, amber, yellow, lime, green, emerald, teal, cyan, sky, blue, indigo, violet, purple, fuchsia, pink, rose

## [1.0.38] - 2026-01-27

### Fixed
- **Separator Component**: Fixed class merging not working properly
  - Changed from `$attributes->class()` to `$attributes->merge(['class' => ...])` 
  - Inner line divs now properly merge custom classes using `$attributes->only('class')`
  - Custom classes like `bg-neutral-100 dark:bg-neutral-950` now work correctly
  - Applies to both labeled and unlabeled separators, horizontal and vertical orientations

## [1.0.37] - 2026-01-27

### Fixed
- **Phone Input**: Enhanced fix for dial code duplication during typing
  - Added `skipSync` parameter to `parseFullNumber()` to prevent re-sync loops
  - When parsing from Livewire watcher, `_lastSyncedValue` is now updated immediately
  - Prevents the dial code from being duplicated with each keystroke (e.g., `3 33 37 51 303017`)
  - User can now type normally without dial code interference

## [1.0.36] - 2026-01-27

### Fixed
- **Phone Input**: Complete fix for dial code duplication bug
  - Replaced `_syncing` flag with `_lastSyncedValue` tracking for more reliable loop prevention
  - Watcher now compares cleaned digit values instead of full strings
  - `syncToWire()` only sends updates when value actually changed
  - Prevents the dial code (e.g., `+33`) from being repeatedly added to the number

## [1.0.35] - 2026-01-27

### Fixed
- **License Service**: Fixed excessive "Token refresh failed" log spam
  - Added 5-minute cooldown mechanism after failed refresh attempts
  - Changed log level from WARNING to DEBUG for repeated failures
  - Added `getCooldownRemaining()` and `resetCooldown()` methods for manual control
  - Prevents spamming the license server and log files

## [1.0.34] - 2026-01-26

### Fixed
- **Phone Input**: Fixed dial code duplication bug causing infinite sync loop with Livewire
  - Added `_syncing` flag to prevent recursive synchronization between component and Livewire
  - Dial code (e.g., `+33` for France) no longer gets duplicated multiple times
  - Watcher now skips updates when the component itself triggered the change

## [1.0.33] - 2026-01-26

### Fixed
- **Tree Component**: Fixed `window is not defined` error during Vite build
  - Tree component was accessing `window` at module level, causing build failure in Node.js environment
  - Wrapped browser-only code in `if (typeof window !== 'undefined')` check
  - Build now works correctly when NeuraKit plugin is imported in `vite.config.js`

## [1.0.32] - 2026-01-25

### Fixed
- **Tree Component**: Fixed `neuraTree is not defined` error when using tree component in modals
  - Moved Alpine component registration from inline script to global TypeScript file
  - Tree component now loads globally and works with dynamically loaded content (modals, Livewire)

### Changed
- **Tree Component**: Improved TypeScript types for tree component

## [1.0.31] - 2026-01-25

### Fixed
- **ModalManager**: Fixed `closeModal()` not properly going back to previous modal in stack
  - Modal components are now destroyed by default when closed (`destroyOnClose` defaults to `true`)
  - Added `closeModal` to ModalManager listeners to receive events from modal components
  - Fixed logic to correctly activate the previous modal when closing current one

### Added
- **ModalComponent**: Added `goBack()` method for clearer semantics when navigating modal stack
  - `$this->goBack()` - Closes current modal and returns to previous one
  - Forces destruction to ensure clean state transition
- **JavaScript**: Added `NeuraKitModal.goBack()` method
  - Can be called from anywhere: `NeuraKitModal.goBack()`

### Usage Example
```php
// In your modal component
class EditUserModal extends ModalComponent
{
    public function save()
    {
        // Save logic...
        
        // Go back to the previous modal
        $this->goBack();
    }
    
    public function cancel()
    {
        // Simply go back without saving
        $this->goBack();
    }
}
```

```javascript
// From JavaScript
NeuraKitModal.goBack();
```

## [1.0.30] - 2026-01-25

### Changed
- **Structure**: Improved organization of dropzone-related classes
  - Moved `DropzoneFiles` from `src/Support/` to `src/Support/Dropzone/`
  - Updated namespace from `Neura\Kit\Support` to `Neura\Kit\Support\Dropzone`
  - Better organization following the same pattern as other features (Modal, Table, Toast, etc.)
  - **Breaking**: Update imports from `use Neura\Kit\Support\DropzoneFiles;` to `use Neura\Kit\Support\Dropzone\DropzoneFiles;`

## [1.0.29] - 2026-01-25

### Changed
- **Structure**: Moved `WithDropzone` trait from `src/Traits/` to `src/Concerns/` to follow project conventions
  - Updated namespace from `Neura\Kit\Traits` to `Neura\Kit\Concerns`
  - All traits are now consistently located in the `Concerns` folder
  - **Breaking**: Update imports from `use Neura\Kit\Traits\WithDropzone;` to `use Neura\Kit\Concerns\WithDropzone;`

### Added
- **WithDropzone Trait**: New trait for Livewire components to simplify dropzone file handling
  - `getDropzoneFiles($property)` - Get files as TemporaryUploadedFile collection
  - `getDropzoneFile($property)` - Get single file as TemporaryUploadedFile
  - `storeDropzoneFiles($property, $path, $disk)` - Store all files directly
  - `storeDropzoneFile($property, $path, $disk)` - Store single file directly
  - `clearDropzone($property)` - Clear dropzone after processing

- **DropzoneFiles Collection**: New collection class extending Laravel Collection
  - `DropzoneFiles::from($data)` - Create from dropzone data
  - `storeAll($path, $disk)` - Store all files and return paths
  - `storeAllAs($path, $disk)` - Store all files with original names

- **ChunkedTemporaryFile**: Improved helper methods
  - `fromDropzone($data)` - Create from single dropzone data array
  - `fromDropzoneMultiple($dataArray)` - Create from multiple dropzone data arrays
  - Now accepts both UUID strings and dropzone data arrays

### Usage Example
```php
use Neura\Kit\Concerns\WithDropzone;

class DocumentUploader extends Component
{
    use WithDropzone;
    
    public $documents = [];
    
    public function save()
    {
        // Store all files directly
        $paths = $this->storeDropzoneFiles('documents', 'uploads');
        
        // Or get files for manual processing
        $files = $this->getDropzoneFiles('documents');
        foreach ($files as $file) {
            $file->store('documents');
        }
        
        $this->clearDropzone('documents');
    }
}
```

## [1.0.28] - 2026-01-25

### Fixed
- **Dropzone Component**: Fixed Livewire validation error state not displaying correctly
  - Now uses hybrid Blade + Alpine approach for reliable error state detection
  - Blade `@class` directive applies error styles during server-side render
  - Alpine `:class` handles dynamic states (dragging, client-side errors)
  - Error state (red border, background, icon) now shows immediately after Livewire validation
  - Works correctly with `wire:model` and array validation (`documents.*`)

### Changed
- **Dropzone Component**: Improved error state styling architecture
  - Static error classes from Blade using `$errors->has()` for server-side detection
  - Dynamic error classes from Alpine for client-side state management
  - Uses CSS `!important` modifiers to ensure Alpine classes override Blade classes when needed

### Added
- **Translations**: Added French translations for dropzone component
  - `dropFilesHere`: "Déposez les fichiers ici"
  - `uploading`: "Téléversement..."
  - `complete`: "Terminé"
  - `failed`: "Échoué"
  - `pending`: "En attente"
  - `invalidFileType`: "Type de fichier invalide"

### Technical
- Refactored dropzone template to use `@class` Blade directive for conditional classes
- Added `$isInvalid` computed variable in Blade for cleaner error detection
- Simplified JavaScript validation sync logic
- Removed debug logging from production code

## [1.0.27] - 2026-01-25

### Fixed
- **Dropzone Component**: Fixed validation error state not updating reactively
  - Changed `invalid` from a static property to a computed getter
  - Now automatically reacts to Livewire validation state changes
  - Border color updates immediately when validation runs
  - Properly detects errors for both single fields and array fields (e.g., `documents.*`, `documents.0`)
  - No longer requires manual watchers or effects

### Changed
- **Dropzone Component**: Internationalization improvements
  - All user-facing text now uses the translation system (`window.t`)
  - Error messages are translatable (HTTP errors, network errors, upload failures)
  - Status messages support localization
  - Fallback to English if translations are not available
  - Added 14 new translation keys for better i18n support

### Technical
- Refactored `invalid` property to use getter pattern for reactivity
- Added `_invalid` private property to store initial validation state
- Improved error message extraction with translation support
- Fixed operator precedence issue with `||` and `??` operators

## [1.0.26] - 2026-01-25

### Changed
- **Dropzone Component**: Complete UI modernization with improved validation error display
  - Redesigned borders with subtle ring effects on error and drag states
  - Larger, rounded icon container (14x14) with smooth hover animations
  - Icon scales up and changes color on drag/error/hover
  - Dynamic text colors that adapt to component state
  - "Drop files here" message appears during drag
  - Modernized file preview cards with status-based styling (success=green, error=red)
  - Smoother progress bar with rounded corners and better transitions
  - Error messages now display inline in preview cards
  - Remove button style adapts to file status
  - Overall cleaner, more polished modern appearance

### Fixed
- **Dropzone Component**: Complete fix for validation error display
  - Properly extracts field name from `wire:model` attribute
  - Checks for array validation errors (e.g., `documents`, `documents.*`, `documents.0`)
  - Uses `red-` colors instead of `danger-` for proper Tailwind compatibility
  - Visual feedback on error: red border, red background, red icon
  - Icon container and upload icon change to red on validation error
  - Works correctly with Livewire validation for single and multiple file uploads
  - Removed internal error display - use external `<neura::error>` component instead

## [1.0.25] - 2026-01-25

### Added
- **Tree View Component**: Modern, minimalist hierarchical tree component inspired by Notion and shadcn/ui
  - Clean, professional UI design with smooth animations
  - 3 variants: `default`, `ghost`, `bordered` (minimalist styles)
  - 3 sizes: `sm`, `md`, `lg`
  - Advanced drag & drop with visual feedback (before/after/inside positions)
  - Smart drop indicators showing where items will land
  - Single and multi-select with Ctrl/Cmd support
  - Full Livewire integration with `wire-model` prop for two-way binding
  - Folders only mode to filter out files
  - Expand all option
  - Modern outline SVG icons (folder, file, image, code)
  - Notion-style badges for counts/status
  - Events: `tree:select`, `tree:move`
  - Comprehensive documentation with real-world examples

### Fixed
- Alpine.js initialization issues with proper event listener registration
- **Dropzone Component**: Error state styling now properly displays when validation fails
  - Added reactive `hasError` computed property
  - Danger border and background colors now show when files have errors
  - Fixed static validation state not updating dynamically

## [1.0.24] - 2026-01-25

### Added
- **Spinner Component**: New loading spinner component with multiple variants and styles
  - 7 variants: `default`, `ring`, `dual-ring`, `dots`, `pulse`, `bars`, `square`
  - 7 sizes: `xs`, `sm`, `md`, `lg`, `xl`, `2xl`, `3xl`
  - 9 colors: `primary`, `secondary`, `success`, `danger`, `warning`, `info`, `white`, `black`, `current`
  - Labels with 4 positions: `top`, `bottom`, `left`, `right`
  - 3 animation speeds: `slow`, `normal`, `fast`
  - Comprehensive documentation with use case examples

- **Progress Component**: New progress bar component for showing completion status
  - 3 variants: `default`, `soft`, `bordered`
  - 6 sizes: `xs`, `sm`, `md`, `lg`, `xl`, `2xl`
  - 10 colors including 5 gradient options
  - Value display with 3 positions: `top`, `bottom`, `right`
  - Labels for describing progress
  - 5 border radius options: `none`, `sm`, `md`, `lg`, `full`
  - Animated transitions
  - Striped pattern option
  - Indeterminate mode for unknown progress
  - Comprehensive documentation with use case examples

## [1.0.23] - 2026-01-25

### Fixed
- **Select Component**: Fixed default value not being detected when passed as HTML attribute
  - When `value` was passed as HTML attribute (e.g., `value="{{ Auth::user()->company_id }}"`), it wasn't being extracted correctly
  - Now extracts value from `$attributes->get('value')` before filtering attributes
  - Default value now works correctly whether passed as Blade prop (`:value="..."`) or HTML attribute (`value="..."`)
  - Fixes issue where adding custom classes would break default value selection

## [1.0.22] - 2026-01-25

### Fixed
- **Select Component**: Fixed bug when adding custom classes
  - Component props (wire:model, x-model, value, etc.) were being passed to the root div element
  - Now using `whereDoesntStartWith()` to exclude component props from being rendered as HTML attributes
  - Custom classes can now be added without conflicts
  - All component-specific props are properly filtered out

## [1.0.21] - 2026-01-25

### Added
- **Select Component**: Added `value` prop for default option selection
  - New `value` prop allows setting a default selected option without `wire:model` or `x-model`
  - Works for both single and multiple selects
  - For single selects: pass a string value (e.g., `value="option2"`)
  - For multiple selects: pass an array of values (e.g., `:value="['option1', 'option3']"`)
  - Default value is used as fallback when no value is set in `wire:model` or `x-model`
  - Updated documentation with examples for default value usage

## [1.0.20] - 2026-01-25

### Added
- **Card Component**: Enhanced with multiple variants and styling options
  - New `variant` prop: `default`, `outline`, `soft`, `elevated`, `flat`, `bordered`, `ghost`
  - New `color` prop: `primary`, `secondary`, `success`, `danger`, `warning`, `info`
  - New `padding` prop: `none`, `xs`, `sm`, `normal`, `md`, `lg`, `xl`
  - New `shadow` prop: `none`, `xs`, `sm`, `md`, `lg`, `xl`, `2xl`, `inner`
  - New `rounded` prop: `none`, `sm`, `md`, `lg`, `xl`, `2xl`, `3xl`, `full`
  - Extended `size` prop: Added `3xl`, `4xl`, `5xl`, `6xl`, `7xl` options
  - Color variants work seamlessly with all style variants
  - All properties can be combined for custom card designs
  - Comprehensive documentation with examples for all properties

## [1.0.19] - 2026-01-24

### Changed
- **Modal**: Refactored Modal classes for better maintainability
  - Extracted max-width map to public constant `ModalComponent::MAX_WIDTH_CLASSES`
  - Added utility methods `getMaxWidthClass()` and `isValidSize()` for reusability
  - `ModalCall` now uses centralized `ModalComponent::getMaxWidthClass()` instead of duplicating the map
  - Added DocBlocks for all public methods in `ModalCall`
  - Extracted `buildOpenJs()` method to eliminate code duplication in `open()`
  - Added `declare(strict_types=1)` to `Contracts/ModalComponent` interface
  - Improved type hints and return types across all Modal classes

## [1.0.18] - 2026-01-24

### Fixed
- **Modal Manager**: Fixed attribute priority logic for `maxWidth` and `maxWidthClass`
  - `maxWidthClass` now has absolute priority and correctly overrides `maxWidth`
  - Improved conditional logic to prevent conflicts between the two attributes

### Changed
- **ModalCall**: Auto-sync `maxWidthClass` when using `maxWidth()` method
  - When calling `->maxWidth('lg')`, automatically sets `maxWidthClass` to `'max-w-lg'`
  - For predefined sizes (xs, sm, md, lg, xl, 2xl, etc.), both attributes are synchronized
  - For custom values (600px, 80%, etc.), `maxWidthClass` is removed to use inline styles
  - Provides better developer experience with automatic class mapping

## [1.0.17] - 2026-01-24

### Fixed
- **Modal Component**: Replaced responsive maxWidth classes with simple fixed classes
  - Changed from complex responsive classes (`sm:max-w-md md:max-w-xl`) to simple classes (`max-w-lg`, `max-w-xl`)
  - Ensures consistent modal widths across all screen sizes
  - Simplified `ModalComponent::$maxWidths` array for better maintainability
- **Modal**: Fixed `maxWidth` prop handling for custom values
  - Predefined sizes (xs, sm, md, lg, xl, 2xl, etc.) now properly use Tailwind classes
  - Custom values (e.g., "600px", "80%", "50rem") now use inline styles instead of broken dynamic classes
  - Removed non-functional `max-w-[{$maxWidth}]` pattern that wasn't being compiled by Tailwind

## [1.0.16] - 2026-01-24

### Fixed
- **Modal Manager**: Fixed `maxWidth` handling to support both predefined sizes and custom values
  - Predefined sizes (xs, sm, md, lg, xl, 2xl, etc.) now use optimized Tailwind classes
  - Custom values (e.g., "600px", "80%", "50rem") use inline styles
  - Support for `maxWidthClass` override with custom Tailwind classes

### Added
- **Phone Input**: National prefix handling for phone validation
  - Automatically removes leading "0" for France and similar countries
  - Added `nationalPrefix` field to Country interface
  - Updated validation logic to handle national prefixes correctly

## [1.0.15] - 2026-01-24

### Added
- **Phone Input**: New comprehensive phone number input component
  - Country selector with 70+ countries and flag emojis
  - Automatic formatting based on country-specific patterns
  - Built-in validation with country-specific regex patterns
  - Preferred countries shown at top of dropdown
  - Filter countries with `onlyCountries` or `excludeCountries` props
  - Searchable country dropdown
  - Full Livewire `wire:model` integration
  - Sizes: `sm`, `md`, `lg`
  - JavaScript API for programmatic access (`getFullNumber()`, `isValidNumber()`, etc.)

## [1.0.14] - 2026-01-24

### Added
- **Wizard Steps**: Added `size` prop for default variant
  - Available sizes: `sm`, `md` (default), `lg`
  - Dynamic sizing for circles, icons, labels, and connector lines
- **Wizard Steps**: Added `color` prop for customizable step colors
  - Built-in colors: `neutral` (default), `primary`, `secondary`, `success`, `danger`, `warning`, `info`
  - Support for all Tailwind colors (red, blue, green, etc.)

### Changed
- **Wizard Steps**: Improved default variant UI
  - Cleaner, more modern design inspired by shadcn/ui
  - Simplified connector lines with proper alignment
  - Better responsive spacing and typography
  - Removed excessive shadows and effects for a subtler look

## [1.0.13] - 2026-01-24

### Added
- **Wizard Navigation**: Added complete step button support
  - New `showCompleteButton` prop to display a button after all steps are completed
  - New `completeLabel` prop to customize the button label
  - New `completeUrl` prop to redirect to a URL when clicked (renders as link)
  - If no `completeUrl`, button calls `wire:click="restart"` to restart the wizard
  - Added i18n support with `__()` for default labels

## [1.0.12] - 2026-01-24

### Fixed
- **Color Picker**: Fixed Livewire `wire:model` binding not working
  - Added direct integration with `$wire.get()` and `$wire.set()` for proper Livewire synchronization
  - Initial value from `wire:model` is now correctly displayed
  - Color changes are synced to Livewire in real-time
  - Added `$watch` to detect external Livewire value changes
  - Fixed wire:model attributes not being passed correctly to hidden input

## [1.0.11] - 2026-01-24

### Added
- **Modal Manager**: Added nested modals support with stack navigation
  - Modals can now be opened from within other modals
  - Previous modal is preserved in a stack and restored on close
  - Added smooth fade transition between modals (fade-out → fade-in)
  - Focus is properly restored when navigating back to previous modal
  - Stack is cleared when all modals are closed

### Changed
- **Modal Manager**: Improved transition animations
  - Added scale and opacity transitions for modal enter/leave
  - Modals now fade out completely before the next one fades in
  - Configurable transition delay (180ms default)

## [1.0.10] - 2026-01-24

### Fixed
- **Color Picker**: Fixed Alpine.js error "$disabled is not defined"
  - Replaced Blade variable `$disabled` with Alpine variable `isDisabled` in button binding
  - Increased popup z-index to `z-[100]` for better visibility in modals

## [1.0.9] - 2026-01-24

### Fixed
- **Select Component**: Fixed click-away behavior not closing the dropdown
  - Added global click handler for reliable close on outside click
  - Menu now properly closes when clicking anywhere outside the component
  - Improved cleanup of event listeners to prevent memory leaks

- **Color Picker**: Fixed click-away behavior not closing the popup
  - Replaced unreliable `@click.away` directive with global click handler
  - Menu now properly closes when clicking outside the component
  - Added proper event listener cleanup on component destroy

## [1.0.8] - 2026-01-23

### Added
- **Empty State Component**: New component for displaying helpful messages when there's no content
  - Supports icon, image, or custom illustration
  - Configurable title and description
  - Multiple size variants: sm, md, lg
  - Multiple style variants: default, bordered, card, ghost
  - Action buttons slot for primary CTAs
  - Footer slot for additional information
  - Compact mode option for full-width display
  - Dark mode support with proper contrast
  - Responsive design for all screen sizes

## [1.0.7] - 2026-01-23

### Fixed
- **Context Menu**: Complete refactoring with Singleton Manager pattern
  - Fixed issue where multiple context menus could be open simultaneously
  - Menus now properly close when clicking outside
  - Menus close when right-clicking to open another context menu
  - Added proper event listener cleanup to prevent memory leaks
  - Fixed timing issues with teleported menu elements

- **Color Picker**: Improved menu behavior
  - Menu now properly closes after selecting a color
  - Menu can be reopened by clicking on input or swatch icon
  - Added click handler on input for better UX
  - Swatch icon now toggles menu open/close
  - Fixed click propagation issues

### Changed
- **Context Menu Architecture**: Refactored to use ContextMenuManager singleton
  - Centralized management of all context menu instances
  - Public `isClickInsideAnyMenu()` method for proper encapsulation
  - Global mousedown and contextmenu handlers for reliable close behavior

## [1.0.6] - 2026-01-23

### Changed
- **Enhanced Popover Styling**: Improved ce-popover with modern Notion-style design
  - Rounded corners with backdrop blur effect
  - Better padding and spacing for items
  - Smooth hover effects with proper transitions
  - Active state highlighting with blue accent
  - Icon and label alignment improvements
  - Support for search input, dividers, and headers
  - Secondary labels for keyboard shortcuts
  - Enhanced dark mode with proper opacity
  - Removed all unwanted bullets/dots from UI elements

### Added
- **EditorJS Notion-style Theme**: Complete redesign of EditorJS with Notion-inspired styling
  - Clean, modern typography with improved readability
  - Notion-style headers with proper hierarchy (H1-H4)
  - Elegant paragraph spacing and line height
  - Beautiful quote blocks with gradient backgrounds
  - Refined code blocks with syntax highlighting support
  - Inline code with distinctive red accent styling
  - Smooth hover effects and transitions
  - Enhanced image blocks with rounded corners and shadows
  - Notion-style link previews and markers
  - Clean table styling with proper borders
  - Floating toolbar with modern icons
  - Selection toolbar with dark theme
  - Improved drag handles and block selection
  - Responsive typography for mobile devices
- **Dark Mode Support**: Full dark mode support for EditorJS
  - Automatic theme switching based on system preference
  - Dark mode optimized colors for all components
  - Proper contrast ratios for accessibility
  - Smooth transitions between light and dark modes
  - Custom scrollbar styling for both themes
  - Dark mode support for all toolbars and popovers
- **Enhanced Editor Container**: Improved editor wrapper styling
  - Rounded corners with subtle shadows
  - Focus ring with blue accent color
  - Hover effects for better interactivity
  - Increased padding for better content breathing room
  - Smooth transitions for all interactive states

### Fixed
- **Inline Toolbar Dot Issue**: Fixed unwanted dot/bullet appearing in ce-inline-toolbar by default
  - Removed all `::before` and `::after` pseudo-elements causing dots
  - Added explicit `list-style: none` to all editor UI elements
  - Preserved list styles for actual content lists (cdx-list)
  - Improved inline tool button styling with proper padding and alignment
- **Editor.js Synchronization**: Fixed "There is no block at index" error by clearing editor before rendering new content
- **Image Upload Reliability**: Major improvements to handle intermittent failures
  - **Retry Logic**: Automatic retry with exponential backoff (3 attempts: 1s, 2s, 4s delays)
  - **Timeout Handling**: 60-second timeout with AbortController to prevent hanging requests
  - **Network Error Detection**: Better detection and messaging for network failures
  - **Client-side Validation**: File size (10MB max) and type validation before upload
  - **Server-side Retry**: File storage retry logic with exponential backoff
  - **PHP Limits Check**: Validates against PHP upload_max_filesize and post_max_size
  - **File Validation**: Checks file validity and permissions before storage
  - **Better Error Messages**: More descriptive error messages for different failure types
- **Upload Logging**: Comprehensive logging in `EditorImageController` and `ImageStorageService`
  - Log upload attempts with detailed file info (size, type, name, validity)
  - Log successful uploads with URL, path, dimensions, and duration
  - Log validation failures with error details
  - Log runtime errors with stack traces (in debug mode)
  - Track upload duration for performance monitoring
- **HTTP Headers**: Added `Accept: application/json` header to ensure JSON responses
- **Editor Initialization**: Added `onReady` callback for better initialization tracking

### Changed
- Editor.js now clears content before rendering to prevent block index conflicts
- Image uploader validates files before sending to server
- Error handling improved with more descriptive messages and proper error classification
- Upload process now includes automatic retries for transient failures
- File storage includes directory creation and permission checks

## [1.0.5] - 2026-01-22

### Added
- **Editor Multi-Variant System**: Modular architecture to support multiple editor types
  - `variants/` structure with Tiptap and Editor.js support
  - `EditorImageController`: Handles image uploads and URL metadata for Editor.js
  - `ImageStorageService`: Service for image storage (S3, local, public) with URL generation and dimensions
  - `UrlMetadataService`: Service to extract metadata (title, description, image) from URLs
  - Integrated routes: `/neura-kit/editor/upload-image` and `/neura-kit/editor/fetch-url`
  - Configuration: `neura-kit.editor.default_variant`, `image_disk`, `image_path`, `max_image_size`
  - Comprehensive tests: 50 tests with 178 assertions for controllers and services
- **Editor.js Variant**: Block-styled editor with structured JSON output
  - Full tool support: Header, List, Quote, Code, Image, InlineCode, Link, Marker, Delimiter, Table
  - Integrated image upload with preview and dimensions
  - Automatic metadata fetching for links
  - Custom CSS styles with dark mode support
  - Dynamic imports to avoid SSR issues
- **Tiptap Variant**: WYSIWYG editor with HTML output
  - Custom toolbar with all formatting tools
  - Support for headings, lists, blockquotes, alignment, links, images
  - HTML and JSON modes
- Editor.js dependencies added to `InstallDependenciesCommand.php`
- Complete editor documentation with examples of both variants

### Changed
- **Editor Component**: Complete refactoring to support variants
  - `variant` prop to choose between 'tiptap' (default) and 'editorjs'
  - JSON mode enforced for Editor.js
  - Modular architecture with separate views per variant
- **ChunkController**: Renamed from `ChunkUploadController` for simplification
  - Business logic delegated to services
  - Better error handling with appropriate HTTP codes (413 for oversized files)
- **Services Architecture**: Clear separation of responsibilities
  - `Editor/ImageStorageService`: Image storage and management
  - `Editor/UrlMetadataService`: Web metadata extraction
  - `Upload/ChunkAssemblerService`: Chunk assembly
  - `Upload/FileNameSanitizerService`: Filename sanitization
- Tests refactored to use controllers instead of Laravel Actions
- `ToastServiceTest` and `LicenseValidatorTest` fixed and all tests passing

### Removed
- Obsolete files: `button.blade.php` and `toolbar.blade.php` at `editor/` root
- Old controllers renamed for consistency

### Fixed
- TypeScript errors in `editorjs.ts`: Replaced `EditorJS` (namespace) with `EditorJSInstance` (type)
- Editor.js toolbox now visible with appropriate CSS styles
- Signing secret configuration in license tests
- `add()` method replaced with `flash()` in ToastService
- All unit and feature tests passing (50 tests, 178 assertions)

### Security
- Strict MIME type validation for images (jpeg, png, gif, webp, svg)
- Configurable maximum size for image uploads
- URL sanitization for metadata fetching
- Storage disk existence verification before upload

## [1.0.4] - 2026-01-21

### Added
- **Chunk Upload System**: Integrated and secure chunk upload system
  - `ChunkUploadController`: Controller with validation, sanitization and automatic assembly
  - `ChunkedTemporaryFile`: Helper to convert chunk uploads to Livewire `TemporaryUploadedFile`
  - Integrated routes: `/neura-kit/upload/chunks` and `/neura-kit/upload/file/{uuid}`
  - Configuration: `neura-kit.upload.max_size` and `neura-kit.upload.chunk_size`
  - Comprehensive tests: 23 tests with 84 assertions (100% coverage of critical features)
- Enhanced Alert component with 13 color variants (blue, green, yellow, orange, red, purple, pink, teal, neutral)
- New Color Variants section in Alert documentation with visual examples
- Advanced Color Usage examples in Alert documentation
- Comprehensive Color & Type Reference table in Alert documentation
- New `merge` utility for Tailwind class merging
- Color Picker documentation completely rewritten (Tailwind palette, token/hex/rgb input, Livewire/Alpine examples, hex normalization)

### Changed
- **Dropzone**: Automatic integration with chunk upload system
  - Automatic upload by default if no `wire:model`
  - Automatic CSRF token in headers
  - `upload:success` and `upload:error` events with complete metadata
  - Complete documentation with Livewire examples, events, and backend configuration
- **TestCase**: Improved test configuration (encryption key, automatic route loading)
- Alert component now supports both `type` (legacy) and `color` props for better flexibility
- Improved Alert documentation with expanded property descriptions
- Updated autocomplete component to use attribute merging instead of class binding
- Enhanced autocomplete options UI with better visual feedback and spacing
- Improved empty state design in autocomplete with proper icon component
- Popup component UI updated to match Notion/shadcn style (rounded-xl, shadow-xl, modern padding, smooth transitions)
- Popup now uses `merge` utility instead of `class` for class merging

### Input
- Improved input component structure and slot support
- Added extra-slot support for advanced customization
- Refactored input options (button, clearable, copyable, revealable) for better composability
- Unified border, focus, and invalid state styles for all input-like components

### Checkbox, Radio & Switch
- Unified border, background, and focus ring colors to match Input component style
- Improved focus and checked states for better accessibility and visual consistency
- Refactored Radio and Checkbox components to use PackResolver for dynamic theming
- Switch component now uses the same color pack logic as Input, Checkbox, and Radio for track and thumb
- Improved Switch accessibility and keyboard support
- Updated Switch, Checkbox, and Radio to have consistent active, hover, and disabled states in both light and dark mode

### OTP (One-Time Password)
- OTP input fields now use the same border, focus, and invalid styles as main input fields
- Improved error display and accessibility for OTP input
- Unified transition and background for OTP fields in all themes

### Avatar & Badge
- Avatar badge now uses PackResolver::badgeColor and badgeSize for unified color and sizing logic
- Fixed avatar badge to always be small and fully rounded for consistent appearance
- Avatar badge rendering now matches badge component logic for color, variant, and size
- Fixed TypeErrors for badgeColor and badgeSize argument types in avatar component
- Improved badge positioning and appearance for avatar

### Fixed
- Documentation inconsistencies in Alert component examples
- Minor styling issues in autocomplete dropdown options
- Fixed border/focus inconsistencies between input, checkbox, radio, switch, and OTP fields
- Improved error and invalid state handling for all form components
- Fixed parse errors and TypeErrors in avatar component after refactor
- Ensured all badge and avatar logic uses correct argument types and defaults
- Color Picker refactor: logic extracted to global TypeScript, UI aligned with Input/Popup, dynamic placeholder, systematic hex normalization (token/hex/rgb), `wire:model`/`x-model` support

### Security
- Protection against directory traversal in filenames
- Automatic filename sanitization (special characters, max length 255)
- Strict file size validation
- Secure storage in Livewire temporary folder

## [1.0.0] - 2024-12-19

### Added
- Initial release of Neura Kit component library
- All atoms components migrated from main project
- Service provider for automatic component registration
- Configuration file for customization
- Publishing support for views and config
- 60+ UI components including forms, layout, feedback, navigation, data, and display components
- Modal system with ModalComponent base class
- Toast notification system
- Dialog confirmation system
- Theme management with dark mode support
- Vite plugin integration
- JavaScript API for modals, toasts, and dialogs
- Artisan commands for modal creation and dependency installation
