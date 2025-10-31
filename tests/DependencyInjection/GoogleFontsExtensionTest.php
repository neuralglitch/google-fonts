<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Tests\DependencyInjection;

use NeuralGlitch\GoogleFonts\DependencyInjection\GoogleFontsExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class GoogleFontsExtensionTest extends TestCase
{
    public function testLoadDefaults(): void
    {
        $container = new ContainerBuilder();
        $extension = new GoogleFontsExtension();

        $extension->load([], $container);

        self::assertTrue($container->hasParameter('google_fonts.use_locked_fonts'));
        self::assertFalse($container->getParameter('google_fonts.use_locked_fonts'));
        self::assertTrue($container->hasParameter('google_fonts.fonts_dir'));
        self::assertTrue($container->hasParameter('google_fonts.manifest_file'));
        self::assertTrue($container->hasParameter('google_fonts.defaults'));
    }

    public function testLoadWithCustomConfig(): void
    {
        $container = new ContainerBuilder();
        $extension = new GoogleFontsExtension();

        $config = [
            [
                'use_locked_fonts' => true,
                'fonts_dir' => '/custom/fonts',
                'manifest_file' => '/custom/manifest.json',
                'defaults' => [
                    'display' => 'optional',
                    'preconnect' => false,
                ],
            ],
        ];

        $extension->load($config, $container);

        self::assertTrue($container->getParameter('google_fonts.use_locked_fonts'));
        self::assertSame('/custom/fonts', $container->getParameter('google_fonts.fonts_dir'));
        self::assertSame('/custom/manifest.json', $container->getParameter('google_fonts.manifest_file'));

        $defaults = $container->getParameter('google_fonts.defaults');
        self::assertIsArray($defaults);
        self::assertSame('optional', $defaults['display']);
        self::assertFalse($defaults['preconnect']);
    }

    public function testGetAlias(): void
    {
        $extension = new GoogleFontsExtension();

        self::assertSame('google_fonts', $extension->getAlias());
    }

    public function testServicesAreRegistered(): void
    {
        $container = new ContainerBuilder();
        $extension = new GoogleFontsExtension();

        $extension->load([], $container);

        self::assertTrue($container->hasDefinition('NeuralGlitch\GoogleFonts\Service\GoogleFontsApi'));
        self::assertTrue($container->hasDefinition('NeuralGlitch\GoogleFonts\Service\FontDownloader'));
        self::assertTrue($container->hasDefinition('NeuralGlitch\GoogleFonts\Service\FontLockManager'));
        self::assertTrue($container->hasDefinition('NeuralGlitch\GoogleFonts\Twig\GoogleFontsExtension'));
    }
}
