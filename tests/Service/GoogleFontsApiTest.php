<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Tests\Service;

use NeuralGlitch\GoogleFonts\Service\GoogleFontsApi;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class GoogleFontsApiTest extends TestCase
{
    protected function setUp(): void
    {
        GoogleFontsApi::clearCache();
    }

    public function testSearchFontsReturnsResults(): void
    {
        $jsonData = json_encode([
            'items' => [
                ['family' => 'Roboto', 'variants' => ['regular', '700'], 'category' => 'sans-serif'],
                ['family' => 'Open Sans', 'variants' => ['regular', '600'], 'category' => 'sans-serif'],
            ],
        ]);
        $mockResponse = new MockResponse(false !== $jsonData ? $jsonData : '{}');

        $httpClient = new MockHttpClient($mockResponse);
        $api = new GoogleFontsApi($httpClient, 'test-api-key');

        $results = $api->searchFonts('Roboto', 10);

        self::assertIsArray($results);
        self::assertNotEmpty($results);
        self::assertCount(1, $results);
        $firstResult = reset($results);
        self::assertTrue(is_array($firstResult));
        self::assertSame('Roboto', $firstResult['family']);
    }

    public function testSearchFontsWithEmptyQuery(): void
    {
        $mockResponse = new MockResponse(
            (string) json_encode([
                'items' => [
                    ['family' => 'Roboto', 'variants' => ['regular'], 'category' => 'sans-serif'],
                    ['family' => 'Ubuntu', 'variants' => ['regular'], 'category' => 'sans-serif'],
                ],
            ])
        );

        $httpClient = new MockHttpClient($mockResponse);
        $api = new GoogleFontsApi($httpClient, 'test-api-key');

        $results = $api->searchFonts('', 10);

        self::assertCount(2, $results);
    }

    public function testSearchFontsRespectsMaxResults(): void
    {
        $items = [];
        for ($i = 0; $i < 50; ++$i) {
            $items[] = ['family' => "Font{$i}", 'variants' => ['regular'], 'category' => 'sans-serif'];
        }

        $mockResponse = new MockResponse((string) json_encode(['items' => $items]));
        $httpClient = new MockHttpClient($mockResponse);
        $api = new GoogleFontsApi($httpClient, 'test-api-key');

        $results = $api->searchFonts('Font', 5);

        self::assertCount(5, $results);
    }

    public function testGetFontMetadataReturnsFont(): void
    {
        $mockResponse = new MockResponse(
            (string) json_encode([
                'items' => [
                    ['family' => 'Roboto', 'variants' => ['regular', '700'], 'category' => 'sans-serif'],
                    ['family' => 'Ubuntu', 'variants' => ['300', 'regular'], 'category' => 'sans-serif'],
                ],
            ])
        );

        $httpClient = new MockHttpClient($mockResponse);
        $api = new GoogleFontsApi($httpClient, 'test-api-key');

        $metadata = $api->getFontMetadata('Ubuntu');

        self::assertIsArray($metadata);
        self::assertSame('Ubuntu', $metadata['family']);
        self::assertSame('sans-serif', $metadata['category']);
    }

    public function testGetFontMetadataReturnsNullWhenNotFound(): void
    {
        $mockResponse = new MockResponse(
            (string) json_encode([
                'items' => [
                    ['family' => 'Roboto', 'variants' => ['regular'], 'category' => 'sans-serif'],
                ],
            ])
        );

        $httpClient = new MockHttpClient($mockResponse);
        $api = new GoogleFontsApi($httpClient, 'test-api-key');

        $metadata = $api->getFontMetadata('NonExistent');

        self::assertNull($metadata);
    }

    public function testGetFontVariantsReturnsWeightsAndStyles(): void
    {
        $mockResponse = new MockResponse(
            (string) json_encode([
                'items' => [
                    ['family' => 'Roboto', 'variants' => ['300', 'regular', '700', '300italic', 'italic', '700italic']],
                ],
            ])
        );

        $httpClient = new MockHttpClient($mockResponse);
        $api = new GoogleFontsApi($httpClient, 'test-api-key');

        $variants = $api->getFontVariants('Roboto');

        self::assertIsArray($variants);
        self::assertArrayHasKey('weights', $variants);
        self::assertArrayHasKey('styles', $variants);
        self::assertContains(300, $variants['weights']);
        self::assertContains(400, $variants['weights']);
        self::assertContains(700, $variants['weights']);
        self::assertContains('normal', $variants['styles']);
        self::assertContains('italic', $variants['styles']);
    }

    public function testGetFontVariantsHandlesRegularVariant(): void
    {
        $mockResponse = new MockResponse(
            (string) json_encode([
                'items' => [
                    ['family' => 'Roboto', 'variants' => ['regular']],
                ],
            ])
        );

        $httpClient = new MockHttpClient($mockResponse);
        $api = new GoogleFontsApi($httpClient, 'test-api-key');

        $variants = $api->getFontVariants('Roboto');

        self::assertContains(400, $variants['weights']);
        self::assertContains('normal', $variants['styles']);
    }

    public function testGetFontVariantsReturnsEmptyWhenFontNotFound(): void
    {
        $mockResponse = new MockResponse((string) json_encode(['items' => []]));
        $httpClient = new MockHttpClient($mockResponse);
        $api = new GoogleFontsApi($httpClient, 'test-api-key');

        $variants = $api->getFontVariants('NonExistent');

        self::assertSame(['weights' => [], 'styles' => []], $variants);
    }

    public function testDownloadFontCssGeneratesCorrectUrl(): void
    {
        $mockResponse = new MockResponse('@font-face { font-family: Ubuntu; }', [
            'http_code' => 200,
        ]);

        $httpClient = new MockHttpClient(function ($method, $url) use ($mockResponse) {
            self::assertStringContainsString('family=Ubuntu', $url);
            self::assertStringContainsString('wght@400;700', $url);
            self::assertStringContainsString('display=swap', $url);

            return $mockResponse;
        });

        $api = new GoogleFontsApi($httpClient, 'test-api-key');
        $css = $api->downloadFontCss('Ubuntu', [400, 700], ['normal'], 'swap');

        self::assertStringContainsString('@font-face', $css);
        self::assertStringContainsString('Ubuntu', $css);
    }

    public function testDownloadFontCssHandlesItalicStyles(): void
    {
        $mockResponse = new MockResponse('@font-face { font-family: Ubuntu; }');
        $httpClient = new MockHttpClient(function ($method, $url) use ($mockResponse) {
            self::assertStringContainsString('ital,wght@0,400;1,400', $url);

            return $mockResponse;
        });

        $api = new GoogleFontsApi($httpClient, 'test-api-key');
        $css = $api->downloadFontCss('Ubuntu', [400], ['normal', 'italic']);

        self::assertIsString($css);
    }

    public function testSearchFontsCaseInsensitive(): void
    {
        $mockResponse = new MockResponse(
            (string) json_encode([
                'items' => [
                    ['family' => 'Open Sans', 'variants' => ['regular'], 'category' => 'sans-serif'],
                    ['family' => 'Roboto', 'variants' => ['regular'], 'category' => 'sans-serif'],
                ],
            ])
        );

        $httpClient = new MockHttpClient($mockResponse);
        $api = new GoogleFontsApi($httpClient, 'test-api-key');

        $results = $api->searchFonts('OPEN', 20);

        self::assertCount(1, $results);
        $firstResult = reset($results);
        self::assertTrue(is_array($firstResult));
        self::assertSame('Open Sans', $firstResult['family']);
    }

    public function testSearchFontsThrowsExceptionWhenApiKeyMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Google Fonts API key is required');

        $httpClient = new MockHttpClient();
        $api = new GoogleFontsApi($httpClient, null);

        $api->searchFonts('Roboto');
    }

    public function testGetFontMetadataThrowsExceptionWhenApiKeyMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Google Fonts API key is required');

        $httpClient = new MockHttpClient();
        $api = new GoogleFontsApi($httpClient, null);

        $api->getFontMetadata('Roboto');
    }

    public function testSearchFontsHandlesEmptyItems(): void
    {
        $mockResponse = new MockResponse((string) json_encode(['items' => []]));
        $httpClient = new MockHttpClient($mockResponse);
        $api = new GoogleFontsApi($httpClient, 'test-api-key');

        $results = $api->searchFonts('NonExistent');

        self::assertIsArray($results);
        self::assertEmpty($results);
    }

    public function testSearchFontsHandlesMissingItems(): void
    {
        $mockResponse = new MockResponse((string) json_encode([]));
        $httpClient = new MockHttpClient($mockResponse);
        $api = new GoogleFontsApi($httpClient, 'test-api-key');

        $results = $api->searchFonts('Roboto');

        self::assertIsArray($results);
        self::assertEmpty($results);
    }

    public function testGetFontVariantsHandlesItalicOnlyVariant(): void
    {
        $mockResponse = new MockResponse(
            (string) json_encode([
                'items' => [
                    ['family' => 'Roboto', 'variants' => ['italic']],
                ],
            ])
        );

        $httpClient = new MockHttpClient($mockResponse);
        $api = new GoogleFontsApi($httpClient, 'test-api-key');

        $variants = $api->getFontVariants('Roboto');

        self::assertContains(400, $variants['weights']);
        self::assertContains('italic', $variants['styles']);
    }

    public function testGetFontVariantsHandlesNonStringVariants(): void
    {
        $mockResponse = new MockResponse(
            (string) json_encode([
                'items' => [
                    ['family' => 'Roboto', 'variants' => ['regular', 123, null, '700']],
                ],
            ])
        );

        $httpClient = new MockHttpClient($mockResponse);
        $api = new GoogleFontsApi($httpClient, 'test-api-key');

        $variants = $api->getFontVariants('Roboto');

        self::assertContains(400, $variants['weights']);
        self::assertContains(700, $variants['weights']);
    }

    public function testGetFontVariantsHandlesMissingVariants(): void
    {
        $mockResponse = new MockResponse(
            (string) json_encode([
                'items' => [
                    ['family' => 'Roboto'],
                ],
            ])
        );

        $httpClient = new MockHttpClient($mockResponse);
        $api = new GoogleFontsApi($httpClient, 'test-api-key');

        $variants = $api->getFontVariants('Roboto');

        self::assertSame(['weights' => [], 'styles' => ['normal']], $variants);
    }

    public function testGetFontVariantsHandlesNonArrayVariants(): void
    {
        $mockResponse = new MockResponse(
            (string) json_encode([
                'items' => [
                    ['family' => 'Roboto', 'variants' => 'invalid'],
                ],
            ])
        );

        $httpClient = new MockHttpClient($mockResponse);
        $api = new GoogleFontsApi($httpClient, 'test-api-key');

        $variants = $api->getFontVariants('Roboto');

        self::assertSame(['weights' => [], 'styles' => ['normal']], $variants);
    }

    public function testDownloadFontCssHandlesFontNameWithSpaces(): void
    {
        $mockResponse = new MockResponse('@font-face { font-family: "Open Sans"; }');
        $httpClient = new MockHttpClient(function ($method, $url) use ($mockResponse) {
            self::assertStringContainsString('family=Open+Sans', $url);

            return $mockResponse;
        });

        $api = new GoogleFontsApi($httpClient, 'test-api-key');
        $css = $api->downloadFontCss('Open Sans', [400], ['normal']);

        self::assertStringContainsString('@font-face', $css);
    }

    public function testDownloadFontCssHandlesCustomDisplay(): void
    {
        $mockResponse = new MockResponse('@font-face { }');
        $httpClient = new MockHttpClient(function ($method, $url) use ($mockResponse) {
            self::assertStringContainsString('display=fallback', $url);

            return $mockResponse;
        });

        $api = new GoogleFontsApi($httpClient, 'test-api-key');
        $css = $api->downloadFontCss('Roboto', [400], ['normal'], 'fallback');

        self::assertIsString($css);
    }

    public function testDownloadFontCssIncludesUserAgent(): void
    {
        $optionsCaptured = [];
        $mockResponse = new MockResponse('@font-face { }');
        $httpClient = new MockHttpClient(function ($method, $url, $options) use ($mockResponse, &$optionsCaptured) {
            $optionsCaptured = $options;

            return $mockResponse;
        });

        $api = new GoogleFontsApi($httpClient, 'test-api-key');
        $api->downloadFontCss('Roboto', [400], ['normal']);

        self::assertArrayHasKey('headers', $optionsCaptured);
        self::assertIsArray($optionsCaptured['headers']);

        $headersFound = false;
        foreach ($optionsCaptured['headers'] as $key => $value) {
            if ('User-Agent' === $key || (is_string($value) && str_contains($value, 'User-Agent'))) {
                $headersFound = true;

                break;
            }
        }

        self::assertTrue($headersFound, 'User-Agent header configuration not found');
    }

    public function testDownloadFontCssHandlesMultipleWeights(): void
    {
        $mockResponse = new MockResponse('@font-face { }');
        $httpClient = new MockHttpClient(function ($method, $url) use ($mockResponse) {
            self::assertStringContainsString('wght@300;400;700', $url);

            return $mockResponse;
        });

        $api = new GoogleFontsApi($httpClient, 'test-api-key');
        $css = $api->downloadFontCss('Roboto', [300, 400, 700], ['normal']);

        self::assertIsString($css);
    }

    public function testGetFontVariantsRemovesDuplicateWeights(): void
    {
        $mockResponse = new MockResponse(
            (string) json_encode([
                'items' => [
                    ['family' => 'Roboto', 'variants' => ['regular', '400', '700', '700italic']],
                ],
            ])
        );

        $httpClient = new MockHttpClient($mockResponse);
        $api = new GoogleFontsApi($httpClient, 'test-api-key');

        $variants = $api->getFontVariants('Roboto');

        $weightCounts = array_count_values($variants['weights']);
        foreach ($weightCounts as $count) {
            self::assertSame(1, $count);
        }
    }

    public function testGetFontVariantsRemovesDuplicateStyles(): void
    {
        $mockResponse = new MockResponse(
            (string) json_encode([
                'items' => [
                    ['family' => 'Roboto', 'variants' => ['italic', '300italic', '700italic']],
                ],
            ])
        );

        $httpClient = new MockHttpClient($mockResponse);
        $api = new GoogleFontsApi($httpClient, 'test-api-key');

        $variants = $api->getFontVariants('Roboto');

        $styleCounts = array_count_values($variants['styles']);
        self::assertSame(1, $styleCounts['italic']);
    }
}
