<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Tests;

use NeuralGlitch\GoogleFonts\Twig\GoogleFontsRuntime;
use PHPUnit\Framework\TestCase;

final class GoogleFontsExtensionMonospaceTest extends TestCase
{
    public function testRenderMonospaceFont(): void
    {
        $extension = new GoogleFontsRuntime('dev', false, null, []);

        $html = $extension->renderFonts('JetBrains Mono', '400 500', 'normal', null, true);

        self::assertStringContainsString('JetBrains Mono', $html);
        self::assertStringContainsString('monospace', $html);
        self::assertStringContainsString('code, pre, kbd, samp, var, tt {', $html);
        self::assertStringNotContainsString('body {', $html);
        self::assertStringNotContainsString('h1, h2, h3', $html);
    }

    public function testMonospaceFontWithMultipleWeights(): void
    {
        $extension = new GoogleFontsRuntime('dev', false, null, []);

        $html = $extension->renderFonts('Fira Code', '300 400 500 700', 'normal', null, true);

        self::assertStringContainsString('Fira Code', $html);
        self::assertStringContainsString('monospace', $html);
        self::assertStringContainsString('font-weight: 300', $html); // Default weight
    }

    public function testNormalFontUsesCorrectFallback(): void
    {
        $extension = new GoogleFontsRuntime('dev', false, null, []);

        $html = $extension->renderFonts('Ubuntu', '400', 'normal', null, false);

        self::assertStringContainsString('sans-serif', $html);
        self::assertStringNotContainsString('monospace', $html);
        self::assertStringContainsString('body {', $html);
    }

    public function testMonospaceCssVariable(): void
    {
        $extension = new GoogleFontsRuntime('dev', false, null, []);

        $html = $extension->renderFonts('Source Code Pro', '400', 'normal', null, true);

        self::assertStringContainsString('--font-family-source-code-pro', $html);
        self::assertStringContainsString('var(--font-family-source-code-pro)', $html);
    }
}
