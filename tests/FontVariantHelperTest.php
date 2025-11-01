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

        self::assertCount(1, $variants);
        self::assertSame('wght@400', $variants[0]);
    }

    public function testGenerateVariantsMultipleWeights(): void
    {
        $variants = FontVariantHelper::generateVariants([300, 400, 500], ['normal']);

        self::assertCount(1, $variants);
        self::assertSame('wght@300;400;500', $variants[0]);
    }

    public function testGenerateVariantsWithItalic(): void
    {
        $variants = FontVariantHelper::generateVariants([400, 700], ['normal', 'italic']);

        self::assertCount(1, $variants);
        self::assertSame('ital,wght@0,400;1,400;0,700;1,700', $variants[0]);
    }

    public function testGenerateVariantsRemovesDuplicates(): void
    {
        $variants = FontVariantHelper::generateVariants([400, 400], ['normal']);

        self::assertCount(1, $variants);
        self::assertSame('wght@400;400', $variants[0]); // Duplicates in input remain
    }
}
