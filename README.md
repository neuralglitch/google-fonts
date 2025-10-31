<div align="center">

# Google Fonts

### Google Fonts integration for Symfony

[![Latest Version](https://img.shields.io/packagist/v/neuralglitch/google-fonts.svg?style=flat-square)](https://packagist.org/packages/neuralglitch/google-fonts)
[![PHP Version](https://img.shields.io/packagist/php-v/neuralglitch/google-fonts.svg?style=flat-square)](https://packagist.org/packages/neuralglitch/google-fonts)
[![License](https://img.shields.io/packagist/l/neuralglitch/google-fonts.svg?style=flat-square)](https://packagist.org/packages/neuralglitch/google-fonts)
<br/>
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%20max-brightgreen.svg?style=flat-square)](phpstan.neon.dist)
[![Tests](https://github.com/neuralglitch/google-fonts/actions/workflows/tests.yml/badge.svg)](https://github.com/neuralglitch/google-fonts/actions/workflows/tests.yml)
[![Static Analysis](https://github.com/neuralglitch/google-fonts/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/neuralglitch/google-fonts/actions/workflows/static-analysis.yml)

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
  {{ google_fonts('Ubuntu', '300 400 500 700', 'normal italic') }}
</head>
```

### 2. Lock fonts for production

```bash
php bin/console gfonts:lock
```

The bundle automatically switches to locked fonts in production (via `when@prod` configuration).

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

## License

MIT - see [LICENSE](LICENSE) for details.

## Support

- **Issues**: [GitHub Issues](https://github.com/neuralglitch/google-fonts/issues)
- **Security**: [SECURITY.md](.github/SECURITY.md)
- **Contributing**: [CONTRIBUTING.md](CONTRIBUTING.md)
