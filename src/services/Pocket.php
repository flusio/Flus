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

        $this->http = new \SpiderBits\Http();
        $this->http->headers = [
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF8',
            'X-Accept' => 'application/json',
        ];
        $this->http->user_agent = \Minz\Configuration::$application['user_agent'];
        $this->http->timeout = 20;
    }

    /**
     * Get the list of items from Pocket
     *
     * @see https://getpocket.com/developer/docs/v3/retrieve
     *
     * @param string $access_token
     * @param array $parameters List of optional parameters to pass in the request
     *
     * @throw \flusio\services\PocketError
     *
     * @return array
     */
    public function retrieve($access_token, $parameters = [])
    {
        $endpoint = self::HOST . '/v3/get';
        try {
            $response = $this->http->post($endpoint, array_merge($parameters, [
                'consumer_key' => $this->consumer_key,
                'access_token' => $access_token,
            ]));
        } catch (\SpiderBits\HttpError $e) {
            throw new PocketError(42, $e->getMessage());
        }

        if ($response->success) {
            $json = json_decode($response->data, true);
            return $json['list'];
        } else {
            throw new PocketError($response->header('X-Error-Code', 42));
        }
    }

    /**
     * Get a request token from Pocket.
     *
     * @param string $redirect_uri
     *
     * @throw \flusio\services\PocketError
     *
     * @return string
     */
    public function requestToken($redirect_uri)
    {
        $endpoint = self::HOST . '/v3/oauth/request';
        try {
            $response = $this->http->post($endpoint, [
                'consumer_key' => $this->consumer_key,
                'redirect_uri' => $redirect_uri,
            ]);
        } catch (\SpiderBits\HttpError $e) {
            throw new PocketError(42, $e->getMessage());
        }

        if ($response->success) {
            $json = json_decode($response->data);
            return $json->code;
        } else {
            throw new PocketError($response->header('X-Error-Code', 42));
        }
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
     * @throw \flusio\services\PocketError
     *
     * @return string[] First item is token, second item is username
     */
    public function accessToken($request_token)
    {
        $endpoint = self::HOST . '/v3/oauth/authorize';
        try {
            $response = $this->http->post($endpoint, [
                'consumer_key' => $this->consumer_key,
                'code' => $request_token,
            ]);
        } catch (\SpiderBits\HttpError $e) {
            throw new PocketError(42, $e->getMessage());
        }

        if ($response->success) {
            $json = json_decode($response->data);
            return [$json->access_token, $json->username];
        } else {
            throw new PocketError($response->header('X-Error-Code', 42));
        }
    }
}
