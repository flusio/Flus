<?php

namespace App\models;

use App\models;
use tests\factories\FeedFactory;

class FeedTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;

    public function testListFeedsToFetchWithSerie(): void
    {
        $feed1 = FeedFactory::create([
            'id' => '42',
            'feed_fetched_next_at' => null,
        ]);
        $feed2 = FeedFactory::create([
            'id' => '43',
            'feed_fetched_next_at' => null,
        ]);
        $feed3 = FeedFactory::create([
            'id' => '44',
            'feed_fetched_next_at' => null,
        ]);

        $serie = ['total' => 3, 'number' => 0];
        $feeds = models\Feed::listToFetch(serie: $serie);
        $this->assertSame(1, count($feeds));
        $this->assertSame($feed1->id, $feeds[0]->id);

        $serie = ['total' => 3, 'number' => 1];
        $feeds = models\Feed::listToFetch(serie: $serie);
        $this->assertSame(1, count($feeds));
        $this->assertSame($feed2->id, $feeds[0]->id);

        $serie = ['total' => 3, 'number' => 2];
        $feeds = models\Feed::listToFetch(serie: $serie);
        $this->assertSame(1, count($feeds));
        $this->assertSame($feed3->id, $feeds[0]->id);
    }
}
