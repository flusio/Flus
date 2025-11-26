<?php

namespace App\twig;

use App\utils;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

class FormattersExtension
{
    /**
     * Format a number accordingly to the current locale
     */
    #[AsTwigFilter('format_number')]
    public static function formatNumber(int|float $number): string
    {
        $locale = utils\Locale::currentLocale();
        $formatter = new \NumberFormatter($locale, \NumberFormatter::DEFAULT_STYLE);

        $formatted_number = $formatter->format($number);

        if ($formatted_number === false) {
            throw new \Exception(
                $formatter->getErrorMessage(),
                $formatter->getErrorCode()
            );
        }

        return $formatted_number;
    }

    /**
     * Return the given reading time as a human-readable string.
     */
    #[AsTwigFilter('format_reading_time', isSafe: ['html'])]
    public static function formatReadingTime(int $reading_time): string
    {
        if ($reading_time < 1) {
            return \Minz\Template\TwigExtension::translate('< 1 min');
        } else {
            return \Minz\Template\TwigExtension::translate('%s&nbsp;min', [
                self::formatNumber($reading_time),
            ]);
        }
    }

    /**
     * Format the given date in ISO-8601.
     */
    #[AsTwigFilter('date_iso')]
    public static function formatDateToIso8601(\DateTimeInterface $date): string
    {
        return $date->format(\DateTime::ATOM);
    }
}
