<?php

declare(strict_types=1);

namespace NeuralGlitch\GoogleFonts\Service;

final class FontVariantHelper
{
    /**
     * Generate CSS variants for Google Fonts API.
     *
     * @param array<int|string> $weights
     * @param array<string>     $styles
     *
     * @return array<string>
     */
    public static function generateVariants(array $weights, array $styles): array
    {
        $variants = [];

        foreach ($styles as $style) {
            foreach ($weights as $weight) {
                if ('italic' === $style) {
                    $variants[] = sprintf('ital,wght@1,%d', (int) $weight);
                }
                $variants[] = sprintf('wght@%d', (int) $weight);
            }
        }

        return array_unique($variants);
    }

    /**
     * Sanitize font name for file paths.
     */
    public static function sanitizeFontName(string $name): string
    {
        return str_replace(' ', '-', strtolower($name));
    }

    /**
     * Normalize input to array.
     *
     * @param array<int|string>|string $input
     *
     * @return array<int|string>
     */
    public static function normalizeArray(array|string $input): array
    {
        if (is_string($input)) {
            return array_map('trim', explode(' ', $input));
        }

        return array_map('strval', $input);
    }
}
