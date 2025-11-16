<?php

namespace App\controllers\links;

use App\forms;
use App\models;
use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;

class SearchesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\FilesystemHelper;
    use \tests\LoginHelper;
    use \tests\MockHttpHelper;

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
        $this->assertResponseTemplateName($response, 'links/searches/show.phtml');
    }

    public function testShowDisplaysExistingLink(): void
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
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
            'url_feeds' => [$feed_url],
        ]);

        $response = $this->appRun('GET', '/links/search', [
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $name);
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
        $url = 'https://flus.fr/carnet/';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $this->assertSame(0, models\Link::count());

        $response = $this->appRun('POST', '/links/search', [
            'csrf_token' => $this->csrfToken(forms\Search::class),
            'url' => $url,
        ]);

        $encoded_url = urlencode($url);
        $this->assertResponseCode($response, 302, "/links/search?url={$encoded_url}");
        $link = models\Link::findBy(['url' => $url]);
        $this->assertNotNull($link);
        $this->assertSame($user->id, $link->user_id);
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
            'csrf_token' => $this->csrfToken(forms\Search::class),
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
            'csrf_token' => $this->csrfToken(forms\Search::class),
            'url' => $url,
        ]);

        $link = models\Link::findBy(['url' => $url]);
        $this->assertNotNull($link);
        $this->assertSame($user->id, $link->user_id);
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
            'csrf_token' => $this->csrfToken(forms\Search::class),
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

    public function testCreateRedirectsIfNotConnected(): void
    {
        $support_user = models\User::supportUser();
        $url = 'https://github.com/flusio/Flus';

        $this->assertSame(0, models\Link::count());

        $response = $this->appRun('POST', '/links/search', [
            'csrf_token' => $this->csrfToken(forms\Search::class),
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
            'csrf_token' => 'not the token',
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
            'csrf_token' => $this->csrfToken(forms\Search::class),
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The link is required.');
        $this->assertSame(0, models\Link::count());
    }
}
