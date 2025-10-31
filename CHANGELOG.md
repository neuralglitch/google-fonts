# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- N/A

### Changed

- N/A

### Fixed

- N/A

---

## [1.0.0] - 2025-01-XX

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

