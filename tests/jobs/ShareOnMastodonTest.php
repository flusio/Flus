<?php

namespace App\jobs;

use App\services;
use tests\factories\LinkFactory;
use tests\factories\MastodonAccountFactory;
use tests\factories\MastodonServerFactory;

class ShareOnMastodonTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\FakerHelper;
    use \tests\HttpHelper;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function initEngine(): void
    {
        $router = \App\Router::load();
        \Minz\Engine::init($router);
    }

    public function testPerformPostsTheThread(): void
    {
        $job = new ShareOnMastodon();
        /** @var string */
        $mastodon_domain = $this->fake('domainName');
        $mastodon_host = 'https://' . $mastodon_domain;
        $statuses_endpoint = $mastodon_host . '/api/v1/statuses';
        $mastodon_server = MastodonServerFactory::create([
            'host' => $mastodon_host,
        ]);
        /** @var string */
        $access_token = $this->fake('sha256');
        $mastodon_account = MastodonAccountFactory::create([
            'mastodon_server_id' => $mastodon_server->id,
            'access_token' => $access_token,
        ]);
        $link = LinkFactory::create();
        $mastodon_status = $mastodon_account->buildMastodonStatus($link);
        $mastodon_status->save();
        $this->mockHttpWithResponse($statuses_endpoint, <<<TEXT
            HTTP/2 200
            Content-type: application/json

            {
                "id": "123456"
            }
            TEXT
        );

        $job->perform($mastodon_status->id);

        $mastodon_status = $mastodon_status->reload();
        $this->assertTrue($mastodon_status->isPosted());
        $this->assertSame('123456', $mastodon_status->status_id);
    }

    public function testPerformFailsIfAccountIsNotSetup(): void
    {
        $job = new ShareOnMastodon();
        /** @var string */
        $mastodon_domain = $this->fake('domainName');
        $mastodon_host = 'https://' . $mastodon_domain;
        $statuses_endpoint = $mastodon_host . '/api/v1/statuses';
        $mastodon_server = MastodonServerFactory::create([
            'host' => $mastodon_host,
        ]);
        $mastodon_account = MastodonAccountFactory::create([
            'mastodon_server_id' => $mastodon_server->id,
            'access_token' => '',
        ]);
        $link = LinkFactory::create();
        $mastodon_status = $mastodon_account->buildMastodonStatus($link);
        $mastodon_status->save();
        $this->mockHttpWithResponse($statuses_endpoint, <<<TEXT
            HTTP/2 200
            Content-type: application/json

            {
                "id": "123456"
            }
            TEXT
        );

        $this->expectException(\RuntimeException::class);

        try {
            $job->perform($mastodon_status->id);
        } finally {
            $mastodon_status = $mastodon_status->reload();
            $this->assertFalse($mastodon_status->isPosted());
        }
    }

    public function testPerformInvalidatesTheAccessTokenIfHostRejectsIt(): void
    {
        $job = new ShareOnMastodon();
        /** @var string */
        $mastodon_domain = $this->fake('domainName');
        $mastodon_host = 'https://' . $mastodon_domain;
        $statuses_endpoint = $mastodon_host . '/api/v1/statuses';
        $mastodon_server = MastodonServerFactory::create([
            'host' => $mastodon_host,
        ]);
        /** @var string */
        $access_token = $this->fake('sha256');
        $mastodon_account = MastodonAccountFactory::create([
            'mastodon_server_id' => $mastodon_server->id,
            'access_token' => $access_token,
        ]);
        $link = LinkFactory::create();
        $mastodon_status = $mastodon_account->buildMastodonStatus($link);
        $mastodon_status->save();
        $this->mockHttpWithResponse($statuses_endpoint, <<<TEXT
            HTTP/2 401
            Content-type: application/json

            {
                "error": "The access token is invalid"
            }
            TEXT
        );

        $this->expectException(services\MastodonInvalidAccessTokenError::class);

        try {
            $job->perform($mastodon_status->id);
        } finally {
            $mastodon_status = $mastodon_status->reload();
            $this->assertFalse($mastodon_status->isPosted());
            $mastodon_account = $mastodon_account->reload();
            $this->assertSame('', $mastodon_account->access_token);
            $this->assertTrue($mastodon_account->isAccessTokenInvalidated());
        }
    }

    public function testPerformFailsIfHostReturnsAnotherError(): void
    {
        $job = new ShareOnMastodon();
        /** @var string */
        $mastodon_domain = $this->fake('domainName');
        $mastodon_host = 'https://' . $mastodon_domain;
        $statuses_endpoint = $mastodon_host . '/api/v1/statuses';
        $mastodon_server = MastodonServerFactory::create([
            'host' => $mastodon_host,
        ]);
        /** @var string */
        $access_token = $this->fake('sha256');
        $mastodon_account = MastodonAccountFactory::create([
            'mastodon_server_id' => $mastodon_server->id,
            'access_token' => $access_token,
        ]);
        $link = LinkFactory::create();
        $mastodon_status = $mastodon_account->buildMastodonStatus($link);
        $mastodon_status->save();
        $this->mockHttpWithResponse($statuses_endpoint, <<<TEXT
            HTTP/2 500
            Content-type: application/json

            {
                "error": "Oops!"
            }
            TEXT
        );

        $this->expectException(services\MastodonError::class);

        try {
            $job->perform($mastodon_status->id);
        } finally {
            $mastodon_status = $mastodon_status->reload();
            $this->assertFalse($mastodon_status->isPosted());
            $mastodon_account = $mastodon_account->reload();
            $this->assertSame($access_token, $mastodon_account->access_token);
            $this->assertFalse($mastodon_account->isAccessTokenInvalidated());
        }
    }
}
