# Google Fonts Bundle

[![Latest Version](https://img.shields.io/packagist/v/neuralglitch/google-fonts.svg?style=flat-square)](https://packagist.org/packages/neuralglitch/google-fonts)
[![PHP Version](https://img.shields.io/packagist/php-v/neuralglitch/google-fonts.svg?style=flat-square)](https://packagist.org/packages/neuralglitch/google-fonts)
[![License](https://img.shields.io/packagist/l/neuralglitch/google-fonts.svg?style=flat-square)](https://packagist.org/packages/neuralglitch/google-fonts)
[![Tests](https://github.com/neuralglitch/google-fonts/actions/workflows/tests.yml/badge.svg)](https://github.com/neuralglitch/google-fonts/actions/workflows/tests.yml)
[![Static Analysis](https://github.com/neuralglitch/google-fonts/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/neuralglitch/google-fonts/actions/workflows/static-analysis.yml)

Google Fonts integration for Symfony with development CDN and production font locking.

## Features

- **Twig Function**: `{{ google_fonts() }}` function for easy font integration
- **Development Mode**: Uses Google Fonts CDN with inline styles for fast iteration
- **Production Mode**: Lock fonts locally with dedicated stylesheets for better performance and privacy
- **Smart CSS**: Automatically applies fonts to body, headings, and bold text
- **Command Line Tools**: Search, import, lock, and warm-cache fonts
- **High Performance**: Optimized Twig function with full template caching support

## Installation

```bash
composer require neuralglitch/google-fonts
```

## Usage

### Basic Usage

Add the font function to your base template:

```twig
{# base.html.twig #}
<head>
  {{ google_fonts('Ubuntu', '300 400 500 700', 'normal italic') }}
</head>
```

### Function Parameters

```twig
{{ google_fonts(name, weights, styles, display) }}
```

- `name` (required): Font family name (e.g., "Ubuntu", "Roboto", "Open Sans")
- `weights` (optional): Font weights as space-separated string or array (default: `["400"]`)
    - Examples: `"300 400 500 700"` or `[300, 400, 500, 700]`
- `styles` (optional): Font styles as space-separated string or array (default: `["normal"]`)
    - Examples: `"normal italic"` or `["normal", "italic"]`
- `display` (optional): Font display value (default: `"swap"`)
    - Options: `"swap"`, `"auto"`, `"block"`, `"fallback"`, `"optional"`

### Examples

```twig
{# Single weight #}
{{ google_fonts('Roboto', '400') }}

{# Multiple weights #}
{{ google_fonts('Ubuntu', '300 400 500 700') }}

{# With italic #}
{{ google_fonts('Open Sans', '400 700', 'normal italic') }}

{# Custom display #}
{{ google_fonts('Inter', '400 600', 'normal', 'swap') }}

{# Using arrays #}
{{ google_fonts('Roboto', [300, 400, 500], ['normal', 'italic']) }}
```

## Commands

### Search Fonts

Search the Google Fonts catalog:

```bash
php bin/console gfonts:search Roboto
php bin/console gfonts:search "Open Sans"
```

### Import Font

Download a specific font locally:

```bash
php bin/console gfonts:import Ubuntu --weights="300,400,500,700" --styles="normal,italic"
```

### Lock Fonts

Scan templates and lock all used fonts for production:

```bash
php bin/console gfonts:lock
php bin/console gfonts:lock templates/ views/
```

This command:

1. Scans all Twig templates for `google_fonts()` function calls
2. Downloads all referenced fonts
3. Creates a manifest file (`assets/fonts.json`)
4. Stores fonts in `assets/fonts/`

### Warm Cache

Pre-download fonts from manifest (useful for CI/CD):

```bash
php bin/console gfonts:warm-cache
```

## Configuration

```yaml
# config/packages/google_fonts.yaml
google_fonts:
  use_locked_fonts: false  # Set to true in production to use locked fonts
  fonts_dir: '%kernel.project_dir%/assets/fonts'
  manifest_file: '%kernel.project_dir%/assets/fonts.json'
  defaults:
    display: 'swap'
    preconnect: true
```

### Production Setup

1. **Lock fonts** (run once during development):
   ```bash
   php bin/console gfonts:lock
   ```

2. **Enable locked fonts** in production config:
   ```yaml
   # config/packages/prod/google_fonts.yaml
   google_fonts:
     use_locked_fonts: true
   ```

3. **Ensure fonts directory is accessible** (Asset Mapper or web server config)

## How It Works

### Development Mode

- Function renders Google Fonts CDN links with inline `<style>` tag
- Fast iteration, no build step required
- Automatically applies CSS variables and font styles

### Production Mode

- Function detects locked fonts from manifest
- Renders two `<link>` tags for local font CSS files instead of CDN
- Better performance and privacy (no external requests)
- Stylesheet with intelligent CSS rules served separately

## Development

### Docker Setup

A minimal Docker container is provided for running tests and CI tasks:

```bash
# Build container (PHP 8.2 by default)
make build

# Use different PHP version
PHP_VERSION=8.3 make build

# Install dependencies
make install

# Run tests
make test

# Run PHPStan
make phpstan

# Fix code style
make cs-fix

# Check code style
make cs-check

# Run tests with code coverage
make test-coverage

# Open shell in container
make shell
```

## Testing

Run the test suite:

```bash
make test
```

Generate code coverage report:

```bash
make test-coverage
```

The coverage report will be generated in `build/coverage/` directory.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to contribute to this project.

## Security

If you discover any security-related issues, please email dev@neuralglit.ch instead of using the issue tracker. Please see [SECURITY.md](.github/SECURITY.md) for more information.

## License

MIT

## Support

- **Issues**: [GitHub Issues](https://github.com/neuralglitch/google-fonts/issues)
- **Documentation**: [README](https://github.com/neuralglitch/google-fonts/blob/main/README.md)
- **Email**: dev@neuralglit.ch

