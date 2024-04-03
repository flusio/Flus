<?php

namespace App\controllers\profiles;

use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\UserFactory;

class FeedsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        /** @var string */
        $link_url = $this->fake('url');
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
            'title' => $link_title,
            'url' => $link_url,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);

        $response = $this->appRun('GET', "/p/{$user->id}/feed.atom.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'profiles/feeds/show.atom.xml.php');
        $this->assertResponseHeaders($response, [
            'Content-Type' => 'application/xml',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $this->assertInstanceOf(\Minz\Response::class, $response);
        $feed = \SpiderBits\feeds\Feed::fromText($response->render());
        $link_alternate = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $this->assertSame(1, count($feed->entries));
        $this->assertSame($link_title, $feed->entries[0]->title);
        $this->assertSame($link_alternate, $feed->entries[0]->links['alternate']);
        $this->assertSame($link_url, $feed->entries[0]->links['via']);
    }

    public function testShowRendersAlternateLinksAsOriginalUrlWithDirectTrue(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $link_url = $this->fake('url');
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
            'url' => $link_url,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);

        $response = $this->appRun('GET', "/p/{$user->id}/feed.atom.xml", [
            'direct' => 'true',
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertInstanceOf(\Minz\Response::class, $response);
        $feed = \SpiderBits\feeds\Feed::fromText($response->render());
        $link_replies = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $this->assertSame(1, count($feed->entries));
        $this->assertSame($link_url, $feed->entries[0]->links['alternate']);
        $this->assertSame($link_replies, $feed->entries[0]->links['replies']);
    }

    public function testShowDoesNotRenderHiddenLinks(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => true,
            'title' => $link_title,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);

        $response = $this->appRun('GET', "/p/{$user->id}/feed.atom.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $link_title);
    }

    public function testShowDoesNotRenderVisibleLinksInPrivateCollections(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'is_public' => false,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
            'title' => $link_title,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);

        $response = $this->appRun('GET', "/p/{$user->id}/feed.atom.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $link_title);
    }

    public function testShowDoesNotRenderVisibleLinksInNoCollections(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
            'title' => $link_title,
        ]);

        $response = $this->appRun('GET', "/p/{$user->id}/feed.atom.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $link_title);
    }

    public function testShowFailsIfUserDoesNotExist(): void
    {
        $response = $this->appRun('GET', '/p/not-an-id/feed.atom.xml');

        $this->assertResponseCode($response, 404);
    }

    public function testAliasRedirectsToShow(): void
    {
        $user = UserFactory::create();

        $response = $this->appRun('GET', "/p/{$user->id}/feed");

        $this->assertResponseCode($response, 301, "/p/{$user->id}/feed.atom.xml");
    }

    public function testAliasRedirectsWithQuery(): void
    {
        $user = UserFactory::create();
        $_SERVER['QUERY_STRING'] = 'direct=true';

        $response = $this->appRun('GET', "/p/{$user->id}/feed");

        $_SERVER['QUERY_STRING'] = '';
        $this->assertResponseCode($response, 301, "/p/{$user->id}/feed.atom.xml?direct=true");
    }
}
