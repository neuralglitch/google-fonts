<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class GoogleFontsExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('google_fonts', [GoogleFontsRuntime::class, 'renderFonts'], [
                'is_safe' => ['html'],
                'needs_runtime' => true,
            ]),
        ];
    }
}
