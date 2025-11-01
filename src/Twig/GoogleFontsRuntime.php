<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Twig;

use NeuralGlitch\GoogleFonts\Service\FontVariantHelper;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class GoogleFontsRuntime implements RuntimeExtensionInterface
{
    /** @var array<string, bool>|null */
    private static ?array $manifestCache = null;
    private static ?int $manifestMtime = null;

    /**
     * @param array<string, mixed> $defaults
     */
    public function __construct(
        private readonly bool $useLockedFonts,
        private readonly ?string $manifestFile = null,
        private readonly array $defaults = [],
        private readonly ?AssetMapperInterface $assetMapper = null
    ) {
    }

    /**
     * Render Google Fonts or locked fonts.
     *
     * @param string                   $name      Font family name (e.g., "Ubuntu", "Roboto")
     * @param array<int|string>|string $weights   Font weights (e.g., "300 400 500 700" or [300, 400, 500, 700])
     * @param array<string>|string     $styles    Font styles (e.g., "normal italic" or ["normal", "italic"])
     * @param string|null              $display   Font display value (default: "swap")
     * @param bool                     $monospace Whether this is a monospace font for code elements (default: false)
     *
     * @return string HTML string with font links and styles
     */
    public function renderFonts(
        string $name,
        array|string $weights = ['400'],
        array|string $styles = ['normal'],
        ?string $display = null,
        bool $monospace = false,
        ?string $subsetText = null,
        ?bool $usePreload = null
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

        // Normalize optional parameters with defaults
        $effectivePreload = is_bool($usePreload) ? $usePreload : false;
        $effectiveText = is_string($subsetText) ? $subsetText : null;

        // Check if we should use locked fonts (controlled by config)
        if ($this->useLockedFonts && $this->hasLockedFonts($name)) {
            return $this->renderLockedFonts($name, $monospace, $effectivePreload);
        }

        return $this->renderGoogleFonts(
            $name,
            $normalizedWeights,
            $normalizedStyles,
            $display,
            $preconnect,
            $monospace,
            $effectiveText,
            $effectivePreload
        );
    }

    /**
     * Render Google Fonts CDN links with inline styles (development).
     *
     * @param array<int|string> $weights
     * @param array<string>     $styles
     */
    private function renderGoogleFonts(
        string $name,
        array $weights,
        array $styles,
        string $display,
        bool $preconnect,
        bool $monospace,
        ?string $text,
        bool $preload
    ): string {
        $family = str_replace(' ', '+', $name);
        $variants = FontVariantHelper::generateVariants($weights, $styles);

        $url = sprintf(
            'https://fonts.googleapis.com/css2?family=%s:%s&display=%s',
            $family,
            implode(';', $variants),
            $display
        );

        // Add text subsetting if provided
        if (null !== $text && '' !== $text) {
            $url .= '&text=' . urlencode($text);
        }

        $fontVar = '--font-family-' . FontVariantHelper::sanitizeFontName($name);
        $defaultWeight = !empty($weights) ? (int) reset($weights) : 400;
        $headingWeight = $this->findWeight($weights, 500, 700);
        $boldWeight = $this->findWeight($weights, 700, 700);

        $parts = [];
        if ($preconnect) {
            $parts[] = '<link rel="preconnect" href="https://fonts.googleapis.com">';
            $parts[] = '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
        }

        // Add preload hint if requested
        if ($preload) {
            $parts[] = sprintf(
                '<link rel="preload" href="%s" as="style">',
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            );
        }

        $parts[] = sprintf('<link href="%s" rel="stylesheet">', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));

        $fallbackFamily = $monospace ? 'monospace' : 'sans-serif';

        $cssLines = [
            ':root {',
            sprintf('  %s: \'%s\', %s;', $fontVar, $name, $fallbackFamily),
            '}',
            '',
        ];

        if ($monospace) {
            // Apply to code elements
            $cssLines = array_merge($cssLines, [
                'code, pre, kbd, samp, var, tt {',
                sprintf('  font-family: var(%s);', $fontVar),
                sprintf('  font-weight: %d;', $defaultWeight),
                '}',
            ]);
        } else {
            // Apply to body and headings
            $cssLines = array_merge($cssLines, [
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
            ]);
        }

        $parts[] = '<style>' . "\n" . implode("\n", $cssLines) . "\n" . '</style>';

        return implode("\n  ", $parts);
    }

    /**
     * Render locked/local fonts (production).
     */
    private function renderLockedFonts(string $name, bool $monospace, bool $preload): string
    {
        // Sanitize font name for file paths (converts to lowercase with hyphens)
        $sanitizedName = FontVariantHelper::sanitizeFontName($name);

        // Get asset path through AssetMapper for proper versioning
        $cssPath = $this->getAssetPath("fonts/{$sanitizedName}.css");

        $parts = [];

        // Add preload hint if requested
        if ($preload) {
            $parts[] = sprintf(
                '<link rel="preload" href="%s" as="style">',
                htmlspecialchars($cssPath, ENT_QUOTES, 'UTF-8')
            );
        }

        $parts[] = sprintf(
            '<link rel="stylesheet" href="%s">',
            htmlspecialchars($cssPath, ENT_QUOTES, 'UTF-8')
        );

        return implode("\n  ", $parts);
    }

    /**
     * Get asset path through AssetMapper (with versioning in prod).
     */
    private function getAssetPath(string $logicalPath): string
    {
        if (null === $this->assetMapper) {
            // Fallback if AssetMapper not available
            return '/assets/' . $logicalPath;
        }

        $asset = $this->assetMapper->getAsset($logicalPath);
        if (null === $asset) {
            // Asset not found, return logical path
            return '/assets/' . $logicalPath;
        }

        return $asset->publicPath;
    }

    /**
     * Normalize input to array.
     *
     * @param array<int|string>|string $input
     *
     * @return array<int|string>
     */
    private function normalizeArray(array|string $input): array
    {
        return FontVariantHelper::normalizeArray($input);
    }

    /**
     * Find weight that meets criteria.
     *
     * @param array<int|string> $weights
     */
    private function findWeight(array $weights, int $min, int $default): int
    {
        foreach ($weights as $weight) {
            $w = (int) $weight;
            if ($w > $min) {
                return $w;
            }
        }

        return $default;
    }

    /**
     * Check if font is locked (with caching for performance).
     */
    private function hasLockedFonts(string $fontName): bool
    {
        if (!$this->manifestFile || !file_exists($this->manifestFile)) {
            return false;
        }

        // Check if manifest file has been modified
        $mtime = filemtime($this->manifestFile);
        if (false === $mtime) {
            return false;
        }

        if (null === self::$manifestCache || self::$manifestMtime !== $mtime) {
            $content = file_get_contents($this->manifestFile);
            if (false === $content) {
                return false;
            }

            $manifest = json_decode($content, true);
            if (!is_array($manifest)) {
                return false;
            }

            // Build lookup cache: font name => bool (case-insensitive)
            self::$manifestCache = [];
            if (isset($manifest['fonts']) && is_array($manifest['fonts'])) {
                foreach (array_keys($manifest['fonts']) as $font) {
                    // Store in lowercase for case-insensitive lookup
                    self::$manifestCache[strtolower($font)] = true;
                }
            }

            self::$manifestMtime = $mtime;
        }

        // Case-insensitive lookup
        return isset(self::$manifestCache[strtolower($fontName)]);
    }
}
