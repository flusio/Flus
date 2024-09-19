<?php

namespace App\models\dao\links;

use App\models;
use tests\factories\UserFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\MessageFactory;

class SearchQueriesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\FakerHelper;

    public function testSearchComputedByUserIdSearchesByTitle(): void
    {
        /** @var string */
        $title = $this->fake('sentence', 10, false);
        $query = $title;
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $title,
        ]);

        $links = models\Link::listComputedByQueryAndUserId($query, $user->id, []);

        $this->assertSame(1, count($links));
        $this->assertSame($link->id, $links[0]->id);
    }

    public function testSearchComputedByUserIdSearchesByUrl(): void
    {
        /** @var string */
        $url = $this->fake('url');
        $query = $url;
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);

        $links = models\Link::listComputedByQueryAndUserId($query, $user->id, []);

        $this->assertSame(1, count($links));
        $this->assertSame($link->id, $links[0]->id);
    }

    public function testSearchComputedByUserIdSortsByCreatedAt(): void
    {
        /** @var string */
        $title = $this->fake('sentence', 10, false);
        $query = $title;
        $user = UserFactory::create();
        $created_at_1 = \Minz\Time::ago(1, 'day');
        $created_at_2 = \Minz\Time::ago(2, 'day');
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

        $links = models\Link::listComputedByQueryAndUserId($query, $user->id, []);

        $this->assertSame(2, count($links));
        $this->assertSame($link_1->id, $links[0]->id);
        $this->assertSame($link_2->id, $links[1]->id);
    }

    public function testSearchComputedByUserIdCanReturnPublishedAt(): void
    {
        /** @var string */
        $title = $this->fake('sentence', 10, false);
        $query = $title;
        $user = UserFactory::create();
        /** @var \DateTimeImmutable */
        $published_at = $this->fake('dateTime');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $title,
            'created_at' => $published_at,
        ]);

        $links = models\Link::listComputedByQueryAndUserId($query, $user->id, ['published_at']);

        $this->assertSame(1, count($links));
        $this->assertEquals($published_at, $links[0]->published_at);
    }

    public function testSearchComputedByUserIdCanReturnNumberComments(): void
    {
        /** @var string */
        $title = $this->fake('sentence', 10, false);
        $query = $title;
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $title,
        ]);
        MessageFactory::create([
            'link_id' => $link->id,
        ]);

        $links = models\Link::listComputedByQueryAndUserId($query, $user->id, ['number_comments']);

        $this->assertSame(1, count($links));
        $this->assertSame(1, $links[0]->number_comments);
    }

    public function testSearchComputedByUserIdCanLimitResults(): void
    {
        /** @var string */
        $title = $this->fake('sentence', 10, false);
        $query = $title;
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(3, 'days'),
            'title' => $title,
            'url' => 'https://example.com/foo1', // urls must be of the same lenght
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(2, 'days'),
            'title' => $title,
            'url' => 'https://example.com/foo2', // urls must be of the same lenght
        ]);
        $link_3 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(1, 'days'),
            'title' => $title,
            'url' => 'https://example.com/foo3', // urls must be of the same lenght
        ]);

        $links = models\Link::listComputedByQueryAndUserId($query, $user->id, [], [
            'limit' => 2,
        ]);

        $this->assertSame(2, count($links));
        $this->assertSame($link_3->id, $links[0]->id);
        $this->assertSame($link_2->id, $links[1]->id);
    }

    public function testSearchComputedByUserIdCanOffsetResults(): void
    {
        /** @var string */
        $title = $this->fake('sentence', 10, false);
        $query = $title;
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(3, 'days'),
            'title' => $title,
            'url' => 'https://example.com/foo1', // urls must be of the same lenght
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(2, 'days'),
            'title' => $title,
            'url' => 'https://example.com/foo2', // urls must be of the same lenght
        ]);
        $link_3 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(1, 'days'),
            'title' => $title,
            'url' => 'https://example.com/foo3', // urls must be of the same lenght
        ]);

        $links = models\Link::listComputedByQueryAndUserId($query, $user->id, [], [
            'offset' => 1,
        ]);

        $this->assertSame(2, count($links));
        $this->assertSame($link_2->id, $links[0]->id);
        $this->assertSame($link_1->id, $links[1]->id);
    }

    public function testSearchComputedByUserIdCanExcludeLinksOnlyInNeverCollection(): void
    {
        /** @var string */
        $title = $this->fake('sentence', 10, false);
        $query = $title;
        $user = UserFactory::create();
        $user = $user->reload();
        $never_list = $user->neverList();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $title,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $never_list->id,
            'link_id' => $link->id,
        ]);

        $links = models\Link::listComputedByQueryAndUserId($query, $user->id, [], [
            'exclude_never_only' => true,
        ]);

        $this->assertSame(0, count($links));
    }

    public function testCountByQueryAndUserId(): void
    {
        /** @var string */
        $title = $this->fake('sentence', 10, false);
        $query = $title;
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $title,
        ]);

        $count = models\Link::countByQueryAndUserId($query, $user->id);

        $this->assertSame(1, $count);
    }

    public function testCountByQueryAndUserIdCanExcludeLinksOnlyInNeverCollection(): void
    {
        /** @var string */
        $title = $this->fake('sentence', 10, false);
        $query = $title;
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

        $count = models\Link::countByQueryAndUserId($query, $user->id, [
            'exclude_never_only' => true,
        ]);

        $this->assertSame(0, $count);
    }
}
