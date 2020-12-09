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

    /**
     * Make a GET HTTP request.
     *
     * @param string $url
     * @param array $parameters
     * @param array $options
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

        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        curl_setopt($curl_handle, CURLOPT_HEADER, false);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl_handle, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, $this->user_agent);

        if (isset($options['auth_basic'])) {
            curl_setopt($curl_handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl_handle, CURLOPT_USERPWD, $options['auth_basic']);
        }

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

        if ($data === false) {
            $data = curl_error($curl_handle);
        }

        curl_close($curl_handle);

        return new Response($status, $data, $headers);
    }
}
