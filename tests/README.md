# Tests for Neura Kit

This directory contains the test suite for the Neura Kit component library.

## Running Tests

To run all tests:

```bash
composer test
```

Or using PHPUnit directly:

```bash
./vendor/bin/phpunit
```

To run specific test suites:

```bash
./vendor/bin/phpunit tests/Unit
./vendor/bin/phpunit tests/Feature
```

## Test Structure

- **Unit Tests** (`tests/Unit/`): Test individual components and service provider functionality
- **Feature Tests** (`tests/Feature/`): Test component rendering, Livewire integration, and path resolution

## Test Coverage

### Service Provider Tests
- Service provider registration
- Component registration
- Component rendering
- Component variants and attributes

### Component Rendering Tests
- Button component with various configurations
- Input components
- Form components (select, checkbox, textarea)
- Icon components
- Modal manager component

### Livewire Integration Tests
- Modal manager with Livewire components
- Table columns with Livewire components
- Component props and arguments handling

### Component Path Tests
- Package views resolution
- Published views resolution
- Component accessibility verification

