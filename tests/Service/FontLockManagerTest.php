<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Tests\Service;

use NeuralGlitch\GoogleFonts\Service\FontDownloader;
use NeuralGlitch\GoogleFonts\Service\FontLockManager;
use NeuralGlitch\GoogleFonts\Service\GoogleFontsApi;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class FontLockManagerTest extends TestCase
{
    private string $tempDir;
    private string $manifestFile;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/google-fonts-lock-test-' . uniqid();
        $this->manifestFile = $this->tempDir . '/fonts.json';
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    public function testScanTemplatesFindsGoogleFontsCalls(): void
    {
        $templatesDir = $this->tempDir . '/templates';
        mkdir($templatesDir, 0777, true);

        file_put_contents(
            $templatesDir . '/base.html.twig',
            <<<'TWIG'
        <head>
          {{ google_fonts('Ubuntu', '300 400 700', 'normal italic') }}
          {{ google_fonts('Roboto', '400') }}
        </head>
        TWIG
        );

        $fontsDir = $this->tempDir . '/fonts';
        $fontDownloader = new FontDownloader(
            $fontsDir,
            new MockHttpClient(),
            new GoogleFontsApi(
                new MockHttpClient()
            ),
            new Filesystem()
        );

        $filesystem = new Filesystem();
        $manager = new FontLockManager($this->tempDir, $this->manifestFile, $fontDownloader, $filesystem);

        $fonts = $manager->scanTemplates($templatesDir);

        self::assertIsArray($fonts);
        self::assertArrayHasKey('Ubuntu', $fonts);
        self::assertArrayHasKey('Roboto', $fonts);

        self::assertContains('300', $fonts['Ubuntu']['weights']);
        self::assertContains('400', $fonts['Ubuntu']['weights']);
        self::assertContains('700', $fonts['Ubuntu']['weights']);

        self::assertContains('normal', $fonts['Ubuntu']['styles']);
        self::assertContains('italic', $fonts['Ubuntu']['styles']);

        self::assertContains('400', $fonts['Roboto']['weights']);
        self::assertContains('normal', $fonts['Roboto']['styles']);
    }

    public function testScanTemplatesHandlesArraySyntax(): void
    {
        $templatesDir = $this->tempDir . '/templates';
        mkdir($templatesDir, 0777, true);

        file_put_contents(
            $templatesDir . '/test.html.twig',
            <<<'TWIG'
        {{ google_fonts('Open Sans', [400, 700], ['normal', 'italic']) }}
        TWIG
        );

        $fontsDir = $this->tempDir . '/fonts';
        $fontDownloader = new FontDownloader(
            $fontsDir,
            new MockHttpClient(),
            new GoogleFontsApi(
                new MockHttpClient()
            ),
            new Filesystem()
        );

        $filesystem = new Filesystem();
        $manager = new FontLockManager($this->tempDir, $this->manifestFile, $fontDownloader, $filesystem);

        $fonts = $manager->scanTemplates($templatesDir);

        self::assertArrayHasKey('Open Sans', $fonts);
        self::assertContains('400', $fonts['Open Sans']['weights']);
        self::assertContains('700', $fonts['Open Sans']['weights']);
        self::assertContains('normal', $fonts['Open Sans']['styles']);
        self::assertContains('italic', $fonts['Open Sans']['styles']);
    }

    public function testScanTemplatesReturnsEmptyWhenNoFontsFound(): void
    {
        $templatesDir = $this->tempDir . '/templates';
        mkdir($templatesDir, 0777, true);

        file_put_contents($templatesDir . '/empty.html.twig', '<h1>No fonts here</h1>');

        $fontsDir = $this->tempDir . '/fonts';
        $fontDownloader = new FontDownloader(
            $fontsDir,
            new MockHttpClient(),
            new GoogleFontsApi(
                new MockHttpClient()
            ),
            new Filesystem()
        );

        $filesystem = new Filesystem();
        $manager = new FontLockManager($this->tempDir, $this->manifestFile, $fontDownloader, $filesystem);

        $fonts = $manager->scanTemplates($templatesDir);

        self::assertEmpty($fonts);
    }

    public function testLockFontsCreatesManifest(): void
    {
        $cssContent = '@font-face { font-family: Ubuntu; }';
        $responses = [
            new MockResponse($cssContent), // API CSS
        ];

        $httpClient = new MockHttpClient($responses);
        $apiClient = new MockHttpClient([
            new MockResponse($cssContent),
        ]);
        $api = new GoogleFontsApi($apiClient, 'test-api-key');

        $filesystem = new Filesystem();
        $fontsDir = $this->tempDir . '/fonts';
        $fontDownloader = new FontDownloader($fontsDir, $httpClient, $api, $filesystem);

        $manager = new FontLockManager($this->tempDir, $this->manifestFile, $fontDownloader, $filesystem);

        $fonts = [
            'Ubuntu' => ['weights' => [400], 'styles' => ['normal']],
        ];

        $manifest = $manager->lockFonts($fonts);

        self::assertIsArray($manifest);
        self::assertArrayHasKey('locked', $manifest);
        self::assertArrayHasKey('fonts', $manifest);
        self::assertTrue(is_array($manifest['fonts']));
        self::assertArrayHasKey('Ubuntu', $manifest['fonts']);
        self::assertTrue($manifest['locked']);
    }

    public function testLockFontsSavesManifestFile(): void
    {
        $cssContent = '@font-face { font-family: Roboto; }';

        $httpClient = new MockHttpClient([
            new MockResponse($cssContent),
        ]);
        $apiClient = new MockHttpClient([
            new MockResponse($cssContent),
        ]);
        $api = new GoogleFontsApi($apiClient, 'test-api-key');

        $filesystem = new Filesystem();
        $fontsDir = $this->tempDir . '/fonts';
        $fontDownloader = new FontDownloader($fontsDir, $httpClient, $api, $filesystem);

        $manager = new FontLockManager($this->tempDir, $this->manifestFile, $fontDownloader, $filesystem);

        $fonts = [
            'Roboto' => ['weights' => [400, 700], 'styles' => ['normal']],
        ];

        $manager->lockFonts($fonts);

        self::assertFileExists($this->manifestFile);

        $content = file_get_contents($this->manifestFile);
        self::assertNotFalse($content);

        $manifest = json_decode($content, true);
        self::assertIsArray($manifest);
        self::assertTrue(isset($manifest['fonts']) && is_array($manifest['fonts']));
        self::assertArrayHasKey('Roboto', $manifest['fonts']);
    }

    public function testGetManifestFileReturnsPath(): void
    {
        $fontsDir = $this->tempDir . '/fonts';
        $fontDownloader = new FontDownloader(
            $fontsDir,
            new MockHttpClient(),
            new GoogleFontsApi(
                new MockHttpClient()
            ),
            new Filesystem()
        );

        $filesystem = new Filesystem();
        $manager = new FontLockManager($this->tempDir, $this->manifestFile, $fontDownloader, $filesystem);

        self::assertSame($this->manifestFile, $manager->getManifestFile());
    }

    public function testScanTemplatesHandlesMixedFormats(): void
    {
        $templatesDir = $this->tempDir . '/templates';
        mkdir($templatesDir, 0777, true);

        file_put_contents(
            $templatesDir . '/mixed.html.twig',
            <<<'TWIG'
        {{ google_fonts('Ubuntu', '300 400', 'normal italic') }}
        {{ google_fonts('Roboto', [500, 700], 'normal') }}
        {{ google_fonts('Inter', '400', ['normal', 'italic']) }}
        TWIG
        );

        $fontsDir = $this->tempDir . '/fonts';
        $fontDownloader = new FontDownloader(
            $fontsDir,
            new MockHttpClient(),
            new GoogleFontsApi(
                new MockHttpClient()
            ),
            new Filesystem()
        );

        $filesystem = new Filesystem();
        $manager = new FontLockManager($this->tempDir, $this->manifestFile, $fontDownloader, $filesystem);

        $fonts = $manager->scanTemplates($templatesDir);

        self::assertArrayHasKey('Ubuntu', $fonts);
        self::assertArrayHasKey('Roboto', $fonts);
        self::assertArrayHasKey('Inter', $fonts);

        self::assertContains('300', $fonts['Ubuntu']['weights']);
        self::assertContains('400', $fonts['Ubuntu']['weights']);
        self::assertContains('500', $fonts['Roboto']['weights']);
        self::assertContains('italic', $fonts['Inter']['styles']);
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
