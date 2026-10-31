<?php

namespace App\models\collections;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class OwnedByUserTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;

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
}
