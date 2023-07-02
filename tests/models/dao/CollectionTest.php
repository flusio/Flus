<?php

namespace flusio\models\dao;

use flusio\models;
use tests\factories\CollectionFactory;
use tests\factories\FollowedCollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\UserFactory;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;

    public function testListComputedByUserId()
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $collections = models\Collection::listComputedByUserId($user->id, []);

        $this->assertSame(1, count($collections));
        $this->assertSame($collection->id, $collections[0]->id);
    }

    public function testListComputedByUserIdDoesNotReturnFeeds()
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'feed',
        ]);

        $collections = models\Collection::listComputedByUserId($user->id, []);

        $this->assertSame(0, count($collections));
    }

    public function testListComputedByUserIdCanExcludePrivateCollections()
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'is_public' => false,
        ]);

        $collections = models\Collection::listComputedByUserId($user->id, [], [
            'private' => false,
        ]);

        $this->assertSame(0, count($collections));
    }

    public function testListComputedByUserIdCanReturnNumberLinks()
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);

        $collections = models\Collection::listComputedByUserId($user->id, ['number_links']);

        $this->assertSame(1, count($collections));
        $this->assertSame($collection->id, $collections[0]->id);
        $this->assertSame(1, $collections[0]->number_links);
    }

    public function testListComputedByUserIdCanExcludeHiddenLinks()
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => true,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);

        $collections = models\Collection::listComputedByUserId($user->id, ['number_links'], [
            'count_hidden' => false,
        ]);

        $this->assertSame(1, count($collections));
        $this->assertSame($collection->id, $collections[0]->id);
        $this->assertSame(0, $collections[0]->number_links);
    }

    public function testListComputedByUserIdCanExcludePublicAndEmptyCollections()
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);

        $collections = models\Collection::listComputedByUserId($user->id, ['number_links'], [
            'private' => false,
        ]);

        $this->assertSame(0, count($collections));
    }

    public function testListComputedByUserIdCanExcludePublicAndEmptyCollectionsAndConsiderCountHidden()
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => true,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);

        $collections = models\Collection::listComputedByUserId($user->id, ['number_links'], [
            'private' => false,
            'count_hidden' => false,
        ]);

        $this->assertSame(0, count($collections));
    }

    public function testListComputedFollowedByUserId()
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);

        $collections = models\Collection::listComputedFollowedByUserId($user->id, []);

        $this->assertSame(1, count($collections));
        $this->assertSame($collection->id, $collections[0]->id);
    }

    public function testListComputedFollowedByUserIdExcludesPrivateCollections()
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => false,
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);

        $collections = models\Collection::listComputedFollowedByUserId($user->id, []);

        $this->assertSame(0, count($collections));
    }

    public function testListComputedFollowedByUserIdCanReturnNumberLinks()
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);

        $collections = models\Collection::listComputedFollowedByUserId($user->id, ['number_links']);

        $this->assertSame(1, count($collections));
        $this->assertSame($collection->id, $collections[0]->id);
        $this->assertSame(1, $collections[0]->number_links);
    }

    public function testListComputedFollowedByUserIdCanReturnTimeFilter()
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'time_filter' => 'strict',
        ]);

        $collections = models\Collection::listComputedFollowedByUserId($user->id, ['time_filter']);

        $this->assertSame(1, count($collections));
        $this->assertSame('strict', $collections[0]->time_filter);
    }

    public function testListComputedFollowedByUserIdCanFilterCollections()
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $collection_1 = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection_2 = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'feed',
            'is_public' => true,
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection_1->id,
            'user_id' => $user->id,
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection_2->id,
            'user_id' => $user->id,
        ]);

        $collections = models\Collection::listComputedFollowedByUserId($user->id, [], [
            'type' => 'collection',
        ]);

        $this->assertSame(1, count($collections));
        $this->assertSame($collection_1->id, $collections[0]->id);
    }

    public function testListComputedFollowedByUserIdCanFilterFeeds()
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $collection_1 = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $collection_2 = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'feed',
            'is_public' => true,
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection_1->id,
            'user_id' => $user->id,
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection_2->id,
            'user_id' => $user->id,
        ]);

        $collections = models\Collection::listComputedFollowedByUserId($user->id, [], [
            'type' => 'feed',
        ]);

        $this->assertSame(1, count($collections));
        $this->assertSame($collection_2->id, $collections[0]->id);
    }

    public function testListComputedFollowedByUserIdExcludesHiddenLinks()
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => true,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);

        $collections = models\Collection::listComputedFollowedByUserId($user->id, ['number_links']);

        $this->assertSame(1, count($collections));
        $this->assertSame($collection->id, $collections[0]->id);
        $this->assertSame(0, $collections[0]->number_links);
    }

    /**
     * This is a very special case. We consider feeds only contain public links
     * and, so, we can optimize the count of links. In other words, this test
     * exists only to document a case which should never happen in real life.
     */
    public function testListComputedFollowedByUserIdDoesNotExcludeHiddenLinksWhenFilteringFeeds()
    {

        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'feed',
            'is_public' => true,
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => true,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);

        $collections = models\Collection::listComputedFollowedByUserId($user->id, ['number_links'], [
            'type' => 'feed',
        ]);

        $this->assertSame(1, count($collections));
        $this->assertSame($collection->id, $collections[0]->id);
        $this->assertSame(1, $collections[0]->number_links);
    }
}
