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

        $this->http = new \SpiderBits\Http();
        $this->http->user_agent = \Minz\Configuration::$application['user_agent'];
        $this->http->timeout = 5;
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
        try {
            $response = $this->http->get($this->host . '/api/account', [
                'email' => $email,
            ], [
                'auth_basic' => $this->private_key . ':',
            ]);
        } catch (\SpiderBits\HttpError $e) {
            \Minz\Log::error("Error while requesting a subscription account: {$e->getMessage()}");
            return null;
        }

        if ($response->success) {
            $data = json_decode($response->data, true);
            $data['expired_at'] = date_create_from_format(
                \Minz\Model::DATETIME_FORMAT,
                $data['expired_at']
            );
            return $data;
        } else {
            return null;
        }
    }

    /**
     * Get a login URL for the given account.
     *
     * @param string $account_id
     *
     * @return string|null
     */
    public function loginUrl($account_id)
    {
        try {
            $response = $this->http->get($this->host . '/api/account/login-url', [
                'account_id' => $account_id,
                'service' => 'flusio',
            ], [
                'auth_basic' => $this->private_key . ':',
            ]);
        } catch (\SpiderBits\HttpError $e) {
            \Minz\Log::error("Error while requesting a subscription login URL: {$e->getMessage()}");
            return null;
        }

        if ($response->success) {
            $data = json_decode($response->data, true);
            return $data['url'];
        } else {
            return null;
        }
    }

    /**
     * Get the expired_at value for the given account.
     *
     * @param string $account_id
     *
     * @return string|null
     */
    public function expiredAt($account_id)
    {
        try {
            $response = $this->http->get($this->host . '/api/account/expired-at', [
                'account_id' => $account_id,
            ], [
                'auth_basic' => $this->private_key . ':',
            ]);
        } catch (\SpiderBits\HttpError $e) {
            \Minz\Log::error("Error while requesting a subscription expiration date: {$e->getMessage()}");
            return null;
        }

        if ($response->success) {
            $data = json_decode($response->data, true);
            return date_create_from_format(
                \Minz\Model::DATETIME_FORMAT,
                $data['expired_at']
            );
        } else {
            return null;
        }
    }
}
