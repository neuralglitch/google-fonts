# Usage Guide

## Basic Usage

Add the `google_fonts()` function to your base template:

```twig
{# templates/base.html.twig #}
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>{% block title %}Welcome{% endblock %}</title>
  
  {{ google_fonts('Ubuntu', '300 400 500 700', 'normal italic') }}
  
  {% block stylesheets %}{% endblock %}
</head>
<body>
  {% block body %}{% endblock %}
</body>
</html>
```

## Function Signature

```twig
{{ google_fonts(name, weights, styles, display) }}
```

### Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `name` | string | Yes | - | Font family name (e.g., "Ubuntu", "Roboto", "Open Sans") |
| `weights` | string\|array | No | `['400']` | Font weights as space-separated string or array |
| `styles` | string\|array | No | `['normal']` | Font styles as space-separated string or array |
| `display` | string | No | `'swap'` | Font display strategy |

### Font Display Options

- `swap` (default) - Swap the fallback font with the custom font when ready
- `auto` - Let the browser decide the display strategy
- `block` - Block rendering until font loads (up to 3 seconds)
- `fallback` - Very short block period, short swap period
- `optional` - No block period, no swap period

## Examples

### Single Weight

```twig
{{ google_fonts('Roboto', '400') }}
```

Renders:
```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400&display=swap" rel="stylesheet">
<style>
:root {
  --font-family-roboto: 'Roboto', sans-serif;
}

body {
  font-family: var(--font-family-roboto);
  font-weight: 400;
}

h1, h2, h3, h4, h5, h6 {
  font-family: var(--font-family-roboto);
  font-weight: 700;
}

strong, b {
  font-weight: 700;
}
</style>
```

### Multiple Weights

```twig
{{ google_fonts('Ubuntu', '300 400 500 700') }}
```

### With Italic Styles

```twig
{{ google_fonts('Open Sans', '400 700', 'normal italic') }}
```

### Custom Display Strategy

```twig
{{ google_fonts('Inter', '400 600', 'normal', 'optional') }}
```

### Using Arrays

```twig
{{ google_fonts('Roboto', [300, 400, 500, 700], ['normal', 'italic']) }}
```

### Multiple Fonts

Load multiple fonts by calling the function multiple times:

```twig
{# Heading font #}
{{ google_fonts('Montserrat', '600 700 800') }}

{# Body font #}
{{ google_fonts('Open Sans', '400 600', 'normal italic') }}

{# Code font #}
{{ google_fonts('JetBrains Mono', '400 500') }}
```

## CSS Variables

The function automatically creates CSS variables for each font:

```twig
{{ google_fonts('Ubuntu') }}
```

Creates:
```css
:root {
  --font-family-ubuntu: 'Ubuntu', sans-serif;
}
```

Use it in your custom CSS:

```css
.my-heading {
  font-family: var(--font-family-ubuntu);
}
```

## Development vs Production

### Development Mode (Default)

In development, fonts are loaded from Google Fonts CDN:

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400&display=swap" rel="stylesheet">
<style>
  /* Inline CSS with font styling */
</style>
```

**Benefits:**
- Fast iteration
- No build step required
- Always up-to-date fonts

### Production Mode (with Locked Fonts)

In production with `use_locked_fonts: true`:

```html
<link rel="stylesheet" href="/assets/fonts/ubuntu/ubuntu.css">
<link rel="stylesheet" href="/assets/fonts/ubuntu/ubuntu-styles.css">
```

**Benefits:**
- Better performance (no external requests)
- Improved privacy (no Google tracking)
- Offline support
- Consistent font versions

See [Production Setup](production.md) for configuration details.

## Advanced Usage

### Conditional Font Loading

```twig
{% if app.environment == 'dev' %}
  {{ google_fonts('Ubuntu', '300 400 700') }}
{% endif %}
```

### Font Loading for Specific Pages

```twig
{% block stylesheets %}
  {% if app.request.attributes.get('_route') == 'blog_post' %}
    {{ google_fonts('Merriweather', '400 700', 'normal italic') }}
  {% endif %}
{% endblock %}
```

### Using with AssetMapper

If you use Symfony AssetMapper, locked fonts are automatically served:

```yaml
# config/packages/prod/google_fonts.yaml
google_fonts:
  use_locked_fonts: true
  fonts_dir: '%kernel.project_dir%/assets/fonts'
```

Fonts in `assets/fonts/` are automatically versioned and served by AssetMapper.

## Best Practices

1. **Minimize Font Weights**: Only load weights you actually use
2. **Lock Fonts for Production**: Always use locked fonts in production
3. **Use CSS Variables**: Leverage generated CSS variables for consistency
4. **Limit Font Families**: Use 2-3 font families maximum per project
5. **Preconnect in Dev**: Keep `preconnect: true` for faster CDN loading
6. **Use `display: swap`**: Best balance between performance and experience

## Troubleshooting

### Fonts Not Loading in Development

Check that:
1. Function is called in `<head>` section
2. No CSP (Content Security Policy) blocking Google Fonts
3. Network connection available

### Fonts Not Loading in Production

Check that:
1. `use_locked_fonts: true` is set in production config
2. Fonts were locked with `php bin/console gfonts:lock`
3. `assets/fonts/` directory is accessible to web server
4. Manifest file `assets/fonts.json` exists

### Wrong Font Displayed

Check that:
1. Font name matches exactly (case-sensitive)
2. Weights are available for that font family
3. CSS specificity isn't overriding font styles

## Next Steps

- [Commands](commands.md) - CLI command reference
- [Configuration](configuration.md) - Detailed configuration options
- [Production Setup](production.md) - Deploy with locked fonts

