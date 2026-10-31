<?php

namespace App\models\link_lists;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\FeedFactory;
use tests\factories\UserFactory;

class FollowableTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;

    public function testListFollowedByUser(): void
    {
        $user = UserFactory::create();
        $feed = FeedFactory::create([
            'is_public' => true,
        ]);
        $user->follow($feed);

        $sources = models\Source::listFollowedByUser($user);

        $this->assertSame(1, count($sources));
        $this->assertSame($feed->id, $sources[0]->id);
    }

    public function testListFollowedByUserExcludesPrivateCollections(): void
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $user->follow($collection);

        $sources = models\Source::listFollowedByUser($user);

        $this->assertSame(0, count($sources));
    }
}
