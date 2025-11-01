<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GoogleFontsApi
{
    private const API_BASE = 'https://www.googleapis.com/webfonts/v1/webfonts';
    private const CSS_API = 'https://fonts.googleapis.com/css2';

    /** @var array<string, array{data: mixed, expires: int}>|null */
    private static ?array $cache = null;
    private static int $cacheTtl = 3600; // 1 hour

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $apiKey = null,
        ?int $cacheTtl = null
    ) {
        if (null !== $cacheTtl) {
            self::$cacheTtl = $cacheTtl;
        }
    }

    /**
     * Clear the API cache.
     */
    public static function clearCache(): void
    {
        self::$cache = null;
    }

    /**
     * Search fonts by name.
     *
     * @return array<string, mixed>
     */
    public function searchFonts(string $query, int $maxResults = 20): array
    {
        if (!$this->apiKey) {
            throw new \RuntimeException('Google Fonts API key is required. Get your free API key at https://console.cloud.google.com/apis/credentials and configure it in config/packages/google_fonts.yaml under "api_key"');
        }

        // Check cache for full fonts list
        $cacheKey = 'fonts_list';
        $cached = $this->getFromCache($cacheKey);
        if (null !== $cached && is_array($cached)) {
            $allFonts = $cached;
        } else {
            $response = $this->httpClient->request('GET', self::API_BASE, [
                'query' => [
                    'key' => $this->apiKey,
                    'sort' => 'popularity',
                ],
            ]);

            $data = $response->toArray();
            $allFonts = $data['items'] ?? [];

            // Cache the full result
            $this->putInCache($cacheKey, $allFonts);
        }

        // Apply filtering AFTER caching
        if ('' !== $query) {
            $queryLower = strtolower($query);
            $fonts = array_filter($allFonts, function (array $font) use ($queryLower): bool {
                return false !== stripos($font['family'], $queryLower);
            });
        } else {
            $fonts = $allFonts;
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
        if (!$this->apiKey) {
            throw new \RuntimeException('Google Fonts API key is required. Get your free API key at https://console.cloud.google.com/apis/credentials and configure it in config/packages/google_fonts.yaml under "api_key"');
        }

        $response = $this->httpClient->request('GET', self::API_BASE, [
            'query' => [
                'key' => $this->apiKey,
                'sort' => 'popularity',
            ],
        ]);

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

    /**
     * Get data from cache.
     *
     * @return mixed|null
     */
    private function getFromCache(string $key): mixed
    {
        if (null === self::$cache) {
            self::$cache = [];
        }

        if (!isset(self::$cache[$key])) {
            return null;
        }

        $entry = self::$cache[$key];
        if ($entry['expires'] < time()) {
            unset(self::$cache[$key]);

            return null;
        }

        return $entry['data'];
    }

    /**
     * Put data in cache.
     */
    private function putInCache(string $key, mixed $data): void
    {
        if (null === self::$cache) {
            self::$cache = [];
        }

        self::$cache[$key] = [
            'data' => $data,
            'expires' => time() + self::$cacheTtl,
        ];
    }
}
