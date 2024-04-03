<?php

namespace App\services;

/**
 * The Subscriptions service allows to get information about a user
 * subscription.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Subscriptions
{
    private string $host;

    private string $private_key;

    private \SpiderBits\Http $http;

    public function __construct(string $host, string $private_key)
    {
        $this->host = $host;
        $this->private_key = $private_key;

        $this->http = new \SpiderBits\Http();
        /** @var string */
        $user_agent = \Minz\Configuration::$application['user_agent'];
        $this->http->user_agent = $user_agent;
        $this->http->timeout = 5;
    }

    /**
     * Get account information for the given email. Please always make sure the
     * email has been validated first!
     *
     * @return ?array{
     *     'id': string,
     *     'expired_at': \DateTimeImmutable
     * }
     */
    public function account(string $email): ?array
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
            /** @var ?mixed[] */
            $data = json_decode($response->data, true);

            $clean_data = [];

            if (!$data) {
                \Minz\Log::error('Error while requesting a subscription account: invalid response');
                return null;
            }

            if (isset($data['id']) && is_string($data['id'])) {
                $clean_data['id'] = $data['id'];
            } else {
                \Minz\Log::error('Error while requesting a subscription account: invalid id');
                return null;
            }

            if (isset($data['expired_at']) && is_string($data['expired_at'])) {
                $expired_at = \DateTimeImmutable::createFromFormat(
                    \Minz\Database\Column::DATETIME_FORMAT,
                    $data['expired_at']
                );

                if ($expired_at === false) {
                    $expired_at = \Minz\Time::now();
                }

                $clean_data['expired_at'] = $expired_at;
            } else {
                \Minz\Log::error('Error while requesting a subscription account: invalid expired_at');
                return null;
            }

            return $clean_data;
        } else {
            \Minz\Log::error('Error while requesting a subscription account: request failed');
            return null;
        }
    }

    /**
     * Get a login URL for the given account.
     */
    public function loginUrl(string $account_id): ?string
    {
        try {
            $response = $this->http->get($this->host . '/api/account/login-url', [
                'account_id' => $account_id,
                'service' => 'Flus',
            ], [
                'auth_basic' => $this->private_key . ':',
            ]);
        } catch (\SpiderBits\HttpError $e) {
            \Minz\Log::error("Error while requesting a subscription login URL: {$e->getMessage()}");
            return null;
        }

        if ($response->success) {
            /** @var ?mixed[] */
            $data = json_decode($response->data, true);

            if (!$data || !isset($data['url']) || !is_string($data['url'])) {
                \Minz\Log::error('Error while requesting a subscription login URL: invalid url');
                return null;
            }

            return $data['url'];
        } else {
            \Minz\Log::error('Error while requesting a subscription login URL: request failed');
            return null;
        }
    }

    /**
     * Get the expired_at value for the given account.
     */
    public function expiredAt(string $account_id): ?\DateTimeImmutable
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
            /** @var ?mixed[] */
            $data = json_decode($response->data, true);

            if (!$data || !isset($data['expired_at']) || !is_string($data['expired_at'])) {
                \Minz\Log::error('Error while requesting a subscription expiration date: invalid expired_at');
                return null;
            }

            $expired_at = \DateTimeImmutable::createFromFormat(
                \Minz\Database\Column::DATETIME_FORMAT,
                $data['expired_at']
            );

            if ($expired_at === false) {
                $expired_at = \Minz\Time::now();
            }

            return $expired_at;
        } else {
            \Minz\Log::error('Error while requesting a subscription expiration date: request failed');
            return null;
        }
    }

    /**
     * Get the expired_at value for the given accounts.
     *
     * @param string[] $account_ids
     *
     * @return ?\DateTimeImmutable[]
     */
    public function sync(array $account_ids): ?array
    {
        try {
            $response = $this->http->post($this->host . '/api/accounts/sync', [
                'account_ids' => json_encode($account_ids),
            ], [
                'auth_basic' => $this->private_key . ':',
            ]);
        } catch (\SpiderBits\HttpError $e) {
            \Minz\Log::error("Error while syncing subscriptions: {$e->getMessage()}");
            return null;
        }

        if (!$response->success) {
            \Minz\Log::error("Error while syncing subscriptions: code {$response->status}");
            return null;
        }

        /** @var ?mixed[] */
        $data = json_decode($response->data, true);
        if (!is_array($data)) {
            \Minz\Log::error("Error while syncing subscriptions: can’t decode data");
            return null;
        }

        $result = [];

        foreach ($data as $account_id => $expired_at) {
            if (!is_string($expired_at)) {
                \Minz\Log::error('Error while syncing subscriptions: invalid expired_at');
                continue;
            }

            $expired_at = \DateTimeImmutable::createFromFormat(
                \Minz\Database\Column::DATETIME_FORMAT,
                $expired_at
            );

            if (!$expired_at) {
                \Minz\Log::error("Error while syncing subscriptions: can’t parse expiration date of {$account_id}");
                continue;
            }

            $result[$account_id] = $expired_at;
        }

        return $result;
    }
}
