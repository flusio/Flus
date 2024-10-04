<?php

namespace App\search_engine;

use App\models;
use tests\factories\UserFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\MessageFactory;

class LinksSearcherTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\FakerHelper;

    public function testGetLinksSearchesByTitle(): void
    {
        /** @var string */
        $title = $this->fake('sentence', 10, false);
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $title,
        ]);
        $query = Query::fromString($title);

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link->id, $links[0]->id);
    }

    public function testGetLinksSearchesByUrl(): void
    {
        /** @var string */
        $url = $this->fake('url');
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $query = Query::fromString("url: {$url}");

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link->id, $links[0]->id);
    }

    public function testGetLinksSearchesByTag(): void
    {
        /** @var string[] */
        $tags = $this->fake('words', 5);
        /** @var string */
        $tag = $this->fake('randomElement', $tags);
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'tags' => $tags,
        ]);
        $query = Query::fromString("#{$tag}");

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link->id, $links[0]->id);
    }

    public function testGetLinksCanExcludeByTag(): void
    {
        /** @var string[] */
        $tags = $this->fake('words', 5);
        /** @var string */
        $tag = $this->fake('randomElement', $tags);
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'tags' => $tags,
        ]);
        $query = Query::fromString("-#{$tag}");

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(0, count($links));
    }

    public function testGetLinksCanExcludeByMultipleTags(): void
    {
        $tag_to_keep = 'foo';
        $tag_to_exclude1 = 'bar';
        $tag_to_exclude2 = 'baz';
        $user = UserFactory::create();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
            'tags' => ['foo'],
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
            'tags' => ['foo', 'bar'],
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
            'tags' => ['foo', 'bar', 'baz'],
        ]);
        $query = Query::fromString("#{$tag_to_keep} -#{$tag_to_exclude1} -#{$tag_to_exclude2}");

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(1, count($links));
        $this->assertSame($link1->id, $links[0]->id);
    }

    public function testGetLinksSortsByCreatedAt(): void
    {
        /** @var string */
        $title = $this->fake('sentence', 10, false);
        $user = UserFactory::create();
        $created_at_1 = \Minz\Time::ago(2, 'day');
        $created_at_2 = \Minz\Time::ago(1, 'day');
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $title,
            'created_at' => $created_at_1,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $title,
            'created_at' => $created_at_2,
        ]);
        $query = Query::fromString($title);

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(2, count($links));
        $this->assertSame($link_2->id, $links[0]->id);
        $this->assertSame($link_1->id, $links[1]->id);
    }

    public function testGetLinksCanLimitResults(): void
    {
        /** @var string */
        $title = $this->fake('sentence', 10, false);
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(3, 'days'),
            'title' => $title,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(2, 'days'),
            'title' => $title,
        ]);
        $link_3 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(1, 'days'),
            'title' => $title,
        ]);
        $query = Query::fromString($title);

        $links = LinksSearcher::getLinks($user, $query, pagination: [
            'limit' => 2,
        ]);

        $this->assertSame(2, count($links));
        $this->assertSame($link_3->id, $links[0]->id);
        $this->assertSame($link_2->id, $links[1]->id);
    }

    public function testGetLinksCanOffsetResults(): void
    {
        /** @var string */
        $title = $this->fake('sentence', 10, false);
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(3, 'days'),
            'title' => $title,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(2, 'days'),
            'title' => $title,
        ]);
        $link_3 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(1, 'days'),
            'title' => $title,
        ]);
        $query = Query::fromString($title);

        $links = LinksSearcher::getLinks($user, $query, pagination: [
            'limit' => 2,
            'offset' => 1,
        ]);

        $this->assertSame(2, count($links));
        $this->assertSame($link_2->id, $links[0]->id);
        $this->assertSame($link_1->id, $links[1]->id);
    }

    public function testGetLinksExcludesLinksOnlyInNeverCollection(): void
    {
        /** @var string */
        $title = $this->fake('sentence', 10, false);
        $user = UserFactory::create();
        $never_list = $user->neverList();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $title,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $never_list->id,
            'link_id' => $link->id,
        ]);
        $query = Query::fromString($title);

        $links = LinksSearcher::getLinks($user, $query);

        $this->assertSame(0, count($links));
    }

    public function testCountLinks(): void
    {
        /** @var string */
        $title = $this->fake('sentence', 10, false);
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $title,
        ]);
        $query = Query::fromString($title);

        $count = LinksSearcher::countLinks($user, $query);

        $this->assertSame(1, $count);
    }

    public function testCountLinksExcludeLinksOnlyInNeverCollection(): void
    {
        /** @var string */
        $title = $this->fake('sentence', 10, false);
        $user = UserFactory::create();
        $never_list = $user->neverList();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $title,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $never_list->id,
            'link_id' => $link->id,
        ]);
        $query = Query::fromString($title);

        $count = LinksSearcher::countLinks($user, $query);

        $this->assertSame(0, $count);
    }
}
