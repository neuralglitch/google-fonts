# Migration from neuralglitch/google-fonts

**`neuralglitch/google-fonts` is abandoned** on Packagist (replacement: **`symfinity/font-manager`**). There is **no** `symfinity/google-fonts` package. This repository is **archived** on GitHub — use [symfinity/font-manager](https://github.com/symfinity/font-manager) for issues, releases, and documentation.

## Package identity

| Item | Legacy (`neuralglitch/*`) | Symfinity (`symfinity/*`) |
|------|---------------------------|---------------------------|
| Composer name | `neuralglitch/google-fonts` | `symfinity/font-manager` |
| GitHub | [neuralglitch/google-fonts](https://github.com/neuralglitch/google-fonts) (archived) | [symfinity/font-manager](https://github.com/symfinity/font-manager) |
| PSR-4 namespace | `NeuralGlitch\GoogleFonts\` | `Symfinity\FontManager\` |
| Bundle class | `NeuralGlitch\GoogleFonts\GoogleFontsBundle` | `Symfinity\FontManager\FontManagerBundle` |
| Config root key | `google_fonts:` | `font_manager:` |
| Config file | `config/packages/google_fonts.yaml` | `config/packages/font_manager.yaml` |
| Manifest | `var/google-fonts.lock.json` | `var/font-manager.lock.json` |

## Composer and Symfony floor

| Constraint | Legacy (last release) | Symfinity successor |
|------------|----------------------|---------------------|
| PHP | `>=8.1` | `>=8.2` |
| Symfony | `^6.4 \|\| ^7.0 \|\| ^8.0` | `^7.4` (org consumer floor) |

## Automated migration (recommended)

Install font-manager and run the built-in migration command:

```bash
composer remove neuralglitch/google-fonts
composer require symfinity/font-manager
php bin/console fonts:migrate-from-google-fonts --dry-run
php bin/console fonts:migrate-from-google-fonts
```

**What it does:**

- Converts `google_fonts.yaml` → `font_manager.yaml`
- Updates templates: `google_fonts()` → `font_manager()`
- Migrates manifest: `google-fonts.lock.json` → `font-manager.lock.json`
- Creates backups and prints a change summary

**Options:**

```bash
php bin/console fonts:migrate-from-google-fonts --skip-templates
php bin/console fonts:migrate-from-google-fonts --skip-config
```

Add the [symfinity/recipes](https://github.com/symfinity/recipes) Flex endpoint before `composer require symfinity/font-manager` (see [recipes README](https://github.com/symfinity/recipes/blob/main/README.md)). Legacy installs used [neuralglitch/symfony-recipes](https://github.com/neuralglitch/symfony-recipes).

## Manual mapping

| Legacy (`google-fonts`) | Symfinity (`font-manager`) |
|-------------------------|----------------------------|
| `google_fonts()` Twig helper | `font_manager()` |
| `gfonts:search` | `fonts:search --provider=google` |
| `gfonts:import` | `fonts:search --provider=google` |
| `gfonts:lock` | `fonts:lock` |
| `gfonts:status` | `fonts:status` |
| `gfonts:prune` | `fonts:prune` |
| `gfonts:validate` | `fonts:validate` |
| `gfonts:warmup-cache` | *(removed — automatic caching)* |

### Configuration example

**Before:**

```yaml
# config/packages/google_fonts.yaml
google_fonts:
    lock_fonts: false
    fonts_dir: '%kernel.project_dir%/assets/fonts'
    manifest_path: '%kernel.project_dir%/var/google-fonts.lock.json'
    use_locked_fonts: false
```

**After:**

```yaml
# config/packages/font_manager.yaml
font_manager:
    default_provider: 'google'
    lock_fonts: false
    fonts_dir: '%kernel.project_dir%/assets/fonts'
    manifest_path: '%kernel.project_dir%/var/font-manager.lock.json'
    use_locked_fonts: false
```

### Templates

```twig
{# Before #}
{{ google_fonts('Roboto', '400 700', 'normal') }}

{# After #}
{{ font_manager('Roboto', '400 700', 'normal', 'swap', false, 'google') }}
```

## Advantages of font-manager

- **Multiple providers** — Google, Bunny (GDPR), Fontsource, local fonts
- **Same Google Fonts catalog** when `default_provider: 'google'`
- **Privacy option** — switch to Bunny Fonts without template changes
- **Multi-format export** — CSS variables, SCSS Bootstrap, Tailwind, TypeScript, design tokens

## Migrating from neuralglitch/font-manager instead?

If you already use **`neuralglitch/font-manager`**, see [Migration from neuralglitch/font-manager](https://github.com/symfinity/font-manager/blob/main/docs/migration.md) on the successor repo.

## Successor documentation

Full handbook for `symfinity/font-manager`:

- [Quickstart](https://github.com/symfinity/font-manager/blob/main/docs/quickstart.md)
- [Installation](https://github.com/symfinity/font-manager/blob/main/docs/installation.md)
- [Configuration](https://github.com/symfinity/font-manager/blob/main/docs/configuration.md)
- [Providers](https://github.com/symfinity/font-manager/blob/main/docs/providers.md)
- [Commands](https://github.com/symfinity/font-manager/blob/main/docs/commands.md)
