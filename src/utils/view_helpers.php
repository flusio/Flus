<?php

/**
 * This file contains helper methods to be used in view files. It doesn't
 * declare a namespace on purpose.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */

/**
 * Format a DateTime to the given format (with `strftime`)
 *
 * @see https://www.php.net/manual/function.strftime
 *
 * @param \DateTime $date
 * @param string $format
 *
 * @return string
 */
function format_date($date, $format)
{
    return strftime($format, $date->getTimestamp());
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
    $today = new \DateTime('today');
    if ($date >= $today) {
        return strftime('%H:%M', $date->getTimestamp());
    } else {
        return strftime('%d %b %H:%M', $date->getTimestamp());
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

/**
 * Return the relative URL for a link image
 *
 * @param string $type Either 'cards' or 'large'
 * @param string $filename The URL saved in link->image_filename
 *
 * @return string
 */
function url_link_image($type, $filename)
{
    if (!$filename) {
        return url_static('default-card.png');
    }

    $media_path = \Minz\Configuration::$application['media_path'];
    $filepath = "{$media_path}/{$type}/{$filename}";
    $modification_time = @filemtime($filepath);
    $file_url = \Minz\Url::path() . "/media/{$type}/{$filename}";
    if ($modification_time) {
        return $file_url . '?' . $modification_time;
    } else {
        return url_static('default-card.png');
    }
}

/**
 * Return the absolute URL for a link image
 *
 * @param string $type Either 'cards' or 'large'
 * @param string $filename The URL saved in link->image_filename
 *
 * @return string
 */
function url_link_image_full($type, $filename)
{
    return \Minz\Url::baseUrl() . url_link_image($type, $filename);
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
    if (!$filename) {
        return url_static('default-avatar.svg');
    }

    $media_path = \Minz\Configuration::$application['media_path'];
    $filepath = "{$media_path}/avatars/{$filename}";
    $modification_time = @filemtime($filepath);
    if ($modification_time) {
        $file_url = \Minz\Url::path() . "/media/avatars/{$filename}";
        return $file_url . '?' . $modification_time;
    } else {
        return url_static('default-avatar.svg');
    }
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
 * Format news preferences so it's readable for humans.
 *
 * @param \flusio\models\NewsPreferences $preferences
 *
 * @return string
 */
function format_news_preferences($preferences)
{
    $duration = format_news_duration($preferences->duration);
    $froms = [];
    if ($preferences->from_bookmarks) {
        $froms[] = _('bookmarks');
    }
    if ($preferences->from_followed) {
        $froms[] = _('followed collections');
    }
    if ($preferences->from_topics) {
        $froms[] = _('points of interest');
    }
    $from = human_implode($froms, ', ', _(' and '));

    return _f('Get about %s of reading from your %s.', $duration, $from);
}

/**
 * Return a duration (in minutes) as a formatted string (only suitable for news
 * preferences).
 *
 * @param integer $duration
 *
 * @return string
 */
function format_news_duration($duration)
{
    $hours = floor($duration / 60);
    $minutes = $duration % 60;

    if ($hours === 0.0) {
        return _nf('%d&nbsp;minute', '%d&nbsp;minutes', $minutes, $minutes);
    }

    if ($minutes === 0) {
        return _nf('%d&nbsp;hour', '%d&nbsp;hours', $hours, $hours);
    }

    return _f('%d&nbsp;h&nbsp;%d', $hours, $minutes);
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
