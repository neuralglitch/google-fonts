<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Tests;

use NeuralGlitch\GoogleFonts\DependencyInjection\GoogleFontsExtension;
use NeuralGlitch\GoogleFonts\GoogleFontsBundle;
use PHPUnit\Framework\TestCase;

final class GoogleFontsBundleTest extends TestCase
{
    public function testGetContainerExtensionReturnsExtension(): void
    {
        $bundle = new GoogleFontsBundle();
        $extension = $bundle->getContainerExtension();

        self::assertInstanceOf(GoogleFontsExtension::class, $extension);
    }

    public function testGetPathReturnsCorrectPath(): void
    {
        $bundle = new GoogleFontsBundle();
        $path = $bundle->getPath();

        self::assertStringContainsString('src', $path);
        self::assertDirectoryExists($path);
    }
}
