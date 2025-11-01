# Development Guide

## Setup

### Prerequisites
- PHP 8.1+
- Docker & Docker Compose
- Make (optional)

### Quick Start

```bash
git clone https://github.com/neuralglitch/google-fonts.git
cd google-fonts

make build    # Build Docker image
make install  # Install dependencies
make test     # Run tests
```

## Makefile Commands

```bash
make help          # Show all commands
make build         # Build Docker image
make install       # Install Composer dependencies
make test          # Run PHPUnit tests
make test-coverage # Run tests with coverage report
make phpstan       # Run static analysis
make cs-fix        # Fix code style issues
make cs-check      # Check code style
make shell         # Open shell in container
```

## Running Tests

### All Tests

```bash
make test
```

### With Coverage

```bash
make test-coverage
open build/coverage/index.html
```

### Specific Test

```bash
docker compose run --rm php vendor/bin/phpunit tests/Service/GoogleFontsApiTest.php
```

## Static Analysis

### PHPStan (Level max)

```bash
make phpstan
```

### Code Style

```bash
make cs-check  # Check only
make cs-fix    # Fix issues
```

## Project Structure

```
src/
  - Command/              # Console commands
  - DependencyInjection/  # Bundle configuration
  - Exception/            # Custom exceptions
  - Service/              # Core business logic
  - Twig/                 # Twig extension + runtime

tests/                    # PHPUnit tests
config/                   # Bundle config files
docker/                   # Docker setup
docs/                     # Documentation
```

## Architecture

### Key Components

**GoogleFontsRuntime** (Twig Runtime)
- Lazy-loaded when `google_fonts()` is called
- Handles dev/prod mode switching
- Manages manifest caching

**GoogleFontsApi** (Service)
- Google Fonts API interaction
- Font search and metadata
- CSS download

**FontDownloader** (Service)
- Downloads font files
- Generates combined CSS (@font-face + styles)
- Tracks actually downloaded weights

**FontLockManager** (Service)
- Scans Twig templates
- Orchestrates font locking
- Manages manifest file

## Writing Tests

### Test Structure

```php
<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Tests\Service;

use PHPUnit\Framework\TestCase;

final class MyServiceTest extends TestCase
{
    public function testSomething(): void
    {
        // Arrange
        $service = new MyService();
        
        // Act
        $result = $service->doSomething();
        
        // Assert
        self::assertTrue($result);
    }
}
```

### Coverage Goals

- **Methods:** >80%
- **Lines:** >90%
- **Critical paths:** 100%

## Contributing

### Workflow

1. Fork the repository
2. Create feature branch: `git checkout -b feature/my-feature`
3. Make changes
4. Run tests: `make test && make phpstan && make cs-check`
5. Commit: `git commit -m "feat: add my feature"`
6. Push: `git push origin feature/my-feature`
7. Create Pull Request

### Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add new feature
fix: fix bug
docs: update documentation
test: add tests
refactor: refactor code
chore: update dependencies
```

### Before Submitting PR

- [ ] All tests pass: `make test`
- [ ] PHPStan passes: `make phpstan`
- [ ] Code style fixed: `make cs-fix`
- [ ] Coverage maintained: `make test-coverage`
- [ ] Documentation updated if needed

## Debugging

### Enable Xdebug

```bash
XDEBUG_ENABLED=1 make test
```

### Debug Configuration

```bash
# In a Symfony app using this bundle
php bin/console debug:config google_fonts
php bin/console debug:container google_fonts
```

## Performance Optimization

### Manifest Caching

```php
// Static cache with mtime checking
private static ?array $manifestCache = null;
private static ?int $manifestMtime = null;
```

Manifest read only once per request, reloaded only if file changes.

### String Building

Use array + implode for large strings:

```php
// Optimized
$lines = [':root {', '  var: value;', '}'];
$css = implode("\n", $lines);
```

## Release Process

1. Update `CHANGELOG.md`
2. Bump version in `composer.json`
3. Create git tag: `git tag v1.0.0`
4. Push tag: `git push --tags`
5. GitHub release created automatically
6. Packagist updates automatically

## Next Steps

- [Usage Guide](usage.md) - Using the bundle
- [Commands](commands.md) - CLI commands
- [Contributing](../CONTRIBUTING.md) - Detailed guidelines
