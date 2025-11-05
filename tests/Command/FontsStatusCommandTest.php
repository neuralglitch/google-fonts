<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Tests\Command;

use NeuralGlitch\GoogleFonts\Command\FontsStatusCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

final class FontsStatusCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/test-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    public function testConfigure(): void
    {
        $command = new FontsStatusCommand('dev', false, $this->tempDir . '/fonts.json', $this->tempDir, new Filesystem());

        self::assertSame('gfonts:status', $command->getName());
        self::assertSame('Show Google Fonts configuration and status', $command->getDescription());

        $help = $command->getHelp();
        self::assertNotEmpty($help);
        self::assertStringContainsString('shows the current Google Fonts configuration', $help);
    }

    public function testExecuteShowsEnvironmentAndConfiguration(): void
    {
        $manifestFile = $this->tempDir . '/fonts.json';
        $fontsDir = $this->tempDir . '/fonts';
        mkdir($fontsDir, 0777, true);

        $command = new FontsStatusCommand('dev', false, $manifestFile, $fontsDir, new Filesystem());
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();

        self::assertStringContainsString('Google Fonts Status', $output);
        self::assertStringContainsString('Environment', $output);
        self::assertStringContainsString('dev', $output);
        self::assertStringContainsString('Use Locked Fonts', $output);
        self::assertStringContainsString('No', $output);
    }

    public function testExecuteShowsLockedFontsWhenManifestExists(): void
    {
        $manifestFile = $this->tempDir . '/fonts.json';
        $fontsDir = $this->tempDir . '/fonts';
        mkdir($fontsDir, 0777, true);

        $manifest = [
            'locked' => true,
            'generated_at' => '2025-11-01T00:00:00+00:00',
            'fonts' => [
                'Roboto' => [
                    'weights' => [300, 400, 700],
                    'styles' => ['normal'],
                    'files' => ['roboto-300.woff2', 'roboto-400.woff2'],
                    'css' => 'assets/fonts/roboto.css',
                    'monospace' => false,
                ],
                'JetBrains Mono' => [
                    'weights' => [400, 500],
                    'styles' => ['normal'],
                    'files' => ['jetbrains-400-mono.woff2'],
                    'css' => 'assets/fonts/jetbrains-mono.css',
                    'monospace' => true,
                ],
            ],
        ];
        file_put_contents($manifestFile, json_encode($manifest));

        $command = new FontsStatusCommand('prod', true, $manifestFile, $fontsDir, new Filesystem());
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();

        self::assertStringContainsString('Locked Fonts', $output);
        self::assertStringContainsString('Roboto', $output);
        self::assertStringContainsString('JetBrains Mono', $output);
        self::assertStringContainsString('300, 400, 700', $output);
        self::assertStringContainsString('400, 500', $output);
        self::assertStringContainsString('Found 2 locked font(s)', $output);
        self::assertStringContainsString('Last locked: 2025-11-01T00:00:00+00:00', $output);
    }

    public function testExecuteWarnsWhenNoManifestFile(): void
    {
        $manifestFile = $this->tempDir . '/fonts.json';
        $fontsDir = $this->tempDir . '/fonts';

        $command = new FontsStatusCommand('dev', false, $manifestFile, $fontsDir, new Filesystem());
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();

        self::assertStringContainsString('No manifest file found', $output);
        self::assertStringContainsString('gfonts:lock', $output);
    }

    public function testExecuteShowsProductionReadinessChecks(): void
    {
        $manifestFile = $this->tempDir . '/fonts.json';
        $fontsDir = $this->tempDir . '/fonts';
        mkdir($fontsDir, 0777, true);

        $manifest = ['locked' => true, 'fonts' => []];
        file_put_contents($manifestFile, json_encode($manifest));

        $command = new FontsStatusCommand('prod', true, $manifestFile, $fontsDir, new Filesystem());
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();

        self::assertStringContainsString('Locked Fonts Readiness', $output);
        self::assertStringContainsString('Ready to use locked fonts', $output);
    }

    public function testExecuteWarnsWhenNotReady(): void
    {
        $manifestFile = $this->tempDir . '/fonts.json';
        $fontsDir = $this->tempDir . '/fonts';

        $command = new FontsStatusCommand('dev', false, $manifestFile, $fontsDir, new Filesystem());
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();

        self::assertStringContainsString('Not ready to use locked fonts', $output);
        self::assertStringContainsString('DEBUG_LOCKED_FONTS.md', $output);
    }

    public function testExecuteHandlesInvalidManifestFile(): void
    {
        $manifestFile = $this->tempDir . '/fonts.json';
        $fontsDir = $this->tempDir . '/fonts';
        mkdir($fontsDir, 0777, true);

        file_put_contents($manifestFile, 'invalid json{{{');

        $command = new FontsStatusCommand('dev', false, $manifestFile, $fontsDir, new Filesystem());
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        self::assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();

        self::assertStringContainsString('Invalid manifest file', $output);
    }

    public function testExecuteHandlesEmptyFontsInManifest(): void
    {
        $manifestFile = $this->tempDir . '/fonts.json';
        $fontsDir = $this->tempDir . '/fonts';
        mkdir($fontsDir, 0777, true);

        $manifest = [
            'locked' => true,
            'fonts' => [],
        ];
        file_put_contents($manifestFile, json_encode($manifest));

        $command = new FontsStatusCommand('dev', false, $manifestFile, $fontsDir, new Filesystem());
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();

        self::assertStringContainsString('No fonts locked yet', $output);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $scanned = scandir($dir);
        if (false === $scanned) {
            return;
        }

        $files = array_diff($scanned, ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
