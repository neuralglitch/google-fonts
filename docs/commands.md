# Commands

## Overview

- **`gfonts:search`** - Search Google Fonts catalog
- **`gfonts:import`** - Download a specific font
- **`gfonts:lock`** - Scan templates and lock all fonts
- **`gfonts:status`** - Show configuration and locked fonts status
- **`gfonts:warm-cache`** - Pre-download fonts from manifest

## gfonts:search

Search the Google Fonts catalog.

```bash
php bin/console gfonts:search [query] [--max-results=20]
```

**Examples:**
```bash
# Show popular fonts
php bin/console gfonts:search

# Search for specific font
php bin/console gfonts:search "Roboto"

# Limit results
php bin/console gfonts:search Ubuntu -m 10
```

**Requires:** Google Fonts API key (configure in `GOOGLE_FONTS_API_KEY` env var)

## gfonts:import

Download a specific font.

```bash
php bin/console gfonts:import <name> [--weights] [--styles] [--display]
```

**Examples:**
```bash
# Import single weight
php bin/console gfonts:import Ubuntu

# Import multiple weights
php bin/console gfonts:import Ubuntu -w "300,400,700"

# With italic styles
php bin/console gfonts:import "Open Sans" -w "400,700" -s "normal,italic"
```

**Requires:** Google Fonts API key

**Output shows:**
- Requested vs downloaded weights
- Warning if some weights unavailable
- File locations

## gfonts:lock

Scan templates and lock all used fonts.

```bash
php bin/console gfonts:lock [template-dirs]... [--force]
```

**Examples:**
```bash
# Scan default directories (templates/, views/)
php bin/console gfonts:lock

# Scan specific directories
php bin/console gfonts:lock templates/ custom/

# Force re-download
php bin/console gfonts:lock --force
```

**What it does:**
1. Scans Twig templates for `google_fonts()` calls
2. Downloads all referenced fonts
3. Creates `assets/fonts.json` manifest
4. Saves fonts to `assets/fonts/`

**Output shows:**
- Found fonts with weights/styles
- Requested vs downloaded weights (with warnings)
- File count and locations

## gfonts:status

Show configuration and locked fonts status.

```bash
php bin/console gfonts:status
```

**Output shows:**
- Current environment (dev/prod/test)
- Locked fonts configuration (enabled/disabled)
- Manifest file status
- List of locked fonts
- Production readiness checks

**Use for debugging:**
- Why locked fonts aren't being used
- Which fonts are currently locked
- Configuration verification

## gfonts:warm-cache

Pre-download fonts from manifest (for CI/CD builds).

```bash
php bin/console gfonts:warm-cache [--manifest]
```

**Examples:**
```bash
# Use default manifest
php bin/console gfonts:warm-cache

# Custom manifest path
php bin/console gfonts:warm-cache -m /path/to/fonts.json
```

**Use case:** Download fonts during CI/CD build without scanning templates.

## Typical Workflow

### Development
```bash
# 1. Search for fonts
php bin/console gfonts:search "Inter"

# 2. Add to template
# {{ google_fonts('Inter', '400 600') }}

# 3. Develop (uses CDN automatically)
```

### Before Production
```bash
# Lock all fonts
php bin/console gfonts:lock

# Check status
php bin/console gfonts:status

# Verify manifest
cat assets/fonts.json
```

### CI/CD Pipeline
```bash
composer install
php bin/console gfonts:warm-cache  # Download from manifest
php bin/console asset-map:compile   # Compile assets
```

## Next Steps

- [Usage Guide](usage.md) - Using fonts in templates
- [Configuration](configuration.md) - Configure the bundle
- [Production](production.md) - Production deployment
