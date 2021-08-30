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
        $this->assertSame('+15 seconds', $feeds_sync_job->frequency);
    }

    public function testInstallWithJobsToCreate()
    {
        \Minz\Configuration::$application['feeds_sync_count'] = 2;
        \Minz\Configuration::$application['job_adapter'] = 'database';
        $feeds_sync_job = new FeedsSync();
        $job_dao = new models\dao\Job();

        $this->assertSame(0, $job_dao->count());

        $feeds_sync_job->install();

        \Minz\Configuration::$application['feeds_sync_count'] = 1;
        \Minz\Configuration::$application['job_adapter'] = 'test';

        $this->assertSame(2, $job_dao->count());
    }

    public function testInstallWithJobsToDelete()
    {
        \Minz\Configuration::$application['job_adapter'] = 'database';
        $feeds_sync_job = new FeedsSync();
        $job_dao = new models\dao\Job();
        $feeds_sync_job->performLater();
        $feeds_sync_job->performLater();

        $this->assertSame(2, $job_dao->count());

        $feeds_sync_job->install();

        \Minz\Configuration::$application['job_adapter'] = 'test';

        $this->assertSame(1, $job_dao->count());
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
        $user_id = $this->create('user', [
            'validated_at' => $this->fake('iso8601'),
        ]);
        $this->create('followed_collection', [
            'collection_id' => $collection_id,
            'user_id' => $user_id,
        ]);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $collection = models\Collection::find($collection_id);
        $this->assertSame('Carnet de Flus', $collection->name);
        $this->assertNotNull($collection->image_fetched_at);
        $this->assertNotNull($collection->image_filename);
        $this->assertNull($collection->locked_at);
        $links_number = count($collection->links());
        $this->assertGreaterThan(0, $links_number);
    }

    public function testPerformLogsFetch()
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $collection_id = $this->create('collection', [
            'type' => 'feed',
            'name' => $this->fake('sentence'),
            'feed_url' => $feed_url,
            'feed_fetched_at' => \Minz\Time::ago(2, 'hours')->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user', [
            'validated_at' => $this->fake('iso8601'),
        ]);
        $this->create('followed_collection', [
            'collection_id' => $collection_id,
            'user_id' => $user_id,
        ]);
        $feeds_sync_job = new FeedsSync();

        $this->assertSame(0, models\FetchLog::count());

        $feeds_sync_job->perform();

        $this->assertSame(1, models\FetchLog::count());
        $fetch_log = models\FetchLog::take();
        $this->assertSame($feed_url, $fetch_log->url);
        $this->assertSame('flus.fr', $fetch_log->host);
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
        $user_id = $this->create('user', [
            'validated_at' => $this->fake('iso8601'),
        ]);
        $this->create('followed_collection', [
            'collection_id' => $collection_id,
            'user_id' => $user_id,
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
        $user_id = $this->create('user', [
            'validated_at' => $this->fake('iso8601'),
        ]);
        $this->create('followed_collection', [
            'collection_id' => $collection_id,
            'user_id' => $user_id,
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
        $user_id = $this->create('user', [
            'validated_at' => $this->fake('iso8601'),
        ]);
        $this->create('followed_collection', [
            'collection_id' => $collection_id,
            'user_id' => $user_id,
        ]);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $collection = models\Collection::find($collection_id);
        $this->assertSame($expected_name, $collection->name);
        $links_number = count($collection->links());
        $this->assertSame(0, $links_number);
    }

    public function testPerformIgnoresFeedThatDidNotChange()
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $expected_name = $this->fakeUnique('sentence');
        // The trick of this test is to create the collection with the hash of
        // the feed that will be fetched. In real life, the hash of the
        // collection would be different. To do so, the feed can’t contain
        // random content (or we would have to calcule the feed hash, which is
        // a bit tedious here).
        $feed_hash = '9d433d21e9a066bb07601ff20efb8ab99d91a0ac7ce7187b246a4a365d197c33';
        $collection_id = $this->create('collection', [
            'type' => 'feed',
            'name' => $expected_name,
            'feed_url' => $feed_url,
            'feed_fetched_at' => \Minz\Time::ago(2, 'hours')->format(\Minz\Model::DATETIME_FORMAT),
            'feed_last_hash' => $feed_hash,
        ]);
        $user_id = $this->create('user', [
            'validated_at' => $this->fake('iso8601'),
        ]);
        $this->create('followed_collection', [
            'collection_id' => $collection_id,
            'user_id' => $user_id,
        ]);
        $hash = \SpiderBits\Cache::hash($feed_url);
        $raw_response = <<<XML
        HTTP/2 200 OK
        Content-Type: application/xml

        <?xml version='1.0' encoding='UTF-8'?>
        <feed xmlns="http://www.w3.org/2005/Atom">
            <title>carnet de flus</title>
            <link href="https://flus.fr/carnet/feeds/all.atom.xml" rel="self" type="application/atom+xml" />
            <link href="https://flus.fr/carnet/" rel="alternate" type="text/html" />
            <id>urn:uuid:4c04fe8e-c966-5b7e-af89-74d092a6ccb0</id>
            <updated>2021-03-30T11:26:00+02:00</updated>
            <entry>
                <title>Les nouveautés de mars 2021</title>
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
        $this->assertSame($feed_hash, $collection->feed_last_hash);
        $this->assertSame($expected_name, $collection->name);
    }

    public function testPerformDuplicatesLinkUrlIfNotInCollection()
    {
        $support_user = models\User::supportUser();
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $collection_id = $this->create('collection', [
            'type' => 'feed',
            'user_id' => $support_user->id,
            'feed_url' => $feed_url,
            'feed_fetched_at' => \Minz\Time::ago(2, 'hours')->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user', [
            'validated_at' => $this->fake('iso8601'),
        ]);
        $this->create('followed_collection', [
            'collection_id' => $collection_id,
            'user_id' => $user_id,
        ]);
        $link_url = 'https://flus.fr/carnet/nouveautes-mars-2021.html';
        $link_entry_id = 'urn:uuid:027e66f5-8137-5040-919d-6377c478ae9d';
        $link_published = '2021-03-30T09:26:00+00:00';
        $original_link_id = $this->create('link', [
            'url' => $link_url,
            'user_id' => $support_user->id,
            'feed_entry_id' => null,
            'created_at' => \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $hash = \SpiderBits\Cache::hash($feed_url);
        $raw_response = <<<XML
        HTTP/2 200 OK
        Content-Type: application/xml

        <?xml version='1.0' encoding='UTF-8'?>
        <feed xmlns="http://www.w3.org/2005/Atom">
            <title>carnet de flus</title>
            <link href="https://flus.fr/carnet/feeds/all.atom.xml" rel="self" type="application/atom+xml" />
            <link href="https://flus.fr/carnet/" rel="alternate" type="text/html" />
            <id>urn:uuid:4c04fe8e-c966-5b7e-af89-74d092a6ccb0</id>
            <updated>2021-03-30T11:26:00+02:00</updated>
            <entry>
                <title>Les nouveautés de mars 2021</title>
                <id>{$link_entry_id}</id>
                <author><name>Marien</name></author>
                <link href="{$link_url}" rel="alternate" type="text/html" />
                <published>{$link_published}</published>
                <updated>2021-03-30T11:26:00+02:00</updated>
                <content type="html"></content>
            </entry>
        </feed>
        XML;
        $cache = new \SpiderBits\Cache(\Minz\Configuration::$application['cache_path']);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $this->assertSame(1, models\Link::count());

        $feeds_sync_job->perform();

        $this->assertSame(2, models\Link::count());
        $link = models\Link::take(1);
        $this->assertNotSame($original_link_id, $link->id);
    }

    public function testPerformSlowsDownFetchIfReachedRateLimit()
    {
        $now1 = $this->fake('dateTime');
        $this->freeze($now1);
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $host = 'flus.fr';
        foreach (range(1, 25) as $i) {
            $seconds = $this->fake('numberBetween', 0, 60);
            $created_at = \Minz\Time::ago($seconds, 'seconds');
            $this->create('fetch_log', [
                'created_at' => $created_at->format(\Minz\Model::DATETIME_FORMAT),
                'url' => $feed_url,
                'host' => $host,
            ]);
        }
        $collection_id = $this->create('collection', [
            'type' => 'feed',
            'name' => $this->fake('sentence'),
            'feed_url' => $feed_url,
            'feed_fetched_at' => \Minz\Time::ago(2, 'hours')->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user', [
            'validated_at' => $this->fake('iso8601'),
        ]);
        $this->create('followed_collection', [
            'collection_id' => $collection_id,
            'user_id' => $user_id,
        ]);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $now2 = \Minz\Time::now();
        $this->assertGreaterThanOrEqual(5, $now2->getTimestamp() - $now1->getTimestamp());
    }

    public function testPerformIgnoresEntriesWithNoLink()
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
        $user_id = $this->create('user', [
            'validated_at' => $this->fake('iso8601'),
        ]);
        $this->create('followed_collection', [
            'collection_id' => $collection_id,
            'user_id' => $user_id,
        ]);
        $hash = \SpiderBits\Cache::hash($feed_url);
        $raw_response = <<<XML
        HTTP/2 200 OK
        Content-Type: application/xml

        <?xml version='1.0' encoding='UTF-8'?>
        <feed xmlns="http://www.w3.org/2005/Atom">
            <title>carnet de flus</title>
            <link href="https://flus.fr/carnet/feeds/all.atom.xml" rel="self" type="application/atom+xml" />
            <link href="https://flus.fr/carnet/" rel="alternate" type="text/html" />
            <id>urn:uuid:4c04fe8e-c966-5b7e-af89-74d092a6ccb0</id>
            <updated>2021-03-30T11:26:00+02:00</updated>
            <entry>
                <title>Les nouveautés de mars 2021</title>
                <id>urn:uuid:027e66f5-8137-5040-919d-6377c478ae9d</id>
                <author><name>Marien</name></author>
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
        $this->assertEmpty($collection->links());
    }

    public function testPerformIgnoresEntriesIfUrlExistsInCollection()
    {
        $support_user = models\User::supportUser();
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $collection_id = $this->create('collection', [
            'type' => 'feed',
            'user_id' => $support_user->id,
            'feed_url' => $feed_url,
            'feed_fetched_at' => \Minz\Time::ago(2, 'hours')->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user', [
            'validated_at' => $this->fake('iso8601'),
        ]);
        $this->create('followed_collection', [
            'collection_id' => $collection_id,
            'user_id' => $user_id,
        ]);
        $link_url = 'https://flus.fr/carnet/nouveautes-mars-2021.html';
        $link_entry_id = 'urn:uuid:027e66f5-8137-5040-919d-6377c478ae9d';
        $link_published = '2021-03-30T09:26:00+00:00';
        $link_id = $this->create('link', [
            'url' => $link_url,
            'user_id' => $support_user->id,
            'feed_entry_id' => null,
            'created_at' => \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);
        $hash = \SpiderBits\Cache::hash($feed_url);
        $raw_response = <<<XML
        HTTP/2 200 OK
        Content-Type: application/xml

        <?xml version='1.0' encoding='UTF-8'?>
        <feed xmlns="http://www.w3.org/2005/Atom">
            <title>carnet de flus</title>
            <link href="https://flus.fr/carnet/feeds/all.atom.xml" rel="self" type="application/atom+xml" />
            <link href="https://flus.fr/carnet/" rel="alternate" type="text/html" />
            <id>urn:uuid:4c04fe8e-c966-5b7e-af89-74d092a6ccb0</id>
            <updated>2021-03-30T11:26:00+02:00</updated>
            <entry>
                <title>Les nouveautés de mars 2021</title>
                <id>{$link_entry_id}</id>
                <author><name>Marien</name></author>
                <link href="{$link_url}" rel="alternate" type="text/html" />
                <published>{$link_published}</published>
                <updated>2021-03-30T11:26:00+02:00</updated>
                <content type="html"></content>
            </entry>
        </feed>
        XML;
        $cache = new \SpiderBits\Cache(\Minz\Configuration::$application['cache_path']);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $link = models\Link::find($link_id);
        $this->assertNull($link->feed_entry_id);
        $this->assertNotSame($link_published, $link->created_at->format(\DateTimeInterface::ATOM));
    }

    public function testPerformForcesEntryIdIfMissing()
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
        $user_id = $this->create('user', [
            'validated_at' => $this->fake('iso8601'),
        ]);
        $this->create('followed_collection', [
            'collection_id' => $collection_id,
            'user_id' => $user_id,
        ]);
        $hash = \SpiderBits\Cache::hash($feed_url);
        $raw_response = <<<XML
        HTTP/2 200 OK
        Content-Type: application/xml

        <?xml version='1.0' encoding='UTF-8'?>
        <feed xmlns="http://www.w3.org/2005/Atom">
            <title>carnet de flus</title>
            <link href="https://flus.fr/carnet/feeds/all.atom.xml" rel="self" type="application/atom+xml" />
            <link href="https://flus.fr/carnet/" rel="alternate" type="text/html" />
            <id>urn:uuid:4c04fe8e-c966-5b7e-af89-74d092a6ccb0</id>
            <updated>2021-03-30T11:26:00+02:00</updated>
            <entry>
                <title>Les nouveautés de mars 2021</title>
                <link href="https://flus.fr/carnet/nouveautes-mars-2021.html" rel="alternate" type="text/html" />
                <author><name>Marien</name></author>
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
        $link = $collection->links()[0];
        $this->assertSame($link->url, $link->feed_entry_id);
    }

    public function testPerformUpdatesUrlIfEntryIdIsIdentical()
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $old_url = $this->fakeUnique('url');
        $new_url = $this->fakeUnique('url');
        $entry_id = 'urn:uuid: ' . $this->fake('uuid');
        $collection_id = $this->create('collection', [
            'type' => 'feed',
            'feed_url' => $feed_url,
            'feed_fetched_at' => \Minz\Time::ago(2, 'hours')->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $link_id = $this->create('link', [
            'url' => $old_url,
            'feed_entry_id' => $entry_id,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $user_id = $this->create('user', [
            'validated_at' => $this->fake('iso8601'),
        ]);
        $this->create('followed_collection', [
            'collection_id' => $collection_id,
            'user_id' => $user_id,
        ]);
        $hash = \SpiderBits\Cache::hash($feed_url);
        $raw_response = <<<XML
        HTTP/2 200 OK
        Content-Type: application/xml

        <?xml version='1.0' encoding='UTF-8'?>
        <feed xmlns="http://www.w3.org/2005/Atom">
            <title>carnet de flus</title>
            <link href="https://flus.fr/carnet/feeds/all.atom.xml" rel="self" type="application/atom+xml" />
            <link href="https://flus.fr/carnet/" rel="alternate" type="text/html" />
            <id>urn:uuid:4c04fe8e-c966-5b7e-af89-74d092a6ccb0</id>
            <updated>2021-03-30T11:26:00+02:00</updated>
            <entry>
                <title>Les nouveautés de mars 2021</title>
                <link href="{$new_url}" rel="alternate" type="text/html" />
                <id>{$entry_id}</id>
                <author><name>Marien</name></author>
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

        $link = models\Link::find($link_id);
        $this->assertSame($new_url, $link->url);
        $this->assertSame($new_url, $link->title);
        $this->assertSame(1617096360, $link->created_at->getTimestamp());
        $this->assertNull($link->fetched_at);
    }

    public function testPerformAbsolutizesLinks()
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $collection_id = $this->create('collection', [
            'type' => 'feed',
            'feed_url' => $feed_url,
            'feed_fetched_at' => \Minz\Time::ago(2, 'hours')->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user', [
            'validated_at' => $this->fake('iso8601'),
        ]);
        $this->create('followed_collection', [
            'collection_id' => $collection_id,
            'user_id' => $user_id,
        ]);
        $hash = \SpiderBits\Cache::hash($feed_url);
        $raw_response = <<<XML
        HTTP/2 200 OK
        Content-Type: application/xml

        <?xml version='1.0' encoding='UTF-8'?>
        <feed xmlns="http://www.w3.org/2005/Atom">
            <title>carnet de flus</title>
            <link href="https://flus.fr/carnet/feeds/all.atom.xml" rel="self" type="application/atom+xml" />
            <link href="/carnet/" rel="alternate" type="text/html" />
            <id>urn:uuid:4c04fe8e-c966-5b7e-af89-74d092a6ccb0</id>
            <updated>2021-03-30T11:26:00+02:00</updated>
            <entry>
                <title>Les nouveautés de mars 2021</title>
                <id>urn:uuid:027e66f5-8137-5040-919d-6377c478ae9d</id>
                <link href="/carnet/nouveautes-mars-2021.html" rel="alternate" type="text/html" />
                <author><name>Marien</name></author>
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
        $this->assertSame('https://flus.fr/carnet/', $collection->feed_site_url);
        $link = $collection->links()[0];
        $this->assertSame('https://flus.fr/carnet/nouveautes-mars-2021.html', $link->url);
    }

    public function testPerformUsesFeedUrlIfSiteUrlIsMissing()
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $collection_id = $this->create('collection', [
            'type' => 'feed',
            'feed_url' => $feed_url,
            'feed_fetched_at' => \Minz\Time::ago(2, 'hours')->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user', [
            'validated_at' => $this->fake('iso8601'),
        ]);
        $this->create('followed_collection', [
            'collection_id' => $collection_id,
            'user_id' => $user_id,
        ]);
        $hash = \SpiderBits\Cache::hash($feed_url);
        $raw_response = <<<XML
        HTTP/2 200 OK
        Content-Type: application/xml

        <?xml version='1.0' encoding='UTF-8'?>
        <feed xmlns="http://www.w3.org/2005/Atom">
            <title>carnet de flus</title>
            <link href="https://flus.fr/carnet/feeds/all.atom.xml" rel="self" type="application/atom+xml" />
            <id>urn:uuid:4c04fe8e-c966-5b7e-af89-74d092a6ccb0</id>
            <updated>2021-03-30T11:26:00+02:00</updated>
            <entry>
                <title>Les nouveautés de mars 2021</title>
                <id>urn:uuid:027e66f5-8137-5040-919d-6377c478ae9d</id>
                <link href="https://flus.fr/carnet/nouveautes-mars-2021.html" rel="alternate" type="text/html" />
                <author><name>Marien</name></author>
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
        $this->assertSame($feed_url, $collection->feed_site_url);
    }

    public function testPerformDoesNotFetchFeedsIfLocked()
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $name = $this->fake('sentence');
        $collection_id = $this->create('collection', [
            'type' => 'feed',
            'name' => $name,
            'feed_url' => $feed_url,
            'feed_fetched_at' => \Minz\Time::ago(2, 'hours')->format(\Minz\Model::DATETIME_FORMAT),
            'locked_at' => $this->fake('iso8601'),
        ]);
        $user_id = $this->create('user', [
            'validated_at' => $this->fake('iso8601'),
        ]);
        $this->create('followed_collection', [
            'collection_id' => $collection_id,
            'user_id' => $user_id,
        ]);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $collection = models\Collection::find($collection_id);
        $this->assertSame($name, $collection->name);
        $this->assertNotNull($collection->locked_at);
        $links_number = count($collection->links());
        $this->assertSame(0, $links_number);
    }
}
