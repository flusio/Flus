<?php

namespace App\models\collections;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\StreamFactory;
use tests\factories\UserFactory;

class InStreamsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;

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
}
