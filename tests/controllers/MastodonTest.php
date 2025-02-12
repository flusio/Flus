<?php

namespace App\controllers;

use App\models;
use App\services;
use tests\factories\MastodonAccountFactory;
use tests\factories\MastodonServerFactory;
use tests\factories\UserFactory;

class MastodonTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;
    use \tests\MockHttpHelper;

    public function testShowRendersCorrectly(): void
    {
        $user = $this->login();

        $response = $this->appRun('GET', '/mastodon');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'mastodon/show.phtml');
        $this->assertResponseContains($response, 'Configure sharing to Mastodon');
    }

    public function testShowRedirectsToLoginIfNotConnected(): void
    {
        $response = $this->appRun('GET', '/mastodon');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmastodon');
    }

    public function testRequestAccessRedirectsCorrectly(): void
    {
        $user = $this->login();
        /** @var string */
        $mastodon_domain = $this->fake('domainName');
        $mastodon_host = 'https://' . $mastodon_domain;
        $create_app_endpoint = $mastodon_host . '/api/v1/apps';
        $authorization_endpoint = $mastodon_host . '/oauth/authorize';
        /** @var string */
        $client_id = $this->fake('sha256');
        /** @var string */
        $client_secret = $this->fake('sha256');
        $this->mockHttpWithResponse($create_app_endpoint, <<<TEXT
            HTTP/2 200
            Content-type: application/json

            {
                "client_id": "{$client_id}",
                "client_secret": "{$client_secret}"
            }
            TEXT
        );
        $authorization_params = http_build_query([
            'client_id' => $client_id,
            'scope' => services\Mastodon::SCOPES,
            'redirect_uri' => \Minz\Url::absoluteFor('mastodon auth'),
            'response_type' => 'code',
        ]);
        $authorization_url = $authorization_endpoint . '?' . $authorization_params;

        $response = $this->appRun('POST', '/mastodon/request', [
            'csrf' => $user->csrf,
            'host' => $mastodon_host,
        ]);

        $this->assertResponseCode($response, 302, $authorization_url);
        $mastodon_server = models\MastodonServer::take();
        $this->assertNotNull($mastodon_server);
        $this->assertSame($mastodon_server->host, $mastodon_host);
        $this->assertSame($mastodon_server->client_id, $client_id);
        $this->assertSame($mastodon_server->client_secret, $client_secret);
        $mastodon_account = models\MastodonAccount::take();
        $this->assertNotNull($mastodon_account);
        $this->assertSame($mastodon_account->mastodon_server_id, $mastodon_server->id);
        $this->assertSame($mastodon_account->user_id, $user->id);
        $this->assertSame($mastodon_account->access_token, '');
    }

    public function testRequestAccessRedirectsIfAlreadyAuthorized(): void
    {
        $user = $this->login();
        /** @var string */
        $mastodon_domain = $this->fake('domainName');
        $mastodon_host = 'https://' . $mastodon_domain;
        $mastodon_server = MastodonServerFactory::create([
            'host' => $mastodon_host,
        ]);
        /** @var string */
        $access_token = $this->fake('sha256');
        $mastodon_account = MastodonAccountFactory::create([
            'mastodon_server_id' => $mastodon_server->id,
            'user_id' => $user->id,
            'access_token' => $access_token,
        ]);

        $response = $this->appRun('POST', '/mastodon/request', [
            'csrf' => $user->csrf,
            'host' => $mastodon_host,
        ]);

        $this->assertResponseCode($response, 302, '/mastodon');
        $this->assertSame(1, models\MastodonServer::count());
        $this->assertSame(1, models\MastodonAccount::count());
    }

    public function testRequestAccessRedirectsToLoginIfNotConnected(): void
    {
        /** @var string */
        $mastodon_domain = $this->fake('domainName');
        $mastodon_host = 'https://' . $mastodon_domain;

        $response = $this->appRun('POST', '/mastodon/request', [
            'csrf' => \Minz\Csrf::generate(),
            'host' => $mastodon_host,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmastodon');
        $this->assertSame(0, models\MastodonServer::count());
        $this->assertSame(0, models\MastodonAccount::count());
    }

    public function testRequestAccessFailsIfHostIsInvalid(): void
    {
        $user = $this->login();
        $mastodon_host = 'not a valid host';

        $response = $this->appRun('POST', '/mastodon/request', [
            'csrf' => $user->csrf,
            'host' => $mastodon_host,
        ]);

        $this->assertResponseCode($response, 302, '/mastodon');
        $errors = \Minz\Flash::get('errors');
        $this->assertIsArray($errors);
        $this->assertSame('The URL is invalid.', $errors['host']);
        $this->assertSame(0, models\MastodonServer::count());
        $this->assertSame(0, models\MastodonAccount::count());
    }

    public function testRequestAccessFailsIfHostFails(): void
    {
        $user = $this->login();
        /** @var string */
        $mastodon_host = $this->fake('domainName');
        $mastodon_host = 'https://' . $mastodon_host;
        $create_app_endpoint = $mastodon_host . '/api/v1/apps';
        $this->mockHttpWithResponse($create_app_endpoint, <<<TEXT
            HTTP/2 422
            Content-type: application/json

            {
                "error": "Oops!"
            }
            TEXT
        );

        $response = $this->appRun('POST', '/mastodon/request', [
            'csrf' => $user->csrf,
            'host' => $mastodon_host,
        ]);

        $this->assertResponseCode($response, 302, '/mastodon');
        $errors = \Minz\Flash::get('errors');
        $this->assertIsArray($errors);
        $this->assertSame('The Mastodon host returned an error, please try later.', $errors['host']);
        $this->assertSame(0, models\MastodonServer::count());
        $this->assertSame(0, models\MastodonAccount::count());
    }

    public function testRequestAccessFailsIfHostReturnsNonJson(): void
    {
        $user = $this->login();
        /** @var string */
        $mastodon_host = $this->fake('domainName');
        $mastodon_host = 'https://' . $mastodon_host;
        $create_app_endpoint = $mastodon_host . '/api/v1/apps';
        $this->mockHttpWithResponse($create_app_endpoint, <<<TEXT
            HTTP/2 200
            Content-type: application/json

            This is not JSON!
            TEXT
        );

        $response = $this->appRun('POST', '/mastodon/request', [
            'csrf' => $user->csrf,
            'host' => $mastodon_host,
        ]);

        $this->assertResponseCode($response, 302, '/mastodon');
        $errors = \Minz\Flash::get('errors');
        $this->assertIsArray($errors);
        $this->assertSame('The Mastodon host returned an error, please try later.', $errors['host']);
        $this->assertSame(0, models\MastodonServer::count());
        $this->assertSame(0, models\MastodonAccount::count());
    }

    public function testRequestAccessFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        /** @var string */
        $mastodon_host = $this->fake('domainName');
        $mastodon_host = 'https://' . $mastodon_host;

        $response = $this->appRun('POST', '/mastodon/request', [
            'csrf' => 'not the token',
            'host' => $mastodon_host,
        ]);

        $this->assertResponseCode($response, 302, '/mastodon');
        $this->assertSame('A security verification failed.', \Minz\Flash::get('error'));
        $this->assertSame(0, models\MastodonServer::count());
        $this->assertSame(0, models\MastodonAccount::count());
    }

    public function testAuthorizationRendersCorrectly(): void
    {
        $user = $this->login();
        /** @var string */
        $code = $this->fake('sha256');
        MastodonAccountFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', '/mastodon/auth', [
            'code' => $code,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'mastodon/authorization.phtml');
        $this->assertResponseContains($response, 'Mastodon authorization');
        $this->assertResponseContains($response, $code);
    }

    public function testAuthorizationRedirectsIfCodeIsMissing(): void
    {
        $user = $this->login();
        MastodonAccountFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', '/mastodon/auth');

        $this->assertResponseCode($response, 302, '/mastodon');
    }

    public function testAuthorizationRedirectsIfMastodonAccountDoesNotExist(): void
    {
        $user = $this->login();
        /** @var string */
        $code = $this->fake('sha256');

        $response = $this->appRun('GET', '/mastodon/auth', [
            'code' => $code,
        ]);

        $this->assertResponseCode($response, 302, '/mastodon');
    }

    public function testAuthorizationRedirectsIfMastodonAccountIsAuthorized(): void
    {
        $user = $this->login();
        /** @var string */
        $code = $this->fake('sha256');
        /** @var string */
        $access_token = $this->fake('sha256');
        MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => $access_token,
        ]);

        $response = $this->appRun('GET', '/mastodon/auth', [
            'code' => $code,
        ]);

        $this->assertResponseCode($response, 302, '/mastodon');
    }

    public function testAuthorizationRedirectsToLoginIfNotConnected(): void
    {
        /** @var string */
        $code = $this->fake('sha256');

        $response = $this->appRun('GET', '/mastodon/auth', [
            'code' => $code,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmastodon');
    }

    public function testAuthorizeRedirectsCorrectly(): void
    {
        $user = $this->login();
        /** @var string */
        $code = $this->fake('sha256');
        /** @var string */
        $mastodon_domain = $this->fake('domainName');
        $mastodon_host = 'https://' . $mastodon_domain;
        $access_token_endpoint = $mastodon_host . '/oauth/token';
        $username_endpoint = $mastodon_host . '/api/v1/accounts/verify_credentials';
        $mastodon_server = MastodonServerFactory::create([
            'host' => $mastodon_host,
        ]);
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'mastodon_server_id' => $mastodon_server->id,
            'access_token' => '',
        ]);
        /** @var string */
        $access_token = $this->fake('sha256');
        $this->mockHttpWithResponse($access_token_endpoint, <<<TEXT
            HTTP/2 200
            Content-type: application/json

            {
                "access_token": "{$access_token}"
            }
            TEXT
        );
        /** @var string */
        $username = $this->fake('username');
        $this->mockHttpWithResponse($username_endpoint, <<<TEXT
            HTTP/2 200
            Content-type: application/json

            {
                "username": "{$username}"
            }
            TEXT
        );

        $response = $this->appRun('POST', '/mastodon/auth', [
            'csrf' => $user->csrf,
            'code' => $code,
        ]);

        $this->assertResponseCode($response, 302, '/mastodon');
        $mastodon_account = $mastodon_account->reload();
        $this->assertSame($mastodon_account->access_token, $access_token);
        $this->assertSame($mastodon_account->username, $username . '@' . $mastodon_domain);
    }

    public function testAuthorizeRedirectsIfUserIsNotConnected(): void
    {
        /** @var string */
        $code = $this->fake('sha256');

        $response = $this->appRun('POST', '/mastodon/auth', [
            'csrf' => \Minz\Csrf::generate(),
            'code' => $code,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmastodon');
    }

    public function testAuthorizeRedirectsIfCodeIsMissing(): void
    {
        $user = $this->login();
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => '',
        ]);

        $response = $this->appRun('POST', '/mastodon/auth', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, '/mastodon');
        $mastodon_account = $mastodon_account->reload();
        $this->assertSame($mastodon_account->access_token, '');
    }

    public function testAuthorizeRedirectsIfMastodonAccountDoesNotExist(): void
    {
        $user = $this->login();
        /** @var string */
        $code = $this->fake('sha256');

        $response = $this->appRun('POST', '/mastodon/auth', [
            'csrf' => $user->csrf,
            'code' => $code,
        ]);

        $this->assertResponseCode($response, 302, '/mastodon');
    }

    public function testAuthorizeRedirectsIfMastodonAccountIsAuthorized(): void
    {
        $user = $this->login();
        /** @var string */
        $code = $this->fake('sha256');
        /** @var string */
        $initial_access_token = $this->fake('sha256');
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => $initial_access_token,
        ]);

        $response = $this->appRun('POST', '/mastodon/auth', [
            'csrf' => $user->csrf,
            'code' => $code,
        ]);

        $this->assertResponseCode($response, 302, '/mastodon');
        $mastodon_account = $mastodon_account->reload();
        $this->assertSame($mastodon_account->access_token, $initial_access_token);
    }

    public function testAuthorizeFailsIfHostFails(): void
    {
        $user = $this->login();
        /** @var string */
        $code = $this->fake('sha256');
        /** @var string */
        $mastodon_domain = $this->fake('domainName');
        $mastodon_host = 'https://' . $mastodon_domain;
        $access_token_endpoint = $mastodon_host . '/oauth/token';
        $mastodon_server = MastodonServerFactory::create([
            'host' => $mastodon_host,
        ]);
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'mastodon_server_id' => $mastodon_server->id,
            'access_token' => '',
        ]);
        /** @var string */
        $access_token = $this->fake('sha256');
        $this->mockHttpWithResponse($access_token_endpoint, <<<TEXT
            HTTP/2 400
            Content-type: application/json

            {
                "error": "Oops!"
            }
            TEXT
        );

        $response = $this->appRun('POST', '/mastodon/auth', [
            'csrf' => $user->csrf,
            'code' => $code,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains(
            $response,
            'The Mastodon host returned an error, please try later.'
        );
        $mastodon_account = $mastodon_account->reload();
        $this->assertSame($mastodon_account->access_token, '');
    }

    public function testAuthorizeFailsIfHostReturnsNonJson(): void
    {
        $user = $this->login();
        /** @var string */
        $code = $this->fake('sha256');
        /** @var string */
        $mastodon_domain = $this->fake('domainName');
        $mastodon_host = 'https://' . $mastodon_domain;
        $access_token_endpoint = $mastodon_host . '/oauth/token';
        $mastodon_server = MastodonServerFactory::create([
            'host' => $mastodon_host,
        ]);
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'mastodon_server_id' => $mastodon_server->id,
            'access_token' => '',
        ]);
        /** @var string */
        $access_token = $this->fake('sha256');
        $this->mockHttpWithResponse($access_token_endpoint, <<<TEXT
            HTTP/2 200
            Content-type: application/json

            This is not JSON!
            TEXT
        );

        $response = $this->appRun('POST', '/mastodon/auth', [
            'csrf' => $user->csrf,
            'code' => $code,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The Mastodon host returned an error, please try later.');
        $mastodon_account = $mastodon_account->reload();
        $this->assertSame($mastodon_account->access_token, '');
    }

    public function testAuthorizeFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        /** @var string */
        $code = $this->fake('sha256');
        /** @var string */
        $mastodon_domain = $this->fake('domainName');
        $mastodon_host = 'https://' . $mastodon_domain;
        $access_token_endpoint = $mastodon_host . '/oauth/token';
        $mastodon_server = MastodonServerFactory::create([
            'host' => $mastodon_host,
        ]);
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'mastodon_server_id' => $mastodon_server->id,
            'access_token' => '',
        ]);

        $response = $this->appRun('POST', '/mastodon/auth', [
            'csrf' => 'not the token',
            'code' => $code,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $mastodon_account = $mastodon_account->reload();
        $this->assertSame($mastodon_account->access_token, '');
    }

    public function testUpdateChangesOptionsAndRedirectsCorrectly(): void
    {
        $user = $this->login();
        $old_link_to_comment = 'auto';
        $new_link_to_comment = 'never';
        /** @var string */
        $old_post_scriptum = $this->fake('sentence');
        /** @var string */
        $new_post_scriptum = $this->fake('sentence');
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'a token',
            'options' => [
                'link_to_comment' => $old_link_to_comment,
                'post_scriptum' => $old_post_scriptum,
            ],
        ]);

        $response = $this->appRun('POST', '/mastodon', [
            'csrf' => $user->csrf,
            'link_to_comment' => $new_link_to_comment,
            'post_scriptum' => $new_post_scriptum,
        ]);

        $this->assertResponseCode($response, 302, '/mastodon');
        $mastodon_account = $mastodon_account->reload();
        $this->assertSame($mastodon_account->options['link_to_comment'], $new_link_to_comment);
        $this->assertSame($mastodon_account->options['post_scriptum'], $new_post_scriptum);
    }

    public function testUpdateRedirectsIfUserIsNotConnected(): void
    {
        $user = UserFactory::create();
        $old_link_to_comment = 'auto';
        $new_link_to_comment = 'never';
        /** @var string */
        $old_post_scriptum = $this->fake('sentence');
        /** @var string */
        $new_post_scriptum = $this->fake('sentence');
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'a token',
            'options' => [
                'link_to_comment' => $old_link_to_comment,
                'post_scriptum' => $old_post_scriptum,
            ],
        ]);

        $response = $this->appRun('POST', '/mastodon', [
            'csrf' => $user->csrf,
            'link_to_comment' => $new_link_to_comment,
            'post_scriptum' => $new_post_scriptum,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmastodon');
        $mastodon_account = $mastodon_account->reload();
        $this->assertSame($mastodon_account->options['link_to_comment'], $old_link_to_comment);
        $this->assertSame($mastodon_account->options['post_scriptum'], $old_post_scriptum);
    }

    public function testUpdateRedirectsIfAccessTokenIsNotSet(): void
    {
        $user = $this->login();
        $old_link_to_comment = 'auto';
        $new_link_to_comment = 'never';
        /** @var string */
        $old_post_scriptum = $this->fake('sentence');
        /** @var string */
        $new_post_scriptum = $this->fake('sentence');
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => '',
            'options' => [
                'link_to_comment' => $old_link_to_comment,
                'post_scriptum' => $old_post_scriptum,
            ],
        ]);

        $response = $this->appRun('POST', '/mastodon', [
            'csrf' => $user->csrf,
            'link_to_comment' => $new_link_to_comment,
            'post_scriptum' => $new_post_scriptum,
        ]);

        $this->assertResponseCode($response, 302, '/mastodon');
        $mastodon_account = $mastodon_account->reload();
        $this->assertSame($mastodon_account->options['link_to_comment'], $old_link_to_comment);
        $this->assertSame($mastodon_account->options['post_scriptum'], $old_post_scriptum);
    }

    public function testUpdateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $old_link_to_comment = 'auto';
        $new_link_to_comment = 'never';
        /** @var string */
        $old_post_scriptum = $this->fake('sentence');
        /** @var string */
        $new_post_scriptum = $this->fake('sentence');
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'a token',
            'options' => [
                'link_to_comment' => $old_link_to_comment,
                'post_scriptum' => $old_post_scriptum,
            ],
        ]);

        $response = $this->appRun('POST', '/mastodon', [
            'csrf' => 'not the token',
            'link_to_comment' => $new_link_to_comment,
            'post_scriptum' => $new_post_scriptum,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $mastodon_account = $mastodon_account->reload();
        $this->assertSame($mastodon_account->options['link_to_comment'], $old_link_to_comment);
        $this->assertSame($mastodon_account->options['post_scriptum'], $old_post_scriptum);
    }

    public function testUpdateFailsIfPostScriptumIsLongerThan100Chars(): void
    {
        $user = $this->login();
        $old_link_to_comment = 'auto';
        $new_link_to_comment = 'never';
        /** @var string */
        $old_post_scriptum = $this->fake('sentence');
        /** @var string */
        $new_post_scriptum = str_repeat('a', 101);
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'a token',
            'options' => [
                'link_to_comment' => $old_link_to_comment,
                'post_scriptum' => $old_post_scriptum,
            ],
        ]);

        $response = $this->appRun('POST', '/mastodon', [
            'csrf' => $user->csrf,
            'link_to_comment' => $new_link_to_comment,
            'post_scriptum' => $new_post_scriptum,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The label must be less than 100 characters.');
        $mastodon_account = $mastodon_account->reload();
        $this->assertSame($mastodon_account->options['link_to_comment'], $old_link_to_comment);
        $this->assertSame($mastodon_account->options['post_scriptum'], $old_post_scriptum);
    }

    public function testDisconnectRemovesTheMastodonAccount(): void
    {
        $user = $this->login();
        $mastodon_account = MastodonAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'a token',
        ]);

        $response = $this->appRun('POST', '/mastodon/disconnect', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, '/mastodon');
        $this->assertFalse(models\MastodonAccount::exists($mastodon_account->id));
    }
}
