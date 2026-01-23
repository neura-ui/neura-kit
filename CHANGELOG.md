# Changelog


All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


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
