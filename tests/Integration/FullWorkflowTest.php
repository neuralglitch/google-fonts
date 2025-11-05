<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Tests\Integration;

use NeuralGlitch\GoogleFonts\Service\FontDownloader;
use NeuralGlitch\GoogleFonts\Service\FontLockManager;
use NeuralGlitch\GoogleFonts\Service\GoogleFontsApi;
use NeuralGlitch\GoogleFonts\Twig\GoogleFontsRuntime;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class FullWorkflowTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/google-fonts-integration-' . uniqid();
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->filesystem->remove($this->tempDir);
        }
    }

    public function testCompleteWorkflowFromTemplateToProduction(): void
    {
        $templatesDir = $this->tempDir . '/templates';
        $this->filesystem->mkdir($templatesDir);

        file_put_contents(
            $templatesDir . '/base.html.twig',
            <<<'TWIG'
<!DOCTYPE html>
<html>
<head>
    {{ google_fonts('Roboto', '400 700', 'normal') }}
    {{ google_fonts('JetBrains Mono', '400', 'normal', null, true) }}
</head>
<body></body>
</html>
TWIG
        );

        $fontsDir = $this->tempDir . '/fonts';
        $manifestFile = $this->tempDir . '/fonts.json';

        $mockCss = '@font-face { font-family: Test; src: url(https://example.com/font.woff2); }';
        $mockFont = 'mock-font-content';

        $httpClient = new MockHttpClient([
            new MockResponse($mockCss),
            new MockResponse($mockFont),
            new MockResponse($mockCss),
            new MockResponse($mockFont),
        ]);

        $apiClient = new MockHttpClient();
        $api = new GoogleFontsApi($apiClient, null);
        $fontDownloader = new FontDownloader($fontsDir, $httpClient, $api, $this->filesystem);
        $lockManager = new FontLockManager($fontsDir, $manifestFile, $fontDownloader, $this->filesystem);

        $scannedFonts = $lockManager->scanTemplates($templatesDir);

        self::assertArrayHasKey('Roboto', $scannedFonts);
        self::assertArrayHasKey('JetBrains Mono', $scannedFonts);

        self::assertArrayHasKey('weights', $scannedFonts['Roboto']);
        self::assertArrayHasKey('weights', $scannedFonts['JetBrains Mono']);

        $manifest = $lockManager->lockFonts($scannedFonts);

        self::assertFileExists($manifestFile);
        self::assertIsArray($manifest);
        self::assertArrayHasKey('fonts', $manifest);
        $manifestFonts = $manifest['fonts'];
        self::assertIsArray($manifestFonts);
        self::assertArrayHasKey('Roboto', $manifestFonts);
        self::assertArrayHasKey('JetBrains Mono', $manifestFonts);

        self::assertDirectoryExists($fontsDir);

        $runtime = new GoogleFontsRuntime(
            false, // use CDN for this test (no AssetMapper in unit test)
            $manifestFile,
            []
        );

        $html = $runtime->renderFonts('Roboto', [400, 700], ['normal']);

        self::assertStringContainsString('fonts.googleapis.com', $html);

        $runtime2 = new GoogleFontsRuntime(
            false, // use CDN
            $manifestFile,
            []
        );

        $html2 = $runtime2->renderFonts('Roboto', [400], ['normal']);

        self::assertStringContainsString('fonts.googleapis.com', $html2);
    }

    public function testWorkflowWithMissingFontFallsBackToCdn(): void
    {
        $manifestFile = $this->tempDir . '/fonts.json';
        $manifest = [
            'fonts' => [
                'Roboto' => [
                    'weights' => [400],
                    'styles' => ['normal'],
                    'files' => [],
                    'css' => 'assets/fonts/roboto.css',
                ],
            ],
        ];
        file_put_contents($manifestFile, json_encode($manifest));

        $runtime = new GoogleFontsRuntime(
            true, // use locked fonts enabled
            $manifestFile,
            []
        );

        $html = $runtime->renderFonts('NonExistent', [400], ['normal']);

        self::assertStringContainsString('fonts.googleapis.com', $html);
    }

    public function testWorkflowHandlesMonospaceFontsCorrectly(): void
    {
        $templatesDir = $this->tempDir . '/templates';
        $this->filesystem->mkdir($templatesDir);

        file_put_contents($templatesDir . '/code.html.twig', '{{ google_fonts("Fira Code", "400 500", "normal", null, true) }}');

        $fontsDir = $this->tempDir . '/fonts';
        $manifestFile = $this->tempDir . '/fonts.json';

        $mockCss = '@font-face { font-family: Test; src: url(https://example.com/font.woff2); }';
        $httpClient = new MockHttpClient([
            new MockResponse($mockCss),
            new MockResponse('font-content'),
        ]);

        $apiClient = new MockHttpClient();
        $api = new GoogleFontsApi($apiClient, null);
        $fontDownloader = new FontDownloader($fontsDir, $httpClient, $api, $this->filesystem);
        $lockManager = new FontLockManager($fontsDir, $manifestFile, $fontDownloader, $this->filesystem);

        $scannedFonts = $lockManager->scanTemplates($templatesDir);

        self::assertArrayHasKey('Fira Code', $scannedFonts);
        self::assertArrayHasKey('weights', $scannedFonts['Fira Code']);

        $manifest = $lockManager->lockFonts($scannedFonts);

        self::assertIsArray($manifest['fonts']);
        self::assertArrayHasKey('Fira Code', $manifest['fonts']);
    }
}
