<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('google_fonts');
        /** @var ArrayNodeDefinition $root */
        $root = $treeBuilder->getRootNode();

        // @phpstan-ignore-next-line - Fluent interface methods are not fully recognized by PHPStan
        $root
            ->children()
                ->booleanNode('use_locked_fonts')
                    ->defaultFalse()
                    ->info('Use locked/local fonts in production instead of Google Fonts CDN')
                ->end()
                ->scalarNode('fonts_dir')
                    ->defaultValue('%kernel.project_dir%/assets/fonts')
                    ->info('Directory where locked fonts are stored')
                ->end()
                ->scalarNode('manifest_file')
                    ->defaultValue('%kernel.project_dir%/assets/fonts.json')
                    ->info('Path to the fonts manifest file')
                ->end()
                ->arrayNode('defaults')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('display')
                            ->defaultValue('swap')
                            ->info('Default font-display value')
                        ->end()
                        ->booleanNode('preconnect')
                            ->defaultTrue()
                            ->info('Enable preconnect hints for Google Fonts')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}

