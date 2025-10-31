<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class GoogleFontsExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('google_fonts.use_locked_fonts', $config['use_locked_fonts'] ?? false);
        $container->setParameter('google_fonts.fonts_dir', $config['fonts_dir'] ?? '%kernel.project_dir%/assets/fonts');
        $container->setParameter(
            'google_fonts.manifest_file',
            $config['manifest_file'] ?? '%kernel.project_dir%/assets/fonts.json'
        );
        $container->setParameter('google_fonts.defaults', $config['defaults'] ?? []);
    }

    public function getAlias(): string
    {
        return 'google_fonts';
    }
}
