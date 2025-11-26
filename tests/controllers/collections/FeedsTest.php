<?php

namespace App\controllers\collections;

use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;

class FeedsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;

    public function testShowRendersCorrectly(): void
    {
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        /** @var string */
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
        $this->assertResponseTemplateName($response, 'collections/feeds/show.atom.xml.twig');
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
        /** @var string */
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
        $this->assertInstanceOf(\Minz\Response::class, $response);
        $feed = \SpiderBits\feeds\Feed::fromText($response->render());
        $link_replies = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $this->assertSame(1, count($feed->entries));
        $this->assertSame($link_url, $feed->entries[0]->links['alternate']);
        $this->assertSame($link_replies, $feed->entries[0]->links['replies']);
    }

    public function testShowDoesNotRenderHiddenLinks(): void
    {
        /** @var string */
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

    public function testShowRedirectsToOriginIfCollectionIsFeed(): void
    {
        /** @var string */
        $feed_url = $this->fake('url');
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
            'feed_url' => $feed_url,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/feed.atom.xml");

        $this->assertResponseCode($response, 301, $feed_url);
    }

    public function testShowFailsIfCollectionIsInaccessible(): void
    {
        /** @var string */
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

        $this->assertResponseCode($response, 403);
    }

    public function testAliasRedirectsToShow(): void
    {
        $collection = CollectionFactory::create();

        $response = $this->appRun('GET', "/collections/{$collection->id}/feed");

        $this->assertResponseCode($response, 301, "/collections/{$collection->id}/feed.atom.xml");
    }

    public function testAliasRedirectsWithQuery(): void
    {
        $collection = CollectionFactory::create();
        $_SERVER['QUERY_STRING'] = 'direct=true';

        $response = $this->appRun('GET', "/collections/{$collection->id}/feed");

        $_SERVER['QUERY_STRING'] = '';
        $this->assertResponseCode($response, 301, "/collections/{$collection->id}/feed.atom.xml?direct=true");
    }
}
