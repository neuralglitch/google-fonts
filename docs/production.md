# Production Deployment

## Quick Start

```bash
# 1. Lock fonts
php bin/console gfonts:lock

# 2. Verify
php bin/console gfonts:status

# 3. Deploy (fonts automatically enabled via when@prod config)
```

## How It Works

The bundle automatically switches to locked fonts in production via `when@prod` configuration:

```yaml
# config/packages/google_fonts.yaml (included by default)
when@prod:
  google_fonts:
    use_locked_fonts: true
    defaults:
      preconnect: false  # Not needed for local fonts
```

**No additional configuration needed!**

## Locked Fonts

### Development (CDN)

```html
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400" rel="stylesheet">
<style>
  /* Inline intelligent CSS */
</style>
```

### Production (Locked)

```html
<link rel="stylesheet" href="/assets/fonts/roboto-abc123.css">
```

**Benefits:**
- No external requests (faster, more private)
- Offline support
- Version controlled
- AssetMapper versioned URLs

## File Structure

After `gfonts:lock`:

```
assets/
  ├── fonts.json              # Manifest (commit to git)
  └── fonts/
      ├── roboto.css          # @font-face + styles (commit to git)
      ├── roboto-400.woff2    # Font files (commit to git)
      ├── roboto-700.woff2
      └── ...
```

After `asset-map:compile` (production):

```
public/assets/fonts/
  ├── roboto-abc123.css       # Versioned CSS
  ├── roboto-400-def456.woff2 # Versioned fonts
  └── ...
```

## Deployment Strategies

### Strategy 1: Commit Font Files (Simple)

```bash
# Lock fonts
php bin/console gfonts:lock

# Commit everything
git add assets/fonts.json assets/fonts/
git commit -m "Lock fonts for production"
git push
```

**Pros:** Simple, works everywhere  
**Cons:** Larger repo size

### Strategy 2: Download During Deploy (Clean)

```bash
# Only commit manifest
git add assets/fonts.json
git commit -m "Lock fonts manifest"
git push

# In deploy script:
php bin/console gfonts:warm-cache  # Downloads from manifest
```

**Pros:** Smaller repo  
**Cons:** Requires internet during deploy

## CI/CD Integration

### GitHub Actions

```yaml
# .github/workflows/deploy.yml
jobs:
  deploy:
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
      
      - run: composer install --no-dev --optimize-autoloader
      - run: php bin/console gfonts:lock
      - run: php bin/console asset-map:compile
      
      # Deploy public/ directory
```

### GitLab CI

```yaml
# .gitlab-ci.yml
build:
  script:
    - composer install --no-dev
    - php bin/console gfonts:lock
    - php bin/console asset-map:compile
  artifacts:
    paths:
      - public/assets/
```

## Troubleshooting

### Fonts Still Using CDN

**Check:**
1. `use_locked_fonts` is `true`: `php bin/console debug:config google_fonts`
2. Manifest exists: `ls assets/fonts.json`
3. Fonts exist: `ls assets/fonts/`
4. Cache cleared: `php bin/console cache:clear`

**Debug:**
```bash
php bin/console gfonts:status
```

### 404 Errors on Font Files

**Cause:** AssetMapper not serving files

**Fix:**
```bash
# Development
php bin/console cache:clear

# Production
php bin/console asset-map:compile
```

### Wrong Font Weights

**Cause:** Google Fonts didn't provide all requested weights

**Check output of:**
```bash
php bin/console gfonts:lock
```

Look for yellow warnings: `(missing: 700)`

The manifest only includes actually downloaded weights.

## Performance

### AssetMapper Integration

Locked fonts work seamlessly with AssetMapper:

- **Dev:** Served from `assets/fonts/` on-the-fly
- **Prod:** Compiled to `public/assets/fonts/` with version hashes
- **Automatic:** No configuration needed

### Cache Headers

Set long cache headers for fonts (they rarely change):

**Nginx:**
```nginx
location ~* \.(woff2|woff|ttf|otf)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

**Apache:**
```apache
<FilesMatch "\.(woff2|woff|ttf|otf)$">
    Header set Cache-Control "public, max-age=31536000, immutable"
</FilesMatch>
```

## Updating Fonts

When you change fonts in templates:

```bash
# 1. Update template
# {{ google_fonts('New Font', '400') }}

# 2. Re-lock
php bin/console gfonts:lock

# 3. Verify
git diff assets/fonts.json

# 4. Deploy
git add assets/fonts.json assets/fonts/
git commit -m "Update fonts"
git push
```

## Best Practices

1. **Always lock before production**
2. **Commit manifest** (`assets/fonts.json`) to git
3. **Use `when@prod`** for clean configuration
4. **Test locally** with locked fonts enabled
5. **Monitor** font loading in production
6. **Set cache headers** for optimal performance
7. **Only load needed weights** to reduce file size

## Next Steps

- [Configuration](configuration.md) - Configuration options
- [Commands](commands.md) - All available commands
- [Usage](usage.md) - Using fonts in templates
