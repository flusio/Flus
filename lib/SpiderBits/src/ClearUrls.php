<?php

namespace SpiderBits;

/**
 * @phpstan-type ClearUrlsProvider array{
 *     'urlPattern': string,
 *     'completeProvider': bool,
 *     'rules': string[],
 *     'referralMarketing': string[],
 *     'rawRules': string[],
 *     'exceptions': string[],
 *     'redirections': string[],
 *     'forceRedirection': bool,
 * }
 *
 * @phpstan-type ClearUrlsData array{
 *     'providers': array<string, ClearUrlsProvider>,
 * }
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ClearUrls
{
    /** @var ?ClearUrlsData */
    private static ?array $clear_urls_data = null;

    /**
     * Clear a URL from its tracker parameters.
     *
     * It uses ClearURLs rules internally to clear the URL. Some behaviours
     * differ from ClearURLs:
     *
     * - if a URL matches a rule with `completeProvider=true`, an empty string
     *   is returned;
     * - referralMarketing parameters are always removed (i.e. there's no
     *   option to allow referral marketing);
     * - `forceRedirection` is ignored.
     *
     * @see https://docs.clearurls.xyz/1.23.0/specs/rules/
     *
     * @throws \Exception
     *     Raised if the clearurls-data.minify.json file cannot be read, or
     *     cannot be parsed to JSON.
     */
    public static function clear(string $url): string
    {
        // A note about the regex used in this method: PCRE patterns must be
        // enclosed by delimiters. They are generally "/", "#" or "~". Problem:
        // these characters are often used in URLs, and so they can be present
        // in the patterns (in which case the `preg_*` functions may fail). It
        // is why I use "@" instead which has very few chances to be used in
        // the patterns.

        $providers = self::loadClearUrlsProviders();
        foreach ($providers as $provider_name => $provider) {
            // set default values so we don't have to check for their presence
            $provider = array_merge([
                'urlPattern' => '',
                'completeProvider' => false,
                'rules' => [],
                'rawRules' => [],
                'referralMarketing' => [],
                'exceptions' => [],
                'redirections' => [],
                'forceRedirection' => false,
            ], $provider);

            // First, verify our URL matches the urlPattern (if not, skip it).
            if (!preg_match("@{$provider['urlPattern']}@i", $url)) {
                continue;
            }

            // Secondly, verify the URL is not in the exceptions list (if it
            // is, skip it).
            $is_exception = false;
            foreach ($provider['exceptions'] as $exception_pattern) {
                if (preg_match("@{$exception_pattern}@i", $url)) {
                    $is_exception = true;
                    break;
                }
            }

            if ($is_exception) {
                continue;
            }

            // If the provider is "completeProvider", the URL should be blocked
            // (i.e. an empty string in Flus context).
            if ($provider['completeProvider']) {
                return '';
            }

            // Extract redirections from the URL if any (e.g.
            // https://google.com/url?q=https://example.com)
            // If we find a redirection, we call clear() recursively (but
            // the current call ends here)
            foreach ($provider['redirections'] as $redirection_pattern) {
                $result = preg_match("@{$redirection_pattern}@i", $url, $matches);
                if ($result && count($matches) >= 2) {
                    // the redirected URL is in the first Regex group (index 0
                    // is the full matching string).
                    $redirected_url = rawurldecode($matches[1]);
                    $redirected_url = Url::sanitize($redirected_url);
                    return self::clear($redirected_url);
                }
            }

            // Directly remove matching rawRules from the URL
            foreach ($provider['rawRules'] as $raw_rule_pattern) {
                $url = preg_replace("@{$raw_rule_pattern}@i", '', $url);

                if ($url === null) {
                    return '';
                }
            }

            // Apply rules and referralMarketing rules to query parameters.
            // Since trackers can also be inserted in the URL fragment, we
            // clear it as well.
            $rules = array_merge(
                $provider['rules'],
                $provider['referralMarketing']
            );

            $parsed_url = parse_url($url);

            if ($parsed_url === false) {
                return '';
            }

            $parsed_url['scheme'] = $parsed_url['scheme'] ?? 'http';
            $parsed_url['host'] = $parsed_url['host'] ?? '';
            $parsed_url['query'] = $parsed_url['query'] ?? '';
            $parsed_url['fragment'] = $parsed_url['fragment'] ?? '';

            $cleared_query = self::clearQuery($parsed_url['query'], $rules);
            $cleared_fragment = self::clearQuery($parsed_url['fragment'], $rules);

            // Finally, rebuild the URL from the parsed and cleared parts
            $rebuilt_url = $parsed_url['scheme'] . '://';
            $rebuilt_url .= $parsed_url['host'];
            if (!empty($parsed_url['port'])) {
                $rebuilt_url .= ':' . $parsed_url['port'];
            }
            $rebuilt_url .= $parsed_url['path'] ?? '';

            $had_empty_query = strpos($url, '?') !== false && $parsed_url['query'] === '';
            if (!empty($cleared_query) || $had_empty_query) {
                $rebuilt_url .= '?' . $cleared_query;
            }

            $had_empty_fragment = strpos($url, '#') !== false && $parsed_url['fragment'] === '';
            if (!empty($cleared_fragment) || $had_empty_fragment) {
                $rebuilt_url .= '#' . $cleared_fragment;
            }

            $url = $rebuilt_url;
        }

        return $url;
    }

    /**
     * Load and return the ClearURLs providers rules from the file.
     *
     * The file is only loaded once, even if you call this method multiple
     * times.
     *
     * @throws \Exception
     *     Raised if the clearurls-data.minify.json file cannot be read, or
     *     cannot be parsed to JSON.
     *
     * @return array<string, ClearUrlsProvider>
     */
    private static function loadClearUrlsProviders(): array
    {
        if (self::$clear_urls_data === null) {
            $rules_path = realpath(__DIR__ . '/../lib/ClearUrlsRules/data.min.json');

            if ($rules_path === false) {
                throw new \Exception('ClearUrlsRules file does not exist.');
            }

            $clear_urls_file_content = @file_get_contents($rules_path);

            if ($clear_urls_file_content === false) {
                throw new \Exception($rules_path . ' file cannot be found.');
            }

            $clear_urls_data = json_decode($clear_urls_file_content, true);

            if (!is_array($clear_urls_data) || !isset($clear_urls_data['providers'])) {
                throw new \Exception(
                    $rules_path . ' file does not contain valid JSON.'
                );
            }

            /** @var ClearUrlsData */
            $clear_urls_data = $clear_urls_data;
            self::$clear_urls_data = $clear_urls_data;
        }

        assert(self::$clear_urls_data !== null);

        return self::$clear_urls_data['providers'];
    }

    /**
     * Remove parameters from a URL query.
     *
     * Parameters are removed from the string if their names match any of the
     * provided rules patterns.
     *
     * @param string[] $rules
     */
    private static function clearQuery(string $query, array $rules): string
    {
        $parameters = Url::parseQuery($query);

        foreach ($parameters as $name => $value) {
            foreach ($rules as $rule_pattern) {
                if (preg_match("@{$rule_pattern}@i", $name)) {
                    unset($parameters[$name]);
                    break;
                }
            }
        }

        return Url::buildQuery($parameters);
    }
}
