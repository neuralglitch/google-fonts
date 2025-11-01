# Configuration

## Default Configuration

```yaml
# config/packages/google_fonts.yaml
google_fonts:
  api_key: '%env(GOOGLE_FONTS_API_KEY)%'  # For search/import commands
  use_locked_fonts: false
  fonts_dir: '%kernel.project_dir%/assets/fonts'
  manifest_file: '%kernel.project_dir%/assets/fonts.json'
  defaults:
    display: 'swap'
    preconnect: true

when@prod:
  google_fonts:
    use_locked_fonts: true
    defaults:
      preconnect: false
```

## Configuration Options

### `api_key`

**Type:** `string|null`  
**Default:** `null`  
**Required for:** `gfonts:search`, `gfonts:import`

Google Fonts API key for searching and importing fonts.

```yaml
google_fonts:
  api_key: '%env(GOOGLE_FONTS_API_KEY)%'
```

Get your free API key: [Google Cloud Console](https://console.cloud.google.com/apis/credentials)

**Note:** NOT required for `gfonts:lock` or `google_fonts()` function.

### `use_locked_fonts`

**Type:** `boolean`  
**Default:** `false`

Use locally locked fonts instead of Google CDN.

```yaml
google_fonts:
  use_locked_fonts: false  # Dev: Use CDN
  # use_locked_fonts: true  # Prod: Use local fonts
```

### `fonts_dir`

**Type:** `string`  
**Default:** `'%kernel.project_dir%/assets/fonts'`

Directory where locked fonts are stored (served by AssetMapper).

```yaml
google_fonts:
  fonts_dir: '%kernel.project_dir%/assets/fonts'
```

### `manifest_file`

**Type:** `string`  
**Default:** `'%kernel.project_dir%/assets/fonts.json'`

Path to the fonts manifest file.

```yaml
google_fonts:
  manifest_file: '%kernel.project_dir%/assets/fonts.json'
```

### `defaults.display`

**Type:** `string`  
**Default:** `'swap'`

Default font-display strategy.

```yaml
google_fonts:
  defaults:
    display: 'swap'  # swap | auto | block | fallback | optional
```

**Options:**
- `swap` (recommended) - Show fallback, swap when ready
- `auto` - Browser decides
- `block` - Block up to 3s
- `fallback` - Brief block, brief swap
- `optional` - No block, no swap

### `defaults.preconnect`

**Type:** `boolean`  
**Default:** `true`

Add preconnect hints for Google Fonts CDN (development only).

```yaml
google_fonts:
  defaults:
    preconnect: true
```

## Environment Configuration

### Using when@ Syntax (Recommended)

```yaml
# config/packages/google_fonts.yaml
google_fonts:
  use_locked_fonts: false

when@prod:
  google_fonts:
    use_locked_fonts: true
    defaults:
      preconnect: false

when@test:
  google_fonts:
    defaults:
      preconnect: false
```

**Benefits:** Single file, clear separation

### Test Locked Fonts in Development

```yaml
when@dev:
  google_fonts:
    use_locked_fonts: true  # Test production mode locally
```

## Debugging

### Check Current Configuration

```bash
php bin/console debug:config google_fonts
```

### Verify Services

```bash
php bin/console debug:container google_fonts
```

### Check Status

```bash
php bin/console gfonts:status
```

## Next Steps

- [Commands](commands.md) - CLI commands
- [Production](production.md) - Deployment guide
- [Usage](usage.md) - Using fonts in templates
