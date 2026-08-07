<?php

namespace App\models\dao;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\FollowedCollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\StreamFactory;
use tests\factories\UserFactory;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\FakerHelper;

    public function testListByLink(): void
    {
        $user = UserFactory::create();
        $collection_1 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $collection_2 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $link = LinkFactory::create(['user_id' => $user->id]);
        $link->addCollections([$collection_1, $collection_2]);

        $collections = models\Collection::listByLink($link);

        $collection_ids = array_column($collections, 'id');
        sort($collection_ids);
        $expected_ids = [$collection_1->id, $collection_2->id];
        sort($expected_ids);
        $this->assertSame($expected_ids, $collection_ids);
    }

    public function testListByLinkDoesNotReturnFeeds(): void
    {
        $user = UserFactory::create();
        $feed = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'feed',
        ]);
        $link = LinkFactory::create(['user_id' => $user->id]);
        $link->addCollection($feed);

        $collections = models\Collection::listByLink($link);

        $this->assertSame([], $collections);
    }

    public function testListByLinkReturnsNothingWhenTheLinkHasNoCollection(): void
    {
        $user = UserFactory::create();
        $link = LinkFactory::create(['user_id' => $user->id]);

        $collections = models\Collection::listByLink($link);

        $this->assertSame([], $collections);
    }

    public function testListComputedByUserId(): void
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

    public function testListComputedByUserIdDoesNotReturnFeeds(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'feed',
        ]);

        $collections = models\Collection::listComputedByUserId($user->id, []);

        $this->assertSame(0, count($collections));
    }

    public function testListComputedByUserIdCanExcludePrivateCollections(): void
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

    public function testListComputedByUserIdCanReturnNumberLinks(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection->addLinks([$link]);

        $collections = models\Collection::listComputedByUserId($user->id, ['number_links']);

        $this->assertSame(1, count($collections));
        $this->assertSame($collection->id, $collections[0]->id);
        $this->assertSame(1, $collections[0]->number_links);
    }

    public function testListComputedByUserIdCanExcludeHiddenLinks(): void
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
        $collection->addLinks([$link]);

        $collections = models\Collection::listComputedByUserId($user->id, ['number_links'], [
            'count_hidden' => false,
        ]);

        $this->assertSame(1, count($collections));
        $this->assertSame($collection->id, $collections[0]->id);
        $this->assertSame(0, $collections[0]->number_links);
    }

    public function testListComputedByUserIdCanExcludePublicAndEmptyCollections(): void
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

    public function testListComputedByUserIdCanExcludePublicAndEmptyCollectionsAndConsiderCountHidden(): void
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
        $collection->addLinks([$link]);

        $collections = models\Collection::listComputedByUserId($user->id, ['number_links'], [
            'private' => false,
            'count_hidden' => false,
        ]);

        $this->assertSame(0, count($collections));
    }

    public function testListComputedFollowedByUserId(): void
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

    public function testListComputedFollowedByUserIdExcludesPrivateCollections(): void
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

    public function testListComputedFollowedByUserIdCanReturnNumberLinks(): void
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
        $collection->addLinks([$link]);

        $collections = models\Collection::listComputedFollowedByUserId($user->id, ['number_links']);

        $this->assertSame(1, count($collections));
        $this->assertSame($collection->id, $collections[0]->id);
        $this->assertSame(1, $collections[0]->number_links);
    }

    public function testListComputedFollowedByUserIdCanFilterCollections(): void
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

    public function testListComputedFollowedByUserIdCanFilterFeeds(): void
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

    public function testListComputedFollowedByUserIdExcludesHiddenLinks(): void
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
        $collection->addLinks([$link]);

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
    public function testListComputedFollowedByUserIdDoesNotExcludeHiddenLinksWhenFilteringFeeds(): void
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
        $collection->addLinks([$link]);

        $collections = models\Collection::listComputedFollowedByUserId($user->id, ['number_links'], [
            'type' => 'feed',
        ]);

        $this->assertSame(1, count($collections));
        $this->assertSame($collection->id, $collections[0]->id);
        $this->assertSame(1, $collections[0]->number_links);
    }

    public function testListSourcesByUser(): void
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

        $sources = models\Collection::listSourcesByUser($user);

        $this->assertSame(1, count($sources));
        $this->assertSame($collection->id, $sources[0]->id);
    }

    public function testListSourcesByUserExcludesPrivateCollections(): void
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

        $sources = models\Collection::listSourcesByUser($user);

        $this->assertSame(0, count($sources));
    }

    public function testListByStreamOnlyListsThePublicSourcesIfNoContextUser(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $public_collection = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => true,
        ]);
        $private_collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'is_public' => false,
        ]);
        $stream->addSource($public_collection);
        $stream->addSource($private_collection);

        $sources = models\Collection::listByStream($stream);

        $this->assertSame(1, count($sources));
        $this->assertSame($public_collection->id, $sources[0]->id);
    }

    public function testListByStreamExcludesTheCollectionsTheContextUserCannotView(): void
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $public_collection = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => true,
        ]);
        $private_collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => false,
        ]);
        $stream->addSource($public_collection);
        $stream->addSource($private_collection);

        $sources = models\Collection::listByStream($stream, [
            'context_user' => $user,
        ]);

        $this->assertSame(1, count($sources));
        $this->assertSame($public_collection->id, $sources[0]->id);
    }

    public function testListByStreamKeepsThePrivateCollectionsOfTheContextUser(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $private_collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'is_public' => false,
        ]);
        $stream->addSource($private_collection);

        $sources = models\Collection::listByStream($stream, [
            'context_user' => $user,
        ]);

        $this->assertSame(1, count($sources));
        $this->assertSame($private_collection->id, $sources[0]->id);
    }

    public function testListFeedsToFetchWithSerie(): void
    {
        $feed1 = CollectionFactory::create([
            'id' => '42',
            'type' => 'feed',
            'feed_fetched_next_at' => null,
        ]);
        $feed2 = CollectionFactory::create([
            'id' => '43',
            'type' => 'feed',
            'feed_fetched_next_at' => null,
        ]);
        $feed3 = CollectionFactory::create([
            'id' => '44',
            'type' => 'feed',
            'feed_fetched_next_at' => null,
        ]);

        $serie = ['total' => 3, 'number' => 0];
        $feeds = models\Collection::listFeedsToFetch(serie: $serie);
        $this->assertSame(1, count($feeds));
        $this->assertSame($feed1->id, $feeds[0]->id);

        $serie = ['total' => 3, 'number' => 1];
        $feeds = models\Collection::listFeedsToFetch(serie: $serie);
        $this->assertSame(1, count($feeds));
        $this->assertSame($feed2->id, $feeds[0]->id);

        $serie = ['total' => 3, 'number' => 2];
        $feeds = models\Collection::listFeedsToFetch(serie: $serie);
        $this->assertSame(1, count($feeds));
        $this->assertSame($feed3->id, $feeds[0]->id);
    }
}
