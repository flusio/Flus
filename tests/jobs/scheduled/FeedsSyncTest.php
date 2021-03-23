<?php

namespace flusio\jobs\scheduled;

use flusio\models;

class FeedsSyncTest extends \PHPUnit\Framework\TestCase
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
        $feeds_sync_job = new FeedsSync();

        $this->assertSame('fetchers', $feeds_sync_job->queue);
    }

    public function testSchedule()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);

        $feeds_sync_job = new FeedsSync();

        $this->assertSame(
            $now->getTimestamp(),
            $feeds_sync_job->perform_at->getTimestamp()
        );
        $this->assertSame('+10 minutes', $feeds_sync_job->frequency);
    }

    public function testPerform()
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $collection_id = $this->create('collection', [
            'type' => 'feed',
            'name' => $this->fake('sentence'),
            'feed_url' => $feed_url,
            'feed_fetched_at' => \Minz\Time::ago(2, 'hours')->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $collection = models\Collection::find($collection_id);
        $this->assertSame('carnet de flus', $collection->name);
        $links_number = count($collection->links());
        $this->assertGreaterThan(0, $links_number);
    }

    public function testPerformSavesResponseInCache()
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $collection_id = $this->create('collection', [
            'type' => 'feed',
            'name' => $this->fake('sentence'),
            'feed_url' => $feed_url,
            'feed_fetched_at' => \Minz\Time::ago(2, 'hours')->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $hash = \SpiderBits\Cache::hash($feed_url);
        $cache_filepath = \Minz\Configuration::$application['cache_path'] . '/' . $hash;
        $this->assertTrue(file_exists($cache_filepath));
    }

    public function testPerformUsesCache()
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $expected_name = $this->fakeUnique('sentence');
        $expected_title = $this->fakeUnique('sentence');
        $collection_id = $this->create('collection', [
            'type' => 'feed',
            'name' => $this->fakeUnique('sentence'),
            'feed_url' => $feed_url,
            'feed_fetched_at' => \Minz\Time::ago(2, 'hours')->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $hash = \SpiderBits\Cache::hash($feed_url);
        $raw_response = <<<XML
        HTTP/2 200 OK
        Content-Type: application/xml

        <?xml version='1.0' encoding='UTF-8'?>
        <feed xmlns="http://www.w3.org/2005/Atom">
            <title>{$expected_name}</title>
            <link href="https://flus.fr/carnet/feeds/all.atom.xml" rel="self" type="application/atom+xml" />
            <link href="https://flus.fr/carnet/" rel="alternate" type="text/html" />
            <id>urn:uuid:4c04fe8e-c966-5b7e-af89-74d092a6ccb0</id>
            <updated>2021-03-30T11:26:00+02:00</updated>
            <entry>
                <title>{$expected_title}</title>
                <id>urn:uuid:027e66f5-8137-5040-919d-6377c478ae9d</id>
                <author><name>Marien</name></author>
                <link href="https://flus.fr/carnet/nouveautes-mars-2021.html" rel="alternate" type="text/html" />
                <published>2021-03-30T11:26:00+02:00</published>
                <updated>2021-03-30T11:26:00+02:00</updated>
                <content type="html"></content>
            </entry>
        </feed>
        XML;
        $cache = new \SpiderBits\Cache(\Minz\Configuration::$application['cache_path']);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $collection = models\Collection::find($collection_id);
        $this->assertSame($expected_name, $collection->name);
        $link = $collection->links()[0];
        $this->assertSame($expected_title, $link->title);
    }

    public function testPerformIgnoresFeedFetchedLastHour()
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $expected_name = $this->fake('sentence');
        $collection_id = $this->create('collection', [
            'type' => 'feed',
            'name' => $expected_name,
            'feed_url' => $feed_url,
            'feed_fetched_at' => \Minz\Time::ago(59, 'minutes')->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $collection = models\Collection::find($collection_id);
        $this->assertSame($expected_name, $collection->name);
        $links_number = count($collection->links());
        $this->assertSame(0, $links_number);
    }
}
