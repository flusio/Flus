<?php

namespace App\models\collections;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\StreamFactory;
use tests\factories\UserFactory;

/**
 * The preloaded values are indistinguishable from the ones the per-collection
 * methods would load by themselves: that is the point of the Preloader. So, to
 * prove that a value really comes from the memoizer cache, these tests delete
 * the data from the database after the preloading. A method that would still
 * query the database then returns nothing, and the test fails.
 */
class PreloaderTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;

    public function testPublishersPreloadsTheOwnerAndTheWriters(): void
    {
        $owner = UserFactory::create();
        $writer = UserFactory::create();
        $reader = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
        ]);
        $collection->shareWith($writer, 'write');
        $collection->shareWith($reader, 'read');

        Preloader::for([$collection])->publishers();

        models\CollectionShare::deleteBy(['collection_id' => $collection->id]);

        $publishers = $collection->publishers();

        // The reader is not a publisher, and the owner comes first.
        $this->assertSame(2, count($publishers));
        $this->assertSame($owner->id, $publishers[0]->id);
        $this->assertSame($writer->id, $publishers[1]->id);
    }

    public function testPublishersPreloadsNothingForAFeed(): void
    {
        $feed = CollectionFactory::create([
            'user_id' => null,
            'type' => 'feed',
        ]);

        Preloader::for([$feed])->publishers();

        $this->assertSame([], $feed->publishers());
        $this->assertNull($feed->owner());
    }

    public function testCountStreamsForPreloadsTheNumberOfStreams(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'feed',
        ]);
        $user->follow($collection->id);
        $stream_1 = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $stream_2 = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $stream_1->addSource($collection);
        $stream_2->addSource($collection);

        Preloader::for([$collection])->countStreamsFor($user);

        $stream_1->removeSource($collection);
        $stream_2->removeSource($collection);

        $this->assertSame(2, $collection->countStreamsByUser($user));
    }

    public function testCountStreamsForPreloadsZeroWhenTheCollectionIsInNoStream(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'feed',
        ]);
        $user->follow($collection->id);

        Preloader::for([$collection])->countStreamsFor($user);

        $this->assertSame(0, $collection->countStreamsByUser($user));
    }

    public function testCountStreamsForDoesNotCountTheStreamsOfAnotherUser(): void
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'feed',
        ]);
        $user->follow($collection->id);
        $other_user->follow($collection->id);
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);
        $stream->addSource($collection);

        Preloader::for([$collection])->countStreamsFor($user);

        $this->assertSame(0, $collection->countStreamsByUser($user));
    }

    public function testCountStreamsForDoesNothingIfTheUserIsNull(): void
    {
        $collection = CollectionFactory::create([
            'type' => 'feed',
        ]);

        $preloader = Preloader::for([$collection])->countStreamsFor(null);

        $this->assertInstanceOf(Preloader::class, $preloader);
    }

    public function testTimeFiltersForPreloadsTheTimeFilterOfTheFollow(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'feed',
        ]);
        $user->follow($collection->id);
        $follow = $user->followedCollection($collection->id);
        $follow->time_filter = 'strict';
        $follow->save();

        Preloader::for([$collection])->timeFiltersFor($user);

        $user->unfollow($collection->id);

        $this->assertSame('strict', $collection->timeFilterByUser($user));
    }

    public function testTimeFiltersForPreloadsNullWhenTheCollectionIsNotFollowed(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'feed',
        ]);

        Preloader::for([$collection])->timeFiltersFor($user);

        $this->assertNull($collection->timeFilterByUser($user));
    }

    public function testTimeFiltersForDoesNotReturnTheFilterOfAnotherUser(): void
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'feed',
        ]);
        $other_user->follow($collection->id);
        $follow = $other_user->followedCollection($collection->id);
        $follow->time_filter = 'strict';
        $follow->save();

        Preloader::for([$collection])->timeFiltersFor($user);

        $this->assertNull($collection->timeFilterByUser($user));
    }
}
