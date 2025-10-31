<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Tests\Service;

use NeuralGlitch\GoogleFonts\Service\GoogleFontsApi;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class GoogleFontsApiTest extends TestCase
{
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
        $api = new GoogleFontsApi($httpClient);

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
        $api = new GoogleFontsApi($httpClient);

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
        $api = new GoogleFontsApi($httpClient);

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
        $api = new GoogleFontsApi($httpClient);

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
        $api = new GoogleFontsApi($httpClient);

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
        $api = new GoogleFontsApi($httpClient);

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
        $api = new GoogleFontsApi($httpClient);

        $variants = $api->getFontVariants('Roboto');

        self::assertContains(400, $variants['weights']);
        self::assertContains('normal', $variants['styles']);
    }

    public function testGetFontVariantsReturnsEmptyWhenFontNotFound(): void
    {
        $mockResponse = new MockResponse((string) json_encode(['items' => []]));
        $httpClient = new MockHttpClient($mockResponse);
        $api = new GoogleFontsApi($httpClient);

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
            self::assertStringContainsString('wght@400', $url);
            self::assertStringContainsString('wght@700', $url);
            self::assertStringContainsString('display=swap', $url);

            return $mockResponse;
        });

        $api = new GoogleFontsApi($httpClient);
        $css = $api->downloadFontCss('Ubuntu', [400, 700], ['normal'], 'swap');

        self::assertStringContainsString('@font-face', $css);
        self::assertStringContainsString('Ubuntu', $css);
    }

    public function testDownloadFontCssHandlesItalicStyles(): void
    {
        $mockResponse = new MockResponse('@font-face { font-family: Ubuntu; }');
        $httpClient = new MockHttpClient(function ($method, $url) use ($mockResponse) {
            self::assertStringContainsString('ital,wght@1,400', $url);

            return $mockResponse;
        });

        $api = new GoogleFontsApi($httpClient);
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
        $api = new GoogleFontsApi($httpClient);

        $results = $api->searchFonts('OPEN', 20);

        self::assertCount(1, $results);
        $firstResult = reset($results);
        self::assertTrue(is_array($firstResult));
        self::assertSame('Open Sans', $firstResult['family']);
    }
}
