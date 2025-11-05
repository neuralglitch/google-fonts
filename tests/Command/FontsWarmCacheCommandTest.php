<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Tests\Command;

use NeuralGlitch\GoogleFonts\Command\FontsWarmCacheCommand;
use NeuralGlitch\GoogleFonts\Service\FontDownloader;
use NeuralGlitch\GoogleFonts\Service\GoogleFontsApi;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class FontsWarmCacheCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/fonts-warm-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $filesystem = new Filesystem();
            $filesystem->remove($this->tempDir);
        }
    }

    public function testConfigureSetOptions(): void
    {
        $filesystem = new Filesystem();
        $fontDownloader = new FontDownloader(
            $this->tempDir,
            new MockHttpClient(),
            new GoogleFontsApi(new MockHttpClient(), 'test-api-key'),
            $filesystem
        );

        $command = new FontsWarmCacheCommand($fontDownloader, $this->tempDir . '/fonts.json', $filesystem);

        $definition = $command->getDefinition();

        self::assertTrue($definition->hasOption('manifest'));

        $help = $command->getHelp();
        self::assertNotEmpty($help);
        self::assertStringContainsString('pre-downloads all fonts', $help);
    }

    public function testExecuteWarmsCache(): void
    {
        $manifestFile = $this->tempDir . '/fonts.json';
        $manifest = [
            'locked' => true,
            'fonts' => [
                'Ubuntu' => ['weights' => [400], 'styles' => ['normal'], 'display' => 'swap'],
            ],
        ];
        file_put_contents($manifestFile, json_encode($manifest));

        $cssContent = '@font-face { font-family: Ubuntu; }';
        $httpClient = new MockHttpClient([new MockResponse($cssContent)]);
        $apiClient = new MockHttpClient([new MockResponse($cssContent)]);
        $api = new GoogleFontsApi($apiClient, 'test-api-key');
        $filesystem = new Filesystem();

        $fontDownloader = new FontDownloader($this->tempDir . '/fonts', $httpClient, $api, $filesystem);

        $command = new FontsWarmCacheCommand($fontDownloader, $manifestFile, $filesystem);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('warmed cache', $output);
    }

    public function testExecuteHandlesMissingManifest(): void
    {
        $manifestFile = $this->tempDir . '/nonexistent.json';

        $filesystem = new Filesystem();
        $fontDownloader = new FontDownloader(
            $this->tempDir . '/fonts',
            new MockHttpClient(),
            new GoogleFontsApi(new MockHttpClient(), 'test-api-key'),
            $filesystem
        );

        $command = new FontsWarmCacheCommand($fontDownloader, $manifestFile, $filesystem);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        self::assertSame(1, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Manifest file not found', $output);
    }

    public function testExecuteHandlesInvalidManifest(): void
    {
        $manifestFile = $this->tempDir . '/invalid.json';
        file_put_contents($manifestFile, 'invalid json');

        $filesystem = new Filesystem();
        $fontDownloader = new FontDownloader(
            $this->tempDir . '/fonts',
            new MockHttpClient(),
            new GoogleFontsApi(new MockHttpClient(), 'test-api-key'),
            $filesystem
        );

        $command = new FontsWarmCacheCommand($fontDownloader, $manifestFile, $filesystem);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        self::assertSame(1, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Invalid manifest', $output);
    }

    public function testExecuteUsesCustomManifestPath(): void
    {
        $customManifest = $this->tempDir . '/custom.json';
        $manifest = [
            'locked' => true,
            'fonts' => [
                'Roboto' => ['weights' => [400], 'styles' => ['normal']],
            ],
        ];
        file_put_contents($customManifest, json_encode($manifest));

        $cssContent = '@font-face { font-family: Roboto; }';
        $httpClient = new MockHttpClient([new MockResponse($cssContent)]);
        $apiClient = new MockHttpClient([new MockResponse($cssContent)]);
        $api = new GoogleFontsApi($apiClient, 'test-api-key');
        $filesystem = new Filesystem();

        $fontDownloader = new FontDownloader($this->tempDir . '/fonts', $httpClient, $api, $filesystem);

        $command = new FontsWarmCacheCommand($fontDownloader, $this->tempDir . '/default.json', $filesystem);
        $commandTester = new CommandTester($command);

        $commandTester->execute(['--manifest' => $customManifest]);

        self::assertSame(0, $commandTester->getStatusCode());
    }
}
