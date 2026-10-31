<?php

namespace App\models;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\FeedFactory;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;

    public function testListByLink(): void
    {
        $user = UserFactory::create();
        $collection_1 = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $collection_2 = CollectionFactory::create([
            'user_id' => $user->id,
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
        $feed = FeedFactory::create();
        $link = LinkFactory::create(['user_id' => $user->id]);
        $feed->addLinks([$link]);

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
        ]);

        $collections = models\Collection::listComputedByUser($user, []);

        $this->assertSame(1, count($collections));
        $this->assertSame($collection->id, $collections[0]->id);
    }

    public function testListComputedByUserIdCanExcludePrivateCollections(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'is_public' => false,
        ]);

        $collections = models\Collection::listComputedByUser($user, [], [
            'private' => false,
        ]);

        $this->assertSame(0, count($collections));
    }

    public function testListComputedByUserIdCanReturnNumberLinks(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection->addLinks([$link]);

        $collections = models\Collection::listComputedByUser($user, ['number_links']);

        $this->assertSame(1, count($collections));
        $this->assertSame($collection->id, $collections[0]->id);
        $this->assertSame(1, $collections[0]->number_links);
    }

    public function testListComputedByUserIdCanExcludeHiddenLinks(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => true,
        ]);
        $collection->addLinks([$link]);

        $collections = models\Collection::listComputedByUser($user, ['number_links'], [
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
            'is_public' => true,
        ]);

        $collections = models\Collection::listComputedByUser($user, ['number_links'], [
            'private' => false,
        ]);

        $this->assertSame(0, count($collections));
    }

    public function testListComputedByUserIdCanExcludePublicAndEmptyCollectionsAndConsiderCountHidden(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => true,
        ]);
        $collection->addLinks([$link]);

        $collections = models\Collection::listComputedByUser($user, ['number_links'], [
            'private' => false,
            'count_hidden' => false,
        ]);

        $this->assertSame(0, count($collections));
    }
}
