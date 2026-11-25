<?php

namespace App\Support;

/**
 * Picks readable text over an employee color. The palette is light, so text
 * is usually `ink`; a dark custom color switches it to white.
 */
class ColorContrast
{
    public const DARK_TEXT = '#15171a';

    public const LIGHT_TEXT = '#ffffff';

    public static function textOn(string $hex): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return self::DARK_TEXT;
        }

        [$r, $g, $b] = array_map(
            fn (string $channel) => self::linear(hexdec($channel) / 255),
            str_split($hex, 2),
        );

        $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;

        // Contrast against ink (luminance ~0.009) vs. white (1.0): pick the higher one.
        $contrastWithInk = ($luminance + 0.05) / (0.0087 + 0.05);
        $contrastWithWhite = 1.05 / ($luminance + 0.05);

        return $contrastWithInk >= $contrastWithWhite ? self::DARK_TEXT : self::LIGHT_TEXT;
    }

    private static function linear(float $channel): float
    {
        return $channel <= 0.04045
            ? $channel / 12.92
            : (($channel + 0.055) / 1.055) ** 2.4;
    }
}
