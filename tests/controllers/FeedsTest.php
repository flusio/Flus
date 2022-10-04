<?php

namespace flusio\controllers;

use flusio\models;

class FeedsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \tests\MockHttpHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    /**
     * @before
     */
    public function emptyCachePath()
    {
        $files = glob(\Minz\Configuration::$application['cache_path'] . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function testIndexRendersCorrectly()
    {
        $user = $this->login();
        $feed_name = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'name' => $feed_name,
            'type' => 'feed',
            'is_public' => 1,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', '/feeds');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'feeds/index.phtml');
        $this->assertResponseContains($response, $feed_name);
    }

    public function testIndexRendersGroups()
    {
        $user = $this->login();
        $group_name = $this->fake('words', 3, true);
        $group_id = $this->create('group', [
            'user_id' => $user->id,
            'name' => $group_name,
        ]);
        $collection_id = $this->create('collection', [
            'type' => 'feed',
            'is_public' => 1,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
            'group_id' => $group_id
        ]);

        $response = $this->appRun('get', '/feeds');

        $this->assertResponseContains($response, $group_name);
    }

    public function testIndexDoesNotRenderPrivateFollowed()
    {
        $user = $this->login();
        $feed_name = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'name' => $feed_name,
            'type' => 'feed',
            'is_public' => 0,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', '/feeds');

        $this->assertResponseNotContains($response, $feed_name);
    }

    public function testIndexRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $feed_name = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'name' => $feed_name,
            'type' => 'feed',
            'is_public' => 1,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', '/feeds');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Ffeeds');
    }

    public function testNewRendersCorrectly()
    {
        $user = $this->login();

        $response = $this->appRun('get', '/feeds/new', [
            'from' => \Minz\Url::for('feeds'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'feeds/new.phtml');
        $this->assertResponseContains($response, 'New feed');
    }

    public function testNewRedirectsIfNotConnected()
    {
        $response = $this->appRun('get', '/feeds/new', [
            'from' => \Minz\Url::for('feeds'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Ffeeds');
    }

    public function testCreateCreatesAFeedAndRedirectToTheFeed()
    {
        $user = $this->login();
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithFixture($feed_url, 'responses/flus.fr_carnet_feeds_all.atom.xml');

        $this->assertSame(0, models\Collection::countBy(['type' => 'feed']));

        $response = $this->appRun('post', '/feeds/new', [
            'csrf' => $user->csrf,
            'url' => $feed_url,
            'from' => \Minz\Url::for('feeds'),
        ]);

        $this->assertSame(1, models\Collection::countBy(['type' => 'feed']));
        $collection = models\Collection::findBy(['type' => 'feed']);
        $this->assertResponseCode($response, 302, "/collections/{$collection->id}");
        $this->assertSame($feed_url, $collection->feed_url);
        $this->assertTrue($user->isFollowing($collection->id));
    }

    public function testCreateAutodetectsFeedUrls()
    {
        $user = $this->login();
        $url = 'https://flus.fr/carnet/';
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        $this->mockHttpWithFixture($feed_url, 'responses/flus.fr_carnet_feeds_all.atom.xml');

        $this->assertSame(0, models\Collection::countBy(['type' => 'feed']));

        $response = $this->appRun('post', '/feeds/new', [
            'csrf' => $user->csrf,
            'url' => $url,
            'from' => \Minz\Url::for('feeds'),
        ]);

        $this->assertSame(1, models\Collection::countBy(['type' => 'feed']));
        $collection = models\Collection::findBy(['type' => 'feed']);
        $this->assertResponseCode($response, 302, "/collections/{$collection->id}");
        $this->assertSame($feed_url, $collection->feed_url);
    }

    public function testCreateRedirectsToLoginIfNotConnected()
    {
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithFixture($feed_url, 'responses/flus.fr_carnet_feeds_all.atom.xml');

        $response = $this->appRun('post', '/feeds/new', [
            'csrf' => $user->csrf,
            'url' => $feed_url,
            'from' => \Minz\Url::for('feeds'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Ffeeds');
        $this->assertSame(0, models\Collection::countBy(['type' => 'feed']));
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithFixture($feed_url, 'responses/flus.fr_carnet_feeds_all.atom.xml');

        $response = $this->appRun('post', '/feeds/new', [
            'csrf' => 'not the token',
            'url' => $feed_url,
            'from' => \Minz\Url::for('feeds'),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\Collection::countBy(['type' => 'feed']));
    }

    public function testCreateFailsIfUrlIsInvalid()
    {
        $user = $this->login();
        $feed_url = 'ftp://flus.fr/carnet/feeds/all.atom.xml';

        $response = $this->appRun('post', '/feeds/new', [
            'csrf' => $user->csrf,
            'url' => $feed_url,
            'from' => \Minz\Url::for('feeds'),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'Link scheme must be either http or https');
        $this->assertSame(0, models\Collection::countBy(['type' => 'feed']));
    }

    public function testCreateFailsIfUrlIsMissing()
    {
        $user = $this->login();
        $feed_url = '';

        $response = $this->appRun('post', '/feeds/new', [
            'csrf' => $user->csrf,
            'url' => $feed_url,
            'from' => \Minz\Url::for('feeds'),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The link is required');
        $this->assertSame(0, models\Collection::countBy(['type' => 'feed']));
    }

    public function testCreateFailsIfNoFeedsCanBeFound()
    {
        $user = $this->login();
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

        $response = $this->appRun('post', '/feeds/new', [
            'csrf' => $user->csrf,
            'url' => $feed_url,
            'from' => \Minz\Url::for('feeds'),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'There is no valid feeds at this address');
        $this->assertSame(0, models\Collection::countBy(['type' => 'feed']));
    }

    public function testWhatIsNewRedirectsToCollection()
    {
        $support_user = models\User::supportUser();
        $feed_url = 'https://github.com/flusio/flusio/releases.atom';
        $collection_id = $this->create('collection', [
            'user_id' => $support_user->id,
            'type' => 'feed',
            'feed_url' => $feed_url,
        ]);

        $response = $this->appRun('get', '/about/new');

        $this->assertResponseCode($response, 302, "/collections/{$collection_id}");
    }

    public function testWhatIsNewCreatesFeedIfItDoesNotExist()
    {
        $support_user = models\User::supportUser();
        $feed_url = 'https://github.com/flusio/flusio/releases.atom';
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

        $this->assertSame(0, models\Collection::count());

        $response = $this->appRun('get', '/about/new');

        $this->assertSame(1, models\Collection::count());

        $collection = models\Collection::take();
        $this->assertSame($feed_url, $collection->feed_url);
        $this->assertResponseCode($response, 302, "/collections/{$collection->id}");
    }
}
