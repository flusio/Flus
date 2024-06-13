<?php

namespace App\controllers\links;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;

class SearchesTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \tests\MockHttpHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    #[\PHPUnit\Framework\Attributes\Before]
    public function emptyCachePath(): void
    {
        $files = glob(\Minz\Configuration::$application['cache_path'] . '/*');

        assert($files !== false);

        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function testShowRendersCorrectly(): void
    {
        $user = $this->login();
        /** @var string */
        $url = $this->fake('url');

        $response = $this->appRun('GET', '/links/search', [
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $url);
        $this->assertResponsePointer($response, 'links/searches/show.phtml');
    }

    public function testShowDisplaysDefaultLink(): void
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        /** @var string */
        $url = $this->fake('url');
        /** @var string */
        $title = $this->fake('sentence');
        $link = LinkFactory::create([
            'user_id' => $support_user->id,
            'url' => $url,
            'is_hidden' => false,
            'title' => $title,
        ]);

        $response = $this->appRun('GET', '/links/search', [
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $title);
    }

    public function testShowDisplaysExistingLinkOverDefaultLink(): void
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        /** @var string */
        $url = $this->fake('url');
        /** @var string */
        $existing_title = $this->fakeUnique('sentence');
        /** @var string */
        $default_title = $this->fakeUnique('sentence');
        $existing_link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
            'title' => $existing_title,
        ]);
        $default_link = LinkFactory::create([
            'user_id' => $support_user->id,
            'url' => $url,
            'is_hidden' => false,
            'title' => $default_title,
        ]);

        $response = $this->appRun('GET', '/links/search', [
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $existing_title);
        $this->assertResponseNotContains($response, $default_title);
    }

    public function testShowDisplaysFeedCollections(): void
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        /** @var string */
        $name = $this->fake('sentence');
        /** @var string */
        $feed_url = $this->fake('url');
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'user_id' => $support_user->id,
            'is_public' => true,
            'name' => $name,
            'feed_url' => $feed_url,
        ]);
        $feed_link = LinkFactory::create();
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'link_id' => $feed_link->id,
        ]);
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $support_user->id,
            'url' => $url,
            'is_hidden' => false,
            'url_feeds' => [$feed_url],
        ]);

        $response = $this->appRun('GET', '/links/search', [
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $name);
    }

    public function testShowHidesDuplicatedFeeds(): void
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        /** @var string */
        $name = $this->fake('sentence');
        /** @var string */
        $feed_url_rss = $this->fakeUnique('url');
        /** @var string */
        $feed_url_atom = $this->fakeUnique('url');
        $collection_rss = CollectionFactory::create([
            'type' => 'feed',
            'user_id' => $support_user->id,
            'is_public' => true,
            'name' => $name,
            'feed_url' => $feed_url_rss,
        ]);
        $collection_atom = CollectionFactory::create([
            'type' => 'feed',
            'user_id' => $support_user->id,
            'is_public' => true,
            'name' => $name,
            'feed_url' => $feed_url_atom,
        ]);
        /** @var string */
        $link_url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $support_user->id,
            'url' => $link_url,
            'is_hidden' => false,
            'url_feeds' => [$feed_url_rss, $feed_url_atom],
        ]);

        $response = $this->appRun('GET', '/links/search', [
            'url' => $link_url,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $collection_rss->id);
        // Only the first feed is considered if name are the same but feed
        // types differ.
        $this->assertResponseNotContains($response, $collection_atom->id);
    }

    public function testShowDoesNotDisplaysHiddenDefaultLink(): void
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        /** @var string */
        $url = $this->fake('url');
        /** @var string */
        $title = $this->fake('sentence');
        $link = LinkFactory::create([
            'user_id' => $support_user->id,
            'url' => $url,
            'is_hidden' => true,
            'title' => $title,
        ]);

        $response = $this->appRun('GET', '/links/search', [
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $title);
    }

    public function testShowRedirectsIfNotConnected(): void
    {
        /** @var string */
        $url = $this->fake('url');

        $response = $this->appRun('GET', '/links/search', [
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Flinks%2Fsearch');
    }

    public function testCreateCreatesALinkAndFetchesIt(): void
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = 'https://flus.fr/carnet/';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $this->assertSame(0, models\Link::count());

        $response = $this->appRun('POST', '/links/search', [
            'csrf' => $user->csrf,
            'url' => $url,
        ]);

        $encoded_url = urlencode($url);
        $this->assertResponseCode($response, 302, "/links/search?url={$encoded_url}");
        $link = models\Link::findBy(['url' => $url]);
        $this->assertNotNull($link);
        $this->assertSame($support_user->id, $link->user_id);
        $this->assertSame('Carnet de Flus', $link->title);
        $this->assertSame(['https://flus.fr/carnet/feeds/all.atom.xml'], $link->url_feeds);
        $this->assertSame(200, $link->fetched_code);
    }

    public function testCreateCreatesFeedCollections(): void
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = 'https://flus.fr/carnet/';
        $url_feed = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        $this->mockHttpWithFixture($url_feed, 'responses/flus.fr_carnet_feeds_all.atom.xml');

        $this->assertSame(0, models\Collection::count());

        $response = $this->appRun('POST', '/links/search', [
            'csrf' => $user->csrf,
            'url' => $url,
        ]);

        $this->assertSame(1, models\Collection::count());
        $collection = models\Collection::findBy(['feed_url' => $url_feed]);
        $this->assertNotNull($collection);
        $this->assertSame($support_user->id, $collection->user_id);
        $this->assertSame('feed', $collection->type);
        $this->assertSame('Carnet de Flus', $collection->name);
        $this->assertSame(200, $collection->feed_fetched_code);
        $this->assertTrue($collection->is_public);
    }

    public function testCreateHandlesFeedUrl(): void
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_feeds_all.atom.xml');

        $response = $this->appRun('POST', '/links/search', [
            'csrf' => $user->csrf,
            'url' => $url,
        ]);

        $link = models\Link::findBy(['url' => $url]);
        $this->assertNotNull($link);
        $this->assertSame($support_user->id, $link->user_id);
        $this->assertSame($url, $link->title);
        $this->assertSame([$url], $link->url_feeds);
        $collection = models\Collection::findBy(['feed_url' => $url]);
        $this->assertNotNull($collection);
        $this->assertSame($support_user->id, $collection->user_id);
        $this->assertSame('feed', $collection->type);
        $this->assertSame('Carnet de Flus', $collection->name);
        $this->assertSame(200, $collection->feed_fetched_code);
        $this->assertTrue($collection->is_public);
    }

    public function testCreateDiscoversYoutubePlaylistFeed(): void
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = 'https://www.youtube.com/playlist?list=PLdhqndoLhA_7mQKanRpMGscqEgejpEWaJ';
        $this->mockHttpWithResponse($url, <<<TEXT
            HTTP/2 200
            Content-type: text/html

            <!DOCTYPE html>
            <html>
                <head>
                    <title>Je me suis (encore) perdu.e sur internet - saison 2</title>
                </head>
                <body></body>
            </html>
            TEXT
        );

        $response = $this->appRun('POST', '/links/search', [
            'csrf' => $user->csrf,
            'url' => $url,
        ]);

        $collection = models\Collection::take();
        $this->assertNotNull($collection);
        $this->assertSame(200, $collection->feed_fetched_code);
        $this->assertSame(
            'https://www.youtube.com/feeds/videos.xml?playlist_id=PLdhqndoLhA_7mQKanRpMGscqEgejpEWaJ',
            $collection->feed_url
        );
    }

    public function testCreateUpdatesDefaultLinkIfItExists(): void
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = 'https://flus.fr/carnet/';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        $link = LinkFactory::create([
            'user_id' => $support_user->id,
            'url' => $url,
            'title' => $url,
            'fetched_code' => 0,
        ]);

        $this->assertSame(1, models\Link::count());

        $response = $this->appRun('POST', '/links/search', [
            'csrf' => $user->csrf,
            'url' => $url,
        ]);

        $this->assertSame(1, models\Link::count());
        $link = $link->reload();
        $this->assertStringContainsString('Carnet de Flus', $link->title);
        $this->assertSame(200, $link->fetched_code);
    }

    public function testCreateDoesNotUpdateFeedIfItExists(): void
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = 'https://flus.fr/carnet/';
        $url_feed = 'https://flus.fr/carnet/feeds/all.atom.xml';
        /** @var string */
        $name = $this->fake('sentence');
        CollectionFactory::create([
            'type' => 'feed',
            'feed_url' => $url_feed,
            'user_id' => $support_user->id,
            'name' => $name,
        ]);

        $this->assertSame(1, models\Collection::count());

        $response = $this->appRun('POST', '/links/search', [
            'csrf' => $user->csrf,
            'url' => $url,
        ]);

        $this->assertSame(1, models\Collection::count());
        $collection = models\Collection::findBy(['feed_url' => $url_feed]);
        $this->assertNotNull($collection);
        $this->assertSame($name, $collection->name);
    }

    public function testCreateRedirectsIfNotConnected(): void
    {
        $support_user = models\User::supportUser();
        $url = 'https://github.com/flusio/Flus';

        $this->assertSame(0, models\Link::count());

        $response = $this->appRun('POST', '/links/search', [
            'csrf' => 'a token',
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Flinks%2Fsearch');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = 'https://github.com/flusio/Flus';

        $this->assertSame(0, models\Link::count());

        $response = $this->appRun('POST', '/links/search', [
            'csrf' => 'not the token',
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfUrlIsInvalid(): void
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = '';

        $this->assertSame(0, models\Link::count());

        $response = $this->appRun('POST', '/links/search', [
            'csrf' => $user->csrf,
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The link is required.');
        $this->assertSame(0, models\Link::count());
    }
}
