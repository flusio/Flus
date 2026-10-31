<?php

namespace App\models\collections;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class HasLinksTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;

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
}
