<?php

namespace flusio\controllers;

use flusio\models;
use tests\factories\CollectionFactory;
use tests\factories\GroupFactory;
use tests\factories\FollowedCollectionFactory;
use tests\factories\UserFactory;

class FeedsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \tests\MockHttpHelper;
    use \Minz\Tests\ApplicationHelper;
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
        $collection = CollectionFactory::create([
            'name' => $feed_name,
            'type' => 'feed',
            'is_public' => true,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('GET', '/feeds');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'feeds/index.phtml');
        $this->assertResponseContains($response, $feed_name);
    }

    public function testIndexRendersGroups()
    {
        $user = $this->login();
        $group_name = $this->fake('words', 3, true);
        $group = GroupFactory::create([
            'user_id' => $user->id,
            'name' => $group_name,
        ]);
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
            'group_id' => $group->id
        ]);

        $response = $this->appRun('GET', '/feeds');

        $this->assertResponseContains($response, $group_name);
    }

    public function testIndexDoesNotRenderPrivateFollowed()
    {
        $user = $this->login();
        $feed_name = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'name' => $feed_name,
            'type' => 'feed',
            'is_public' => false,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('GET', '/feeds');

        $this->assertResponseNotContains($response, $feed_name);
    }

    public function testIndexRedirectsIfNotConnected()
    {
        $user = UserFactory::create();
        $feed_name = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'name' => $feed_name,
            'type' => 'feed',
            'is_public' => true,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('GET', '/feeds');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Ffeeds');
    }

    public function testNewRendersCorrectly()
    {
        $user = $this->login();

        $response = $this->appRun('GET', '/feeds/new', [
            'from' => \Minz\Url::for('feeds'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'feeds/new.phtml');
        $this->assertResponseContains($response, 'New feed');
    }

    public function testNewRedirectsIfNotConnected()
    {
        $response = $this->appRun('GET', '/feeds/new', [
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

        $response = $this->appRun('POST', '/feeds/new', [
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

        $response = $this->appRun('POST', '/feeds/new', [
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
        $user = UserFactory::create();
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithFixture($feed_url, 'responses/flus.fr_carnet_feeds_all.atom.xml');

        $response = $this->appRun('POST', '/feeds/new', [
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

        $response = $this->appRun('POST', '/feeds/new', [
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

        $response = $this->appRun('POST', '/feeds/new', [
            'csrf' => $user->csrf,
            'url' => $feed_url,
            'from' => \Minz\Url::for('feeds'),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The link is invalid.');
        $this->assertSame(0, models\Collection::countBy(['type' => 'feed']));
    }

    public function testCreateFailsIfUrlIsMissing()
    {
        $user = $this->login();
        $feed_url = '';

        $response = $this->appRun('POST', '/feeds/new', [
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

        $response = $this->appRun('POST', '/feeds/new', [
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
        $collection = CollectionFactory::create([
            'user_id' => $support_user->id,
            'type' => 'feed',
            'feed_url' => $feed_url,
        ]);

        $response = $this->appRun('GET', '/about/new');

        $this->assertResponseCode($response, 302, "/collections/{$collection->id}");
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

        $response = $this->appRun('GET', '/about/new');

        $this->assertSame(1, models\Collection::count());

        $collection = models\Collection::take();
        $this->assertSame($feed_url, $collection->feed_url);
        $this->assertResponseCode($response, 302, "/collections/{$collection->id}");
    }
}
