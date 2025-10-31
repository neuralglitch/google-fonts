<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Tests\Exception;

use NeuralGlitch\GoogleFonts\Exception\FontDownloadException;
use NeuralGlitch\GoogleFonts\Exception\GoogleFontsException;
use NeuralGlitch\GoogleFonts\Exception\ManifestException;
use PHPUnit\Framework\TestCase;

final class ExceptionsTest extends TestCase
{
    public function testGoogleFontsExceptionCanBeThrown(): void
    {
        $this->expectException(GoogleFontsException::class);
        $this->expectExceptionMessage('Test exception');

        throw new GoogleFontsException('Test exception');
    }

    public function testFontDownloadExceptionExtendsGoogleFontsException(): void
    {
        $exception = new FontDownloadException('Download failed');

        self::assertInstanceOf(GoogleFontsException::class, $exception);
        self::assertSame('Download failed', $exception->getMessage());
    }

    public function testFontDownloadExceptionAcceptsPrevious(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new FontDownloadException('Download failed', 0, $previous);

        self::assertSame($previous, $exception->getPrevious());
    }

    public function testManifestExceptionExtendsGoogleFontsException(): void
    {
        $exception = new ManifestException('Manifest error');

        self::assertInstanceOf(GoogleFontsException::class, $exception);
        self::assertSame('Manifest error', $exception->getMessage());
    }

    public function testManifestExceptionAcceptsPrevious(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new ManifestException('Manifest error', 0, $previous);

        self::assertSame($previous, $exception->getPrevious());
    }
}
