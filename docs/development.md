# Development Guide

## Getting Started

### Prerequisites

- PHP 8.2 or higher
- Docker and Docker Compose
- Git
- Make (optional, for convenience commands)

### Clone and Setup

```bash
git clone https://github.com/neuralglitch/google-fonts.git
cd google-fonts

# Build Docker container
make build

# Install dependencies
make install

# Run tests
make test
```

## Development Environment

### Docker Setup

The project includes a minimal Docker environment for consistency:

```yaml
# docker-compose.yml
services:
  php:
    build:
      context: ./docker/php
      args:
        PHP_VERSION: 8.2
    volumes:
      - ./:/app
```

**Available PHP versions**: 8.1, 8.2, 8.3

```bash
# Use different PHP version
PHP_VERSION=8.3 make build
```

### Makefile Commands

```bash
make help          # Show available commands
make build         # Build Docker image
make install       # Install dependencies
make test          # Run PHPUnit tests
make test-coverage # Run tests with coverage
make phpstan       # Run static analysis
make cs-fix        # Fix code style
make cs-check      # Check code style
make shell         # Open shell in container
```

## Project Structure

```
google-fonts/
  - src/
      - Command/           # Console commands
      - DependencyInjection/  # Symfony DI
      - Exception/         # Custom exceptions
      - Service/           # Core services
      - Twig/              # Twig extension
  - tests/                 # PHPUnit tests
  - config/                # Bundle configuration
  - docker/                # Docker setup
  - docs/                  # Documentation
  - .github/               # GitHub workflows
  - Makefile               # Development commands
```

## Running Tests

### PHPUnit

```bash
# Run all tests
make test

# Run specific test
docker compose run --rm php vendor/bin/phpunit tests/GoogleFontsExtensionTest.php

# Run with coverage
make test-coverage

# View coverage report
open build/coverage/index.html
```

### Test Structure

```
tests/
  - GoogleFontsExtensionTest.php        # Twig function tests
  - FontVariantHelperTest.php           # Helper tests
  - GoogleFontsExtensionLockedFontsTest.php  # Production mode tests
```

### Writing Tests

```php
<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Tests;

use NeuralGlitch\GoogleFonts\Twig\GoogleFontsExtension;
use PHPUnit\Framework\TestCase;

final class MyTest extends TestCase
{
    public function testSomething(): void
    {
        $extension = new GoogleFontsExtension('dev', false);
        $html = $extension->renderFonts('Ubuntu', '400');
        
        self::assertStringContainsString('Ubuntu', $html);
    }
}
```

## Static Analysis

### PHPStan

```bash
# Run PHPStan (level 9)
make phpstan

# Check specific files
docker compose run --rm php vendor/bin/phpstan analyse src/Service/
```

Configuration:

```neon
# phpstan.neon.dist
parameters:
  level: 9
  paths:
    - src
    - tests
```

## Code Style

### PHP-CS-Fixer

```bash
# Fix code style
make cs-fix

# Check without fixing
make cs-check
```

Configuration:

```php
// .php-cs-fixer.php
return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'declare_strict_types' => true,
        // ...
    ]);
```

## Contributing

### Workflow

1. **Fork the repository**
2. **Create a feature branch**
   ```bash
   git checkout -b feature/my-feature
   ```
3. **Make your changes**
4. **Run tests**
   ```bash
   make test
   make phpstan
   make cs-check
   ```
5. **Commit changes**
   ```bash
   git commit -m "Add my feature"
   ```
6. **Push to your fork**
   ```bash
   git push origin feature/my-feature
   ```
7. **Create a Pull Request**

### Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add support for font subsets
fix: correct manifest file path resolution
docs: update configuration examples
test: add tests for FontVariantHelper
refactor: simplify CSS generation logic
chore: update dependencies
```

### Pull Request Guidelines

- **Title**: Clear, descriptive title
- **Description**: Explain what and why
- **Tests**: Add tests for new features
- **Docs**: Update documentation if needed
- **Code Style**: Run `make cs-fix`
- **Static Analysis**: Ensure `make phpstan` passes
- **Tests**: Ensure `make test` passes

## Debugging

### Enable Xdebug

```bash
# Enable Xdebug in container
XDEBUG_ENABLED=1 docker compose run --rm php vendor/bin/phpunit
```

### Debugging Tests

```php
public function testDebug(): void
{
    $extension = new GoogleFontsExtension('dev', false);
    $html = $extension->renderFonts('Ubuntu', '400');
    
    var_dump($html);  // Debug output
    die();
}
```

### Check Configuration

```bash
# In a Symfony app using the bundle
php bin/console debug:config google_fonts
php bin/console debug:container GoogleFonts
```

## Architecture

### Key Components

1. **GoogleFontsExtension** (Twig)
   - Renders `google_fonts()` function
   - Handles dev/prod mode switching
   - Manages manifest cache

2. **GoogleFontsApi** (Service)
   - Interacts with Google Fonts API
   - Downloads font CSS
   - Searches font catalog

3. **FontDownloader** (Service)
   - Downloads font files
   - Generates @font-face CSS
   - Creates intelligent stylesheets

4. **FontLockManager** (Service)
   - Scans Twig templates
   - Orchestrates font locking
   - Manages manifest file

5. **Commands**
   - `FontsSearchCommand` - Search fonts
   - `FontsImportCommand` - Import font
   - `FontsLockCommand` - Lock fonts
   - `FontsWarmCacheCommand` - Warm cache

### Design Patterns

- **Service Layer**: Business logic separated from controllers
- **Dependency Injection**: All services use constructor injection
- **Immutability**: Use `readonly` properties where possible
- **Type Safety**: Strict types enforced, PHPStan level 9
- **Exception Handling**: Custom exceptions for different error types

## Performance Optimization

### Manifest Caching

```php
// Static cache for manifest
private static ?array $manifestCache = null;
private static ?int $manifestMtime = null;

private function hasLockedFonts(string $fontName): bool
{
    $mtime = filemtime($this->manifestFile);
    
    // Only reload if file changed
    if (self::$manifestCache === null || self::$manifestMtime !== $mtime) {
        self::$manifestCache = json_decode(
            file_get_contents($this->manifestFile),
            true
        );
        self::$manifestMtime = $mtime;
    }
    
    return isset(self::$manifestCache['fonts'][$fontName]);
}
```

### String Building

Use array + implode for large strings:

```php
// BAD: Slow
$css = '';
$css .= ':root {';
$css .= '  --font: value;';
$css .= '}';

// GOOD: Fast
$lines = [
    ':root {',
    '  --font: value;',
    '}',
];
$css = implode("\n", $lines);
```

## CI/CD

### GitHub Actions

```yaml
# .github/workflows/tests.yml
name: Tests
on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['8.1', '8.2', '8.3']
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
      - run: composer install
      - run: vendor/bin/phpunit
```

### Static Analysis

```yaml
# .github/workflows/static-analysis.yml
name: Static Analysis
on: [push, pull_request]

jobs:
  phpstan:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
      - run: composer install
      - run: vendor/bin/phpstan analyse
```

## Release Process

1. **Update CHANGELOG.md**
2. **Bump version** in relevant files
3. **Create git tag**
   ```bash
   git tag -a v1.0.0 -m "Release 1.0.0"
   git push --tags
   ```
4. **Create GitHub release**
5. **Publish to Packagist** (automatic via webhook)

## Troubleshooting

### Tests Failing

```bash
# Clear Docker cache
make build

# Reinstall dependencies
make install

# Run tests with verbose output
docker compose run --rm php vendor/bin/phpunit --verbose
```

### PHPStan Errors

```bash
# Generate baseline (temporary fix)
docker compose run --rm php vendor/bin/phpstan analyse --generate-baseline

# Analyze specific file
docker compose run --rm php vendor/bin/phpstan analyse src/Service/FontDownloader.php -vvv
```

### Container Issues

```bash
# Rebuild from scratch
docker compose down -v
docker compose build --no-cache
make install
```

## Resources

- [Symfony UX Documentation](https://symfony.com/doc/current/ux.html)
- [Twig Extension Documentation](https://twig.symfony.com/doc/3.x/advanced.html)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)

## Getting Help

- **Issues**: [GitHub Issues](https://github.com/neuralglitch/google-fonts/issues)
- **Discussions**: [GitHub Discussions](https://github.com/neuralglitch/google-fonts/discussions)
- **Email**: dev@neuralglit.ch

## Next Steps

- [Usage Guide](usage.md) - Learn how to use the bundle
- [Commands](commands.md) - CLI command reference
- [Contributing Guidelines](../CONTRIBUTING.md) - Detailed contribution guide

