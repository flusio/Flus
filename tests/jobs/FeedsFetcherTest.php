<?php

namespace flusio\jobs;

use flusio\models;

class FeedsFetcherTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\TimeHelper;

    /**
     * @before
     */
    public function emptyCachePath()
    {
        $files = glob(\Minz\Configuration::$application['cache_path'] . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function testQueue()
    {
        $feeds_fetcher_job = new FeedsFetcher();

        $this->assertSame('fetchers', $feeds_fetcher_job->queue);
    }

    public function testPerform()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $collection_id = $this->create('collection', [
            'name' => 'https://flus.fr/carnet/feeds/all.atom.xml',
            'feed_url' => 'https://flus.fr/carnet/feeds/all.atom.xml',
            'type' => 'feed',
            'feed_fetched_at' => null,
        ]);
        $feeds_fetcher_job = new FeedsFetcher();

        $feeds_fetcher_job->perform();

        $collection = models\Collection::find($collection_id);
        $this->assertSame(200, $collection->feed_fetched_code);
        $this->assertSame('Carnet de Flus', $collection->name);
        $this->assertSame($now->getTimestamp(), $collection->feed_fetched_at->getTimestamp());
    }
}
