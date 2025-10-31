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

    /**
     * Download and save a font
     *
     * @param array<int|string> $weights
     * @param array<string> $styles
     * @return array{files: array<string, string>, css: string, cssPath: string, stylesheetPath: string, stylesheetCss: string} Font files and CSS content
     */
    public function downloadFont(string $fontName, array $weights, array $styles, string $display = 'swap'): array
    {
        // Ensure fonts directory exists
        $this->filesystem->mkdir($this->fontsDir, 0755);

        $fontDir = $this->fontsDir . '/' . FontVariantHelper::sanitizeFontName($fontName);
        $this->filesystem->mkdir($fontDir, 0755);

        try {
            // Download CSS
            $css = $this->api->downloadFontCss($fontName, $weights, $styles, $display);
        } catch (HttpExceptionInterface|TransportExceptionInterface $e) {
            throw new FontDownloadException(
                sprintf('Failed to download CSS for font "%s": %s', $fontName, $e->getMessage()),
                0,
                $e
            );
        }

        // Extract font URLs from CSS and download files
        $files = [];
        $processedCss = preg_replace_callback(
            '/url\(([^)]+)\)/',
            function (array $matches) use (&$files, $fontDir, $fontName): string {
                $url = trim($matches[1], '\'"');

                try {
                    // Download font file
                    $response = $this->httpClient->request('GET', $url);
                    $content = $response->getContent();
                } catch (HttpExceptionInterface|TransportExceptionInterface $e) {
                    throw new FontDownloadException(
                        sprintf('Failed to download font file "%s": %s', $url, $e->getMessage()),
                        0,
                        $e
                    );
                }

                // Determine file extension and name
                $urlParts = parse_url($url);
                $pathParts = explode('/', $urlParts['path'] ?? '');
                $filename = end($pathParts);

                if (!$filename || !str_contains($filename, '.')) {
                    // Generate filename from URL
                    $filename = FontVariantHelper::sanitizeFontName($fontName) . '-' . md5($url) . '.woff2';
                }

                $filePath = $fontDir . '/' . $filename;
                $this->filesystem->dumpFile($filePath, $content);

                $files[$filename] = $filePath;

                // Update CSS to use relative path
                return sprintf('url("./%s")', $filename);
            },
            $css
        );

        if (!is_string($processedCss)) {
            throw new FontDownloadException('Failed to process CSS file URLs');
        }

        // Save CSS file (with @font-face declarations)
        $cssPath = $fontDir . '/' . FontVariantHelper::sanitizeFontName($fontName) . '.css';
        $this->filesystem->dumpFile($cssPath, $processedCss);

        // Generate and save stylesheet with intelligent CSS rules
        $stylesheetCss = $this->generateStylesheetCss($fontName, $weights, $styles);
        $stylesheetPath = $fontDir . '/' . FontVariantHelper::sanitizeFontName($fontName) . '-styles.css';
        $this->filesystem->dumpFile($stylesheetPath, $stylesheetCss);

        return [
            'files' => $files,
            'css' => $processedCss,
            'cssPath' => $cssPath,
            'stylesheetPath' => $stylesheetPath,
            'stylesheetCss' => $stylesheetCss,
        ];
    }

    /**
     * Generate intelligent CSS rules for the font
     *
     * @param array<int|string> $weights
     * @param array<string> $styles
     */
    private function generateStylesheetCss(string $fontName, array $weights, array $styles): string
    {
        $fontVar = '--font-family-' . FontVariantHelper::sanitizeFontName($fontName);
        $fontFamily = sprintf("'%s', sans-serif", $fontName);

        // Determine weights
        $defaultWeight = !empty($weights) ? (int)reset($weights) : 400;

        // Find heading weight (first weight > 500, or 700)
        $headingWeight = 700;
        foreach ($weights as $weight) {
            $w = (int)$weight;
            if ($w > 500) {
                $headingWeight = $w;
                break;
            }
        }

        // Find bold weight (first weight >= 700, or 700)
        $boldWeight = 700;
        foreach ($weights as $weight) {
            $w = (int)$weight;
            if ($w >= 700) {
                $boldWeight = $w;
                break;
            }
        }

        $lines = [
            ":root {",
            "  {$fontVar}: {$fontFamily};",
            "}",
            "",
            "body {",
            "  font-family: var({$fontVar});",
            "  font-weight: {$defaultWeight};",
            "}",
            "",
            "h1, h2, h3, h4, h5, h6 {",
            "  font-family: var({$fontVar});",
            "  font-weight: {$headingWeight};",
            "}",
            "",
            "strong, b {",
            "  font-weight: {$boldWeight};",
            "}",
        ];

        return implode("\n", $lines);
    }
}

