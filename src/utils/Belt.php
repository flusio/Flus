<?php

namespace flusio\utils;

/**
 * The Belt is a collection of useful snippets to reuse within the application.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Belt
{
    /**
     * Strip a substring if string starts with.
     *
     * If the string doesn’t start with substring, the string is returned.
     *
     * @param string $string The string to look into
     * @param string $substring The string to strip
     *
     * @return string
     */
    public static function stripsStart($string, $substring)
    {
        if (str_starts_with($string, $substring)) {
            return substr($string, strlen($substring));
        } else {
            return $string;
        }
    }

    /**
     * Strip a substring if string ends with.
     *
     * If the string doesn’t end with substring, the string is returned.
     *
     * @param string $string The string to look into
     * @param string $substring The string to strip
     *
     * @return string
     */
    public static function stripsEnd($string, $substring)
    {
        if (str_ends_with($string, $substring)) {
            return substr($string, 0, -strlen($substring));
        } else {
            return $string;
        }
    }

    /**
     * Keep only the first $size characters of a string.
     *
     * @param string $string The string to shorten
     * @param integer $size The max size of the final string
     *
     * @return string
     */
    public static function cut($string, $size)
    {
        return mb_substr($string, 0, $size);
    }

    /**
     * Extract the host from a URL. If the host starts with "www.", they are
     * removed from the host.
     *
     * @param string $url
     *
     * @return string
     */
    public static function host($url)
    {
        if (!$url) {
            return '';
        }

        $parsed_url = parse_url($url);
        if (!isset($parsed_url['host'])) {
            return '';
        }

        $host = idn_to_utf8($parsed_url['host'], IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        if (str_starts_with($host, 'www.')) {
            return substr($host, 4);
        } else {
            return $host;
        }
    }

    /**
     * Remove the scheme (http:// or https://) from a URL.
     *
     * @param string $url
     *
     * @return string
     */
    public static function removeScheme($url)
    {
        if (!$url) {
            return '';
        }

        return preg_replace('#^https?://(.*)#', '\1', $url);
    }

    /**
     * Return a subpath from a file name (used for media files).
     *
     * The name must contain at least 9 characters, excluding the dots. The
     * function returns an empty string otherwise.
     *
     * @param string $name
     *
     * @return string
     */
    public static function filenameToSubpath($filename)
    {
        $name = str_replace('.', '', $filename);
        if (strlen($name) < 9) {
            return '';
        }

        return substr($name, 0, 3) . '/' . substr($name, 3, 3) . '/' . substr($name, 6, 3);
    }
}
