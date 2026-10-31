<?php

namespace App\models\link_lists;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\FeedFactory;
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
        $feed = FeedFactory::create();

        Preloader::for([$feed])->publishers();

        $this->assertSame([], $feed->publishers());
        $this->assertNull($feed->owner());
    }

    public function testCountStreamsForPreloadsTheNumberOfStreams(): void
    {
        $user = UserFactory::create();
        $feed = FeedFactory::create();
        $user->follow($feed);
        $stream_1 = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $stream_2 = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $stream_1->addSource($feed);
        $stream_2->addSource($feed);

        Preloader::for([$feed])->countStreamsFor($user);

        $stream_1->removeSource($feed);
        $stream_2->removeSource($feed);

        $this->assertSame(2, $feed->countStreamsByUser($user));
    }

    public function testCountStreamsForPreloadsZeroWhenTheCollectionIsInNoStream(): void
    {
        $user = UserFactory::create();
        $feed = FeedFactory::create();
        $user->follow($feed);

        Preloader::for([$feed])->countStreamsFor($user);

        $this->assertSame(0, $feed->countStreamsByUser($user));
    }

    public function testCountStreamsForDoesNotCountTheStreamsOfAnotherUser(): void
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $feed = FeedFactory::create();
        $user->follow($feed);
        $other_user->follow($feed);
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);
        $stream->addSource($feed);

        Preloader::for([$feed])->countStreamsFor($user);

        $this->assertSame(0, $feed->countStreamsByUser($user));
    }

    public function testCountStreamsForDoesNothingIfTheUserIsNull(): void
    {
        $feed = FeedFactory::create();

        $preloader = Preloader::for([$feed])->countStreamsFor(null);

        $this->assertInstanceOf(Preloader::class, $preloader);
    }
}
