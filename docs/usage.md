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
  
  {# Normal font for body and headings #}
  {{ google_fonts('Ubuntu', '300 400 500 700', 'normal italic') }}
  
  {# Monospace font for code elements #}
  {{ google_fonts('JetBrains Mono', '400 500', 'normal', null, true) }}
  
  {% block stylesheets %}{% endblock %}
</head>
<body>
  {% block body %}{% endblock %}
</body>
</html>
```

## Function Signature

```twig
{{ google_fonts(name, weights, styles, display, monospace) }}
```

### Parameters

| Parameter   | Type          | Required | Default      | Description                                              |
|-------------|---------------|----------|--------------|----------------------------------------------------------|
| `name`      | string        | Yes      | -            | Font family name (e.g., "Ubuntu", "Roboto", "Open Sans") |
| `weights`   | string\|array | No       | `['400']`    | Font weights as space-separated string or array          |
| `styles`    | string\|array | No       | `['normal']` | Font styles as space-separated string or array           |
| `display`   | string        | No       | `'swap'`     | Font display strategy                                    |
| `monospace` | bool          | No       | `false`      | Whether this is a monospace font for code elements       |

### Font Display Options

- `swap` (default) - Swap the fallback font with the custom font when ready
- `auto` - Let the browser decide the display strategy
- `block` - Block rendering until font loads (up to 3 seconds)
- `fallback` - Very short block period, short swap period
- `optional` - No block period, no swap period

### Monospace Font Usage

When you set the `monospace` parameter to `true`, the font will be applied to code-related elements instead of
body/headings:

```twig
{# Apply to code elements: code, pre, kbd, samp, var, tt #}
{{ google_fonts('Fira Code', '400 500', 'normal', null, true) }}
```

**Generated CSS (development):**

```css
:root {
  --font-family-fira-code: 'Fira Code', monospace;
}

code, pre, kbd, samp, var, tt {
  font-family: var(--font-family-fira-code);
  font-weight: 400;
}
```

**Common Monospace Fonts:**

- JetBrains Mono
- Fira Code
- Source Code Pro
- Roboto Mono
- IBM Plex Mono
- Inconsolata

## Complete Examples

### Basic Website with Normal + Monospace Fonts

```twig
{# templates/base.html.twig #}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{% block title %}My Website{% endblock %}</title>
    
    {# Normal font for body and headings #}
    {{ google_fonts('Inter', '300 400 500 600 700', 'normal') }}
    
    {# Monospace font for code elements #}
    {{ google_fonts('JetBrains Mono', '400 500', 'normal', null, true) }}
    
    {% block stylesheets %}{% endblock %}
</head>
<body>
    <header>
        <h1>Welcome to My Website</h1>
    </header>
    
    <main>
        {% block content %}
            <p>This text uses Inter font.</p>
            <pre><code>This code uses JetBrains Mono font.</code></pre>
        {% endblock %}
    </main>
</body>
</html>
```

### Blog with Serif + Monospace Fonts

```twig
{# templates/blog/base.html.twig #}
<!DOCTYPE html>
<html>
<head>
    {# Serif font for elegant reading #}
    {{ google_fonts('Crimson Pro', '300 400 600 700', 'normal italic') }}
    
    {# Monospace for code snippets #}
    {{ google_fonts('Fira Code', '400 500 600', 'normal', null, true) }}
</head>
<body>
    <article>
        <h1>{{ post.title }}</h1>
        <p>{{ post.content }}</p>
        <pre><code>{{ post.codeSnippet }}</code></pre>
    </article>
</body>
</html>
```

### Technical Documentation

```twig
{# templates/docs/base.html.twig #}
<!DOCTYPE html>
<html>
<head>
    {# Clean sans-serif for documentation #}
    {{ google_fonts('IBM Plex Sans', '300 400 500 600 700', 'normal') }}
    
    {# Matching monospace from same family #}
    {{ google_fonts('IBM Plex Mono', '400 500 600', 'normal', null, true) }}
</head>
<body>
    <nav>
        <h2>Documentation</h2>
    </nav>
    <main>
        {% block docs %}{% endblock %}
    </main>
</body>
</html>
```

### Portfolio with Modern Fonts

```twig
{# templates/portfolio/base.html.twig #}
<!DOCTYPE html>
<html>
<head>
    {# Modern geometric sans-serif #}
    {{ google_fonts('Outfit', '300 400 500 600 700 800', 'normal') }}
    
    {# Modern monospace #}
    {{ google_fonts('Source Code Pro', '400 500 600', 'normal', null, true) }}
</head>
<body>
    <h1>John Doe - Developer</h1>
    <section>
        <h2>Projects</h2>
        <pre><code class="language-php">
echo "Hello World";
        </code></pre>
    </section>
</body>
</html>
```

## Quick Examples

### Single Weight

```twig
{{ google_fonts('Roboto', '400') }}
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
{{ google_fonts('JetBrains Mono', '400 500', 'normal', null, true) }}
```

## Font Pairing Recommendations

### Modern & Clean
- **Normal**: Inter, Roboto, Open Sans
- **Mono**: JetBrains Mono, Fira Code, Source Code Pro

### Elegant & Professional
- **Normal**: Crimson Pro, Lora, Playfair Display
- **Mono**: IBM Plex Mono, Roboto Mono

### Technical & Sharp
- **Normal**: IBM Plex Sans, Noto Sans, Work Sans
- **Mono**: IBM Plex Mono, Inconsolata, Space Mono

### Friendly & Approachable
- **Normal**: Nunito, Quicksand, Poppins
- **Mono**: Overpass Mono, Ubuntu Mono

## CSS Selectors Reference

### Normal Font (monospace: false)
Applied to:
- `body`
- `h1, h2, h3, h4, h5, h6`
- `strong, b` (weight only)

### Monospace Font (monospace: true)
Applied to:
- `code`
- `pre`
- `kbd`
- `samp`
- `var`
- `tt`

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

### Custom Display Strategy

```twig
{# Use 'optional' for non-critical fonts #}
{{ google_fonts('Roboto', '400', 'normal', 'optional') }}
```

### Multiple Styles

```twig
{# Include italic variants #}
{{ google_fonts('Merriweather', '300 400 700', 'normal italic') }}
{{ google_fonts('Fira Code', '400 500', 'normal', null, true) }}
```

### Minimal Weights for Performance

```twig
{# Only load exactly what you need #}
{{ google_fonts('Inter', '400 600', 'normal') }}
{{ google_fonts('JetBrains Mono', '400', 'normal', null, true) }}
```

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

