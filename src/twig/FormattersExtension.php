<?php

namespace App\twig;

use App\utils;
use Twig\Attribute\AsTwigFilter;

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
     * Format a publication frequency.
     *
     * It displays the frequency in priority by day, then week, month or year,
     * depending on the first frequency that is greater or equal to 1.
     */
    #[AsTwigFilter('format_publication_frequency')]
    public static function formatPublicationFrequency(int $frequency_per_year): string
    {
        $frequency_per_day = (int) floor($frequency_per_year / 365);
        $frequency_per_week = (int) floor($frequency_per_year / 52);
        $frequency_per_month = (int) floor($frequency_per_year / 12);

        if ($frequency_per_day > 0) {
            return \Minz\Template\TwigExtension::translate(
                '%d link per day',
                '%d links per day',
                $frequency_per_day,
                [$frequency_per_day]
            );
        }

        if ($frequency_per_week > 0) {
            return \Minz\Template\TwigExtension::translate(
                '%d link per week',
                '%d links per week',
                $frequency_per_week,
                [$frequency_per_week],
            );
        }

        if ($frequency_per_month > 0) {
            return \Minz\Template\TwigExtension::translate(
                '%d link per month',
                '%d links per month',
                $frequency_per_month,
                [$frequency_per_month],
            );
        }

        if ($frequency_per_year > 0) {
            return \Minz\Template\TwigExtension::translate(
                '%d link per year',
                '%d links per year',
                $frequency_per_year,
                [$frequency_per_year],
            );
        }

        return \Minz\Template\TwigExtension::translate('Inactive');
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
