<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Tests\Service;

use NeuralGlitch\GoogleFonts\Exception\FontDownloadException;
use NeuralGlitch\GoogleFonts\Service\FontDownloader;
use NeuralGlitch\GoogleFonts\Service\GoogleFontsApi;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class FontDownloaderTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/google-fonts-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    public function testDownloadFontCreatesFiles(): void
    {
        $cssContent = '@font-face { src: url(https://fonts.gstatic.com/test.woff2); }';
        $fontContent = 'fake-woff2-content';

        $responses = [
            new MockResponse($cssContent),
            new MockResponse($fontContent),
        ];

        $httpClient = new MockHttpClient($responses);

        // Create a real GoogleFontsApi with mock client
        $apiClient = new MockHttpClient([new MockResponse($cssContent)]);
        $api = new GoogleFontsApi($apiClient);

        $filesystem = new Filesystem();
        $downloader = new FontDownloader($this->tempDir, $httpClient, $api, $filesystem);

        $result = $downloader->downloadFont('Ubuntu', [400], ['normal']);

        self::assertIsArray($result);
        self::assertArrayHasKey('files', $result);
        self::assertArrayHasKey('css', $result);
        self::assertArrayHasKey('cssPath', $result);
        self::assertArrayHasKey('stylesheetPath', $result);
        self::assertArrayHasKey('stylesheetCss', $result);

        self::assertFileExists($result['cssPath']);
        self::assertFileExists($result['stylesheetPath']);
    }

    public function testDownloadFontGeneratesStylesheet(): void
    {
        $cssContent = '@font-face { font-family: Ubuntu; }';

        $httpClient = new MockHttpClient([new MockResponse($cssContent)]);

        $apiClient = new MockHttpClient([new MockResponse($cssContent)]);
        $api = new GoogleFontsApi($apiClient);

        $filesystem = new Filesystem();
        $downloader = new FontDownloader($this->tempDir, $httpClient, $api, $filesystem);

        $result = $downloader->downloadFont('Ubuntu', [400, 700], ['normal']);

        self::assertStringContainsString(':root', $result['stylesheetCss']);
        self::assertStringContainsString('--font-family-ubuntu', $result['stylesheetCss']);
        self::assertStringContainsString('body', $result['stylesheetCss']);
        self::assertStringContainsString('font-weight: 400', $result['stylesheetCss']);
        self::assertStringContainsString('h1, h2, h3, h4, h5, h6', $result['stylesheetCss']);
        self::assertStringContainsString('font-weight: 700', $result['stylesheetCss']);
    }

    public function testDownloadFontSelectsCorrectHeadingWeight(): void
    {
        $cssContent = '@font-face { font-family: Ubuntu; }';

        $httpClient = new MockHttpClient([new MockResponse($cssContent)]);

        $apiClient = new MockHttpClient([new MockResponse($cssContent)]);
        $api = new GoogleFontsApi($apiClient);

        $filesystem = new Filesystem();
        $downloader = new FontDownloader($this->tempDir, $httpClient, $api, $filesystem);

        // Weights: 300, 400, 600 - should select 600 for headings (first > 500)
        $result = $downloader->downloadFont('Ubuntu', [300, 400, 600], ['normal']);

        self::assertStringContainsString('font-weight: 600', $result['stylesheetCss']);
    }

    public function testDownloadFontWithMultipleStyles(): void
    {
        $cssContent = '@font-face { font-family: "Open Sans"; }';

        $httpClient = new MockHttpClient([new MockResponse($cssContent)]);

        $apiClient = new MockHttpClient([new MockResponse($cssContent)]);
        $api = new GoogleFontsApi($apiClient);

        $filesystem = new Filesystem();
        $downloader = new FontDownloader($this->tempDir, $httpClient, $api, $filesystem);

        $result = $downloader->downloadFont('Open Sans', [400, 700], ['normal', 'italic']);

        self::assertStringContainsString('--font-family-open-sans', $result['stylesheetCss']);
        self::assertStringContainsString("'Open Sans', sans-serif", $result['stylesheetCss']);
    }

    public function testDownloadFontThrowsExceptionOnApiFailure(): void
    {
        $httpClient = new MockHttpClient();

        // API client that throws exception
        $apiClient = new MockHttpClient([
            new MockResponse('', ['http_code' => 500, 'error' => 'Server Error']),
        ]);
        $api = new GoogleFontsApi($apiClient);

        $filesystem = new Filesystem();
        $downloader = new FontDownloader($this->tempDir, $httpClient, $api, $filesystem);

        $this->expectException(FontDownloadException::class);
        $this->expectExceptionMessage('Failed to download CSS for font "Ubuntu"');

        $downloader->downloadFont('Ubuntu', [400], ['normal']);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if (false === $items) {
            return;
        }

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
