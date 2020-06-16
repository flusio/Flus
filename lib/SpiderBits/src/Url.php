<?php

namespace SpiderBits;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Url
{
    /**
     * Return the given URL as a sanitized string. It allows to compute a
     * canonical URL.
     *
     * Algorithm comes from https://developers.google.com/safe-browsing/v4/urls-hashing#canonicalization
     * with few adaptations.
     *
     * @param string $url
     *
     * @return string
     */
    public static function sanitize($url)
    {
        // Remove unwanted characters
        $cleaned_url = trim($url);
        $cleaned_url = str_replace(["\t", "\r", "\n"], '', $cleaned_url);

        if (!$cleaned_url) {
            return '';
        }

        // Parse components of the URL. Note we percent-decode later since
        // "%23" are replaced by hashes (#) and could lead to a bad parsing.
        $parsed_url = parse_url($cleaned_url);
        if (!$parsed_url) {
            return '';
        }

        // Then, we decode each part of the parsed URL. We want to decode as
        // long as percent-encoding characters exist.
        foreach ($parsed_url as $component => $value) {
            while (preg_match('/%[0-9A-Fa-f]{2}/', $value) === 1) {
                $value = urldecode($value);
            }

            $parsed_url[$component] = $value;
        }

        // Get the scheme (default is http)
        if (isset($parsed_url['scheme'])) {
            $scheme = $parsed_url['scheme'];
        } else {
            $scheme = 'http';
        }

        // Get the host. In some situations (e.g. scheme is omitted), the host
        // is considered as a path by `parse_url()`.
        if (isset($parsed_url['host'])) {
            $host = $parsed_url['host'];
        } elseif (isset($parsed_url['path'])) {
            $host = trim($parsed_url['path'], '/');
            unset($parsed_url['path']);
        } else {
            $host = '';
        }

        // Clean the extra dots from the host
        $host = trim($host, '.');
        $host = preg_replace('/\.{2,}/', '.', $host);

        // The host can be a valid integer ip address, we want to normalize it
        // to 4 dot-separated decimal values
        if (filter_var($host, FILTER_VALIDATE_INT) !== false) {
            $host = long2ip($host);
        }

        // idn_to_ascii allows to transform an unicode hostname to an
        // ASCII representation
        // @see https://en.wikipedia.org/wiki/Punycode
        // It also lowercases the string.
        $host = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

        // Get the path with ./ and ../ paths replaced.
        if (isset($parsed_url['path'])) {
            $path = self::normalizePath($parsed_url['path']);
        } else {
            $path = '/';
        }

        // We finally rebuild the sanitized URL.
        $sanitized_url = $scheme . '://';
        $sanitized_url .= $host;
        if (isset($parsed_url['port'])) {
            $sanitized_url .= ':' . $parsed_url['port'];
        }
        $sanitized_url .= $path;
        if (isset($parsed_url['query'])) {
            $sanitized_url .= '?' . $parsed_url['query'];
        } elseif (strpos($url, '?') !== false) {
            // If the initial URL had a `?` without query string, `parse_url()`
            // doesn't return the query component. We want to keep the question
            // mark though.
            $sanitized_url .= '?';
        }

        // Re-percent-encode the URL. We don't want to use directly
        // rawurlencode() since it will convert slashes (/), colons (:) and
        // question mark (?)
        $sanitized_url = self::percentEncode($sanitized_url);

        // The fragment must be added afterwhile or the hash (#) could be
        // converted.
        if (isset($parsed_url['fragment'])) {
            $sanitized_url .= '#' . self::percentEncode($parsed_url['fragment']);
        }

        return $sanitized_url;
    }

    /**
     * Resolves references to ./, ../ and extra / characters from a path.
     *
     * It is similar to the realpath() function, but it doesn't require the
     * file to exist.
     *
     * @see https://www.php.net/manual/function.realpath.php
     *
     * @param string $path
     *
     * @return string
     */
    private static function normalizePath($path)
    {
        $realpath = array();

        // We just simulate browsing the path, segment by segment.
        $path_segments = explode('/', $path);
        foreach ($path_segments as $path_segment) {
            // . is the current folder, so we can ignore it. Same if the
            // segment is empty, we don't want it.
            if ($path_segment === '.' || strlen($path_segment) === 0) {
                continue;
            }

            if ($path_segment === '..') {
                // .. is the parent folder, so we must go back to the parent
                // level
                array_pop($realpath);
            } else {
                $realpath[] = $path_segment;
            }
        }

        // Rebuild the path and make sure to keep the first and last slash if
        // they existed in the original path.
        $realpath = implode('/', $realpath);
        if ($path[0] === '/') {
            $realpath = '/' . $realpath;
        }
        if ($realpath !== '/' && $path[strlen($path) - 1] === '/') {
            $realpath = $realpath . '/';
        }

        return $realpath;
    }

    /**
     * Percent-encode a URL.
     *
     * Contrary to urlencode() and rawurlencode(), this method only encodes
     * ASCII characters <= 32, >= 127, '"', "#" and "%". This leaves for
     * instance "/", ":" and "?" as they are.
     *
     * @see https://www.php.net/manual/function.rawurlencode.php
     * @see https://en.wikipedia.org/wiki/ASCII
     *
     * @param string url
     *
     * @return string
     */
    private static function percentEncode($url)
    {
        $escaped_url = '';
        foreach (str_split($url) as $char) {
            $ord = ord($char);
            if ($ord > 32 && $ord < 127 && $char !== '"' && $char !== '#' && $char !== '%') {
                $escaped_url .= $char;
            } else {
                $escaped_url .= rawurlencode($char);
            }
        }
        return $escaped_url;
    }
}
