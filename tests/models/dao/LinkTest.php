<?php

namespace App\models\dao;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\MessageFactory;
use tests\factories\UserFactory;

class LinkTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\FakerHelper;

    public function testBulkInsert(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $link_url_1 = $this->fake('url');
        /** @var string */
        $link_url_2 = $this->fake('url');
        /** @var string */
        $link_url_3 = $this->fake('url');
        $link_1 = new models\Link($link_url_1, $user->id, true);
        $link_2 = new models\Link($link_url_2, $user->id, true);
        $link_3 = new models\Link($link_url_3, $user->id, false);
        /** @var \DateTimeImmutable */
        $created_at = $this->fake('dateTime');
        $link_1->created_at = $created_at;
        /** @var \DateTimeImmutable */
        $created_at = $this->fake('dateTime');
        $link_2->created_at = $created_at;
        /** @var \DateTimeImmutable */
        $created_at = $this->fake('dateTime');
        $link_3->created_at = $created_at;

        $this->assertSame(0, models\Link::count());

        $result = models\Link::bulkInsert([$link_1, $link_2, $link_3]);

        $this->assertTrue($result);
        $this->assertSame(3, models\Link::count());
        $this->assertTrue(models\Link::exists($link_1->id));
        $this->assertTrue(models\Link::exists($link_2->id));
        $this->assertTrue(models\Link::exists($link_3->id));
    }

    public function testListComputedByUserId(): void
    {
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $links = models\Link::listComputedByUserId($user->id, []);

        $this->assertSame(1, count($links));
        $this->assertSame($link->id, $links[0]->id);
    }

    public function testListComputedByUserIdCanReturnPublishedAt(): void
    {
        $user = UserFactory::create();
        /** @var \DateTimeImmutable */
        $published_at = $this->fake('dateTime');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => $published_at,
        ]);

        $links = models\Link::listComputedByUserId($user->id, ['published_at']);

        $this->assertSame(1, count($links));
        $this->assertEquals($published_at, $links[0]->published_at);
    }

    public function testListComputedByUserIdConsidersLinkToCollectionPublishedAtWhenUnsharedIsFalse(): void
    {
        $user = UserFactory::create();
        /** @var \DateTimeImmutable */
        $published_at = $this->fake('dateTime');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
            'created_at' => $published_at,
        ]);

        $links = models\Link::listComputedByUserId($user->id, ['published_at'], [
            'unshared' => false,
        ]);

        $this->assertSame(1, count($links));
        $this->assertEquals($published_at, $links[0]->published_at);
    }

    public function testListComputedByUserIdCanReturnNumberComments(): void
    {
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        MessageFactory::create([
            'link_id' => $link->id,
        ]);

        $links = models\Link::listComputedByUserId($user->id, ['number_comments']);

        $this->assertSame(1, $links[0]->number_comments);
    }

    public function testListComputedByUserIdCanListSharedOnly(): void
    {
        $user = UserFactory::create();
        $unshared_link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
        ]);
        $unshared_link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => true,
        ]);
        $unshared_link_3 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
        ]);
        $shared_link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
        ]);
        $private_collection = CollectionFactory::create([
            'user_id' => $user->id,
            'is_public' => false,
        ]);
        $public_collection = CollectionFactory::create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $unshared_link_2->id,
            'collection_id' => $public_collection->id,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $unshared_link_3->id,
            'collection_id' => $private_collection->id,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $shared_link->id,
            'collection_id' => $public_collection->id,
        ]);

        $links = models\Link::listComputedByUserId($user->id, [], [
            'unshared' => false,
        ]);

        $this->assertSame(1, count($links));
        $this->assertSame($shared_link->id, $links[0]->id);
    }

    public function testListComputedByUserIdCanLimitResults(): void
    {
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(3, 'days'),
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(2, 'days'),
        ]);
        $link_3 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(1, 'days'),
        ]);

        $links = models\Link::listComputedByUserId($user->id, [], [
            'limit' => 2,
        ]);

        $this->assertSame(2, count($links));
        $this->assertSame($link_3->id, $links[0]->id);
        $this->assertSame($link_2->id, $links[1]->id);
    }

    public function testListComputedByUserIdCanOffsetResults(): void
    {
        $user = UserFactory::create();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(3, 'days'),
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(2, 'days'),
        ]);
        $link_3 = LinkFactory::create([
            'user_id' => $user->id,
            'created_at' => \Minz\Time::ago(1, 'days'),
        ]);

        $links = models\Link::listComputedByUserId($user->id, [], [
            'offset' => 1,
        ]);

        $this->assertSame(2, count($links));
        $this->assertSame($link_2->id, $links[0]->id);
        $this->assertSame($link_1->id, $links[1]->id);
    }

    public function testListComputedByUserIdDoesNotDuplicateLinks(): void
    {
        $user = UserFactory::create();
        $today = \Minz\Time::relative('today');
        $yesterday = \Minz\Time::relative('today -1 day');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
        ]);
        $public_collection_1 = CollectionFactory::create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);
        $public_collection_2 = CollectionFactory::create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $public_collection_1->id,
            'created_at' => $today,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $public_collection_2->id,
            'created_at' => $yesterday,
        ]);

        $links = models\Link::listComputedByUserId($user->id, ['published_at'], [
            'unshared' => false,
        ]);

        $this->assertSame(1, count($links));
        $this->assertEquals($today, $links[0]->published_at);
    }

    public function testListComputedByUserIdLimitsResultsAfterDeduplication(): void
    {
        $user = UserFactory::create();
        $today = \Minz\Time::relative('today');
        $yesterday = \Minz\Time::relative('today -1 day');
        $two_days_ago = \Minz\Time::relative('today -2 days');
        $three_days_ago = \Minz\Time::relative('today -3 days');
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
        ]);
        $link_3 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
        ]);
        $public_collection_1 = CollectionFactory::create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);
        $public_collection_2 = CollectionFactory::create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link_1->id,
            'collection_id' => $public_collection_2->id,
            'created_at' => $three_days_ago,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link_2->id,
            'collection_id' => $public_collection_1->id,
            'created_at' => $two_days_ago,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link_3->id,
            'collection_id' => $public_collection_1->id,
            'created_at' => $yesterday,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link_1->id,
            'collection_id' => $public_collection_1->id,
            'created_at' => $today,
        ]);

        $links = models\Link::listComputedByUserId($user->id, ['published_at'], [
            'unshared' => false,
            'limit' => 2,
        ]);

        $this->assertSame(2, count($links));
        $this->assertEquals($link_1->id, $links[0]->id);
        $this->assertEquals($link_3->id, $links[1]->id);
    }
}
