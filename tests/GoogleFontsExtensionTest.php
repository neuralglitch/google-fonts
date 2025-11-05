<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Tests;

use NeuralGlitch\GoogleFonts\Twig\GoogleFontsExtension;
use NeuralGlitch\GoogleFonts\Twig\GoogleFontsRuntime;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

final class GoogleFontsExtensionTest extends TestCase
{
    public function testGetFunctionsReturnsTwigFunctions(): void
    {
        $extension = new GoogleFontsExtension();
        $functions = $extension->getFunctions();

        self::assertIsArray($functions);
        self::assertCount(1, $functions);
        self::assertInstanceOf(TwigFunction::class, $functions[0]);
    }

    public function testRenderFontsWithStringWeights(): void
    {
        $extension = new GoogleFontsRuntime(false, null, []);

        $html = $extension->renderFonts('Ubuntu', '300 400 500 700');

        self::assertStringContainsString('Ubuntu', $html);
        self::assertStringContainsString('fonts.googleapis.com', $html);
        self::assertStringContainsString('300', $html);
        self::assertStringContainsString('400', $html);
        self::assertStringContainsString('500', $html);
        self::assertStringContainsString('700', $html);
    }

    public function testRenderFontsWithArrayWeights(): void
    {
        $extension = new GoogleFontsRuntime(false, null, []);

        $html = $extension->renderFonts('Roboto', [300, 400, 500]);

        self::assertStringContainsString('Roboto', $html);
        self::assertStringContainsString('fonts.googleapis.com', $html);
    }

    public function testRenderFontsWithStyles(): void
    {
        $extension = new GoogleFontsRuntime(false, null, []);

        $html = $extension->renderFonts('Open Sans', '400 700', 'normal italic');

        self::assertStringContainsString('Open Sans', $html);
        self::assertStringContainsString('ital,wght@', $html); // Italic variants in Google Fonts API format
    }

    public function testRenderFontsWithCustomDisplay(): void
    {
        $extension = new GoogleFontsRuntime(false, null, []);

        $html = $extension->renderFonts('Roboto', '400', 'normal', 'block');

        self::assertStringContainsString('display=block', $html);
    }

    public function testRenderFontsUsesDefaultDisplay(): void
    {
        $extension = new GoogleFontsRuntime(false, null, ['display' => 'fallback']);

        $html = $extension->renderFonts('Roboto', '400');

        self::assertStringContainsString('display=fallback', $html);
    }

    public function testRenderFontsIncludesPreconnectByDefault(): void
    {
        $extension = new GoogleFontsRuntime(false, null, []);

        $html = $extension->renderFonts('Roboto', '400');

        self::assertStringContainsString('preconnect', $html);
        self::assertStringContainsString('fonts.googleapis.com', $html);
        self::assertStringContainsString('fonts.gstatic.com', $html);
    }

    public function testRenderFontsCanDisablePreconnect(): void
    {
        $extension = new GoogleFontsRuntime(false, null, ['preconnect' => false]);

        $html = $extension->renderFonts('Roboto', '400');

        self::assertStringNotContainsString('preconnect', $html);
        self::assertStringContainsString('fonts.googleapis.com', $html);
    }

    public function testRenderFontsGeneratesCssVariables(): void
    {
        $extension = new GoogleFontsRuntime(false, null, []);

        $html = $extension->renderFonts('Ubuntu', '400');

        self::assertStringContainsString('--font-family-ubuntu', $html);
        self::assertStringContainsString(':root', $html);
        self::assertStringContainsString('body', $html);
        self::assertStringContainsString('h1, h2, h3, h4, h5, h6', $html);
    }

    public function testRenderFontsWithSpacesInFontName(): void
    {
        $extension = new GoogleFontsRuntime(false, null, []);

        $html = $extension->renderFonts('Open Sans', '400');

        self::assertStringContainsString('Open+Sans', $html);
        self::assertStringContainsString('Open Sans', $html);
    }

    public function testRenderFontsSelectsBoldWeight(): void
    {
        $extension = new GoogleFontsRuntime(false, null, []);

        $html = $extension->renderFonts('Ubuntu', '300 400 800');

        self::assertStringContainsString('font-weight: 800', $html);
    }

    public function testRenderFontsSelectsHeadingWeight(): void
    {
        $extension = new GoogleFontsRuntime(false, null, []);

        $html = $extension->renderFonts('Ubuntu', '300 400 600');

        self::assertStringContainsString('font-weight: 600', $html);
    }

    public function testRenderFontsWithTextSubsetting(): void
    {
        $extension = new GoogleFontsRuntime(false, null, []);

        $html = $extension->renderFonts('Roboto', '400', 'normal', null, false, 'Hello World');

        self::assertStringContainsString('&amp;text=Hello+World', $html);
    }

    public function testRenderFontsWithEmptyTextSubsetting(): void
    {
        $extension = new GoogleFontsRuntime(false, null, []);

        $html = $extension->renderFonts('Roboto', '400', 'normal', null, false, ''); // empty text

        self::assertStringNotContainsString('text=', $html);
        self::assertStringContainsString('family=Roboto:wght@400', $html);
    }

    public function testRenderFontsWithPreload(): void
    {
        $extension = new GoogleFontsRuntime(false, null, []);

        $html = $extension->renderFonts('Roboto', '400', 'normal', null, false, null, true);

        self::assertStringContainsString('<link rel="preload"', $html);
        self::assertStringContainsString('as="style">', $html);
        self::assertStringContainsString('family=Roboto:wght@400', $html);
    }

    public function testRenderFontsWithNonStringDisplayDefault(): void
    {
        $extension = new GoogleFontsRuntime(false, null, ['display' => 123]); // Invalid type

        $html = $extension->renderFonts('Roboto', '400');

        self::assertStringContainsString('display=swap', $html);
    }

    public function testRenderFontsWithNonBoolPreconnectDefault(): void
    {
        $extension = new GoogleFontsRuntime(false, null, ['preconnect' => 'invalid']); // Invalid type

        $html = $extension->renderFonts('Roboto', '400');

        self::assertStringContainsString('<link rel="preconnect"', $html);
    }

    public function testFindWeightReturnsDefaultWhenNoMatch(): void
    {
        $extension = new GoogleFontsRuntime(false, null, []);

        $html = $extension->renderFonts('Roboto', '300 400 500');

        self::assertStringContainsString('h1, h2, h3, h4, h5, h6 {', $html);
        self::assertStringContainsString('font-weight: 700;', $html);
    }

    public function testFindWeightReturnsFirstMatchAboveMin(): void
    {
        $extension = new GoogleFontsRuntime(false, null, []);

        $html = $extension->renderFonts('Roboto', '300 400 600 800');

        self::assertStringContainsString('h1, h2, h3, h4, h5, h6 {', $html);
        self::assertStringContainsString('font-weight: 600;', $html);

        self::assertStringContainsString('strong, b {', $html);
        self::assertStringContainsString('font-weight: 800;', $html);
    }

    public function testFallbackToDefaultsWhenInvalidConfigTypes(): void
    {
        $extension = new GoogleFontsRuntime(false, null, [
            'display' => ['invalid' => 'array'], // Invalid type
            'preconnect' => 'not a bool', // Invalid type
        ]);

        $html = $extension->renderFonts('Roboto', '400');

        self::assertStringContainsString('display=swap', $html);
        self::assertStringContainsString('<link rel="preconnect"', $html);
    }

    public function testHasLockedFontsWithInvalidManifestFile(): void
    {
        $extension = new GoogleFontsRuntime(true, sys_get_temp_dir() . '/invalid.json', []);

        file_put_contents(sys_get_temp_dir() . '/invalid.json', 'invalid json');

        $html = $extension->renderFonts('Roboto');

        self::assertStringContainsString('fonts.googleapis.com', $html);

        unlink(sys_get_temp_dir() . '/invalid.json');
    }

    public function testHasLockedFontsWithInvalidFilemtime(): void
    {
        $extension = new GoogleFontsRuntime(true, '/nonexistent/path/fonts.json', []);

        $html = $extension->renderFonts('Roboto');

        self::assertStringContainsString('fonts.googleapis.com', $html);
    }

    public function testHasLockedFontsWithFailedFileGetContents(): void
    {
        $dirPath = sys_get_temp_dir() . '/test_dir_' . uniqid();
        mkdir($dirPath);

        $extension = new GoogleFontsRuntime(true, $dirPath, []);

        $html = $extension->renderFonts('Roboto');

        self::assertStringContainsString('fonts.googleapis.com', $html);

        rmdir($dirPath);
    }

    public function testNormalizeArrayWithEmptyInput(): void
    {
        $extension = new GoogleFontsRuntime(false, null, []);

        $html = $extension->renderFonts('Roboto', [], 'normal');

        self::assertStringContainsString('font-weight: 400;', $html);
    }

    public function testNormalizeArrayWithStringInput(): void
    {
        $extension = new GoogleFontsRuntime(false, null, []);

        $html = $extension->renderFonts('Roboto', '300 400 700', 'normal italic');

        self::assertStringContainsString('wght@', $html);
        self::assertStringContainsString('ital', $html);
    }
}
