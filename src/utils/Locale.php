<?php

namespace App\utils;

/**
 * Useful methods to manage localization.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Locale
{
    public const DEFAULT_LOCALE = 'en_GB';
    public const DEFAULT_LOCALE_NAME = 'English';

    private static bool $is_initialized = false;

    /**
     * Return the path to the locales.
     */
    public static function localesPath(): string
    {
        return \App\Configuration::$app_path . '/locales';
    }

    /**
     * Return the current locale.
     */
    public static function currentLocale(): string
    {
        if (!self::$is_initialized) {
            self::init();
        }

        $locale = setlocale(LC_ALL, '0');
        if ($locale === false) {
            return self::DEFAULT_LOCALE;
        }

        // we want to remove the '.UTF8' part
        return substr($locale, 0, -5);
    }

    /**
     * Set current locale and return the new locale or false if the locale doesn't exist.
     *
     * The locale must be available on the system.
     *
     * @see https://www.php.net/manual/en/function.setlocale.php
     */
    public static function setCurrentLocale(string $locale): string|false
    {
        if (!self::$is_initialized) {
            self::init();
        }

        return setlocale(LC_ALL, $locale . '.UTF8');
    }

    /**
     * Return an array containing the available locales under the locales/
     * folder. Keys are the locales and values are their human-readable
     * translations.
     *
     * @return array<string, string>
     */
    public static function availableLocales(): array
    {
        $locales = [
            self::DEFAULT_LOCALE => self::DEFAULT_LOCALE_NAME,
        ];

        $locales_path = self::localesPath();
        $locales_dirs = scandir($locales_path);

        if ($locales_dirs === false) {
            return $locales;
        }

        foreach ($locales_dirs as $locale_dir) {
            if ($locale_dir[0] === '.') {
                continue;
            }

            $locale_metadata_path = $locales_path . '/' . $locale_dir . '/metadata.json';

            $raw_metadata = file_get_contents($locale_metadata_path);
            if ($raw_metadata === false) {
                continue;
            }

            /** @var mixed[] */
            $metadata = json_decode($raw_metadata, true);

            if (isset($metadata['name']) && is_string($metadata['name'])) {
                $locales[$locale_dir] = $metadata['name'];
            }
        }

        return $locales;
    }

    /**
     * Return the most adequate locale based on the http Accept-Language header.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-5.3.5
     */
    public static function best(string $http_accept_language): string
    {
        if (!$http_accept_language) {
            return self::DEFAULT_LOCALE;
        }

        // We start by parsing the HTTP Accept-Language header
        $result = preg_match_all(
            '/(?P<language>[\w-]+)(\s*;\s*[qQ]\s*=\s*(?P<weight>[01](\.\d{1,3})?))?/',
            $http_accept_language,
            $matches
        );

        if (!$result) {
            // No results? Stop here and return the default locale
            return self::DEFAULT_LOCALE;
        }

        // We now need to sort the accepted languages by their weight
        $languages = $matches['language'];
        $weights = array_map(function (string $weight): float {
            if ($weight === '') {
                return 1.0;
            } else {
                return floatval($weight);
            }
        }, $matches['weight']);
        $matches = array_combine($languages, $weights);
        arsort($matches);

        // We create a lookup array to facilitate the following manipulations.
        // This array is composed of available lowercased locales, and parts
        // before the underscore (so for fr_FR, the array will contain fr_fr
        // and fr).
        $available_locales = self::availableLocales();
        $lookup_locales = [];
        foreach ($available_locales as $locale => $name) {
            $lower_locale = strtolower($locale);
            $lookup_locales[$lower_locale] = $locale;

            $splitted_locale = explode('_', $lower_locale, 2);
            if (count($splitted_locale) > 1) {
                $lookup_locales[$splitted_locale[0]] = $locale;
            }
        }

        // Finally, we try to find our sorted language in the lookup array
        foreach ($matches as $language => $weight) {
            $locale = strtolower(str_replace('-', '_', $language));
            if (isset($lookup_locales[$locale])) {
                return $lookup_locales[$locale];
            }

            $splitted_locale = explode('_', $locale, 2);
            if (count($splitted_locale) > 1 && isset($lookup_locales[$splitted_locale[0]])) {
                return $lookup_locales[$splitted_locale[0]];
            }
        }

        // Still nothing? Return the default locale
        return self::DEFAULT_LOCALE;
    }

    /**
     * Set domain path for localization.
     */
    private static function init(): void
    {
        bindtextdomain('main', self::localesPath());
        textdomain('main');
        self::$is_initialized = true;
        self::setCurrentLocale(self::DEFAULT_LOCALE);
    }
}
