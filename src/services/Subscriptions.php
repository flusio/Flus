<?php

namespace flusio\services;

/**
 * The Subscriptions service allows to get information about a user
 * subscription.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Subscriptions
{
    /** @var string */
    private $host;

    /** @var string */
    private $private_key;

    /**
     * @param string $host
     * @param string $private_key
     */
    public function __construct($host, $private_key)
    {
        $this->host = $host;
        $this->private_key = $private_key;
    }

    /**
     * Get account information for the given email. Please always make sure the
     * email has been validated first!
     *
     * @param string $email
     *
     * @return array|null
     */
    public function account($email)
    {
        $http = new \SpiderBits\Http();
        $response = $http->get($this->host . '/api/account', [
            'email' => $email,
        ], [
            'auth_basic' => $this->private_key . ':',
        ]);
        if ($response->success) {
            return json_decode($response->data, true);
        } else {
            return null;
        }
    }
}
