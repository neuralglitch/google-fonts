# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.2.0] - 2025-11-05

### Added

- Font subsetting support via `text` parameter in `google_fonts()` for character subset optimization
- Preload hints via `preload` parameter to generate `<link rel="preload">` for critical fonts
- API response caching with configurable TTL (default 1 hour) for Google Fonts API calls
- Font validation in commands before downloading from Google Fonts catalog
- Dry-run mode for `gfonts:lock` and `gfonts:import` commands
- Progress bars for long-running CLI commands
- New console commands:
  - `gfonts:status` - Display configuration and locked fonts status
  - `gfonts:prune` - Remove unused fonts from locked fonts directory

### Changed

- Environment-agnostic configuration using YAML `when@prod` and `when@dev` instead of runtime environment checks
- CSS optimization by merging `@font-face` declarations and intelligent styling rules into single CSS file per font
- AssetMapper integration now uses relative font paths (`./font.woff2`) in CSS for proper compatibility
- Manifest structure now records only actually downloaded weights, not requested weights
- Enhanced CLI output showing requested vs downloaded weights with warnings for unavailable weights
- Detailed tables with font information in command output

### Fixed

- AssetMapper 404 errors by fixing asset path generation (`fonts_dir` now properly in `assets/`)
- Symfony Flex recipe implementation with proper manifest structure and GitHub Actions workflow for automatic endpoint generation
- PHP notices by proper error handling for `file_get_contents()` failures in edge cases

### Improved

- Documentation consolidated and updated, redundancy removed

## [0.1.0] - 2025-11-01

### Added

- Initial release of Google Fonts Bundle for Symfony
- Twig function `google_fonts()` for easy font integration in templates
- Development mode with Google Fonts CDN and inline styles
- Production mode with local font locking and dedicated stylesheets
- Automatic CSS variable generation for font families
- Intelligent CSS rules for body, headings, and bold text
- Console commands:
    - `gfonts:search` - Search Google Fonts catalog
    - `gfonts:import` - Download a specific font locally
    - `gfonts:lock` - Scan templates and lock all used fonts
    - `gfonts:warm-cache` - Pre-download fonts from manifest for CI/CD
- Font manifest file for production font management
- Performance optimizations with manifest caching
- Support for multiple weights and styles
- Font variant helper for consistent font handling
- Comprehensive exception handling
- Full PHP 8.1+ type safety with strict types
- Symfony 6.4+ and 7.x+ compatibility

