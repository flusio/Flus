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
     * Return if a string starts with a substring
     *
     * @see https://stackoverflow.com/a/834355
     *
     * @param string $haystack The string to look into
     * @param string $needle The substring to look for
     *
     * @return boolean True if $haystack starts with $needle
     */
    public static function startsWith($haystack, $needle)
    {
         $needle_length = strlen($needle);
         return substr($haystack, 0, $needle_length) === $needle;
    }

    /**
     * Return if a string ends with a substring
     *
     * @see https://stackoverflow.com/a/834355
     *
     * @param string $haystack The string to look into
     * @param string $needle The substring to look for
     *
     * @return boolean True if $haystack ends with $needle
     */
    public static function endsWith($haystack, $needle)
    {
        $needle_length = strlen($needle);
        return substr($haystack, -$needle_length, $needle_length) === $needle;
    }

    /**
     * Return whether a string contains a substring or not
     *
     * @param string $haystack The string to look into
     * @param string $needle The substring to look for
     *
     * @return boolean True if $haystack contains with $needle
     */
    public static function contains($haystack, $needle)
    {
        if (!$needle) {
            return true;
        }

        return strpos($haystack, $needle) !== false;
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
        $parsed_url = parse_url($url);
        $host = idn_to_utf8($parsed_url['host'], IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        if (self::startsWith($host, 'www.')) {
            return substr($host, 4);
        } else {
            return $host;
        }
    }
}
