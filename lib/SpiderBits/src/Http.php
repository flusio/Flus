<?php

namespace SpiderBits;

/**
 * HTTP made easy.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Http
{
    /** @var integer */
    public $timeout = 5;

    /** @var string */
    public $user_agent = 'SpiderBits/0.0.1 (' . PHP_OS . '; https://github.com/flusio/SpiderBits)';

    /** @var array */
    public $headers = [];

    /** @var string */
    public static $mock_host = '';

    /**
     * Make a GET HTTP request.
     *
     * @param string $url
     * @param array $parameters
     * @param array $options
     *
     * @throws \SpiderBits\HttpError
     *
     * @return \SpiderBits\Response
     */
    public function get($url, $parameters = [], $options = [])
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
     * @param string $url
     * @param array $parameters
     * @param array $options
     *
     * @throws \SpiderBits\HttpError
     *
     * @return \SpiderBits\Response
     */
    public function post($url, $parameters = [], $options = [])
    {
        return $this->request('post', $url, $parameters, $options);
    }

    /**
     * Generic method that uses Curl to make HTTP requests.
     *
     * @param string $method get or post
     * @param string $url
     * @param array $parameters
     * @param array $options
     *
     * @throws \SpiderBits\HttpError
     *
     * @return \SpiderBits\Response
     */
    private function request($method, $url, $parameters = [], $options = [])
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

        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        curl_setopt($curl_handle, CURLOPT_HEADER, false);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl_handle, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, $user_agent);

        if ($method === 'post') {
            curl_setopt($curl_handle, CURLOPT_POST, true);
            curl_setopt($curl_handle, CURLOPT_POSTFIELDS, http_build_query($parameters));
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
            curl_setopt($curl_handle, CURLOPT_BUFFERSIZE, 128);
            curl_setopt($curl_handle, CURLOPT_NOPROGRESS, false);
            curl_setopt(
                $curl_handle,
                CURLOPT_PROGRESSFUNCTION,
                function ($resource, $download_size, $downloaded, $upload_size, $uploaded) use ($max_size) {
                    return ($downloaded > $max_size) ? 1 : 0;
                }
            );
        }

        if (isset($options['headers'])) {
            $request_headers = array_merge($this->headers, $options['headers']);
        } else {
            $request_headers = $this->headers;
        }
        $request_headers = array_map(function ($name, $value) {
            return "{$name}: {$value}";
        }, array_keys($request_headers), $request_headers);
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $request_headers);

        $headers = '';
        $done = false;
        curl_setopt(
            $curl_handle,
            CURLOPT_HEADERFUNCTION,
            function ($curl, $current_header) use (&$headers, &$done) {
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

        $data = curl_exec($curl_handle);
        $status = curl_getinfo($curl_handle, CURLINFO_RESPONSE_CODE);

        $error = null;
        if ($data === false) {
            $error = curl_error($curl_handle);
        }

        curl_close($curl_handle);

        if ($error) {
            throw new HttpError($error);
        }

        return new Response($status, $data, $headers);
    }
}
