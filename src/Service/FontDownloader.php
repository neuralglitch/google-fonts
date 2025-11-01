<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Service;

use NeuralGlitch\GoogleFonts\Exception\FontDownloadException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FontDownloader
{
    public function __construct(
        private readonly string $fontsDir,
        private readonly HttpClientInterface $httpClient,
        private readonly GoogleFontsApi $api,
        private readonly Filesystem $filesystem
    ) {
    }

    public function getApi(): GoogleFontsApi
    {
        return $this->api;
    }

    /**
     * Download and save a font.
     *
     * @param array<int|string> $weights
     * @param array<string>     $styles
     *
     * @return array{files: array<string, string>, css: string, cssPath: string, downloadedWeights: array<int>} Font files and CSS content
     */
    public function downloadFont(
        string $fontName,
        array $weights,
        array $styles,
        string $display = 'swap',
        bool $monospace = false
    ): array {
        // Ensure fonts directory exists
        $this->filesystem->mkdir($this->fontsDir, 0755);

        $sanitizedName = FontVariantHelper::sanitizeFontName($fontName);

        try {
            // Download CSS
            $css = $this->api->downloadFontCss($fontName, $weights, $styles, $display);
        } catch (HttpExceptionInterface|TransportExceptionInterface $e) {
            throw new FontDownloadException(sprintf('Failed to download CSS for font "%s": %s', $fontName, $e->getMessage()), 0, $e);
        }

        // Prepare weight and style mappings for file naming
        $weightsMap = array_map(fn ($w) => (int) $w, $weights);
        $hasItalic = in_array('italic', $styles, true);

        // Extract font URLs from CSS and download files
        $files = [];
        $downloadedWeights = []; // Track actually downloaded weights
        $weightIndex = 0;
        $processedCss = preg_replace_callback(
            '/url\(([^)]+)\)/',
            function (array $matches) use (&$files, &$downloadedWeights, &$weightIndex, $sanitizedName, $weightsMap, $hasItalic, $monospace): string {
                $url = trim($matches[1], '\'"');

                try {
                    // Download font file
                    $response = $this->httpClient->request('GET', $url);
                    $content = $response->getContent();
                } catch (HttpExceptionInterface|TransportExceptionInterface $e) {
                    throw new FontDownloadException(sprintf('Failed to download font file "%s": %s', $url, $e->getMessage()), 0, $e);
                }

                // Determine file extension
                $extension = '.woff2'; // Default to woff2
                if (preg_match('/\.(woff2?|ttf|eot|otf)$/i', $url, $extMatch)) {
                    $extension = strtolower($extMatch[0]);
                }

                // Determine weight and style from URL or use from array
                $weight = $weightsMap[$weightIndex % count($weightsMap)] ?? 400;
                $isItalic = $hasItalic && (1 === $weightIndex % 2);

                // Generate descriptive filename: roboto-400.woff2, roboto-700-italic.woff2, jetbrains-500-mono.woff2
                // Extract base name without "mono" suffix if font name contains mono keywords
                $baseName = $sanitizedName;
                if ($monospace && preg_match('/-mono$/', $baseName)) {
                    $baseName = preg_replace('/-mono$/', '', $baseName);
                }

                $parts = [$baseName, (string) $weight];
                if ($isItalic) {
                    $parts[] = 'italic';
                }
                if ($monospace) {
                    $parts[] = 'mono';
                }
                $baseFilename = implode('-', $parts) . $extension;

                // Handle duplicate filenames by adding a counter
                $filename = $baseFilename;
                $counter = 1;
                while (isset($files[$filename])) {
                    $filename = implode('-', $parts) . '-' . $counter . $extension;
                    ++$counter;
                }

                $filePath = $this->fontsDir . '/' . $filename;
                $this->filesystem->dumpFile($filePath, $content);

                $files[$filename] = $filePath;

                // Track the actual weight that was downloaded
                if (!in_array($weight, $downloadedWeights, true)) {
                    $downloadedWeights[] = $weight;
                }

                ++$weightIndex;

                // Update CSS to use relative path (relative to the CSS file)
                return sprintf('url("./%s")', $filename);
            },
            $css
        );

        if (!is_string($processedCss)) {
            throw new FontDownloadException('Failed to process CSS file URLs');
        }

        // Generate intelligent CSS rules
        $stylesheetCss = $this->generateStylesheetCss($fontName, $weights, $styles, $monospace);

        // Combine @font-face declarations and intelligent styles into one CSS file
        $combinedCss = $processedCss . "\n\n" . $stylesheetCss;

        // Save combined CSS file
        $cssPath = $this->fontsDir . '/' . $sanitizedName . '.css';
        $this->filesystem->dumpFile($cssPath, $combinedCss);

        // Sort downloaded weights
        sort($downloadedWeights);

        return [
            'files' => $files,
            'css' => $combinedCss,
            'cssPath' => $cssPath,
            'downloadedWeights' => $downloadedWeights,
        ];
    }

    /**
     * Generate intelligent CSS rules for the font.
     *
     * @param array<int|string> $weights
     * @param array<string>     $styles
     */
    private function generateStylesheetCss(
        string $fontName,
        array $weights,
        array $styles,
        bool $monospace = false
    ): string {
        $fontVar = '--font-family-' . FontVariantHelper::sanitizeFontName($fontName);
        $fallbackFamily = $monospace ? 'monospace' : 'sans-serif';
        $fontFamily = sprintf("'%s', %s", $fontName, $fallbackFamily);

        // Determine weights
        $defaultWeight = !empty($weights) ? (int) reset($weights) : 400;

        $lines = [
            ':root {',
            "  {$fontVar}: {$fontFamily};",
            '}',
            '',
        ];

        if ($monospace) {
            // Apply to code elements
            $lines = array_merge($lines, [
                'code, pre, kbd, samp, var, tt {',
                "  font-family: var({$fontVar});",
                "  font-weight: {$defaultWeight};",
                '}',
            ]);
        } else {
            // Find heading weight (first weight > 500, or 700)
            $headingWeight = 700;
            foreach ($weights as $weight) {
                $w = (int) $weight;
                if ($w > 500) {
                    $headingWeight = $w;

                    break;
                }
            }

            // Find bold weight (first weight >= 700, or 700)
            $boldWeight = 700;
            foreach ($weights as $weight) {
                $w = (int) $weight;
                if ($w >= 700) {
                    $boldWeight = $w;

                    break;
                }
            }

            // Apply to body and headings
            $lines = array_merge($lines, [
                'body {',
                "  font-family: var({$fontVar});",
                "  font-weight: {$defaultWeight};",
                '}',
                '',
                'h1, h2, h3, h4, h5, h6 {',
                "  font-family: var({$fontVar});",
                "  font-weight: {$headingWeight};",
                '}',
                '',
                'strong, b {',
                "  font-weight: {$boldWeight};",
                '}',
            ]);
        }

        return implode("\n", $lines);
    }
}
