<?php

namespace App\controllers;

use App\forms;
use App\models;
use tests\factories\FeedFactory;
use tests\factories\UserFactory;

class FeedsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\FilesystemHelper;
    use \tests\HttpHelper;
    use \tests\LoginHelper;

    public function testIndexRendersANoticePointingToTheSources(): void
    {
        $user = $this->login();
        /** @var string */
        $feed_name = $this->fake('words', 3, true);
        /** @var string */
        $feed_url = $this->fake('url');
        $feed = FeedFactory::create([
            'name' => $feed_name,
            'is_public' => true,
            'feed_url' => $feed_url,
        ]);
        $user->follow($feed);

        $response = $this->appRun('GET', '/feeds');

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'feeds/index.html.twig');
        $this->assertResponseContains($response, 'This page has moved');
        $this->assertResponseNotContains($response, $feed_name);
    }

    public function testIndexRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $feed_name = $this->fake('words', 3, true);
        /** @var string */
        $feed_url = $this->fake('url');
        $feed = FeedFactory::create([
            'name' => $feed_name,
            'is_public' => true,
            'feed_url' => $feed_url,
        ]);
        $user->follow($feed);

        $response = $this->appRun('GET', '/feeds');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Ffeeds');
    }

    public function testNewRendersCorrectly(): void
    {
        $user = $this->login();

        $response = $this->appRun('GET', '/feeds/new');

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'feeds/new.html.twig');
        $this->assertResponseContains($response, 'New feed');
    }

    public function testNewRedirectsIfNotConnected(): void
    {
        $response = $this->appRun('GET', '/feeds/new');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Ffeeds%2Fnew');
    }

    public function testCreateCreatesAFeedAndRedirectToTheFeed(): void
    {
        $user = $this->login();
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithFixture($feed_url, 'responses/flus.fr_carnet_feeds_all.atom.xml');

        $this->assertSame(0, models\Feed::count());

        $response = $this->appRun('POST', '/feeds/new', [
            'csrf_token' => $this->csrfToken(forms\feeds\NewFeed::class),
            'url' => $feed_url,
        ]);

        $this->assertSame(1, models\Feed::count());
        $feed = models\Feed::take();
        $this->assertNotNull($feed);
        $this->assertResponseCode($response, 302, "/collections/{$feed->id}");
        $this->assertSame($feed_url, $feed->feed_url);
        $this->assertTrue($user->isFollowing($feed));
    }

    public function testCreateAutodetectsFeedUrls(): void
    {
        $user = $this->login();
        $url = 'https://flus.fr/carnet/';
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        $this->mockHttpWithFixture($feed_url, 'responses/flus.fr_carnet_feeds_all.atom.xml');

        $this->assertSame(0, models\Feed::count());

        $response = $this->appRun('POST', '/feeds/new', [
            'csrf_token' => $this->csrfToken(forms\feeds\NewFeed::class),
            'url' => $url,
        ]);

        $this->assertSame(1, models\Feed::count());
        $feed = models\Feed::take();
        $this->assertNotNull($feed);
        $this->assertResponseCode($response, 302, "/collections/{$feed->id}");
        $this->assertSame($feed_url, $feed->feed_url);
    }

    public function testCreateRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithFixture($feed_url, 'responses/flus.fr_carnet_feeds_all.atom.xml');

        $response = $this->appRun('POST', '/feeds/new', [
            'csrf_token' => $this->csrfToken(forms\feeds\NewFeed::class),
            'url' => $feed_url,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Ffeeds%2Fnew');
        $this->assertSame(0, models\Feed::count());
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithFixture($feed_url, 'responses/flus.fr_carnet_feeds_all.atom.xml');

        $response = $this->appRun('POST', '/feeds/new', [
            'csrf_token' => 'not the token',
            'url' => $feed_url,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\Feed::count());
    }

    public function testCreateFailsIfUrlIsInvalid(): void
    {
        $user = $this->login();
        $feed_url = 'ftp://flus.fr/carnet/feeds/all.atom.xml';

        $response = $this->appRun('POST', '/feeds/new', [
            'csrf_token' => $this->csrfToken(forms\feeds\NewFeed::class),
            'url' => $feed_url,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The link is invalid.');
        $this->assertSame(0, models\Feed::count());
    }

    public function testCreateFailsIfUrlIsMissing(): void
    {
        $user = $this->login();
        $feed_url = '';

        $response = $this->appRun('POST', '/feeds/new', [
            'csrf_token' => $this->csrfToken(forms\feeds\NewFeed::class),
            'url' => $feed_url,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The link is required');
        $this->assertSame(0, models\Feed::count());
    }

    public function testCreateFailsIfNoFeedsCanBeFound(): void
    {
        $user = $this->login();
        /** @var string */
        $feed_url = $this->fake('url');
        $this->mockHttpWithResponse($feed_url, <<<TEXT
            HTTP/2 200
            Content-type: text/html

            <html>
                <head>
                    <title>Hello World</title>
                </head>
                <body>This site has no feeds.</body>
            </html>
            TEXT
        );

        $response = $this->appRun('POST', '/feeds/new', [
            'csrf_token' => $this->csrfToken(forms\feeds\NewFeed::class),
            'url' => $feed_url,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'There is no valid feeds at this address');
        $this->assertSame(0, models\Feed::count());
    }

    public function testWhatIsNewRedirectsToCollection(): void
    {
        $feed_url = 'https://github.com/flusio/Flus/releases.atom';
        $feed = FeedFactory::create([
            'feed_url' => $feed_url,
        ]);

        $response = $this->appRun('GET', '/about/new');

        $this->assertResponseCode($response, 302, "/collections/{$feed->id}");
    }

    public function testWhatIsNewCreatesFeedIfItDoesNotExist(): void
    {
        $feed_url = 'https://github.com/flusio/Flus/releases.atom';
        $this->mockHttpWithFixture(
            $feed_url,
            'responses/flus.fr_carnet_feeds_all.atom.xml'
        );
        // The FeedFetcher will call these URLs as well because the feed URL is
        // mocked with the feed of flus.fr/carnet.
        $this->mockHttpWithFixture(
            'https://flus.fr/carnet/',
            'responses/flus.fr_carnet_index.html'
        );
        $this->mockHttpWithFile(
            'https://flus.fr/carnet/card.png',
            'public/static/og-card.png'
        );

        $this->assertSame(0, models\Feed::count());

        $response = $this->appRun('GET', '/about/new');

        $this->assertSame(1, models\Feed::count());

        $feed = models\Feed::take();
        $this->assertNotNull($feed);
        $this->assertSame($feed_url, $feed->feed_url);
        $this->assertResponseCode($response, 302, "/collections/{$feed->id}");
    }
}
