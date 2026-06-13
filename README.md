<div align="center">

# Google Fonts

### Google Fonts integration for Symfony

[![PHP Version](https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat&logo=php&logoColor=white)](composer.json)
[![Symfony](https://img.shields.io/badge/Symfony-6.4+-343434?style=flat&logo=symfony&logoColor=white)](composer.json)
<br/>
[![Release](https://img.shields.io/packagist/v/neuralglitch/google-fonts.svg?style=flat&logo=packagist&logoColor=white)](https://packagist.org/packages/neuralglitch/google-fonts)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat)](LICENSE)

</div>

> [!WARNING]
>
> `neuralglitch/google-fonts` is
>
> - **abandoned** on [Packagist](https://packagist.org/packages/neuralglitch/google-fonts)
> - **read-only / archived** on [GitHub](https://github.com/neuralglitch/google-fonts)
>
> Replacement: `symfinity/font-manager`
>
> - New installs, issues, and releases: [symfinity/font-manager](https://github.com/symfinity/font-manager)
> - Migration: [docs/migration.md](docs/migration.md)

## Features (legacy)

- **Twig Function** — `{{ google_fonts() }}`
- **Development Mode** — Google Fonts CDN with inline styles
- **Production Mode** — Lock fonts locally for performance and privacy
- **Smart CSS** — Automatic styling for body, headings, and bold text
- **CLI Tools** — Search, import, lock, and warm-cache commands

The successor **`symfinity/font-manager`** adds Bunny Fonts, Fontsource, local fonts, and multi-format export — see [successor handbook](https://github.com/symfinity/font-manager/tree/main/docs).

## New projects

Do **not** install this package. Use:

```bash
composer require symfinity/font-manager
```

Add the [symfinity/recipes](https://github.com/symfinity/recipes) Flex endpoint first — see [successor installation guide](https://github.com/symfinity/font-manager/blob/main/docs/installation.md).

## Documentation (legacy tree)

Historical docs for the last `neuralglitch/*` release. For current Symfinity docs, use the [font-manager handbook](https://github.com/symfinity/font-manager/tree/main/docs).

- **[Migration from neuralglitch/google-fonts](docs/migration.md)** — upgrade to `symfinity/font-manager`
- **[Usage Guide](docs/usage.md)** — Legacy `google_fonts()` examples
- **[Commands](docs/commands.md)** — Legacy `gfonts:*` CLI reference
- **[Configuration](docs/configuration.md)** — Legacy configuration options
- **[Production Setup](docs/production.md)** — Deploying with locked fonts (legacy)
- **[Development](docs/development.md)** — Historical contributing notes

## Requirements (last legacy release)

- PHP 8.1 or higher
- Symfony 6.4, 7.x, or 8.x
- Twig 3.0 or higher

## Support

- **Issues / security:** [symfinity/font-manager](https://github.com/symfinity/font-manager/issues) — not this archived repo
- [Contributing](CONTRIBUTING.md) — historical; contributions go to symfinity/symfinity

## License

[MIT](LICENSE)
