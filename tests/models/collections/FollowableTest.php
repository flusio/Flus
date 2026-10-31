<?php

namespace App\models\collections;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\UserFactory;

class FollowableTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;

    public function testListSourcesByUser(): void
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'feed',
            'is_public' => true,
        ]);
        $user->follow($collection);

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
        $user->follow($collection);

        $sources = models\Collection::listSourcesByUser($user);

        $this->assertSame(0, count($sources));
    }
}
