<?php

namespace App\jobs\scheduled;

use tests\factories\MastodonServerFactory;

class MastodonServersSyncTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\HttpHelper;

    public function testPerformSyncsTheMaxCharacters(): void
    {
        $job = new MastodonServersSync();
        $mastodon_host = 'https://mastodon.example.com';
        $mastodon_server = MastodonServerFactory::create([
            'host' => $mastodon_host,
            'statuses_max_characters' => 500,
        ]);
        $this->mockHttpWithResponse($mastodon_host . '/api/v2/instance', <<<TEXT
            HTTP/2 200
            Content-type: application/json

            {
                "configuration": {
                    "statuses": {
                        "max_characters": 5000
                    }
                }
            }
            TEXT
        );

        $job->perform();

        $mastodon_server = $mastodon_server->reload();
        $this->assertSame(5000, $mastodon_server->statuses_max_characters);
    }

    public function testPerformFallbacksToApiV1IfApiV2Fails(): void
    {
        $job = new MastodonServersSync();
        $mastodon_host = 'https://mastodon.example.com';
        $mastodon_server = MastodonServerFactory::create([
            'host' => $mastodon_host,
            'statuses_max_characters' => 500,
        ]);
        $this->mockHttpWithResponse($mastodon_host . '/api/v2/instance', <<<TEXT
            HTTP/2 404
            Content-type: application/json

            {
                "error": "Not Found"
            }
            TEXT
        );
        $this->mockHttpWithResponse($mastodon_host . '/api/v1/instance', <<<TEXT
            HTTP/2 200
            Content-type: application/json

            {
                "configuration": {
                    "statuses": {
                        "max_characters": 5000
                    }
                }
            }
            TEXT
        );

        $job->perform();

        $mastodon_server = $mastodon_server->reload();
        $this->assertSame(5000, $mastodon_server->statuses_max_characters);
    }

    public function testPerformKeepsTheMaxCharactersIfHostIsUnreachable(): void
    {
        $job = new MastodonServersSync();
        $mastodon_host = 'https://mastodon.example.com';
        $mastodon_server = MastodonServerFactory::create([
            'host' => $mastodon_host,
            'statuses_max_characters' => 5000,
        ]);
        $this->mockHttpAsUnreachable($mastodon_host . '/api/v2/instance');

        $job->perform();

        $mastodon_server = $mastodon_server->reload();
        $this->assertSame(5000, $mastodon_server->statuses_max_characters);
    }

    public function testPerformSyncsTheNextServersIfAHostIsUnreachable(): void
    {
        $job = new MastodonServersSync();
        $unreachable_host = 'https://unreachable.example.com';
        MastodonServerFactory::create([
            'host' => $unreachable_host,
        ]);
        $mastodon_host = 'https://mastodon.example.com';
        $mastodon_server = MastodonServerFactory::create([
            'host' => $mastodon_host,
            'statuses_max_characters' => 500,
        ]);
        $this->mockHttpAsUnreachable($unreachable_host . '/api/v2/instance');
        $this->mockHttpWithResponse($mastodon_host . '/api/v2/instance', <<<TEXT
            HTTP/2 200
            Content-type: application/json

            {
                "configuration": {
                    "statuses": {
                        "max_characters": 5000
                    }
                }
            }
            TEXT
        );

        $job->perform();

        $mastodon_server = $mastodon_server->reload();
        $this->assertSame(5000, $mastodon_server->statuses_max_characters);
    }
}
