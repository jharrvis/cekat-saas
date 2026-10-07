<?php

namespace App\Support;

use Illuminate\Support\Facades\App;
use NumberFormatter;

/**
 * T-12 step 6: locale-aware formatting in one place.
 *
 * - Numbers/decimals use the active locale's separators (id: "1.234,5",
 *   en: "1,234.5") via ICU NumberFormatter.
 * - Durations are rounded to at most one decimal with a localized unit
 *   (id: "3,2 dtk", en: "3.2 s") — replacing the raw long-decimal seconds
 *   that used to leak into the UI.
 */
class Format
{
    public static function number(int|float $value, ?string $locale = null): string
    {
        $locale = $locale ?: App::getLocale();
        $formatter = new NumberFormatter($locale === 'id' ? 'id_ID' : $locale, NumberFormatter::DECIMAL);

        return $formatter->format($value);
    }

    public static function decimal(int|float $value, int $maxDecimals = 1, ?string $locale = null): string
    {
        $locale = $locale ?: App::getLocale();
        $formatter = new NumberFormatter($locale === 'id' ? 'id_ID' : $locale, NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $maxDecimals);
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 0);

        return $formatter->format($value);
    }

    public static function duration(float $seconds, ?string $locale = null): string
    {
        $locale = $locale ?: App::getLocale();
        $unit = $locale === 'en' ? 's' : 'dtk';

        return self::decimal($seconds, 1, $locale) . ' ' . $unit;
    }
}
