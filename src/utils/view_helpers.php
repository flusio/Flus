<?php

/**
 * This file contains helper methods to be used in view files. It doesn't
 * declare a namespace on purpose.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */

/**
 * Format a datetime.
 *
 * @see https://www.php.net/manual/class.intldateformatter.php
 * @see https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax
 *
 * @param \DateTime $date
 *     The datetime to format
 * @param string $format
 *     The format following ICU Datetime format syntax
 *
 * @return string
 */
function _date($date, $format)
{
    $current_locale = \flusio\utils\Locale::currentLocale();
    $formatter = new IntlDateFormatter(
        $current_locale,
        IntlDateFormatter::FULL,
        IntlDateFormatter::FULL,
        null,
        null,
        $format
    );
    return $formatter->format($date);
}

/**
 * Format a DateTime according to current day (designed for Message dates)
 *
 * @param \DateTime $date
 *
 * @return string
 */
function format_message_date($date)
{
    $today = \Minz\Time::relative('today');
    if ($date >= $today) {
        return _date($date, 'HH:mm');
    } elseif ($date->format('Y') === $today->format('Y')) {
        return _date($date, 'dd MMM, HH:mm');
    } else {
        return _date($date, 'dd MMM Y, HH:mm');
    }
}

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
function locale_to_bcp_47($locale)
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

/**
 * Return the relative URL for a media image
 *
 * @param string $type Either 'cards', 'large' or 'avatars'
 * @param string $filename The filename of the image to get
 * @param string $default The default image to return if file doesn't exist
 *
 * @return string
 */
function url_media($type, $filename, $default = 'default-card.png')
{
    if (!$filename) {
        return url_static($default);
    }

    $media_path = \Minz\Configuration::$application['media_path'];
    $subpath = \flusio\utils\Belt::filenameToSubpath($filename);
    $filepath = "{$media_path}/{$type}/{$subpath}/{$filename}";
    $modification_time = @filemtime($filepath);
    $file_url = \Minz\Url::path() . "/media/{$type}/{$subpath}/{$filename}";
    if ($modification_time) {
        return $file_url . '?' . $modification_time;
    } else {
        return url_static($default);
    }
}

/**
 * Return the absolute URL for a media image
 *
 * @param string $type Either 'cards', 'large' or 'avatars'
 * @param string $filename The filename of the image to get
 * @param string $default The default image to return if file doesn't exist
 *
 * @return string
 */
function url_media_full($type, $filename)
{
    return \Minz\Url::baseUrl() . url_media($type, $filename);
}

/**
 * Return the relative URL of an avatar.
 *
 * @param string $filename The path saved in user->avatar_filename
 *
 * @return string
 */
function url_avatar($filename)
{
    return url_media('avatars', $filename, 'default-avatar.svg');
}

/**
 * Return a SVG icon
 *
 * @see \flusio\utils\Icon::get
 */
function icon($icon_name)
{
    return \flusio\utils\Icon::get($icon_name);
}

/**
 * Join items from a list, allowing to change the last separator to make it
 * more natural for humans.
 *
 * It acts as normal `implode()` function except the last item is concatenated
 * with $last_separator. Please note that the array is the first parameter.
 *
 * @param string[] $array
 * @param string $separator
 * @param string $last_separator
 *
 * @return string
 */
function human_implode($array, $separator, $last_separator)
{
    $string = '';
    foreach ($array as $index => $item) {
        $first_item = $index === 0;
        $last_item = ($index + 1) === count($array);
        if ($first_item) {
            $string .= $array[$index];
        } elseif ($last_item) {
            $string .= $last_separator . $array[$index];
        } else {
            $string .= $separator . $array[$index];
        }
    }
    return $string;
}

/**
 * Return a random sentence to display when there are no news.
 *
 * @return string
 */
function no_news_sentence()
{
    $bookmarks_url = url('bookmarks');
    $sentence = _('There are no relevant links to suggest at this time.') . '<br />';
    $sentence .= _f('You can add links to <a href="%s">your bookmarks</a> to read them later.', $bookmarks_url);

    if (rand(0, 100) === 0) {
        if (rand(0, 10) === 0) {
            $sentence .= '<span class="easter-egg">🦔</span>';
        } else {
            $sentence .= '<span class="easter-egg">🐾</span>';
        }
    }

    return $sentence;
}
