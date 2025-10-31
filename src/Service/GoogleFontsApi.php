<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GoogleFontsApi
{
    private const API_BASE = 'https://www.googleapis.com/webfonts/v1/webfonts';
    private const CSS_API = 'https://fonts.googleapis.com/css2';

    public function __construct(
        private readonly HttpClientInterface $httpClient
    ) {
    }

    /**
     * Search fonts by name.
     *
     * @return array<string, mixed>
     */
    public function searchFonts(string $query, int $maxResults = 20): array
    {
        $response = $this->httpClient->request('GET', self::API_BASE, [
            'query' => [
                'key' => '', // Public API, no key needed for basic access
                'sort' => 'popularity',
            ],
        ]);

        $data = $response->toArray();
        $fonts = $data['items'] ?? [];

        if ('' !== $query) {
            $queryLower = strtolower($query);
            $fonts = array_filter($fonts, function (array $font) use ($queryLower): bool {
                return false !== stripos($font['family'], $queryLower);
            });
        }

        return array_slice($fonts, 0, $maxResults);
    }

    /**
     * Get font metadata.
     *
     * @return array<string, mixed>|null
     */
    public function getFontMetadata(string $fontName): ?array
    {
        $response = $this->httpClient->request('GET', self::API_BASE);

        $data = $response->toArray();
        $fonts = $data['items'] ?? [];

        foreach ($fonts as $font) {
            if ($font['family'] === $fontName) {
                return $font;
            }
        }

        return null;
    }

    /**
     * Get available weights and styles for a font.
     *
     * @return array{weights: array<int>, styles: array<string>}
     */
    public function getFontVariants(string $fontName): array
    {
        $metadata = $this->getFontMetadata($fontName);

        if (!$metadata || !is_array($metadata)) {
            return ['weights' => [], 'styles' => []];
        }

        $variantsValue = $metadata['variants'] ?? null;
        $variants = is_array($variantsValue) ? $variantsValue : [];
        $weights = [];
        $styles = ['normal'];

        foreach ($variants as $variant) {
            if (!is_string($variant)) {
                continue;
            }
            // Parse variant like "300", "300italic", "regular", "italic", "700", "700italic"
            if (str_ends_with($variant, 'italic')) {
                $weightStr = substr($variant, 0, -6);
                $weight = '' !== $weightStr ? (int) $weightStr : 0;
                if (0 === $weight) {
                    $weight = 400;
                }
                $weights[] = $weight;
                if (!in_array('italic', $styles, true)) {
                    $styles[] = 'italic';
                }
            } else {
                $weight = '' !== $variant ? (int) $variant : 0;
                if (0 === $weight) {
                    $weight = 400; // "regular" variant
                }
                $weights[] = $weight;
            }
        }

        return [
            'weights' => array_unique($weights),
            'styles' => array_unique($styles),
        ];
    }

    /**
     * Download font CSS from Google Fonts.
     *
     * @param array<int|string> $weights
     * @param array<string>     $styles
     */
    public function downloadFontCss(string $fontName, array $weights, array $styles, string $display = 'swap'): string
    {
        $family = str_replace(' ', '+', $fontName);
        $variants = FontVariantHelper::generateVariants($weights, $styles);

        $url = sprintf(
            '%s?family=%s:%s&display=%s',
            self::CSS_API,
            $family,
            implode(';', $variants),
            $display
        );

        $response = $this->httpClient->request('GET', $url, [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; Symfony GoogleFonts)',
            ],
        ]);

        return $response->getContent();
    }
}
