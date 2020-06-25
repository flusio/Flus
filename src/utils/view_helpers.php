<?php

/**
 * This file contains helper methods to be used in view files. It doesn't
 * declare a namespace on purpose.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */

/**
 * Transform a locale to BCP47 format
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/lang
 * @see https://www.ietf.org/rfc/bcp/bcp47.txt
 *
 * @param string $locale
 *
 * @return string
 */
function localeToBCP47($locale)
{
    $splitted_locale = explode('_', $locale, 2);
    if (!$splitted_locale) {
        // This is line is virtually inaccessible
        return $locale; // @codeCoverageIgnore
    }

    if (count($splitted_locale) === 1) {
        return $splitted_locale[0];
    }

    return $splitted_locale[0] . '-' . strtoupper($splitted_locale[1]);
}

/**
 * Return the given reading time as a human-readable string.
 *
 * @param integer $reading_time
 *
 * @return integer
 */
function format_reading_time($reading_time)
{
    if ($reading_time < 1) {
        return _('< 1 min');
    } else {
        return _f('%d&nbsp;min', $reading_time);
    }
}

/**
 * Return the relative URL for an asset file (under public/assets/ or
 * public/dev_assets/ folder)
 *
 * @param string $filename
 *
 * @return string
 */
function url_asset($filename)
{
    if (\Minz\Configuration::$environment === 'development') {
        $assets_folder = 'dev_assets';
    } else {
        $assets_folder = 'assets';
    }

    $filepath = \Minz\Configuration::$app_path . "/public/{$assets_folder}/{$filename}";
    $modification_time = @filemtime($filepath);

    $file_url = \Minz\Url::path() . "/{$assets_folder}/{$filename}";
    if ($modification_time) {
        return $file_url . '?' . $modification_time;
    } else {
        return $file_url;
    }
}
