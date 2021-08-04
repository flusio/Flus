<?php

namespace flusio\controllers\links;

use flusio\models;

class SearchesTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $user = $this->login();
        $url = $this->fake('url');

        $response = $this->appRun('get', '/links/search', [
            'url' => $url,
        ]);

        $this->assertResponse($response, 200, $url);
        $this->assertPointer($response, 'links/searches/show.phtml');
    }

    public function testShowDisplaysDefaultLink()
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = $this->fake('url');
        $title = $this->fake('sentence');
        $link_id = $this->create('link', [
            'user_id' => $support_user->id,
            'url' => $url,
            'is_hidden' => 0,
            'title' => $title,
        ]);

        $response = $this->appRun('get', '/links/search', [
            'url' => $url,
        ]);

        $this->assertResponse($response, 200, $title);
    }

    public function testShowDisplaysExistingLinkOverDefaultLink()
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = $this->fake('url');
        $existing_title = $this->fakeUnique('sentence');
        $default_title = $this->fakeUnique('sentence');
        $existing_link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
            'title' => $existing_title,
        ]);
        $default_link_id = $this->create('link', [
            'user_id' => $support_user->id,
            'url' => $url,
            'is_hidden' => 0,
            'title' => $default_title,
        ]);

        $response = $this->appRun('get', '/links/search', [
            'url' => $url,
        ]);

        $this->assertResponse($response, 200);
        $output = $response->render();
        $this->assertStringContainsString($existing_title, $output);
        $this->assertStringNotContainsString($default_title, $output);
    }

    public function testShowDisplaysFeedCollections()
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $name = $this->fake('sentence');
        $feed_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'type' => 'feed',
            'user_id' => $support_user->id,
            'is_public' => 1,
            'name' => $name,
            'feed_url' => $feed_url,
        ]);
        $feed_link_id = $this->create('link');
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $feed_link_id,
        ]);
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $support_user->id,
            'url' => $url,
            'is_hidden' => 0,
            'url_feeds' => "[\"{$feed_url}\"]",
        ]);

        $response = $this->appRun('get', '/links/search', [
            'url' => $url,
        ]);

        $this->assertResponse($response, 200);
        $output = $response->render();
        $this->assertStringContainsString($name, $output);
    }

    public function testShowDoesNotDisplaysHiddenDefaultLink()
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = $this->fake('url');
        $title = $this->fake('sentence');
        $link_id = $this->create('link', [
            'user_id' => $support_user->id,
            'url' => $url,
            'is_hidden' => 1,
            'title' => $title,
        ]);

        $response = $this->appRun('get', '/links/search', [
            'url' => $url,
        ]);

        $this->assertResponse($response, 200);
        $output = $response->render();
        $this->assertStringNotContainsString($title, $output);
    }

    public function testShowRedirectsIfNotConnected()
    {
        $url = $this->fake('url');

        $response = $this->appRun('get', '/links/search', [
            'url' => $url,
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Flinks%2Fsearch');
    }

    public function testCreateCreatesALinkAndFetchesIt()
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = 'https://flus.fr/carnet/';

        $this->assertSame(0, models\Link::count());

        $response = $this->appRun('post', '/links/search', [
            'csrf' => $user->csrf,
            'url' => $url,
        ]);

        $encoded_url = urlencode($url);
        $this->assertResponse($response, 302, "/links/search?url={$encoded_url}");
        $link = models\Link::findBy(['url' => $url]);
        $this->assertSame($support_user->id, $link->user_id);
        $this->assertSame('Carnet de Flus', $link->title);
        $this->assertSame(['https://flus.fr/carnet/feeds/all.atom.xml'], $link->feedUrls());
        $this->assertSame(200, $link->fetched_code);
    }

    public function testCreateCreatesFeedCollections()
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = 'https://flus.fr/carnet/';
        $url_feed = 'https://flus.fr/carnet/feeds/all.atom.xml';

        $this->assertSame(0, models\Collection::count());

        $response = $this->appRun('post', '/links/search', [
            'csrf' => $user->csrf,
            'url' => $url,
        ]);

        $this->assertSame(1, models\Collection::count());
        $collection = models\Collection::findBy(['feed_url' => $url_feed]);
        $this->assertSame($support_user->id, $collection->user_id);
        $this->assertSame('feed', $collection->type);
        $this->assertSame('Carnet de Flus', $collection->name);
        $this->assertSame(200, $collection->feed_fetched_code);
        $this->assertTrue($collection->is_public);
    }

    public function testCreateHandlesFeedUrl()
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = 'https://flus.fr/carnet/feeds/all.atom.xml';

        $response = $this->appRun('post', '/links/search', [
            'csrf' => $user->csrf,
            'url' => $url,
        ]);

        $link = models\Link::findBy(['url' => $url]);
        $this->assertSame($support_user->id, $link->user_id);
        $this->assertSame($url, $link->title);
        $this->assertSame([$url], $link->feedUrls());
        $collection = models\Collection::findBy(['feed_url' => $url]);
        $this->assertSame($support_user->id, $collection->user_id);
        $this->assertSame('feed', $collection->type);
        $this->assertSame('Carnet de Flus', $collection->name);
        $this->assertSame(200, $collection->feed_fetched_code);
        $this->assertTrue($collection->is_public);
    }

    public function testCreateDiscoversYoutubePlaylistFeed()
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = 'https://www.youtube.com/playlist?list=PLdhqndoLhA_7mQKanRpMGscqEgejpEWaJ';

        $response = $this->appRun('post', '/links/search', [
            'csrf' => $user->csrf,
            'url' => $url,
        ]);

        $collection = models\Collection::take();
        $this->assertSame(200, $collection->feed_fetched_code);
        $this->assertSame(
            'https://www.youtube.com/feeds/videos.xml?playlist_id=PLdhqndoLhA_7mQKanRpMGscqEgejpEWaJ',
            $collection->feed_url
        );
    }

    public function testCreateUpdatesDefaultLinkIfItExists()
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = 'https://github.com/flusio/flusio';
        $link_id = $this->create('link', [
            'user_id' => $support_user->id,
            'url' => $url,
            'title' => $url,
            'fetched_code' => 0,
        ]);

        $this->assertSame(1, models\Link::count());

        $response = $this->appRun('post', '/links/search', [
            'csrf' => $user->csrf,
            'url' => $url,
        ]);

        $this->assertSame(1, models\Link::count());
        $link = models\Link::find($link_id);
        $this->assertStringContainsString('flusio/flusio', $link->title);
        $this->assertSame(200, $link->fetched_code);
    }

    public function testCreateDoesNotUpdateFeedIfItExists()
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = 'https://flus.fr/carnet/';
        $url_feed = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $name = $this->fake('sentence');
        $this->create('collection', [
            'type' => 'feed',
            'feed_url' => $url_feed,
            'user_id' => $support_user->id,
            'name' => $name,
        ]);

        $this->assertSame(1, models\Collection::count());

        $response = $this->appRun('post', '/links/search', [
            'csrf' => $user->csrf,
            'url' => $url,
        ]);

        $this->assertSame(1, models\Collection::count());
        $collection = models\Collection::findBy(['feed_url' => $url_feed]);
        $this->assertSame($name, $collection->name);
    }

    public function testCreateRedirectsIfNotConnected()
    {
        $support_user = models\User::supportUser();
        $url = 'https://github.com/flusio/flusio';

        $this->assertSame(0, models\Link::count());

        $response = $this->appRun('post', '/links/search', [
            'csrf' => 'a token',
            'url' => $url,
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Flinks%2Fsearch');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = 'https://github.com/flusio/flusio';

        $this->assertSame(0, models\Link::count());

        $response = $this->appRun('post', '/links/search', [
            'csrf' => 'not the token',
            'url' => $url,
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfUrlIsInvalid()
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = '';

        $this->assertSame(0, models\Link::count());

        $response = $this->appRun('post', '/links/search', [
            'csrf' => $user->csrf,
            'url' => $url,
        ]);

        $this->assertResponse($response, 400, 'The link is required.');
        $this->assertSame(0, models\Link::count());
    }
}
