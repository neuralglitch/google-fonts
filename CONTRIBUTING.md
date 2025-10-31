# Contributing to Google Fonts Bundle

Thank you for your interest in contributing to the Google Fonts Bundle for Symfony!

## Development Setup

1. **Clone the repository**:
   ```bash
   git clone https://github.com/neuralglitch/google-fonts.git
   cd google-fonts
   ```

2. **Install dependencies**:
   ```bash
   composer install
   ```

3. **Run tests**:
   ```bash
   composer test
   ```

4. **Run static analysis**:
   ```bash
   composer phpstan
   ```

5. **Fix code style**:
   ```bash
   composer cs-fix
   ```

## Making Changes

1. **Write code** following the [Coding Standards](#coding-standards)
2. **Write tests** for new functionality
3. **Update documentation** if needed
4. **Run tests** to ensure nothing breaks

```bash
# Run PHPUnit tests
composer test

# Run PHPStan (static analysis)
composer phpstan

# Run code style fixer
composer cs-fix
```

## Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add font caching support
fix: resolve manifest parsing issue
docs: update installation guide
test: add tests for FontDownloader service
refactor: optimize manifest file reading
```

Examples:

- `feat(command): add --force option to lock command`
- `fix(extension): resolve locked font detection`
- `docs(readme): update configuration examples`
- `test(api): add tests for font search functionality`

---

## Coding Standards

### PHP

This project follows **Symfony Coding Standards**:

- PHP 8.1+ strict typing: `declare(strict_types=1);`
- Final classes where appropriate
- Typed properties and parameters
- Return type declarations
- DocBlocks for complex logic
- Readonly properties where applicable

#### File Structure

```php
<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Service;

final class ServiceName
{
    public function __construct(
        private readonly Dependency $dependency
    ) {}
    
    public function methodName(): string
    {
        // Implementation
    }
}
```

### Naming Conventions

- **Classes/Interfaces**: `PascalCase`
- **Methods/Properties**: `camelCase`
- **Constants**: `UPPER_SNAKE_CASE`
- **Function names**: `snake_case` (for Twig functions)
- **File names**: Match class name

### Configuration

- YAML format for configuration files
- Snake_case for parameter keys
- Provide comprehensive defaults

### Error Handling

- Use custom exceptions (`GoogleFontsException`, `FontDownloadException`, `ManifestException`)
- Provide meaningful error messages
- Preserve exception chain with `$previous` parameter

---

## Pull Request Process

### Before Submitting

1. **Ensure all tests pass**:
   ```bash
   composer test
   composer phpstan
   ```

2. **Update documentation** if needed:
    - README.md
    - CHANGELOG.md
    - Code comments

3. **Add yourself** to contributors (if first contribution)

### Submitting PR

1. Push your branch:
   ```bash
   git push origin feature/your-feature-name
   ```

2. Create Pull Request on GitHub:
    - Use a descriptive title
    - Reference related issues (#123)
    - Describe changes clearly
    - List breaking changes (if any)

3. **PR Template**:

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Related Issue
Fixes #(issue number)

## Testing
- [ ] Tests added/updated
- [ ] All tests pass
- [ ] Manual testing completed

## Checklist
- [ ] Code follows project style
- [ ] Documentation updated
- [ ] CHANGELOG.md updated
- [ ] No breaking changes (or documented)
```

### Review Process

- Maintainers will review your PR
- Address feedback and update PR
- Once approved, maintainer will merge

---

## Reporting Issues

### Bug Reports

Include:

- Symfony version
- PHP version
- Bundle version
- Steps to reproduce
- Expected vs actual behavior
- Error messages/logs
- Minimal code example

### Feature Requests

Include:

- Clear description of feature
- Use cases and benefits
- Possible implementation approach
- Examples from other projects

### Security Issues

**Do NOT** open public issues for security vulnerabilities.

Please email security concerns to: **security@neuralglit.ch**

---

## Testing Guidelines

### Unit Tests

- Test all public methods
- Test error conditions
- Use descriptive test method names
- Follow Arrange-Act-Assert pattern

Example:

```php
public function testDownloadFontThrowsExceptionOnInvalidUrl(): void
{
    // Arrange
    $downloader = new FontDownloader(...);
    
    // Act & Assert
    $this->expectException(FontDownloadException::class);
    $downloader->downloadFont('Invalid Font', [], []);
}
```

### Integration Tests

- Test command execution
- Test Twig extension rendering
- Test file system operations

---

## Code Review Checklist

- [ ] Code follows Symfony coding standards
- [ ] All tests pass
- [ ] New functionality has tests
- [ ] Documentation updated
- [ ] No hardcoded values
- [ ] Proper error handling
- [ ] Type hints on all methods
- [ ] No unused imports
- [ ] Meaningful variable/method names

---

Thank you for contributing!

