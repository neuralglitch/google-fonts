<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Tests;

use NeuralGlitch\GoogleFonts\Twig\GoogleFontsExtension;
use PHPUnit\Framework\TestCase;

final class GoogleFontsExtensionLockedFontsTest extends TestCase
{
    private string $manifestFile;

    protected function setUp(): void
    {
        $this->manifestFile = sys_get_temp_dir() . '/google-fonts-test-manifest-' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->manifestFile)) {
            unlink($this->manifestFile);
        }
    }

    public function testRenderLockedFonts(): void
    {
        // Create manifest file
        $manifest = [
            'locked' => true,
            'generated_at' => date('c'),
            'fonts' => [
                'Ubuntu' => [
                    'weights' => [400, 700],
                    'styles' => ['normal'],
                ],
            ],
        ];
        file_put_contents($this->manifestFile, json_encode($manifest));

        $extension = new GoogleFontsExtension(
            'prod',
            true,
            $this->manifestFile,
            []
        );

        $html = $extension->renderFonts('Ubuntu');

        self::assertStringContainsString('/assets/fonts/ubuntu/ubuntu.css', $html);
        self::assertStringContainsString('/assets/fonts/ubuntu/ubuntu-styles.css', $html);
        self::assertStringNotContainsString('fonts.googleapis.com', $html);
    }

    public function testUseLockedFontsFalseFallsBackToCdn(): void
    {
        // Create manifest file
        $manifest = [
            'locked' => true,
            'generated_at' => date('c'),
            'fonts' => [
                'Ubuntu' => [
                    'weights' => [400, 700],
                    'styles' => ['normal'],
                ],
            ],
        ];
        file_put_contents($this->manifestFile, json_encode($manifest));

        $extension = new GoogleFontsExtension(
            'prod',
            false, // use_locked_fonts = false
            $this->manifestFile,
            []
        );

        $html = $extension->renderFonts('Ubuntu');

        self::assertStringContainsString('fonts.googleapis.com', $html);
        self::assertStringNotContainsString('/assets/fonts/', $html);
    }

    public function testDevEnvironmentUsesCdnEvenWithLockedFonts(): void
    {
        // Create manifest file
        $manifest = [
            'locked' => true,
            'generated_at' => date('c'),
            'fonts' => [
                'Ubuntu' => [
                    'weights' => [400, 700],
                    'styles' => ['normal'],
                ],
            ],
        ];
        file_put_contents($this->manifestFile, json_encode($manifest));

        $extension = new GoogleFontsExtension(
            'dev', // dev environment
            true,
            $this->manifestFile,
            []
        );

        $html = $extension->renderFonts('Ubuntu');

        self::assertStringContainsString('fonts.googleapis.com', $html);
        self::assertStringNotContainsString('/assets/fonts/', $html);
    }

    public function testNonExistentFontFallsBackToCdn(): void
    {
        // Create manifest file without the font
        $manifest = [
            'locked' => true,
            'generated_at' => date('c'),
            'fonts' => [],
        ];
        file_put_contents($this->manifestFile, json_encode($manifest));

        $extension = new GoogleFontsExtension(
            'prod',
            true,
            $this->manifestFile,
            []
        );

        $html = $extension->renderFonts('NonExistentFont');

        self::assertStringContainsString('fonts.googleapis.com', $html);
        self::assertStringNotContainsString('/assets/fonts/', $html);
    }
}

