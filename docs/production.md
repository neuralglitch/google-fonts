# Production Setup Guide

## Overview

For production, lock fonts locally instead of using Google Fonts CDN. This provides:

- **Better Performance** - No external requests
- **Improved Privacy** - No Google tracking
- **Offline Support** - Fonts work without internet
- **Version Control** - Consistent font versions

## Quick Setup

### 1. Lock Fonts

Scan templates and download all used fonts:

```bash
php bin/console gfonts:lock
```

This creates:
- `assets/fonts/` - Downloaded font files
- `assets/fonts.json` - Font manifest

### 2. Enable in Production

The bundle includes production configuration by default using `when@prod`:

```yaml
# config/packages/google_fonts.yaml
when@prod:
  google_fonts:
    use_locked_fonts: true  # Already configured!
```

**No additional configuration needed** - locked fonts are enabled automatically in production.

### 3. Deploy

Commit and deploy:

```bash
git add assets/fonts.json
git commit -m "Lock fonts for production"
git push
```

## Detailed Setup

### Step 1: Development - Add Fonts

During development, use the CDN:

```twig
{# templates/base.html.twig #}
<head>
  {{ google_fonts('Ubuntu', '300 400 500 700', 'normal italic') }}
  {{ google_fonts('Roboto', '400 700') }}
</head>
```

Configuration (development):
```yaml
# config/packages/google_fonts.yaml
google_fonts:
  use_locked_fonts: false  # Use CDN in development
```

### Step 2: Lock Fonts

Before production deployment, lock all fonts:

```bash
php bin/console gfonts:lock
```

Output:
```
Lock Fonts

Scanning templates
------------------
* templates/

Found fonts
-----------
* Ubuntu (weights: 300, 400, 500, 700, styles: normal, italic)
* Roboto (weights: 400, 700, styles: normal)

Downloading fonts
-----------------
[OK] Locked 2 font(s) successfully!

[NOTE] Enable locked fonts in production by setting: google_fonts.use_locked_fonts: true
```

### Step 3: Verify Files

Check that fonts were downloaded:

```bash
ls -la assets/fonts/
```

Output:
```
ubuntu/
  - ubuntu.css
  - ubuntu-styles.css
  - ubuntu-300.woff2
  - ubuntu-400.woff2
  - ...
roboto/
  - roboto.css
  - roboto-styles.css
  - roboto-400.woff2
  - roboto-700.woff2
  - ...
fonts.json
```

### Step 4: Production Configuration

The default configuration already includes production settings via `when@prod`:

```yaml
# config/packages/google_fonts.yaml (already configured)
when@prod:
  google_fonts:
    use_locked_fonts: true
    defaults:
      preconnect: false
```

**No additional configuration needed** - the bundle automatically uses locked fonts in production.

### Step 5: AssetMapper (Optional)

If using Symfony AssetMapper, ensure fonts are included:

```yaml
# config/packages/asset_mapper.yaml
framework:
  asset_mapper:
    paths:
      - assets/fonts/
```

AssetMapper will:
- Version font files
- Serve with proper cache headers
- Update manifest automatically

### Step 6: Web Server Configuration

Ensure `assets/fonts/` is web-accessible.

**Nginx:**
```nginx
location /assets/fonts/ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

**Apache:**
```apache
<Directory /path/to/project/assets/fonts>
    ExpiresActive On
    ExpiresDefault "access plus 1 year"
</Directory>
```

### Step 7: Commit and Deploy

```bash
# Commit manifest (required)
git add assets/fonts.json
git commit -m "Lock fonts for production"

# Optionally commit font files (or download during deployment)
git add assets/fonts/
git commit -m "Add locked font files"

# Deploy
git push
```

## Deployment Strategies

### Strategy 1: Commit Font Files

**Pros:**
- Simple deployment
- No build step needed
- Works anywhere

**Cons:**
- Larger repository size
- More merge conflicts

```bash
git add assets/fonts/
git add assets/fonts.json
git commit -m "Lock fonts"
```

### Strategy 2: Download During Deployment

**Pros:**
- Smaller repository
- Cleaner git history

**Cons:**
- Requires build step
- Depends on external service

```bash
# Only commit manifest
git add assets/fonts.json
git commit -m "Lock fonts manifest"

# In deployment script:
php bin/console gfonts:warm-cache
```

**Deployment script example:**

```bash
#!/bin/bash
set -e

# Install dependencies
composer install --no-dev --optimize-autoloader

# Download fonts
php bin/console gfonts:warm-cache

# Clear cache
php bin/console cache:clear --env=prod

# Deploy assets
# ...
```

### Strategy 3: Build Artifact

**Pros:**
- Clean separation
- Optimized for CI/CD

**Cons:**
- More complex setup

```yaml
# .gitlab-ci.yml
build:
  script:
    - composer install
    - php bin/console gfonts:lock
    - tar -czf release.tar.gz vendor/ assets/
  artifacts:
    paths:
      - release.tar.gz

deploy:
  script:
    - scp release.tar.gz server:/path/
    - ssh server "cd /path && tar -xzf release.tar.gz"
```

## CI/CD Integration

### GitHub Actions

```yaml
# .github/workflows/deploy.yml
name: Deploy

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      
      - name: Install dependencies
        run: composer install --no-dev
      
      - name: Lock fonts
        run: php bin/console gfonts:lock
      
      - name: Deploy
        run: |
          # Your deployment commands
```

### GitLab CI

```yaml
# .gitlab-ci.yml
build:
  image: php:8.2-cli
  before_script:
    - apt-get update && apt-get install -y git zip
    - curl -sS https://getcomposer.org/installer | php
  script:
    - php composer.phar install --no-dev
    - php bin/console gfonts:lock
  artifacts:
    paths:
      - vendor/
      - assets/fonts/
      - assets/fonts.json

deploy:
  script:
    - rsync -avz assets/fonts/ server:/path/to/assets/fonts/
```

## Verification

### Check Production Output

In production, the function should render local files:

```html
<link rel="stylesheet" href="/assets/fonts/ubuntu/ubuntu.css">
<link rel="stylesheet" href="/assets/fonts/ubuntu/ubuntu-styles.css">
```

Not CDN:
```html
<!-- This should NOT appear in production -->
<link href="https://fonts.googleapis.com/..." rel="stylesheet">
```

### Test Locally

Test production mode locally:

```bash
# Set environment
APP_ENV=prod

# Clear cache
php bin/console cache:clear --env=prod

# Check config
php bin/console debug:config google_fonts --env=prod
```

### Browser DevTools

Check Network tab:
- [x] No requests to `fonts.googleapis.com`
- [x] Fonts loaded from `/assets/fonts/`
- [x] Proper cache headers

## Updating Fonts

When you change fonts in templates:

```bash
# 1. Update templates
# templates/base.html.twig - change or add fonts

# 2. Re-lock fonts
php bin/console gfonts:lock

# 3. Verify changes
git diff assets/fonts.json

# 4. Commit
git add assets/fonts.json assets/fonts/
git commit -m "Update locked fonts"

# 5. Deploy
git push
```

## Rollback

If fonts break in production:

```bash
# Quick fix: disable locked fonts
# config/packages/prod/google_fonts.yaml
google_fonts:
  use_locked_fonts: false  # Fall back to CDN

# Deploy
# Clear cache
php bin/console cache:clear --env=prod
```

## Troubleshooting

### Fonts Not Loading

**Check:**
1. `use_locked_fonts: true` in production config
2. `assets/fonts.json` exists
3. Font files exist in `assets/fonts/`
4. Directory is web-accessible

**Debug:**
```bash
# Check config
php bin/console debug:config google_fonts

# Check manifest
cat assets/fonts.json

# Check files
ls -la assets/fonts/ubuntu/
```

### 404 Errors

**Cause**: Fonts not accessible to web server

**Fix**:
```bash
# Check permissions
chmod -R 755 assets/fonts/

# Verify web server can read
sudo -u www-data ls assets/fonts/
```

### Wrong Font Version

**Cause**: Old locked fonts

**Fix**:
```bash
# Force re-lock
php bin/console gfonts:lock --force
```

### Large Repository Size

**Cause**: Committing many font files

**Solution**: Use Strategy 2 (download during deployment)

```bash
# Remove fonts from repo
git rm -r assets/fonts/
echo '/assets/fonts/' >> .gitignore

# Only track manifest
git add assets/fonts.json .gitignore
```

## Best Practices

1. **Always lock before production deploy**
2. **Commit `assets/fonts.json` to git**
3. **Set proper cache headers** (1 year for fonts)
4. **Use `display: swap`** for best performance
5. **Test locally with `APP_ENV=prod`**
6. **Monitor font loading in production**
7. **Re-lock after font changes**

## Performance Tips

1. **Only load needed weights** - Reduces file size
2. **Use WOFF2 format** - Automatically handled
3. **Enable HTTP/2** - Parallel font downloads
4. **Set long cache headers** - Fonts rarely change
5. **Use AssetMapper** - Automatic versioning

## Security

Locked fonts improve security:

- No external requests (CSP-friendly)
- No Google tracking
- Version-controlled (audit trail)
- Offline support (no SPOF)

## Next Steps

- [Configuration](configuration.md) - Detailed configuration options
- [Commands](commands.md) - CLI command reference
- [Development](development.md) - Contributing guide

