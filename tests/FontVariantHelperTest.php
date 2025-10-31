<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Tests;

use NeuralGlitch\GoogleFonts\Service\FontVariantHelper;
use PHPUnit\Framework\TestCase;

final class FontVariantHelperTest extends TestCase
{
    public function testSanitizeFontName(): void
    {
        self::assertSame('ubuntu', FontVariantHelper::sanitizeFontName('Ubuntu'));
        self::assertSame('open-sans', FontVariantHelper::sanitizeFontName('Open Sans'));
        self::assertSame('roboto-mono', FontVariantHelper::sanitizeFontName('Roboto Mono'));
        self::assertSame('my-custom-font', FontVariantHelper::sanitizeFontName('My Custom Font'));
    }

    public function testNormalizeArrayWithString(): void
    {
        $result = FontVariantHelper::normalizeArray('300 400 500 700');
        self::assertSame(['300', '400', '500', '700'], $result);
    }

    public function testNormalizeArrayWithArray(): void
    {
        $result = FontVariantHelper::normalizeArray([300, 400, 500, 700]);
        self::assertSame(['300', '400', '500', '700'], $result);
    }

    public function testNormalizeArrayWithStringArray(): void
    {
        $result = FontVariantHelper::normalizeArray(['300', '400', '500']);
        self::assertSame(['300', '400', '500'], $result);
    }

    public function testGenerateVariantsSingleWeight(): void
    {
        $variants = FontVariantHelper::generateVariants([400], ['normal']);
        self::assertContains('wght@400', $variants);
        self::assertCount(1, $variants);
    }

    public function testGenerateVariantsMultipleWeights(): void
    {
        $variants = FontVariantHelper::generateVariants([300, 400, 500], ['normal']);
        self::assertContains('wght@300', $variants);
        self::assertContains('wght@400', $variants);
        self::assertContains('wght@500', $variants);
        self::assertCount(3, $variants);
    }

    public function testGenerateVariantsWithItalic(): void
    {
        $variants = FontVariantHelper::generateVariants([400, 700], ['normal', 'italic']);
        self::assertContains('wght@400', $variants);
        self::assertContains('wght@700', $variants);
        self::assertContains('ital,wght@1,400', $variants);
        self::assertContains('ital,wght@1,700', $variants);
        self::assertCount(4, $variants);
    }

    public function testGenerateVariantsRemovesDuplicates(): void
    {
        $variants = FontVariantHelper::generateVariants([400, 400], ['normal']);
        self::assertCount(1, $variants);
        self::assertContains('wght@400', $variants);
    }
}
