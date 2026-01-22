# Changelog


All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [1.0.4] - 2026-01-21

### Added
- **Chunk Upload System**: Système d'upload par chunks intégré et sécurisé
  - `ChunkUploadController`: Contrôleur avec validation, sanitization et assembly automatique
  - `ChunkedTemporaryFile`: Helper pour convertir les uploads par chunks en `TemporaryUploadedFile` Livewire
  - Routes intégrées: `/neura-kit/upload/chunks` et `/neura-kit/upload/file/{uuid}`
  - Configuration: `neura-kit.upload.max_size` et `neura-kit.upload.chunk_size`
  - Tests complets: 23 tests avec 84 assertions (100% de couverture des fonctionnalités critiques)
- Enhanced Alert component with 13 color variants (blue, green, yellow, orange, red, purple, pink, teal, neutral)
- New Color Variants section in Alert documentation with visual examples
- Advanced Color Usage examples in Alert documentation
- Comprehensive Color & Type Reference table in Alert documentation
- New `merge` utility for Tailwind class merging
- Color Picker documentation entièrement réécrite (palette Tailwind, saisie token/hex/rgb, exemples Livewire/Alpine, normalisation hex)

### Changed
- **Dropzone**: Intégration automatique avec le système de chunk upload
  - Upload automatique par défaut si pas de `wire:model`
  - CSRF token automatique dans les headers
  - Événements `upload:success` et `upload:error` avec métadonnées complètes
  - Documentation complète avec exemples Livewire, événements, et configuration backend
- **TestCase**: Configuration améliorée pour les tests (clé d'encryption, chargement automatique des routes)
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
- Color Picker refacto : logique extraite en TypeScript global, UI alignée sur Input/Popup, placeholder dynamique, normalisation systématique en hex (token/hex/rgb), support `wire:model`/`x-model`

### Security
- Protection contre directory traversal dans les noms de fichiers
- Sanitization automatique des noms de fichiers (caractères spéciaux, longueur max 255)
- Validation stricte de la taille des fichiers
- Stockage sécurisé dans le dossier temporaire Livewire

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
