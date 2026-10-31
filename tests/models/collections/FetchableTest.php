<?php

namespace App\models\collections;

use App\models;
use tests\factories\CollectionFactory;

class FetchableTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;

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
