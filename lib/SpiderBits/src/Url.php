<?php

namespace SpiderBits;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Url
{
    /**
     * Make an URL absolute.
     *
     * If the URL is already absolute, it’s returned as it is. Otherwise, it’s
     * returned relatively to a base URL. You must make sure to pass a valid
     * base or the URL will be returned directly.
     */
    public static function absolutize(string $url, string $base_url): string
    {
        $parsed_url = parse_url(trim($url));
        if (isset($parsed_url['scheme'])) {
            // The initial URL is already absolute, return as it is.
            return $url;
        }

        if (strpos($url, '#') === 0) {
            // If the URL starts by a hash, it should simply be added to the
            // base URL (by removing its own hash if any).
            if (strpos($base_url, '#') !== false) {
                $base_url = strtok($base_url, '#');
            }
            return $base_url . $url;
        }

        $parsed_base_url = parse_url(trim($base_url));
        if (!isset($parsed_base_url['host'])) {
            // If there is no host in the base URL, we can’t do anything.
            // Return the URL as it is
            return $url;
        }

        if (!isset($parsed_base_url['scheme'])) {
            // Base URL should always be absolute, but in case it’s not, we
            // rely on a default scheme
            $parsed_base_url['scheme'] = 'http';
        }

        if (!isset($parsed_base_url['path'])) {
            $parsed_base_url['path'] = '';
        }

        if (strpos($url, '//') === 0) {
            // The initial URL protocol is relative to the base URL, but the
            // rest of the URL is already absolute.
            return $parsed_base_url['scheme'] . ':' . $url;
        }

        $absolute_url = $parsed_base_url['scheme'] . '://';
        $absolute_url .= $parsed_base_url['host'];
        if (isset($parsed_base_url['port'])) {
            $absolute_url .= ':' . $parsed_base_url['port'];
        }

        if (strpos($url, '/') === 0) {
            // If initial URL starts with a slash, it’s an absolute path
            $absolute_url .= $url;
        } else {
            // Else, it’s a relative path, rebuild a path by removing the last
            // part of the $base_path (i.e. $url is relative to the document
            // served by $base_url).
            $base_path = trim($parsed_base_url['path'], '/');
            $base_path_split = explode('/', $base_path);
            array_pop($base_path_split);
            $base_path = implode('/', $base_path_split);

            if ($base_path) {
                $base_path = '/' . $base_path;
            }

            $absolute_url .= $base_path . '/' . $url;
        }

        return $absolute_url;
    }

    /**
     * Return the given URL as a sanitized string. It allows to compute a
     * canonical URL.
     *
     * Algorithm comes from https://developers.google.com/safe-browsing/v4/urls-hashing#canonicalization
     * with few adaptations.
     */
    public static function sanitize(string $url): string
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

        // Get the scheme (default is http)
        if (isset($parsed_url['scheme'])) {
            $scheme = mb_strtolower(self::fullPercentDecode($parsed_url['scheme']));
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

        $host = self::fullPercentDecode($host);

        // Clean the extra dots from the host
        $host = trim($host, '.');
        $host = preg_replace('/\.{2,}/', '.', $host);

        // The host can be a valid integer ip address, we want to normalize it
        // to 4 dot-separated decimal values
        if ($host && filter_var($host, FILTER_VALIDATE_INT) !== false) {
            $host = long2ip(intval($host));
        }

        // idn_to_ascii allows to transform an unicode hostname to an
        // ASCII representation
        // @see https://en.wikipedia.org/wiki/Punycode
        // It also lowercases the string.
        if ($host) {
            $host = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        }

        if (!$host) {
            $host = '';
        }

        // Get the path with ./ and ../ paths replaced.
        if (isset($parsed_url['path'])) {
            $path = self::fullPercentDecode($parsed_url['path']);
            $path = self::normalizePath($path);
        } else {
            $path = '/';
        }

        // We finally rebuild the sanitized URL.
        $sanitized_url = self::percentEncode($scheme) . '://';
        $sanitized_url .= self::percentEncode($host);
        if (isset($parsed_url['port'])) {
            $sanitized_url .= ':' . $parsed_url['port'];
        }
        $sanitized_url .= self::percentEncode($path);
        if (isset($parsed_url['query'])) {
            $sanitized_url .= '?' . self::percentRecodeQuery($parsed_url['query']);
        } elseif (strpos($url, '?') !== false) {
            // If the initial URL had a `?` without query string, `parse_url()`
            // doesn't return the query component. We want to keep the question
            // mark though.
            $sanitized_url .= '?';
        }
        if (isset($parsed_url['fragment'])) {
            $fragment = self::fullPercentDecode($parsed_url['fragment']);
            $sanitized_url .= '#' . self::percentEncode($fragment);
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
    private static function normalizePath(string $path): string
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
     * Percent-decode a value.
     *
     * It applies rawurldecode() (or urldecode based on the raw parameter) on
     * the value as long as a percent-encoded character is detected.
     */
    private static function fullPercentDecode(string $value, bool $raw = true): string
    {
        while (
            (preg_match('/%[0-9A-Fa-f]{2}/', $value) === 1) ||
            (!$raw && str_contains($value, '+'))
        ) {
            if ($raw) {
                $value = rawurldecode($value);
            } else {
                $value = urldecode($value);
            }
        }

        return $value;
    }

    /**
     * Percent-encode a value.
     *
     * Contrary to urlencode() and rawurlencode(), this method only encodes
     * ASCII characters <= 32, >= 127, '"', "#" and "%". This leaves for
     * instance "/", ":" and "?" as they are.
     *
     * @see https://www.php.net/manual/function.rawurlencode.php
     * @see https://en.wikipedia.org/wiki/ASCII
     */
    private static function percentEncode(string $value): string
    {
        $escaped_url = '';
        foreach (str_split($value) as $char) {
            $ord = ord($char);
            if ($ord > 32 && $ord < 127 && $char !== '"' && $char !== '#' && $char !== '%') {
                $escaped_url .= $char;
            } else {
                $escaped_url .= rawurlencode($char);
            }
        }
        return $escaped_url;
    }

    /**
     * Re-encode the query parameters.
     */
    private static function percentRecodeQuery(string $query): string
    {
        $decoded_parameters = [];
        $parameters = self::parseQuery($query);
        foreach ($parameters as $name => $value) {
            $name = urlencode(self::fullPercentDecode($name, raw: false));
            if (is_array($value)) {
                $value = array_map(function (?string $partial_value): ?string {
                    if ($partial_value === null) {
                        return null;
                    } else {
                        return urlencode(self::fullPercentDecode($partial_value, raw: false));
                    }
                }, $value);
            } elseif ($value !== null) {
                $value = urlencode(self::fullPercentDecode($value, raw: false));
            }
            $decoded_parameters[$name] = $value;
        }
        return self::buildQuery($decoded_parameters);
    }

    /**
     * Parse a query to parameters similarly to the parse_str function.
     *
     * Differences compared to parse_str:
     *
     * - the parameters are returned as an array;
     * - the parameters with the same name are returned in an array instead of
     *   being overwritten;
     * - the value of parameters with no `=` sign is `null` instead of empty
     *   string.
     *
     * @see https://www.php.net/manual/function.parse-str.php
     *
     * @return array<string, string|null|array<?string>>
     */
    public static function parseQuery(string $query): array
    {
        if (empty($query)) {
            return [];
        }

        $raw_parameters = explode('&', $query);
        foreach ($raw_parameters as $raw_parameter) {
            $exploded_parameter = explode('=', $raw_parameter, 2);
            $name = $exploded_parameter[0];

            if (count($exploded_parameter) === 2) {
                $value = $exploded_parameter[1];
            } else {
                // The parameter may not contain a "=". Setting the value to
                // null allows to distinguish both cases: `?foo` (value will
                // be null) and `?foo=` (value will be empty string). This
                // allows to rebuild the query properly later.
                $value = null;
            }

            if (isset($parameters[$name])) {
                if (!is_array($parameters[$name])) {
                    $parameters[$name] = [$parameters[$name]];
                }
                $parameters[$name][] = $value;
            } else {
                $parameters[$name] = $value;
            }
        }

        return $parameters;
    }

    /**
     * Rebuild a query from parameters similarly to the http_build_query function.
     *
     * Differences compared to http_build_query:
     *
     * - parameters can only be an array;
     * - it takes less arguments;
     * - the arg separator is always "&";
     * - it is expected than values are already encoded;
     * - if a value is null, the parameter name is still appended, but without
     *   an "=" sign.
     *
     * @see https://www.php.net/manual/function.http-build-query.php
     *
     * @param array<string, string|null|array<?string>> $parameters
     */
    public static function buildQuery(array $parameters): string
    {
        $built_parameters = [];
        foreach ($parameters as $name => $value) {
            if ($value === null) {
                $built_parameters[] = $name;
            } elseif (is_array($value)) {
                foreach ($value as $partial_value) {
                    $built_parameters[] = "{$name}={$partial_value}";
                }
            } else {
                $built_parameters[] = "{$name}={$value}";
            }
        }
        return implode('&', $built_parameters);
    }

    /**
     * Return true if the given URL is valid, false otherwise.
     *
     * @param string[] $accepted_schemes
     */
    public static function isValid(string $url, array $accepted_schemes = ['http', 'https']): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $url_components = parse_url($url);

        if (
            !$url_components ||
            !isset($url_components['scheme']) ||
            !isset($url_components['host'])
        ) {
            return false;
        }

        $url_scheme = strtolower($url_components['scheme']);
        return in_array($url_scheme, $accepted_schemes);
    }
}
