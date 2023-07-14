<?php

namespace flusio\controllers\collections;

use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;

class FeedsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $link_title = $this->fake('words', 3, true);
        $link_url = $this->fake('url');
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'title' => $link_title,
            'url' => $link_url,
            'is_hidden' => false,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/feed.atom.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'collections/feeds/show.atom.xml.php');
        $this->assertResponseHeaders($response, [
            'Content-Type' => 'application/xml',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $feed = \SpiderBits\feeds\Feed::fromText($response->render());
        $link_alternate = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $this->assertSame(1, count($feed->entries));
        $this->assertSame($link_title, $feed->entries[0]->title);
        $this->assertSame($link_alternate, $feed->entries[0]->links['alternate']);
        $this->assertSame($link_url, $feed->entries[0]->links['via']);
    }

    public function testShowRendersAlternateLinksAsOriginalUrlWithDirectTrue()
    {
        $link_url = $this->fake('url');
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'url' => $link_url,
            'is_hidden' => false,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/feed.atom.xml", [
            'direct' => true,
        ]);

        $this->assertResponseCode($response, 200);
        $feed = \SpiderBits\feeds\Feed::fromText($response->render());
        $link_replies = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $this->assertSame(1, count($feed->entries));
        $this->assertSame($link_url, $feed->entries[0]->links['alternate']);
        $this->assertSame($link_replies, $feed->entries[0]->links['replies']);
    }

    public function testShowDoesNotRenderHiddenLinks()
    {
        $link_title = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'title' => $link_title,
            'is_hidden' => true,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/feed.atom.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $link_title);
    }

    public function testShowRedirectsToOriginIfCollectionIsFeed()
    {
        $feed_url = $this->fake('url');
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
            'feed_url' => $feed_url,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/feed.atom.xml");

        $this->assertResponseCode($response, 301, $feed_url);
    }

    public function testShowFailsIfCollectionIsInaccessible()
    {
        $link_title = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => false,
        ]);
        $link = LinkFactory::create([
            'title' => $link_title,
            'is_hidden' => false,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/feed.atom.xml");

        $this->assertResponseCode($response, 404);
    }

    public function testAliasRedirectsToShow()
    {
        $collection = CollectionFactory::create();

        $response = $this->appRun('GET', "/collections/{$collection->id}/feed");

        $this->assertResponseCode($response, 301, "/collections/{$collection->id}/feed.atom.xml");
    }

    public function testAliasRedirectsWithQuery()
    {
        $collection = CollectionFactory::create();
        $_SERVER['QUERY_STRING'] = 'direct=true';

        $response = $this->appRun('GET', "/collections/{$collection->id}/feed");

        $_SERVER['QUERY_STRING'] = '';
        $this->assertResponseCode($response, 301, "/collections/{$collection->id}/feed.atom.xml?direct=true");
    }
}
