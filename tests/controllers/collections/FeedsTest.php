<?php

namespace flusio\controllers\collections;

class FeedsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $link_title = $this->fake('words', 3, true);
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $link_id = $this->create('link', [
            'title' => $link_title,
            'url' => $link_url,
            'is_hidden' => 0,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', "/collections/{$collection_id}/feed.atom.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'collections/feeds/show.atom.xml.php');
        $this->assertResponseHeaders($response, [
            'Content-Type' => 'application/xml',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $feed = \SpiderBits\feeds\Feed::fromText($response->render());
        $link_alternate = \Minz\Url::absoluteFor('link', ['id' => $link_id]);
        $this->assertSame(1, count($feed->entries));
        $this->assertSame($link_title, $feed->entries[0]->title);
        $this->assertSame($link_alternate, $feed->entries[0]->links['alternate']);
        $this->assertSame($link_url, $feed->entries[0]->links['via']);
    }

    public function testShowRendersAlternateLinksAsOriginalUrlWithDirectTrue()
    {
        $link_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $link_id = $this->create('link', [
            'url' => $link_url,
            'is_hidden' => 0,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', "/collections/{$collection_id}/feed.atom.xml", [
            'direct' => true,
        ]);

        $this->assertResponseCode($response, 200);
        $feed = \SpiderBits\feeds\Feed::fromText($response->render());
        $link_replies = \Minz\Url::absoluteFor('link', ['id' => $link_id]);
        $this->assertSame(1, count($feed->entries));
        $this->assertSame($link_url, $feed->entries[0]->links['alternate']);
        $this->assertSame($link_replies, $feed->entries[0]->links['replies']);
    }

    public function testShowDoesNotRenderHiddenLinks()
    {
        $link_title = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $link_id = $this->create('link', [
            'title' => $link_title,
            'is_hidden' => 1,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', "/collections/{$collection_id}/feed.atom.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $link_title);
    }

    public function testShowRedirectsToOriginIfCollectionIsFeed()
    {
        $feed_url = $this->fake('url');
        $collection_id = $this->create('collection', [
            'type' => 'feed',
            'is_public' => 1,
            'feed_url' => $feed_url,
        ]);

        $response = $this->appRun('get', "/collections/{$collection_id}/feed.atom.xml");

        $this->assertResponseCode($response, 301, $feed_url);
    }

    public function testShowFailsIfCollectionIsInaccessible()
    {
        $link_title = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'is_public' => 0,
        ]);
        $link_id = $this->create('link', [
            'title' => $link_title,
            'is_hidden' => 0,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', "/collections/{$collection_id}/feed.atom.xml");

        $this->assertResponseCode($response, 404);
    }

    public function testAliasRedirectsToShow()
    {
        $collection_id = $this->create('collection');

        $response = $this->appRun('get', "/collections/{$collection_id}/feed");

        $this->assertResponseCode($response, 301, "/collections/{$collection_id}/feed.atom.xml");
    }

    public function testAliasRedirectsWithQuery()
    {
        $collection_id = $this->create('collection');
        $_SERVER['QUERY_STRING'] = 'direct=true';

        $response = $this->appRun('get', "/collections/{$collection_id}/feed");

        $_SERVER['QUERY_STRING'] = '';
        $this->assertResponseCode($response, 301, "/collections/{$collection_id}/feed.atom.xml?direct=true");
    }
}
