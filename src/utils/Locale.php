<?php

namespace flusio\utils;

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

    /**
     * Return the path to the locales.
     *
     * @return string
     */
    public static function localesPath()
    {
        return \Minz\Configuration::$app_path . '/locales';
    }

    /**
     * Return the current locale.
     *
     * @return string
     */
    public static function currentLocale()
    {
        // we want to remove the '.UTF8' part
        return substr(setlocale(LC_ALL, 0), 0, -5);
    }

    /**
     * Set current locale.
     *
     * The locale must be available on the system.
     *
     * @see https://www.php.net/manual/en/function.setlocale.php
     *
     * @param string|boolean $locale Return the new locale or false if the
     *                               locale doesn't exist.
     */
    public static function setCurrentLocale($locale)
    {
        return setlocale(LC_ALL, $locale . '.UTF8');
    }

    /**
     * Return an array containing the available locales under the locales/
     * folder. Keys are the locales and values are their human-readable
     * translations.
     *
     * @return string[]
     */
    public static function availableLocales()
    {
        $locales = [
            self::DEFAULT_LOCALE => self::DEFAULT_LOCALE_NAME,
        ];

        $locales_path = self::localesPath();
        foreach (scandir($locales_path) as $locale_dir) {
            if ($locale_dir[0] === '.') {
                continue;
            }

            $locale_metadata_path = $locales_path . '/' . $locale_dir . '/metadata.json';
            $raw_metadata = file_get_contents($locale_metadata_path);
            $metadata = json_decode($raw_metadata);
            $locales[$locale_dir] = $metadata->name;
        }
        return $locales;
    }
}
