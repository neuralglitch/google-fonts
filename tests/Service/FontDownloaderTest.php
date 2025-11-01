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

    public function testDownloadFontThrowsExceptionOnFontFileFailure(): void
    {
        $cssContent = '@font-face { src: url(https://fonts.gstatic.com/test.woff2); }';

        // Second request fails (font file download)
        $responses = [
            new MockResponse('', ['http_code' => 404, 'error' => 'Not Found']), // Font file fails
        ];

        $httpClient = new MockHttpClient($responses);

        // API client that succeeds
        $apiClient = new MockHttpClient([new MockResponse($cssContent)]);
        $api = new GoogleFontsApi($apiClient);

        $filesystem = new Filesystem();
        $downloader = new FontDownloader($this->tempDir, $httpClient, $api, $filesystem);

        $this->expectException(FontDownloadException::class);
        $this->expectExceptionMessage('Failed to download font file');

        $downloader->downloadFont('Ubuntu', [400], ['normal']);
    }

    public function testDownloadFontWithMonospaceFlag(): void
    {
        $cssContent = '@font-face { font-family: "JetBrains Mono"; }';

        $httpClient = new MockHttpClient([new MockResponse($cssContent)]);

        $apiClient = new MockHttpClient([new MockResponse($cssContent)]);
        $api = new GoogleFontsApi($apiClient);

        $filesystem = new Filesystem();
        $downloader = new FontDownloader($this->tempDir, $httpClient, $api, $filesystem);

        $result = $downloader->downloadFont('JetBrains Mono', [400, 500], ['normal'], 'swap', true);

        // Check monospace fallback
        self::assertStringContainsString("'JetBrains Mono', monospace", $result['stylesheetCss']);
        // Check code elements
        self::assertStringContainsString('code, pre, kbd, samp, var, tt', $result['stylesheetCss']);
        // Should NOT contain body/heading rules
        self::assertStringNotContainsString('body {', $result['stylesheetCss']);
        self::assertStringNotContainsString('h1, h2, h3', $result['stylesheetCss']);
    }

    public function testDownloadFontWithEmptyWeights(): void
    {
        $cssContent = '@font-face { font-family: Ubuntu; }';

        $httpClient = new MockHttpClient([new MockResponse($cssContent)]);

        $apiClient = new MockHttpClient([new MockResponse($cssContent)]);
        $api = new GoogleFontsApi($apiClient);

        $filesystem = new Filesystem();
        $downloader = new FontDownloader($this->tempDir, $httpClient, $api, $filesystem);

        $result = $downloader->downloadFont('Ubuntu', [], ['normal']);

        // Should default to 400
        self::assertStringContainsString('font-weight: 400', $result['stylesheetCss']);
    }

    public function testDownloadFontWithNameContainingSpaces(): void
    {
        $cssContent = '@font-face { font-family: "Open Sans"; }';

        $httpClient = new MockHttpClient([new MockResponse($cssContent)]);

        $apiClient = new MockHttpClient([new MockResponse($cssContent)]);
        $api = new GoogleFontsApi($apiClient);

        $filesystem = new Filesystem();
        $downloader = new FontDownloader($this->tempDir, $httpClient, $api, $filesystem);

        $result = $downloader->downloadFont('Open Sans', [400], ['normal']);

        // Should sanitize font name in CSS variable
        self::assertStringContainsString('--font-family-open-sans', $result['stylesheetCss']);
        // Original name should be preserved in font-family value
        self::assertStringContainsString("'Open Sans'", $result['stylesheetCss']);
    }

    public function testDownloadFontWithCustomDisplay(): void
    {
        $cssContent = '@font-face { font-family: Ubuntu; }';

        $httpClient = new MockHttpClient([new MockResponse($cssContent)]);

        $apiClient = new MockHttpClient([new MockResponse($cssContent)]);
        $api = new GoogleFontsApi($apiClient);

        $filesystem = new Filesystem();
        $downloader = new FontDownloader($this->tempDir, $httpClient, $api, $filesystem);

        // Display parameter should be passed to API
        $result = $downloader->downloadFont('Ubuntu', [400], ['normal'], 'optional');

        self::assertIsArray($result);
        self::assertArrayHasKey('css', $result);
    }

    public function testDownloadFontSelectsCorrectBoldWeight(): void
    {
        $cssContent = '@font-face { font-family: Ubuntu; }';

        $httpClient = new MockHttpClient([new MockResponse($cssContent)]);

        $apiClient = new MockHttpClient([new MockResponse($cssContent)]);
        $api = new GoogleFontsApi($apiClient);

        $filesystem = new Filesystem();
        $downloader = new FontDownloader($this->tempDir, $httpClient, $api, $filesystem);

        // Weights: 300, 400, 800 - should select 800 for bold (first >= 700)
        $result = $downloader->downloadFont('Ubuntu', [300, 400, 800], ['normal']);

        self::assertStringContainsString('strong, b {', $result['stylesheetCss']);
        self::assertStringContainsString('font-weight: 800', $result['stylesheetCss']);
    }

    public function testDownloadFontDefaultsHeadingWeightWhenNoWeightAbove500(): void
    {
        $cssContent = '@font-face { font-family: Ubuntu; }';

        $httpClient = new MockHttpClient([new MockResponse($cssContent)]);

        $apiClient = new MockHttpClient([new MockResponse($cssContent)]);
        $api = new GoogleFontsApi($apiClient);

        $filesystem = new Filesystem();
        $downloader = new FontDownloader($this->tempDir, $httpClient, $api, $filesystem);

        // Weights: 300, 400 (none > 500) - should default to 700 for headings
        $result = $downloader->downloadFont('Ubuntu', [300, 400], ['normal']);

        // Check heading has default 700
        $lines = explode("\n", $result['stylesheetCss']);
        $inHeadingBlock = false;
        foreach ($lines as $line) {
            if (str_contains($line, 'h1, h2, h3')) {
                $inHeadingBlock = true;
            }
            if ($inHeadingBlock && str_contains($line, 'font-weight')) {
                self::assertStringContainsString('700', $line);

                break;
            }
        }
    }

    public function testDownloadFontDefaultsBoldWeightWhenNoWeightAbove700(): void
    {
        $cssContent = '@font-face { font-family: Ubuntu; }';

        $httpClient = new MockHttpClient([new MockResponse($cssContent)]);

        $apiClient = new MockHttpClient([new MockResponse($cssContent)]);
        $api = new GoogleFontsApi($apiClient);

        $filesystem = new Filesystem();
        $downloader = new FontDownloader($this->tempDir, $httpClient, $api, $filesystem);

        // Weights: 300, 400, 500, 600 (none >= 700) - should default to 700 for bold
        $result = $downloader->downloadFont('Ubuntu', [300, 400, 500, 600], ['normal']);

        // Check bold has default 700
        $lines = explode("\n", $result['stylesheetCss']);
        $inBoldBlock = false;
        foreach ($lines as $line) {
            if (str_contains($line, 'strong, b')) {
                $inBoldBlock = true;
            }
            if ($inBoldBlock && str_contains($line, 'font-weight')) {
                self::assertStringContainsString('700', $line);

                break;
            }
        }
    }

    public function testDownloadFontWithMultipleFontUrls(): void
    {
        $cssContent = '@font-face { src: url(https://fonts.gstatic.com/font1.woff2); } @font-face { src: url(https://fonts.gstatic.com/font2.woff2); }';
        $fontContent1 = 'fake-woff2-content-1';
        $fontContent2 = 'fake-woff2-content-2';

        $responses = [
            new MockResponse($cssContent), // CSS
            new MockResponse($fontContent1), // Font 1
            new MockResponse($fontContent2), // Font 2
        ];

        $httpClient = new MockHttpClient($responses);

        $apiClient = new MockHttpClient([new MockResponse($cssContent)]);
        $api = new GoogleFontsApi($apiClient);

        $filesystem = new Filesystem();
        $downloader = new FontDownloader($this->tempDir, $httpClient, $api, $filesystem);

        $result = $downloader->downloadFont('Ubuntu', [400], ['normal']);

        // Should have 2 font files
        self::assertCount(2, $result['files']);
        self::assertStringContainsString('./font1.woff2', $result['css']);
        self::assertStringContainsString('./font2.woff2', $result['css']);
    }

    public function testDownloadFontGeneratesFilenameForUrlWithoutExtension(): void
    {
        // URL without file extension
        $cssContent = '@font-face { src: url(https://fonts.gstatic.com/s/font); }';
        $fontContent = 'fake-woff2-content';

        $responses = [
            new MockResponse($cssContent),
            new MockResponse($fontContent),
        ];

        $httpClient = new MockHttpClient($responses);

        $apiClient = new MockHttpClient([new MockResponse($cssContent)]);
        $api = new GoogleFontsApi($apiClient);

        $filesystem = new Filesystem();
        $downloader = new FontDownloader($this->tempDir, $httpClient, $api, $filesystem);

        $result = $downloader->downloadFont('Ubuntu', [400], ['normal']);

        // Should generate filename with MD5 hash
        self::assertCount(1, $result['files']);
        $filename = array_key_first($result['files']);
        self::assertNotNull($filename);
        self::assertStringContainsString('ubuntu-', $filename);
        self::assertStringEndsWith('.woff2', $filename);
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
