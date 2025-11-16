<?php

namespace App\services;

use App\models;
use App\utils;

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

    public function __construct()
    {
        $this->host = \App\Configuration::$application['subscriptions_host'];
        $this->private_key = \App\Configuration::$application['subscriptions_private_key'];

        $this->http = new \SpiderBits\Http();
        $this->http->user_agent = utils\UserAgent::get();
        $this->http->timeout = 5;
    }

    /**
     * Initialize the user's subscription account and returns true on success.
     *
     * It does nothing if the email hasn't been validated first.
     */
    public function initAccount(models\User $user): bool
    {
        if ($user->hasSubscriptionAccount()) {
            return true;
        }

        if (!$user->isValidated()) {
            \Minz\Log::error("Error while requesting subscription account for user {$user->id}: email not validated");
            return false;
        }

        try {
            $response = $this->http->get($this->host . '/api/account', [
                'email' => $user->email,
            ], [
                'auth_basic' => ':' . $this->private_key,
            ]);
        } catch (\SpiderBits\HttpError $e) {
            \Minz\Log::error("Error while requesting subscription account for user {$user->id}: {$e->getMessage()}");
            return false;
        }

        if (!$response->success) {
            \Minz\Log::error("Error while requesting subscription account for user {$user->id}: request failed");
            return false;
        }

        /** @var ?mixed[] */
        $data = json_decode($response->data, true);

        $clean_data = [];

        if (!$data) {
            \Minz\Log::error("Error while requesting subscription account for user {$user->id}: invalid response");
            return false;
        }

        if (isset($data['id']) && is_string($data['id'])) {
            $clean_data['id'] = $data['id'];
        } else {
            \Minz\Log::error("Error while requesting subscription account for user {$user->id}: invalid id");
            return false;
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
            \Minz\Log::error("Error while requesting subscription account for user {$user->id}: invalid expired_at");
            return false;
        }

        $user->subscription_account_id = $clean_data['id'];
        $user->subscription_expired_at = $clean_data['expired_at'];

        return true;
    }

    /**
     * Get a login URL for the given account.
     */
    public function loginUrl(models\User $user): ?string
    {
        if (!$user->hasSubscriptionAccount()) {
            \Minz\Log::error("Error while requesting a subscription login URL for user {$user->id}: no account");
            return null;
        }

        try {
            $response = $this->http->get($this->host . '/api/account/login-url', [
                'account_id' => $user->subscription_account_id,
                'service' => 'Flus',
            ], [
                'auth_basic' => ':' . $this->private_key,
            ]);
        } catch (\SpiderBits\HttpError $e) {
            \Minz\Log::error(
                "Error while requesting a subscription login URL for user {$user->id}: {$e->getMessage()}"
            );
            return null;
        }

        if ($response->success) {
            /** @var ?mixed[] */
            $data = json_decode($response->data, true);

            if (!$data || !isset($data['url']) || !is_string($data['url'])) {
                \Minz\Log::error("Error while requesting a subscription login URL for user {$user->id}: invalid url");
                return null;
            }

            return $data['url'];
        } else {
            \Minz\Log::error("Error while requesting a subscription login URL for user {$user->id}: request failed");
            return null;
        }
    }

    /**
     * Refresh the expired_at value for the given account and return true on success.
     */
    public function refreshExpiredAt(models\User $user): bool
    {
        if (!$user->hasSubscriptionAccount()) {
            \Minz\Log::error("Error while requesting subscription expiration for user {$user->id}: no account");
            return false;
        }

        try {
            $response = $this->http->get($this->host . '/api/account/expired-at', [
                'account_id' => $user->subscription_account_id,
            ], [
                'auth_basic' => ':' . $this->private_key,
            ]);
        } catch (\SpiderBits\HttpError $e) {
            \Minz\Log::error("Error while requesting subscription expiration for user {$user->id}: {$e->getMessage()}");
            return false;
        }

        if (!$response->success) {
            \Minz\Log::error("Error while requesting subscription expiration for user {$user->id}: request failed");
            return false;
        }

        /** @var ?mixed[] */
        $data = json_decode($response->data, true);

        if (!$data || !isset($data['expired_at']) || !is_string($data['expired_at'])) {
            \Minz\Log::error("Error while requesting subscription expiration for user {$user->id}: invalid expired_at");
            return false;
        }

        $expired_at = \DateTimeImmutable::createFromFormat(
            \Minz\Database\Column::DATETIME_FORMAT,
            $data['expired_at']
        );

        if ($expired_at === false) {
            $expired_at = \Minz\Time::now();
        }

        $user->subscription_expired_at = $expired_at;

        return true;
    }

    /**
     * Refresh the expired_at value for the given users.
     *
     * @param models\User[] $users
     */
    public function sync(array $users): void
    {
        $account_ids_to_users = array_column($users, null, 'subscription_account_id');
        $account_ids = array_keys($account_ids_to_users);

        try {
            $response = $this->http->post($this->host . '/api/accounts/sync', [
                'account_ids' => json_encode($account_ids),
            ], [
                'auth_basic' => ':' . $this->private_key,
            ]);
        } catch (\SpiderBits\HttpError $e) {
            \Minz\Log::error("Error while syncing subscriptions: {$e->getMessage()}");
            return;
        }

        if (!$response->success) {
            \Minz\Log::error("Error while syncing subscriptions: code {$response->status}");
            return;
        }

        /** @var ?mixed[] */
        $data = json_decode($response->data, true);
        if (!is_array($data)) {
            \Minz\Log::error("Error while syncing subscriptions: can’t decode data");
            return;
        }

        foreach ($data as $account_id => $expired_at) {
            if (!is_string($expired_at)) {
                \Minz\Log::error("Error while syncing subscriptions: invalid expired_at for {$account_id}");
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

            if (!isset($account_ids_to_users[$account_id])) {
                \Minz\Log::error("Error while syncing subscriptions: {$account_id} does not exist in DB.");
                continue;
            }

            $user = $account_ids_to_users[$account_id];
            if ($user->subscription_expired_at != $expired_at) {
                $user->subscription_expired_at = $expired_at;
                $user->save();
            }
        }
    }
}
