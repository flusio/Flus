<?php

namespace App\controllers\streams;

use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\StreamFactory;
use tests\factories\UserFactory;

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
        $feed = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'user_id' => $feed->user_id,
            'title' => $link_title,
            'url' => $link_url,
            'is_hidden' => false,
        ]);
        $feed->addLinks([$link], at: \Minz\Time::now());
        $stream = StreamFactory::create([
            'is_public' => true,
        ]);
        $stream->addSource($feed);

        $response = $this->appRun('GET', "/streams/{$stream->id}/feed.atom.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'streams/feeds/show.atom.xml.twig');
        $this->assertResponseHeaders($response, [
            'Content-Type' => 'application/xml',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $this->assertInstanceOf(\Minz\Response::class, $response);
        $atom = \SpiderBits\feeds\Feed::fromText($response->render());
        $link_alternate = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $this->assertSame(1, count($atom->entries));
        $this->assertSame($link_title, $atom->entries[0]->title);
        $this->assertSame($link_alternate, $atom->entries[0]->links['alternate']);
        $this->assertSame($link_url, $atom->entries[0]->links['via']);
    }

    public function testShowRendersAlternateLinksAsOriginalUrlWithDirectTrue(): void
    {
        /** @var string */
        $link_url = $this->fake('url');
        $feed = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'user_id' => $feed->user_id,
            'url' => $link_url,
            'is_hidden' => false,
        ]);
        $feed->addLinks([$link], at: \Minz\Time::now());
        $stream = StreamFactory::create([
            'is_public' => true,
        ]);
        $stream->addSource($feed);

        $response = $this->appRun('GET', "/streams/{$stream->id}/feed.atom.xml", [
            'direct' => true,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertInstanceOf(\Minz\Response::class, $response);
        $atom = \SpiderBits\feeds\Feed::fromText($response->render());
        $link_replies = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $this->assertSame(1, count($atom->entries));
        $this->assertSame($link_url, $atom->entries[0]->links['alternate']);
        $this->assertSame($link_replies, $atom->entries[0]->links['replies']);
    }

    public function testShowDeduplicatesLinksListedBySeveralSources(): void
    {
        $user = UserFactory::create();
        $collection_1 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection_2 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'user_id' => $collection_1->user_id,
            'is_hidden' => false,
        ]);
        $collection_1->addLinks([$link], at: \Minz\Time::now());
        $collection_2->addLinks([$link], at: \Minz\Time::now());
        $stream = StreamFactory::create([
            'is_public' => true,
        ]);
        $stream->addSource($collection_1);
        $stream->addSource($collection_2);

        $response = $this->appRun('GET', "/streams/{$stream->id}/feed.atom.xml");

        $this->assertResponseCode($response, 200);
        $this->assertInstanceOf(\Minz\Response::class, $response);
        $atom = \SpiderBits\feeds\Feed::fromText($response->render());
        $this->assertSame(1, count($atom->entries));
    }

    public function testShowRendersLinksOlderThanThirtyDays(): void
    {
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $feed = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'user_id' => $feed->user_id,
            'title' => $link_title,
            'is_hidden' => false,
        ]);
        $feed->addLinks([$link], at: \Minz\Time::ago(2, 'months'));
        $stream = StreamFactory::create([
            'is_public' => true,
        ]);
        $stream->addSource($feed);

        $response = $this->appRun('GET', "/streams/{$stream->id}/feed.atom.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $link_title);
    }

    public function testShowDoesNotRenderHiddenLinks(): void
    {
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $feed = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'user_id' => $feed->user_id,
            'title' => $link_title,
            'is_hidden' => true,
        ]);
        $feed->addLinks([$link], at: \Minz\Time::now());
        $stream = StreamFactory::create([
            'is_public' => true,
        ]);
        $stream->addSource($feed);

        $response = $this->appRun('GET', "/streams/{$stream->id}/feed.atom.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $link_title);
    }

    public function testShowDoesNotRenderLinksOfPrivateSources(): void
    {
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => false,
        ]);
        $link = LinkFactory::create([
            'user_id' => $collection->user_id,
            'title' => $link_title,
            'is_hidden' => false,
        ]);
        $collection->addLinks([$link], at: \Minz\Time::now());
        $stream = StreamFactory::create([
            'is_public' => true,
        ]);
        $stream->addSource($collection);

        $response = $this->appRun('GET', "/streams/{$stream->id}/feed.atom.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $link_title);
    }

    public function testShowFailsIfStreamIsInaccessible(): void
    {
        $stream = StreamFactory::create([
            'is_public' => false,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/feed.atom.xml");

        $this->assertResponseCode($response, 403);
    }

    public function testAliasRedirectsToShow(): void
    {
        $stream = StreamFactory::create([
            'is_public' => true,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/feed");

        $this->assertResponseCode($response, 301, "/streams/{$stream->id}/feed.atom.xml");
    }

    public function testAliasRedirectsWithQuery(): void
    {
        $stream = StreamFactory::create([
            'is_public' => true,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/feed", server: [
            'QUERY_STRING' => 'direct=true',
        ]);

        $this->assertResponseCode($response, 301, "/streams/{$stream->id}/feed.atom.xml?direct=true");
    }
}
