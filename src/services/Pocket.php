<?php

namespace flusio\services;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Pocket
{
    public const HOST = 'https://getpocket.com';

    /** @var string */
    private $consumer_key;

    /** @var \SpiderBits\Http */
    private $http;

    /**
     * @param string $consumer_key
     */
    public function __construct($consumer_key)
    {
        $this->consumer_key = $consumer_key;

        $php_os = PHP_OS;
        $flusio_version = \Minz\Configuration::$application['version'];
        $this->http = new \SpiderBits\Http();
        $this->http->headers = [
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF8',
            'X-Accept' => 'application/json',
        ];
        $this->http->user_agent = "flusio/{$flusio_version} ({$php_os}; https://github.com/flusio/flusio)";
        $this->http->timeout = 5;
    }

    /**
     * Get a request token from Pocket.
     *
     * @param string $redirect_uri
     *
     * @return string
     */
    public function requestToken($redirect_uri)
    {
        $endpoint = self::HOST . '/v3/oauth/request';
        $response = $this->http->post($endpoint, [
            'consumer_key' => $this->consumer_key,
            'redirect_uri' => $redirect_uri,
        ]);
        $json = json_decode($response->data);
        return $json->code;
    }

    /**
     * Return the URL to redirect user so it can authorize flusio
     *
     * @param string $request_token
     * @param string $redirect_uri
     *
     * @return string
     */
    public function authorizationUrl($request_token, $redirect_uri)
    {
        $url = self::HOST . '/auth/authorize';
        $query = http_build_query([
            'request_token' => $request_token,
            'redirect_uri' => $redirect_uri,
        ]);
        return $url . '?' . $query;
    }

    /**
     * Get access token (and username) from a request token
     *
     * @param string $request_token
     *
     * @return string[] First item is token, second item is username
     */
    public function accessToken($request_token)
    {
        $endpoint = self::HOST . '/v3/oauth/authorize';
        $response = $this->http->post($endpoint, [
            'consumer_key' => $this->consumer_key,
            'code' => $request_token,
        ]);
        $json = json_decode($response->data);
        return [$json->access_token, $json->username];
    }
}
