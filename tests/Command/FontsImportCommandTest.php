<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Tests\Command;

use NeuralGlitch\GoogleFonts\Command\FontsImportCommand;
use NeuralGlitch\GoogleFonts\Service\FontDownloader;
use NeuralGlitch\GoogleFonts\Service\GoogleFontsApi;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class FontsImportCommandTest extends TestCase
{
    public function testConfigureSetArgumentsAndOptions(): void
    {
        $tempDir = sys_get_temp_dir() . '/test-' . uniqid();
        mkdir($tempDir, 0777, true);

        $filesystem = new Filesystem();
        $fontDownloader = new FontDownloader(
            $tempDir,
            new MockHttpClient(),
            new GoogleFontsApi(
                new MockHttpClient()
            ),
            $filesystem
        );

        $command = new FontsImportCommand($fontDownloader);

        $definition = $command->getDefinition();

        self::assertTrue($definition->hasArgument('name'));
        self::assertTrue($definition->hasOption('weights'));
        self::assertTrue($definition->hasOption('styles'));
        self::assertTrue($definition->hasOption('display'));

        // Test help text is set
        $help = $command->getHelp();
        self::assertNotEmpty($help);
        self::assertStringContainsString('downloads a Google Font', $help);

        $filesystem->remove($tempDir);
    }

    public function testExecuteImportsFont(): void
    {
        $tempDir = sys_get_temp_dir() . '/test-' . uniqid();
        mkdir($tempDir, 0777, true);

        $cssContent = '@font-face { font-family: Ubuntu; }';
        $httpClient = new MockHttpClient([
            new MockResponse($cssContent),
        ]);

        // Mock API responses for validation and download
        $apiResponses = [
            // First: getFontMetadata for validation
            new MockResponse((string) json_encode([
                'items' => [
                    ['family' => 'Ubuntu', 'variants' => ['300', 'regular', '700']],
                ],
            ])),
            // Second: downloadFontCss
            new MockResponse($cssContent),
        ];
        $apiClient = new MockHttpClient($apiResponses);
        $api = new GoogleFontsApi($apiClient, 'test-api-key');
        $filesystem = new Filesystem();
        $fontDownloader = new FontDownloader($tempDir, $httpClient, $api, $filesystem);

        $command = new FontsImportCommand($fontDownloader);
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'name' => 'Ubuntu',
            '--weights' => '300,400,700',
            '--styles' => 'normal,italic',
        ]);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Successfully imported font', $output);
        self::assertStringContainsString('Ubuntu', $output);

        // Cleanup
        if (is_dir($tempDir)) {
            $filesystem->remove($tempDir);
        }
    }

    public function testExecuteHandlesSpaceSeparatedWeights(): void
    {
        $tempDir = sys_get_temp_dir() . '/test-' . uniqid();
        mkdir($tempDir, 0777, true);

        $cssContent = '@font-face { font-family: Roboto; }';
        $httpClient = new MockHttpClient([
            new MockResponse($cssContent),
        ]);
        $apiResponses = [
            new MockResponse((string) json_encode(['items' => [['family' => 'Roboto', 'variants' => ['regular', '700']]]])),
            new MockResponse($cssContent),
        ];
        $apiClient = new MockHttpClient($apiResponses);
        $api = new GoogleFontsApi($apiClient, 'test-api-key');
        $filesystem = new Filesystem();
        $fontDownloader = new FontDownloader($tempDir, $httpClient, $api, $filesystem);

        $command = new FontsImportCommand($fontDownloader);
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'name' => 'Roboto',
            '--weights' => '400 700',
        ]);

        self::assertSame(0, $commandTester->getStatusCode());

        $filesystem->remove($tempDir);
    }

    public function testExecuteUsesDefaultValues(): void
    {
        $tempDir = sys_get_temp_dir() . '/test-' . uniqid();
        mkdir($tempDir, 0777, true);

        $cssContent = '@font-face { font-family: Inter; }';
        $httpClient = new MockHttpClient([
            new MockResponse($cssContent),
        ]);
        $apiResponses = [
            new MockResponse((string) json_encode(['items' => [['family' => 'Inter', 'variants' => ['regular']]]])),
            new MockResponse($cssContent),
        ];
        $apiClient = new MockHttpClient($apiResponses);
        $api = new GoogleFontsApi($apiClient, 'test-api-key');
        $filesystem = new Filesystem();
        $fontDownloader = new FontDownloader($tempDir, $httpClient, $api, $filesystem);

        $command = new FontsImportCommand($fontDownloader);
        $commandTester = new CommandTester($command);

        $commandTester->execute(['name' => 'Inter']);

        self::assertSame(0, $commandTester->getStatusCode());

        $filesystem->remove($tempDir);
    }

    public function testExecuteHandlesCustomDisplay(): void
    {
        $tempDir = sys_get_temp_dir() . '/test-' . uniqid();
        mkdir($tempDir, 0777, true);

        $cssContent = '@font-face { font-family: Poppins; }';
        $httpClient = new MockHttpClient([
            new MockResponse($cssContent),
        ]);
        $apiResponses = [
            new MockResponse((string) json_encode(['items' => [['family' => 'Poppins', 'variants' => ['regular']]]])),
            new MockResponse($cssContent),
        ];
        $apiClient = new MockHttpClient($apiResponses);
        $api = new GoogleFontsApi($apiClient, 'test-api-key');
        $filesystem = new Filesystem();
        $fontDownloader = new FontDownloader($tempDir, $httpClient, $api, $filesystem);

        $command = new FontsImportCommand($fontDownloader);
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'name' => 'Poppins',
            '--display' => 'optional',
        ]);

        self::assertSame(0, $commandTester->getStatusCode());

        $filesystem->remove($tempDir);
    }

    public function testExecuteHandlesDownloadFailure(): void
    {
        $tempDir = sys_get_temp_dir() . '/test-' . uniqid();
        mkdir($tempDir, 0777, true);

        // Create failing mock
        $httpClient = new MockHttpClient([
            new MockResponse('', ['http_code' => 500]),
        ]);
        $apiClient = new MockHttpClient([
            new MockResponse('', ['http_code' => 500]),
        ]);
        $api = new GoogleFontsApi($apiClient, 'test-api-key');
        $filesystem = new Filesystem();
        $fontDownloader = new FontDownloader($tempDir, $httpClient, $api, $filesystem);

        $command = new FontsImportCommand($fontDownloader);
        $commandTester = new CommandTester($command);

        $commandTester->execute(['name' => 'Ubuntu']);

        self::assertSame(1, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Failed to import', $output);

        $filesystem->remove($tempDir);
    }
}
