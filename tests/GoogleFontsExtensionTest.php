<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Tests;

use NeuralGlitch\GoogleFonts\Twig\GoogleFontsExtension;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

final class GoogleFontsExtensionTest extends TestCase
{
    public function testGetFunctionsReturnsTwigFunctions(): void
    {
        $extension = new GoogleFontsExtension('dev', false, null, []);
        $functions = $extension->getFunctions();

        self::assertIsArray($functions);
        self::assertCount(1, $functions);
        self::assertInstanceOf(TwigFunction::class, $functions[0]);
    }

    public function testRenderFontsWithStringWeights(): void
    {
        $extension = new GoogleFontsExtension('dev', false, null, []);

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
        $extension = new GoogleFontsExtension('dev', false, null, []);

        $html = $extension->renderFonts('Roboto', [300, 400, 500]);

        self::assertStringContainsString('Roboto', $html);
        self::assertStringContainsString('fonts.googleapis.com', $html);
    }

    public function testRenderFontsWithStyles(): void
    {
        $extension = new GoogleFontsExtension('dev', false, null, []);

        $html = $extension->renderFonts('Open Sans', '400 700', 'normal italic');

        self::assertStringContainsString('Open Sans', $html);
        self::assertStringContainsString('ital,wght@', $html); // Italic variants in Google Fonts API format
    }

    public function testRenderFontsWithCustomDisplay(): void
    {
        $extension = new GoogleFontsExtension('dev', false, null, []);

        $html = $extension->renderFonts('Roboto', '400', 'normal', 'block');

        self::assertStringContainsString('display=block', $html);
    }

    public function testRenderFontsUsesDefaultDisplay(): void
    {
        $extension = new GoogleFontsExtension('dev', false, null, ['display' => 'fallback']);

        $html = $extension->renderFonts('Roboto', '400');

        self::assertStringContainsString('display=fallback', $html);
    }

    public function testRenderFontsIncludesPreconnectByDefault(): void
    {
        $extension = new GoogleFontsExtension('dev', false, null, []);

        $html = $extension->renderFonts('Roboto', '400');

        self::assertStringContainsString('preconnect', $html);
        self::assertStringContainsString('fonts.googleapis.com', $html);
        self::assertStringContainsString('fonts.gstatic.com', $html);
    }

    public function testRenderFontsCanDisablePreconnect(): void
    {
        $extension = new GoogleFontsExtension('dev', false, null, ['preconnect' => false]);

        $html = $extension->renderFonts('Roboto', '400');

        self::assertStringNotContainsString('preconnect', $html);
        self::assertStringContainsString('fonts.googleapis.com', $html);
    }

    public function testRenderFontsGeneratesCssVariables(): void
    {
        $extension = new GoogleFontsExtension('dev', false, null, []);

        $html = $extension->renderFonts('Ubuntu', '400');

        self::assertStringContainsString('--font-family-ubuntu', $html);
        self::assertStringContainsString(':root', $html);
        self::assertStringContainsString('body', $html);
        self::assertStringContainsString('h1, h2, h3, h4, h5, h6', $html);
    }

    public function testRenderFontsWithSpacesInFontName(): void
    {
        $extension = new GoogleFontsExtension('dev', false, null, []);

        $html = $extension->renderFonts('Open Sans', '400');

        self::assertStringContainsString('Open+Sans', $html);
        self::assertStringContainsString('Open Sans', $html);
    }

    public function testRenderFontsSelectsBoldWeight(): void
    {
        $extension = new GoogleFontsExtension('dev', false, null, []);

        // With weight >= 700, should use it for bold
        $html = $extension->renderFonts('Ubuntu', '300 400 800');

        self::assertStringContainsString('font-weight: 800', $html);
    }

    public function testRenderFontsSelectsHeadingWeight(): void
    {
        $extension = new GoogleFontsExtension('dev', false, null, []);

        // With weight > 500, should use it for headings
        $html = $extension->renderFonts('Ubuntu', '300 400 600');

        self::assertStringContainsString('font-weight: 600', $html);
    }
}
