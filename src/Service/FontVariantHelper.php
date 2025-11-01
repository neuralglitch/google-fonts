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
        $hasItalic = in_array('italic', $styles, true);
        $weightList = array_map(fn ($w) => (int) $w, $weights);

        if ($hasItalic) {
            // Use ital,wght format: ital,wght@0,400;0,700;1,400;1,700
            $variants = [];
            foreach ($weightList as $weight) {
                $variants[] = sprintf('0,%d', $weight); // Normal
                $variants[] = sprintf('1,%d', $weight); // Italic
            }

            return ['ital,wght@' . implode(';', $variants)];
        }

        // Use simple wght format: wght@300;400;500;700
        return ['wght@' . implode(';', $weightList)];
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
