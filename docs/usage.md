# Usage Guide

## Basic Usage

Add fonts to your base template:

```twig
{# templates/base.html.twig #}
<!DOCTYPE html>
<html>
<head>
  {# Normal font for body and headings #}
  {{ google_fonts('Ubuntu', '400 700', 'normal') }}
  
  {# Monospace font for code elements #}
  {{ google_fonts('JetBrains Mono', '400 500', 'normal', null, true) }}
</head>
<body>
  {% block body %}{% endblock %}
</body>
</html>
```

## Function Parameters

```twig
{{ google_fonts(name, weights, styles, display, monospace) }}
```

| Parameter   | Type          | Default      | Description                        |
|-------------|---------------|--------------|------------------------------------|
| `name`      | string        | required     | Font family (e.g., "Ubuntu")       |
| `weights`   | string\|array | `['400']`    | Weights: `"400 700"` or `[400, 700]` |
| `styles`    | string\|array | `['normal']` | Styles: `"normal italic"` or array |
| `display`   | string\|null  | `'swap'`     | Font display strategy              |
| `monospace` | bool          | `false`      | Apply to code elements             |

## Examples

### Single Weight
```twig
{{ google_fonts('Roboto', '400') }}
```

### Multiple Weights
```twig
{{ google_fonts('Ubuntu', '300 400 700') }}
{{ google_fonts('Inter', [400, 600, 700]) }}
```

### With Italic
```twig
{{ google_fonts('Open Sans', '400 700', 'normal italic') }}
```

### Monospace Fonts
```twig
{{ google_fonts('JetBrains Mono', '400 500', 'normal', null, true) }}
{{ google_fonts('Fira Code', '400', 'normal', null, true) }}
```

### Custom Display
```twig
{{ google_fonts('Inter', '400', 'normal', 'optional') }}
```

## Development vs Production

### Development Mode (Default)

Uses Google Fonts CDN:

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400" rel="stylesheet">
<style>
  :root { --font-family-ubuntu: 'Ubuntu', sans-serif; }
  body { font-family: var(--font-family-ubuntu); }
</style>
```

**Benefits:** Fast iteration, no setup

### Production Mode (Locked Fonts)

Uses local fonts served by AssetMapper:

```html
<link rel="stylesheet" href="/assets/fonts/ubuntu-abc123.css">
```

**Benefits:** Better performance, privacy, offline support

See [Production Setup](production.md) for deployment guide.

## Generated CSS

### Normal Font (monospace: false)

```css
:root {
  --font-family-ubuntu: 'Ubuntu', sans-serif;
}

body {
  font-family: var(--font-family-ubuntu);
  font-weight: 400;
}

h1, h2, h3, h4, h5, h6 {
  font-family: var(--font-family-ubuntu);
  font-weight: 700;
}

strong, b {
  font-weight: 700;
}
```

### Monospace Font (monospace: true)

```css
:root {
  --font-family-jetbrains-mono: 'JetBrains Mono', monospace;
}

code, pre, kbd, samp, var, tt {
  font-family: var(--font-family-jetbrains-mono);
  font-weight: 400;
}
```

## Font Pairing

### Modern & Clean
- **Body:** Inter, Roboto, Open Sans
- **Code:** JetBrains Mono, Fira Code

### Elegant & Professional
- **Body:** Crimson Pro, Lora, Playfair Display
- **Code:** IBM Plex Mono, Roboto Mono

### Technical & Sharp
- **Body:** IBM Plex Sans, Work Sans
- **Code:** IBM Plex Mono, Inconsolata

## Best Practices

1. **Minimize weights** - Only load what you use
2. **Lock for production** - Run `gfonts:lock` before deploy
3. **Use CSS variables** - Leverage generated `--font-family-*` variables
4. **Limit families** - 2-3 fonts maximum per site
5. **Use `display: swap`** - Best balance of performance and UX

## Troubleshooting

### Fonts not loading in development

Check:
- Function called in `<head>` section
- No CSP blocking Google Fonts
- Network connection available

### Fonts not loading in production

Check:
```bash
php bin/console gfonts:status
```

See [Production Setup](production.md) for troubleshooting.

## Next Steps

- [Commands](commands.md) - CLI commands
- [Configuration](configuration.md) - Configuration options
- [Production](production.md) - Production deployment
