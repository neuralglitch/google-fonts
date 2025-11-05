<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Tests\Command;

use NeuralGlitch\GoogleFonts\Command\FontsLockCommand;
use NeuralGlitch\GoogleFonts\Service\FontDownloader;
use NeuralGlitch\GoogleFonts\Service\FontLockManager;
use NeuralGlitch\GoogleFonts\Service\GoogleFontsApi;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class FontsLockCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/fonts-lock-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $filesystem = new Filesystem();
            $filesystem->remove($this->tempDir);
        }
    }

    public function testConfigureSetArgumentsAndOptions(): void
    {
        $filesystem = new Filesystem();
        $fontDownloader = new FontDownloader(
            $this->tempDir,
            new MockHttpClient(),
            new GoogleFontsApi(new MockHttpClient(), 'test-api-key'),
            $filesystem
        );
        $lockManager = new FontLockManager(
            $this->tempDir,
            $this->tempDir . '/fonts.json',
            $fontDownloader,
            $filesystem
        );

        $command = new FontsLockCommand($lockManager, $this->tempDir);

        $definition = $command->getDefinition();

        self::assertTrue($definition->hasArgument('template-dirs'));
        self::assertTrue($definition->hasOption('force'));

        $help = $command->getHelp();
        self::assertNotEmpty($help);
        self::assertStringContainsString('scans Twig templates', $help);
    }

    public function testExecuteScansAndLocksFonts(): void
    {
        $templatesDir = $this->tempDir . '/templates';
        mkdir($templatesDir, 0777, true);

        file_put_contents(
            $templatesDir . '/base.html.twig',
            <<<'TWIG'
        {{ google_fonts('Ubuntu', '400') }}
        TWIG
        );

        $cssContent = '@font-face { font-family: Ubuntu; }';
        $httpClient = new MockHttpClient([new MockResponse($cssContent)]);
        $apiClient = new MockHttpClient([new MockResponse($cssContent)]);
        $api = new GoogleFontsApi($apiClient, 'test-api-key');
        $filesystem = new Filesystem();

        $fontsDir = $this->tempDir . '/fonts';
        $manifestFile = $this->tempDir . '/fonts.json';

        $fontDownloader = new FontDownloader($fontsDir, $httpClient, $api, $filesystem);
        $lockManager = new FontLockManager($fontsDir, $manifestFile, $fontDownloader, $filesystem);

        $command = new FontsLockCommand($lockManager, $this->tempDir);
        $commandTester = new CommandTester($command);

        $commandTester->execute(['template-dirs' => [$templatesDir]]);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Locked', $output);
        self::assertStringContainsString('Ubuntu', $output);
    }

    public function testExecuteHandlesNoFontsFound(): void
    {
        $templatesDir = $this->tempDir . '/templates';
        mkdir($templatesDir, 0777, true);

        file_put_contents($templatesDir . '/empty.html.twig', '<h1>No fonts</h1>');

        $filesystem = new Filesystem();
        $fontsDir = $this->tempDir . '/fonts';
        $manifestFile = $this->tempDir . '/fonts.json';

        $fontDownloader = new FontDownloader(
            $fontsDir,
            new MockHttpClient(),
            new GoogleFontsApi(new MockHttpClient(), 'test-api-key'),
            $filesystem
        );
        $lockManager = new FontLockManager($fontsDir, $manifestFile, $fontDownloader, $filesystem);

        $command = new FontsLockCommand($lockManager, $this->tempDir);
        $commandTester = new CommandTester($command);

        $commandTester->execute(['template-dirs' => [$templatesDir]]);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('No google_fonts()', $output);
    }

    public function testExecuteUsesDefaultDirectories(): void
    {
        mkdir($this->tempDir . '/templates', 0777, true);

        $filesystem = new Filesystem();
        $fontsDir = $this->tempDir . '/fonts';
        $manifestFile = $this->tempDir . '/fonts.json';

        $fontDownloader = new FontDownloader(
            $fontsDir,
            new MockHttpClient(),
            new GoogleFontsApi(new MockHttpClient(), 'test-api-key'),
            $filesystem
        );
        $lockManager = new FontLockManager($fontsDir, $manifestFile, $fontDownloader, $filesystem);

        $command = new FontsLockCommand($lockManager, $this->tempDir);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        self::assertSame(0, $commandTester->getStatusCode());
    }
}
