<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Twig;

use NeuralGlitch\GoogleFonts\Service\FontVariantHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class GoogleFontsExtension extends AbstractExtension
{
    /** @var array<string, bool>|null */
    private static ?array $manifestCache = null;
    private static ?int $manifestMtime = null;

    /**
     * @param array<string, mixed> $defaults
     */
    public function __construct(
        private readonly string $environment,
        private readonly bool $useLockedFonts,
        private readonly ?string $manifestFile = null,
        private readonly array $defaults = []
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('google_fonts', [$this, 'renderFonts'], [
                'is_safe' => ['html'],
            ]),
        ];
    }

    /**
     * Render Google Fonts or locked fonts
     *
     * @param string $name Font family name (e.g., "Ubuntu", "Roboto")
     * @param array<int|string>|string $weights Font weights (e.g., "300 400 500 700" or [300, 400, 500, 700])
     * @param array<string>|string $styles Font styles (e.g., "normal italic" or ["normal", "italic"])
     * @param string|null $display Font display value (default: "swap")
     * @return string HTML string with font links and styles
     */
    public function renderFonts(
        string $name,
        array|string $weights = ['400'],
        array|string $styles = ['normal'],
        ?string $display = null
    ): string {
        // Normalize weights and styles
        $normalizedWeights = $this->normalizeArray($weights);
        $normalizedStylesRaw = $this->normalizeArray($styles);
        // Cast styles to string array
        $normalizedStyles = array_map('strval', $normalizedStylesRaw);
        
        $displayValue = $this->defaults['display'] ?? 'swap';
        $display = is_string($display) ? $display : (is_string($displayValue) ? $displayValue : 'swap');
        
        $preconnectValue = $this->defaults['preconnect'] ?? true;
        $preconnect = is_bool($preconnectValue) ? $preconnectValue : true;

        // Check if we should use locked fonts
        if ($this->useLockedFonts && $this->environment === 'prod' && $this->hasLockedFonts($name)) {
            return $this->renderLockedFonts($name);
        }

        return $this->renderGoogleFonts($name, $normalizedWeights, $normalizedStyles, $display, $preconnect);
    }

    /**
     * Render Google Fonts CDN links with inline styles (development)
     *
     * @param array<int|string> $weights
     * @param array<string> $styles
     */
    private function renderGoogleFonts(
        string $name,
        array $weights,
        array $styles,
        string $display,
        bool $preconnect
    ): string {
        $family = str_replace(' ', '+', $name);
        $variants = FontVariantHelper::generateVariants($weights, $styles);

        $url = sprintf(
            'https://fonts.googleapis.com/css2?family=%s:%s&display=%s',
            $family,
            implode(';', $variants),
            $display
        );

        $fontVar = '--font-family-' . FontVariantHelper::sanitizeFontName($name);
        $defaultWeight = !empty($weights) ? (int)reset($weights) : 400;
        $headingWeight = $this->findWeight($weights, 500, 700);
        $boldWeight = $this->findWeight($weights, 700, 700);

        $parts = [];
        if ($preconnect) {
            $parts[] = '<link rel="preconnect" href="https://fonts.googleapis.com">';
            $parts[] = '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
        }
        $parts[] = sprintf('<link href="%s" rel="stylesheet">', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));

        $cssLines = [
            ':root {',
            sprintf('  %s: \'%s\', sans-serif;', $fontVar, $name),
            '}',
            '',
            'body {',
            sprintf('  font-family: var(%s);', $fontVar),
            sprintf('  font-weight: %d;', $defaultWeight),
            '}',
            '',
            'h1, h2, h3, h4, h5, h6 {',
            sprintf('  font-family: var(%s);', $fontVar),
            sprintf('  font-weight: %d;', $headingWeight),
            '}',
            '',
            'strong, b {',
            sprintf('  font-weight: %d;', $boldWeight),
            '}',
        ];

        $parts[] = '<style>' . "\n" . implode("\n", $cssLines) . "\n" . '</style>';

        return implode("\n  ", $parts);
    }

    /**
     * Render locked/local fonts (production)
     */
    private function renderLockedFonts(string $name): string
    {
        $fontDir = FontVariantHelper::sanitizeFontName($name);

        return sprintf(
            '<link rel="stylesheet" href="/assets/fonts/%s/%s.css">' . "\n  " .
            '<link rel="stylesheet" href="/assets/fonts/%s/%s-styles.css">',
            $fontDir,
            $fontDir,
            $fontDir,
            $fontDir
        );
    }

    /**
     * Normalize input to array
     *
     * @param array<int|string>|string $input
     * @return array<int|string>
     */
    private function normalizeArray(array|string $input): array
    {
        return FontVariantHelper::normalizeArray($input);
    }

    /**
     * Find weight that meets criteria
     *
     * @param array<int|string> $weights
     */
    private function findWeight(array $weights, int $min, int $default): int
    {
        foreach ($weights as $weight) {
            $w = (int)$weight;
            if ($w > $min) {
                return $w;
            }
        }
        return $default;
    }

    /**
     * Check if font is locked (with caching for performance)
     */
    private function hasLockedFonts(string $fontName): bool
    {
        if (!$this->manifestFile || !file_exists($this->manifestFile)) {
            return false;
        }

        // Check if manifest file has been modified
        $mtime = filemtime($this->manifestFile);
        if ($mtime === false) {
            return false;
        }

        if (self::$manifestCache === null || self::$manifestMtime !== $mtime) {
            $content = file_get_contents($this->manifestFile);
            if ($content === false) {
                return false;
            }

            $manifest = json_decode($content, true);
            if (!is_array($manifest)) {
                return false;
            }

            // Build lookup cache: font name => bool
            self::$manifestCache = [];
            if (isset($manifest['fonts']) && is_array($manifest['fonts'])) {
                foreach (array_keys($manifest['fonts']) as $font) {
                    self::$manifestCache[$font] = true;
                }
            }

            self::$manifestMtime = $mtime;
        }

        return isset(self::$manifestCache[$fontName]);
    }
}

