# Command Reference

## Overview

Google Fonts Bundle provides four console commands for managing fonts:

- `gfonts:search` - Search Google Fonts catalog
- `gfonts:import` - Download a specific font locally
- `gfonts:lock` - Scan templates and lock all used fonts
- `gfonts:warm-cache` - Pre-download fonts from manifest

## gfonts:search

Search the Google Fonts catalog by name.

### Usage

```bash
php bin/console gfonts:search [query] [--max-results=20]
```

### Arguments

- `query` (optional) - Search query (leave empty to show popular fonts)

### Options

- `--max-results`, `-m` - Maximum number of results (default: 20)

### Examples

```bash
# Show popular fonts
php bin/console gfonts:search

# Search for "Roboto"
php bin/console gfonts:search Roboto

# Search for "Open Sans"
php bin/console gfonts:search "Open Sans"

# Limit results
php bin/console gfonts:search Ubuntu --max-results=10
```

### Output

```
Search Google Fonts

Search Results for: "Ubuntu"

+------------+----------+------------------------------+-----------+
| Font Name  | Variants | Available Variants           | Category  |
+------------+----------+------------------------------+-----------+
| Ubuntu     | 14       | 300, 300italic, regular, ... | sans-serif|
| Ubuntu ... | 8        | regular, italic, 700, 700... | monospace |
+------------+----------+------------------------------+-----------+
```

## gfonts:import

Download a specific font and store it locally.

### Usage

```bash
php bin/console gfonts:import <name> [--weights] [--styles] [--display]
```

### Arguments

- `name` (required) - Font family name (e.g., "Ubuntu", "Roboto")

### Options

- `--weights`, `-w` - Font weights, comma or space-separated (default: "400")
- `--styles`, `-s` - Font styles, comma or space-separated (default: "normal")
- `--display`, `-d` - Font display value (default: "swap")

### Examples

```bash
# Import single weight
php bin/console gfonts:import Ubuntu

# Import multiple weights
php bin/console gfonts:import Ubuntu --weights="300,400,500,700"

# Import with italic styles
php bin/console gfonts:import "Open Sans" --weights="400,700" --styles="normal,italic"

# Custom display strategy
php bin/console gfonts:import Roboto --weights="400" --display="optional"

# Space-separated (alternative syntax)
php bin/console gfonts:import Ubuntu --weights="300 400 700"
```

### Output

```
Importing font: Ubuntu

Configuration
-------------

* Font: Ubuntu
* Weights: 300, 400, 500, 700
* Styles: normal, italic
* Display: swap

[OK] Font "Ubuntu" imported successfully!
     Files saved: 8
     CSS file: /path/to/assets/fonts/ubuntu/ubuntu.css
```

### Downloaded Files

The command creates:

```
assets/fonts/ubuntu/
  - ubuntu.css              # @font-face declarations
  - ubuntu-styles.css       # Intelligent CSS rules
  - ubuntu-300.woff2        # Font files
  - ubuntu-400.woff2
  - ubuntu-500.woff2
  - ubuntu-700.woff2
  - ubuntu-300italic.woff2
  - ubuntu-400italic.woff2
  - ubuntu-500italic.woff2
  - ubuntu-700italic.woff2
```

## gfonts:lock

Scan Twig templates for `google_fonts()` calls and download all referenced fonts.

### Usage

```bash
php bin/console gfonts:lock [template-dirs]... [--force]
```

### Arguments

- `template-dirs` (optional) - Template directories to scan (default: `templates/`, `views/`)

### Options

- `--force`, `-f` - Force re-download even if fonts already exist

### Examples

```bash
# Scan default directories
php bin/console gfonts:lock

# Scan specific directories
php bin/console gfonts:lock templates/ views/

# Force re-download
php bin/console gfonts:lock --force
```

### Output

```
Lock Fonts

Scanning templates
------------------

* templates/
* views/

Found fonts
-----------

* Ubuntu (weights: 300, 400, 500, 700, styles: normal, italic)
* Roboto (weights: 400, 700, styles: normal)
* Open Sans (weights: 400, 600, styles: normal, italic)

Downloading fonts
-----------------

[OK] Locked 3 font(s) successfully!

[NOTE] Enable locked fonts in production by setting: google_fonts.use_locked_fonts: true
```

### Generated Manifest

The command creates `assets/fonts.json`:

```json
{
  "locked": true,
  "generated_at": "2025-10-31T22:00:00+00:00",
  "fonts": {
    "Ubuntu": {
      "weights": [300, 400, 500, 700],
      "styles": ["normal", "italic"],
      "files": ["ubuntu-300.woff2", "ubuntu-400.woff2", ...],
      "css": "assets/fonts/ubuntu/ubuntu.css",
      "stylesheet": "assets/fonts/ubuntu/ubuntu-styles.css"
    },
    "Roboto": {
      "weights": [400, 700],
      "styles": ["normal"],
      "files": ["roboto-400.woff2", "roboto-700.woff2"],
      "css": "assets/fonts/roboto/roboto.css",
      "stylesheet": "assets/fonts/roboto/roboto-styles.css"
    }
  }
}
```

### Template Scanning

The command scans for patterns like:

```twig
{{ google_fonts('Ubuntu', '300 400 700') }}
{{ google_fonts('Roboto', [400, 700], ['normal']) }}
```

And extracts:
- Font family name
- Weights (with defaults if omitted)
- Styles (with defaults if omitted)

## gfonts:warm-cache

Pre-download all fonts from the manifest file. Useful for CI/CD builds.

### Usage

```bash
php bin/console gfonts:warm-cache [--manifest]
```

### Options

- `--manifest`, `-m` - Path to manifest file (default: from config)

### Examples

```bash
# Warm cache from default manifest
php bin/console gfonts:warm-cache

# Use custom manifest
php bin/console gfonts:warm-cache --manifest=/path/to/fonts.json
```

### Output

```
Warm Font Cache

Found 3 font(s) in manifest

3/3 [============================] 100%

[OK] Successfully warmed cache for 3 font(s)
```

### Use Case: CI/CD

In your CI/CD pipeline:

```yaml
# .gitlab-ci.yml
build:
  script:
    - composer install
    - php bin/console gfonts:warm-cache
    - # ... rest of build
```

This ensures all fonts are downloaded during the build process.

## Command Integration

### Typical Workflow

1. **Development**: Use `gfonts:search` to find fonts
2. **Development**: Add fonts to templates with `google_fonts()`
3. **Before Production**: Run `gfonts:lock` to download all fonts
4. **Production Deploy**: Fonts are already locked and ready
5. **CI/CD**: Use `gfonts:warm-cache` to pre-download during build

### Example Deployment Script

```bash
#!/bin/bash

# Lock all fonts used in templates
php bin/console gfonts:lock

# Commit the manifest
git add assets/fonts.json
git commit -m "Lock fonts for production"

# Deploy with locked fonts
# ...
```

## Error Handling

### Common Errors

**Error**: "No template directories found"

```bash
php bin/console gfonts:lock templates/
```

**Error**: "Font not found"

Check font name spelling:
```bash
php bin/console gfonts:search "Font Name"
```

**Error**: "Manifest file not found"

Run lock command first:
```bash
php bin/console gfonts:lock
```

**Error**: "Failed to download font"

Check:
- Internet connection
- Google Fonts API availability
- Font name is correct

## Next Steps

- [Usage Guide](usage.md) - Using fonts in templates
- [Configuration](configuration.md) - Configure the bundle
- [Production Setup](production.md) - Deploy with locked fonts

