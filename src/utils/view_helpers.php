<?php

use App\auth;
use App\models;

/**
 * This file contains helper methods to be used in view files. It doesn't
 * declare a namespace on purpose.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */

function csrf_token(): string
{
    return \App\Csrf::generate();
}

/**
 * Format a datetime in the current locale.
 *
 * @see https://www.php.net/manual/class.intldateformatter.php
 * @see https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax
 */
function _date(\DateTimeInterface $date, string $format): string
{
    $current_locale = \App\utils\Locale::currentLocale();
    return \Minz\Template\SimpleTemplateHelpers::formatDate($date, $format, $current_locale);
}

/**
 * Format a number accordingly to the current locale
 */
function format_number(int|float $number): string
{
    $locale = \App\utils\Locale::currentLocale();
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
 * Format a publication frequency.
 *
 * It displays the frequency in priority by day, then week, month or year,
 * depending on the first frequency that is greater or equal to 1.
 */
function format_publication_frequency(int $frequency_per_year): string
{
    $frequency_per_day = (int) floor($frequency_per_year / 365);
    $frequency_per_week = (int) floor($frequency_per_year / 52);
    $frequency_per_month = (int) floor($frequency_per_year / 12);

    if ($frequency_per_day > 0) {
        return _nf('%d link per day', '%d links per day', $frequency_per_day, $frequency_per_day);
    }

    if ($frequency_per_week > 0) {
        return _nf('%d link per week', '%d links per week', $frequency_per_week, $frequency_per_week);
    }

    if ($frequency_per_month > 0) {
        return _nf('%d link per month', '%d links per month', $frequency_per_month, $frequency_per_month);
    }

    if ($frequency_per_year > 0) {
        return _nf('%d link per year', '%d links per year', $frequency_per_year, $frequency_per_year);
    }

    return _('Inactive');
}

function is_environment(string $environment): bool
{
    return \App\Configuration::$environment === $environment;
}

function get_app_configuration(string $key): mixed
{
    return \App\Configuration::$application[$key] ?? '';
}

function get_current_host(): string
{
    return \App\Configuration::$url_options['host'];
}

function get_current_locale(): string
{
    return \App\utils\Locale::currentLocale();
}

/**
 * Transform a locale to BCP47 format
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/lang
 * @see https://www.ietf.org/rfc/bcp/bcp47.txt
 */
function locale_to_bcp_47(string $locale): string
{
    $splitted_locale = explode('_', $locale, 2);

    if (count($splitted_locale) === 1) {
        return $splitted_locale[0];
    }

    return $splitted_locale[0] . '-' . strtoupper($splitted_locale[1]);
}

/**
 * Return the given reading time as a human-readable string.
 */
function format_reading_time(int $reading_time): string
{
    if ($reading_time < 1) {
        return _('< 1 min');
    } else {
        return _f('%s&nbsp;min', format_number($reading_time));
    }
}

/**
 * Return the relative URL for an asset file (under public/assets/ or
 * public/dev_assets/ folder)
 */
function url_asset(string $filename): string
{
    if (\App\Configuration::$environment === 'development') {
        $assets_folder = 'dev_assets';
    } else {
        $assets_folder = 'assets';
    }

    $filepath = \App\Configuration::$app_path . "/public/{$assets_folder}/{$filename}";
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
 * @param 'cards'|'large'|'avatars' $type
 */
function url_media(string $type, ?string $filename, string $default = 'default-card.png'): string
{
    if ($default === 'default-card.png' && feature_enabled('alpha')) {
        $default = 'default-card-beta.png';
    }

    if (!$filename) {
        return url_static($default);
    }

    $media_path = \App\Configuration::$application['media_path'];
    $subpath = \App\utils\Belt::filenameToSubpath($filename);
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
 * @param 'cards'|'large'|'avatars' $type
 */
function url_media_full(string $type, ?string $filename): string
{
    return \Minz\Url::baseUrl() . url_media($type, $filename);
}

/**
 * Return the relative URL of an avatar.
 */
function url_avatar(?string $filename): string
{
    return url_media('avatars', $filename, 'default-avatar.svg');
}

/**
 * Return a SVG icon.
 */
function icon(string $icon_name, string $additional_class_names = ''): string
{
    $class = "icon icon--{$icon_name}";
    if ($additional_class_names) {
        $class .= ' ' . $additional_class_names;
    }

    $url_icons = \Minz\Template\SimpleTemplateHelpers::urlStatic('icons.svg');
    $svg = "<svg class=\"{$class}\" aria-hidden=\"true\" width=\"36\" height=\"36\">";
    $svg .= "<use xlink:href=\"{$url_icons}#{$icon_name}\"/>";
    $svg .= '</svg>';
    return $svg;
}

/**
 * Join items from a list, allowing to change the last separator to make it
 * more natural for humans.
 *
 * It acts as normal `implode()` function except the last item is concatenated
 * with $last_separator. Please note that the array is the first parameter.
 *
 * @param string[] $array
 */
function human_implode(array $array, string $separator, string $last_separator): string
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
 * Return the list of publishers of a collection.
 */
function collection_publishers(\App\models\Collection $collection, ?\App\models\User $current_user): string
{
    $owner = $collection->owner();
    $shares = $collection->shares(['access_type' => 'write']);

    $publishers = array_map(function (\App\models\CollectionShare $share): \App\models\User {
        return $share->user();
    }, $shares);
    array_unshift($publishers, $owner);

    $publishers_as_strings = array_map(function ($user) use ($current_user): string {
        $url_profile = url('profile', ['id' => $user->id]);
        if ($current_user && $user->id === $current_user->id) {
            $username = _('you');
        } else {
            $username = protect($user->username);
        }

        return "<a href=\"{$url_profile}\">{$username}</a>";
    }, $publishers);

    return implode(', ', $publishers_as_strings);
}

function feature_enabled(string $feature): bool
{
    $current_user = auth\CurrentUser::get();
    return $current_user && models\FeatureFlag::isEnabled($feature, $current_user->id);
}
