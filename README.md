<div align="center">

# Google Fonts

### Google Fonts integration for Symfony

[![PHP Version](https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat&logo=php&logoColor=white)](composer.json)
[![Symfony](https://img.shields.io/badge/Symfony-6.4+-343434?style=flat&logo=symfony&logoColor=white)](composer.json)
<br/>
[![Tests](https://github.com/neuralglitch/google-fonts/actions/workflows/tests.yml/badge.svg?style=flat&logo=github)](https://github.com/neuralglitch/google-fonts/actions/workflows/tests.yml)
[![Code Coverage](https://img.shields.io/badge/PHPUnit-93.75%25-32c252?style=flat&logo=codecov&logoColor=white)](build/coverage/index.html)
[![Static Analysis](https://github.com/neuralglitch/google-fonts/actions/workflows/static-analysis.yml/badge.svg?style=flat&logo=github)](https://github.com/neuralglitch/google-fonts/actions/workflows/static-analysis.yml)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-level%20max-32c252?style=flat&logo=php&logoColor=white)](phpstan.neon.dist)
<br/>
[![Release](https://img.shields.io/packagist/v/neuralglitch/google-fonts.svg?style=flat&logo=packagist&logoColor=white)](https://packagist.org/packages/neuralglitch/google-fonts)
[![Downloads](https://img.shields.io/packagist/dt/neuralglitch/google-fonts.svg?style=flat&logo=packagist&logoColor=white)](https://packagist.org/packages/neuralglitch/google-fonts)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat)](LICENSE)

</div>

## Features

- **Twig Function** - Simple `{{ google_fonts() }}` function
- **Development Mode** - Google Fonts CDN with inline styles
- **Production Mode** - Lock fonts locally for better performance and privacy
- **Smart CSS** - Automatic font styling for body, headings, and bold text
- **CLI Tools** - Search, import, lock, and warm-cache commands
- **High Performance** - Optimized with full template caching support

## Installation

```bash
composer require neuralglitch/google-fonts
```

## Quick Start

### 1. Add fonts to your template

```twig
{# templates/base.html.twig #}
<head>
  {# Normal font for body and headings #}
  {{ google_fonts('Ubuntu', '300 400 500 700', 'normal italic') }}
  
  {# Monospace font for code elements #}
  {{ google_fonts('JetBrains Mono', '400 500', 'normal', null, true) }}
</head>
```

### 2. Lock fonts for production

```bash
php bin/console gfonts:lock
```

This downloads fonts to `assets/fonts/` (served by AssetMapper in dev, compiled to `public/` in prod).

Each font gets a single CSS file containing both `@font-face` declarations and intelligent styling rules.

The bundle automatically switches to locked fonts in production (via `when@prod` configuration).

**Troubleshooting:** If locked fonts aren't being used in production, see `DEBUG_LOCKED_FONTS.md`.

### 3. Optional: Configure API key for search/import commands

```bash
# .env.local
GOOGLE_FONTS_API_KEY=your_api_key_here
```

Get your free API key at [Google Cloud Console](https://console.cloud.google.com/apis/credentials).

**Note**: The API key is only required for `gfonts:search` and `gfonts:import` commands. The `google_fonts()` Twig function and `gfonts:lock` command do NOT require an API key.

## Documentation

- **[Usage Guide](docs/usage.md)** - Detailed examples and function parameters
- **[Commands](docs/commands.md)** - CLI command reference
- **[Configuration](docs/configuration.md)** - Configuration options
- **[Production Setup](docs/production.md)** - Deploying with locked fonts
- **[Development](docs/development.md)** - Contributing and testing

## Requirements

- PHP 8.1 or higher
- Symfony 6.4, 7.x, or 8.x
- Twig 3.0 or higher

## Support

- [GitHub Issues](https://github.com/neuralglitch/google-fonts/issues)
- [Security](.github/SECURITY.md)
- [Contributing](CONTRIBUTING.md)

## License

[MIT](LICENSE)