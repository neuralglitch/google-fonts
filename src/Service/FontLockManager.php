<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Service;

use NeuralGlitch\GoogleFonts\Exception\FontDownloadException;
use NeuralGlitch\GoogleFonts\Exception\ManifestException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

final class FontLockManager
{
    public function __construct(
        /** @phpstan-ignore-next-line - Property is used indirectly via FontDownloader */
        private readonly string $fontsDir,
        private readonly string $manifestFile,
        private readonly FontDownloader $fontDownloader,
        private readonly Filesystem $filesystem
    ) {
    }

    /**
     * Scan Twig templates for google_fonts() function usage.
     *
     * @param string|array<string> $templateDirs
     *
     * @return array<string, array{weights: array<int|string>, styles: array<string>}>
     */
    public function scanTemplates($templateDirs): array
    {
        $fonts = [];
        $dirs = is_array($templateDirs) ? $templateDirs : [$templateDirs];

        $finder = new Finder();
        $finder->files()
            ->in($dirs)
            ->name('*.twig');

        foreach ($finder as $file) {
            // Use Filesystem::readFile() if available (Symfony 7.1+), otherwise file_get_contents()
            if (method_exists($this->filesystem, 'readFile')) {
                $content = $this->filesystem->readFile($file->getPathname());
            } else {
                $content = file_get_contents($file->getPathname());
                if (false === $content) {
                    continue;
                }
            }

            // Match google_fonts() function calls
            // Pattern: google_fonts('Font Name', 'weights', 'styles', 'display')
            // or: google_fonts('Font Name', ['weights'], ['styles'], 'display')
            preg_match_all(
                '/google_fonts\s*\(\s*([^)]+)\)/',
                $content,
                $matches
            );

            foreach ($matches[1] as $args) {
                $fontData = $this->parseFunctionArgs($args);

                if (!isset($fontData['name']) || !is_string($fontData['name'])) {
                    continue;
                }

                $fontName = trim($fontData['name'], '\'"');

                if (!isset($fonts[$fontName])) {
                    $fonts[$fontName] = [
                        'weights' => [],
                        'styles' => [],
                    ];
                }

                // Merge weights
                if (isset($fontData['weights']) && is_string($fontData['weights'])) {
                    $weights = $this->parseArrayOrString($fontData['weights']);
                    $fonts[$fontName]['weights'] = array_unique(
                        array_merge($fonts[$fontName]['weights'], $weights)
                    );
                } else {
                    // Default weight
                    if (empty($fonts[$fontName]['weights'])) {
                        $fonts[$fontName]['weights'] = ['400'];
                    }
                }

                // Merge styles
                if (isset($fontData['styles']) && is_string($fontData['styles'])) {
                    $styles = $this->parseArrayOrString($fontData['styles']);
                    $fonts[$fontName]['styles'] = array_unique(
                        array_merge($fonts[$fontName]['styles'], $styles)
                    );
                } else {
                    // Default style
                    if (empty($fonts[$fontName]['styles'])) {
                        $fonts[$fontName]['styles'] = ['normal'];
                    }
                }
            }
        }

        return $fonts;
    }

    /**
     * Lock fonts (download and generate manifest).
     *
     * @param array<string, array{weights: array<int|string>, styles: array<string>, monospace?: bool}> $fonts
     * @param callable(int, int, string): void|null                                                     $progressCallback
     *
     * @return array<string, mixed>
     */
    public function lockFonts(array $fonts, ?callable $progressCallback = null): array
    {
        $this->filesystem->mkdir(dirname($this->manifestFile), 0755);

        $manifest = [
            'locked' => true,
            'generated_at' => (new \DateTimeImmutable())->format('c'),
            'fonts' => [],
        ];

        $totalFonts = count($fonts);
        $currentFont = 0;

        foreach ($fonts as $fontName => $config) {
            if (!is_array($config)) {
                continue;
            }

            ++$currentFont;

            // Call progress callback if provided
            if (is_callable($progressCallback)) {
                call_user_func($progressCallback, $currentFont, $totalFonts, $fontName);
            }

            /** @var array{weights?: array<int|string>, styles?: array<string>, monospace?: bool} $config */
            $weightsValue = $config['weights'] ?? [];
            /** @var array<int|string> $weightsValue */
            $weights = array_map('intval', $weightsValue);
            $stylesValue = $config['styles'] ?? [];
            /** @var array<string> $stylesValue */
            $styles = array_map('strval', $stylesValue);
            $monospace = $config['monospace'] ?? false;

            try {
                $result = $this->fontDownloader->downloadFont($fontName, $weights, $styles, 'swap', $monospace);

                $sanitizedName = FontVariantHelper::sanitizeFontName($fontName);
                $relativeCssPath = 'assets/fonts/' . $sanitizedName . '.css';

                // Use actually downloaded weights, not requested weights
                $actualWeights = !empty($result['downloadedWeights']) ? $result['downloadedWeights'] : $weights;

                $manifest['fonts'][$fontName] = [
                    'weights' => $actualWeights,
                    'styles' => $styles,
                    'files' => array_keys($result['files']),
                    'css' => $relativeCssPath,
                    'monospace' => $monospace,
                ];
            } catch (FontDownloadException $e) {
                throw new FontDownloadException(sprintf('Failed to download font "%s": %s', $fontName, $e->getMessage()), 0, $e);
            }
        }

        // Save manifest
        $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (false === $json) {
            throw new ManifestException('Failed to encode manifest JSON');
        }

        $this->filesystem->dumpFile($this->manifestFile, $json);

        return $manifest;
    }

    /**
     * Parse function arguments.
     *
     * @return array<string, mixed>
     */
    private function parseFunctionArgs(string $args): array
    {
        $result = [];
        $args = trim($args);

        // Split by comma, but respect quoted strings and arrays
        $parts = $this->splitFunctionArgs($args);

        // First argument is always the font name
        if (isset($parts[0])) {
            $result['name'] = trim($parts[0], '\'"');
        }

        // Second argument is weights (optional)
        if (isset($parts[1])) {
            $result['weights'] = trim($parts[1]);
        }

        // Third argument is styles (optional)
        if (isset($parts[2])) {
            $result['styles'] = trim($parts[2]);
        }

        return $result;
    }

    /**
     * Split function arguments respecting quotes and arrays.
     *
     * @return array<string>
     */
    private function splitFunctionArgs(string $args): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $inQuotes = false;
        $quoteChar = null;

        for ($i = 0; $i < strlen($args); ++$i) {
            $char = $args[$i];

            if (!$inQuotes && ('\'' === $char || '"' === $char)) {
                $inQuotes = true;
                $quoteChar = $char;
                $current .= $char;
            } elseif ($inQuotes && $char === $quoteChar) {
                $inQuotes = false;
                $quoteChar = null;
                $current .= $char;
            } elseif (!$inQuotes && '[' === $char) {
                ++$depth;
                $current .= $char;
            } elseif (!$inQuotes && ']' === $char) {
                --$depth;
                $current .= $char;
            } elseif (!$inQuotes && 0 === $depth && ',' === $char) {
                $parts[] = trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if ('' !== $current) {
            $parts[] = trim($current);
        }

        return $parts;
    }

    /**
     * Parse array or string value.
     *
     * @return array<string>
     */
    private function parseArrayOrString(string $value): array
    {
        $value = trim($value);

        // Check if it's an array syntax
        if (preg_match('/^\s*\[(.*)\]\s*$/', $value, $matches)) {
            // Parse array elements
            $elements = [];
            $content = $matches[1];
            $parts = $this->splitFunctionArgs($content);

            foreach ($parts as $part) {
                $elements[] = trim($part, '\'"');
            }

            return $elements;
        }

        // It's a string, split by spaces
        return array_map('trim', explode(' ', trim($value, '\'"')));
    }

    public function getManifestFile(): string
    {
        return $this->manifestFile;
    }
}
