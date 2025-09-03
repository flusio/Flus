<?php

namespace SpiderBits;

/**
 * HTTP made easy.
 *
 * @phpstan-type Options array{
 *     'user_agent'?: non-empty-string,
 *     'auth_basic'?: non-empty-string,
 *     'force_ipv4'?: bool,
 *     'interface'?: ?non-empty-string,
 *     'max_size'?: int,
 *     'headers'?: array<string, string>,
 * }
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Http
{
    public int $timeout = 5;

    /** @var non-empty-string */
    public string $user_agent = 'SpiderBits/0.0.1 (' . PHP_OS . '; https://github.com/flusio/SpiderBits)';

    /** @var array<string, string> */
    public array $headers = [];

    public static string $mock_host = '';

    /**
     * Make a GET HTTP request.
     *
     * @param non-empty-string $url
     * @param array<string, mixed> $parameters
     * @param Options $options
     *
     * @throws \SpiderBits\HttpError
     */
    public function get(string $url, array $parameters = [], array $options = []): Response
    {
        if ($parameters) {
            $parameters_query = http_build_query($parameters);
            if (strpos($url, '?') === false) {
                $url .= '?' . $parameters_query;
            } else {
                $url .= '&' . $parameters_query;
            }
        }

        return $this->request('get', $url, [], $options);
    }

    /**
     * Make a POST HTTP request.
     *
     * @param non-empty-string $url
     * @param array<string, mixed> $parameters
     * @param Options $options
     *
     * @throws \SpiderBits\HttpError
     */
    public function post(string $url, array $parameters = [], array $options = []): Response
    {
        return $this->request('post', $url, $parameters, $options);
    }

    /**
     * Generic method that uses Curl to make HTTP requests.
     *
     * @param 'get'|'post' $method
     * @param non-empty-string $url
     * @param array<string, mixed> $parameters
     * @param Options $options
     *
     * @throws \SpiderBits\HttpError
     */
    private function request(string $method, string $url, array $parameters = [], array $options = []): Response
    {
        if (self::$mock_host) {
            $encoded_url = urlencode($url);
            $url = self::$mock_host . '?url=' . $encoded_url;
        }

        if (isset($options['user_agent'])) {
            $user_agent = $options['user_agent'];
        } else {
            $user_agent = $this->user_agent;
        }

        if (isset($options['headers'])) {
            $request_headers = array_merge($this->headers, $options['headers']);
        } else {
            $request_headers = $this->headers;
        }

        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        curl_setopt($curl_handle, CURLOPT_HEADER, false);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl_handle, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($curl_handle, CURLOPT_ENCODING, '');

        if ($method === 'post') {
            if ($this->isJsonContentType($request_headers)) {
                $postfields = json_encode($parameters);

                if ($postfields === false) {
                    throw new HttpError('Parameters cannot be JSON encoded.');
                }
            } else {
                $postfields = http_build_query($parameters);
            }

            curl_setopt($curl_handle, CURLOPT_POST, true);
            curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $postfields);
        }

        if (isset($options['auth_basic'])) {
            curl_setopt($curl_handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl_handle, CURLOPT_USERPWD, $options['auth_basic']);
        }

        if (isset($options['force_ipv4']) && $options['force_ipv4']) {
            curl_setopt($curl_handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }

        if (isset($options['interface'])) {
            curl_setopt($curl_handle, CURLOPT_INTERFACE, $options['interface']);
        }

        if (isset($options['max_size'])) {
            $max_size = $options['max_size'];
            curl_setopt($curl_handle, CURLOPT_BUFFERSIZE, 16 * 1024);
            curl_setopt($curl_handle, CURLOPT_NOPROGRESS, false);
            curl_setopt(
                $curl_handle,
                CURLOPT_PROGRESSFUNCTION,
                function ($resource, $download_size, $downloaded, $upload_size, $uploaded) use ($max_size): int {
                    return ($downloaded > $max_size) ? 1 : 0;
                }
            );
        }

        $request_headers = array_map(function (string $name, string $value): string {
            return "{$name}: {$value}";
        }, array_keys($request_headers), $request_headers);
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $request_headers);

        $headers = '';
        $done = false;
        curl_setopt(
            $curl_handle,
            CURLOPT_HEADERFUNCTION,
            function ($curl, string $current_header) use (&$headers, &$done): int {
                if ($done) {
                    // we want to drop headers from previous requests (e.g. on
                    // 302 responses for instance)
                    $headers = '';
                    $done = false;
                }

                $headers .= $current_header;

                if ($current_header === "\r\n") {
                    $done = true;
                }

                return strlen($current_header);
            }
        );

        /** @var string|false */
        $data = curl_exec($curl_handle);
        $status = curl_getinfo($curl_handle, CURLINFO_RESPONSE_CODE);

        if ($data === false) {
            $error = curl_error($curl_handle);

            curl_close($curl_handle);

            throw new HttpError($error);
        }

        curl_close($curl_handle);

        return new Response($status, $data, $headers);
    }

    /**
     * Return whether a content-type header is defined as application/json or not.
     *
     * @param array<string, string> $headers
     */
    private function isJsonContentType(array $headers): bool
    {
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'content-type') {
                return $value === 'application/json';
            }
        }

        return false;
    }
}
