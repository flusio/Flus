<?php

namespace App\services;

use App\models;
use App\utils;

/**
 * @phpstan-import-type Options from models\MastodonAccount
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Mastodon
{
    public const SCOPES = 'write:statuses read:accounts';

    public models\MastodonServer $server;

    private \SpiderBits\Http $http;

    /**
     * Return an HTTP client.
     */
    private static function http(): \SpiderBits\Http
    {
        $http = new \SpiderBits\Http();

        $http->user_agent = utils\UserAgent::get();
        $http->timeout = 10;

        return $http;
    }

    /**
     * Get the Mastodon service for the given host.
     *
     * This method instantiates the service with a MastodonServer.
     * If there is no MastodonServer (i.e. the app is not known by the Mastodon
     * host), it is created.
     */
    public static function get(string $host): self
    {
        $host = rtrim($host, '/');
        $mastodon_server = models\MastodonServer::findBy(['host' => $host]);

        if (!$mastodon_server) {
            $mastodon_server = self::createApp($host);
            $mastodon_server->save();
        }

        return new self($mastodon_server);
    }

    /**
     * Create the app on the Mastodon host, and return the corresponding
     * MastodonServer.
     */
    public static function createApp(string $host): models\MastodonServer
    {
        $redirect_uris = \Minz\Url::absoluteFor('mastodon auth');
        $website = \Minz\Url::absoluteFor('home');

        $http = self::http();

        $response = $http->post($host . '/api/v1/apps', [
            'client_name' => \App\Configuration::$application['brand'],
            'redirect_uris' => $redirect_uris,
            'scopes' => self::SCOPES,
            'website' => $website,
        ]);

        if ($response->status !== 200) {
            $data = $response->utf8Data();
            throw new MastodonError(
                "Mastodon host {$host} failed to create app: {$data}"
            );
        }

        $data = json_decode($response->data, true);

        if (
            !is_array($data) ||
            !is_string($data['client_id']) ||
            !is_string($data['client_secret'])
        ) {
            $data = $response->utf8Data();
            throw new MastodonError(
                "Mastodon host {$host} returned invalid JSON: {$data}"
            );
        }

        $client_id = $data['client_id'];
        $client_secret = $data['client_secret'];

        return new models\MastodonServer($host, $client_id, $client_secret);
    }

    /**
     * Instantiate the Mastodon service.
     */
    public function __construct(models\MastodonServer $server)
    {
        $this->server = $server;
        $this->http = self::http();
    }

    /**
     * Return the URL to redirect the user so it can authorize Flus.
     */
    public function authorizationUrl(): string
    {
        $redirect_uri = \Minz\Url::absoluteFor('mastodon auth');

        $url = $this->server->host . '/oauth/authorize';
        $query = http_build_query([
            'client_id' => $this->server->client_id,
            'scope' => self::SCOPES,
            'redirect_uri' => $redirect_uri,
            'response_type' => 'code',
        ]);

        return $url . '?' . $query;
    }

    /**
     * Get an access token for a user.
     */
    public function accessToken(string $code): string
    {
        $redirect_uri = \Minz\Url::absoluteFor('mastodon auth');

        $host = $this->server->host;
        $endpoint = $host . '/oauth/token';
        $response = $this->http->post($endpoint, [
            'client_id' => $this->server->client_id,
            'client_secret' => $this->server->client_secret,
            'redirect_uri' => $redirect_uri,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'scope' => self::SCOPES,
        ]);

        if ($response->status !== 200) {
            $data = $response->utf8Data();
            throw new MastodonError(
                "Mastodon host {$host} failed to return access token: {$data}"
            );
        }

        $data = json_decode($response->data, true);

        if (
            !is_array($data) ||
            !is_string($data['access_token'] ?? null)
        ) {
            $data = $response->utf8Data();
            throw new MastodonError(
                "Mastodon host {$host} returned invalid JSON: {$data}"
            );
        }

        return $data['access_token'];
    }

    /**
     * Get the username of the account.
     */
    public function getUsername(models\MastodonAccount $account): string
    {
        $redirect_uri = \Minz\Url::absoluteFor('mastodon auth');

        $host = $this->server->host;
        $endpoint = $host . '/api/v1/accounts/verify_credentials';
        $response = $this->http->get($endpoint, [], [
            'headers' => [
                'Authorization' => "Bearer {$account->access_token}",
            ],
        ]);

        if ($response->status !== 200) {
            $data = $response->utf8Data();
            throw new MastodonError(
                "Mastodon host {$host} failed to verify credentials: {$data}"
            );
        }

        $data = json_decode($response->data, true);

        if (
            !is_array($data) ||
            !is_string($data['username'] ?? null)
        ) {
            $data = $response->utf8Data();
            throw new MastodonError(
                "Mastodon host {$host} returned invalid JSON: {$data}"
            );
        }

        $hostname = parse_url($host, PHP_URL_HOST);
        return $data['username'] . '@' . $hostname;
    }

    /**
     * Post a status to the given account.
     */
    public function postStatus(models\MastodonStatus $status): bool
    {
        $account = $status->account();

        $endpoint = $this->server->host . '/api/v1/statuses';
        $response = $this->http->post($endpoint, [
            'visibility' => 'public',
            'status' => $status->content,
        ], [
            'headers' => [
                'Authorization' => "Bearer {$account->access_token}",
                'Idempotency-Key' => $status->id,
            ]
        ]);

        $success = $response->success;

        if ($success) {
            $json = json_decode($response->data, associative: true);

            if (is_array($json) && is_string($json['id'] ?? '')) {
                $status->status_id = $json['id'] ?? '';
            }
            $status->posted_at = \Minz\Time::now();
            $status->save();
        }

        return $success;
    }
}
