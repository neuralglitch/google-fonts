<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Tests\DependencyInjection;

use NeuralGlitch\GoogleFonts\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testDefaultConfiguration(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();

        $config = $processor->processConfiguration($configuration, []);

        self::assertFalse($config['use_locked_fonts']);
        self::assertSame('%kernel.project_dir%/assets/fonts', $config['fonts_dir']);
        self::assertSame('%kernel.project_dir%/assets/fonts.json', $config['manifest_file']);
        self::assertSame('swap', $config['defaults']['display']);
        self::assertTrue($config['defaults']['preconnect']);
    }

    public function testCustomConfiguration(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();

        $config = $processor->processConfiguration($configuration, [
            'google_fonts' => [
                'use_locked_fonts' => true,
                'fonts_dir' => '/custom/fonts',
                'manifest_file' => '/custom/manifest.json',
                'defaults' => [
                    'display' => 'optional',
                    'preconnect' => false,
                ],
            ],
        ]);

        self::assertTrue($config['use_locked_fonts']);
        self::assertSame('/custom/fonts', $config['fonts_dir']);
        self::assertSame('/custom/manifest.json', $config['manifest_file']);
        self::assertSame('optional', $config['defaults']['display']);
        self::assertFalse($config['defaults']['preconnect']);
    }

    public function testPartialConfiguration(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();

        $config = $processor->processConfiguration($configuration, [
            'google_fonts' => [
                'use_locked_fonts' => true,
            ],
        ]);

        self::assertTrue($config['use_locked_fonts']);
        self::assertSame('%kernel.project_dir%/assets/fonts', $config['fonts_dir']);
        self::assertSame('swap', $config['defaults']['display']);
    }
}
