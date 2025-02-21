<?php

namespace App\jobs\scheduled;

use App\http;
use App\models;
use tests\factories\CollectionFactory;
use tests\factories\FetchLogFactory;
use tests\factories\FollowedCollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\UserFactory;

class FeedsSyncTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\TimeHelper;
    use \tests\FakerHelper;
    use \tests\FilesystemHelper;
    use \tests\MockHttpHelper;

    public function testQueue(): void
    {
        $feeds_sync_job = new FeedsSync();

        $this->assertSame('fetchers', $feeds_sync_job->queue);
    }

    public function testSchedule(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);

        $feeds_sync_job = new FeedsSync();

        $this->assertSame('+15 seconds', $feeds_sync_job->frequency);
    }

    public function testInstallWithJobsToCreate(): void
    {
        \App\Configuration::$application['job_feeds_sync_count'] = 2;
        \App\Configuration::$jobs_adapter = 'database';

        $this->assertSame(0, \Minz\Job::count());

        FeedsSync::install();

        \App\Configuration::$application['job_feeds_sync_count'] = 1;
        \App\Configuration::$jobs_adapter = 'test';

        $this->assertSame(2, \Minz\Job::count());
    }

    public function testInstallWithJobsToDelete(): void
    {
        \App\Configuration::$jobs_adapter = 'database';
        $feeds_sync_job = new FeedsSync();
        $feeds_sync_job->performAsap();
        $feeds_sync_job = new FeedsSync();
        $feeds_sync_job->performAsap();

        $this->assertSame(2, \Minz\Job::count());

        FeedsSync::install();

        \App\Configuration::$jobs_adapter = 'test';

        $this->assertSame(1, \Minz\Job::count());
    }

    public function testPerform(): void
    {
        $this->freeze();

        /** @var string */
        $old_name = $this->fake('sentence');
        $url = 'https://flus.fr/carnet/';
        $card_url = 'https://flus.fr/carnet/card.png';
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        $this->mockHttpWithFile($card_url, 'public/static/og-card.png');
        $this->mockHttpWithFixture($feed_url, 'responses/flus.fr_carnet_feeds_all.atom.xml');
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'name' => $old_name,
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
        ]);
        $user = UserFactory::create([
            'validated_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);
        $feeds_sync_job = new FeedsSync();

        $this->assertSame(0, http\FetchLog::count());

        $feeds_sync_job->perform();

        $collection = $collection->reload();
        $this->assertSame('Carnet de Flus', $collection->name);
        $this->assertSame('atom', $collection->feed_type);
        $this->assertGreaterThanOrEqual(
            \Minz\Time::fromNow(1, 'hour')->getTimestamp(),
            $collection->feed_fetched_next_at?->getTimestamp(),
        );
        $this->assertNotNull($collection->image_fetched_at);
        $this->assertNotEmpty($collection->image_filename);
        $this->assertNull($collection->locked_at);
        $links_number = count($collection->links());
        $this->assertSame(3, $links_number);

        $this->assertSame(3, http\FetchLog::count());
        $fetch_log1 = http\FetchLog::take(0);
        $fetch_log2 = http\FetchLog::take(1);
        $fetch_log3 = http\FetchLog::take(2);
        $this->assertNotNull($fetch_log1);
        $this->assertNotNull($fetch_log2);
        $this->assertNotNull($fetch_log3);
        $this->assertSame($feed_url, $fetch_log1->url);
        $this->assertSame('flus.fr', $fetch_log1->host);
        $this->assertSame($url, $fetch_log2->url);
        $this->assertSame('flus.fr', $fetch_log2->host);
        $this->assertSame($card_url, $fetch_log3->url);
        $this->assertSame('flus.fr', $fetch_log3->host);
    }

    public function testPerformSavesResponseInCache(): void
    {
        /** @var string */
        $old_name = $this->fake('sentence');
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithFixture($feed_url, 'responses/flus.fr_carnet_feeds_all.atom.xml');
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'name' => $old_name,
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
        ]);
        $user = UserFactory::create([
            'validated_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $hash = \SpiderBits\Cache::hash($feed_url);
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache_filepath = $cache_path . '/' . $hash;
        $this->assertTrue(file_exists($cache_filepath));
    }

    public function testPerformUsesCache(): void
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        /** @var string */
        $old_name = $this->fake('sentence');
        /** @var string */
        $expected_name = $this->fakeUnique('sentence');
        /** @var string */
        $expected_title = $this->fakeUnique('sentence');
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'name' => $old_name,
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
        ]);
        $user = UserFactory::create([
            'validated_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
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
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $collection = $collection->reload();
        $this->assertSame($expected_name, $collection->name);
        $link = $collection->links()[0];
        $this->assertSame($expected_title, $link->title);
    }

    public function testPerformSavesPublishedDate(): void
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        /** @var string */
        $old_name = $this->fakeUnique('sentence');
        /** @var string */
        $expected_name = $this->fakeUnique('sentence');
        /** @var string */
        $expected_title = $this->fakeUnique('sentence');
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'name' => $old_name,
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
        ]);
        $user = UserFactory::create([
            'validated_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);
        $hash = \SpiderBits\Cache::hash($feed_url);
        $raw_response = <<<XML
        HTTP/2 200 OK
        Content-Type: application/xml

        <?xml version='1.0' encoding='UTF-8'?>
        <feed xmlns="http://www.w3.org/2005/Atom">
            <title>Carnet de Flus</title>
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
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $link_to_collection = models\LinkToCollection::take();
        $this->assertNotNull($link_to_collection);
        $this->assertSame(1617096360, $link_to_collection->created_at->getTimestamp());
    }

    public function testPerformIgnoresFeedNotYetToFetch(): void
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        /** @var string */
        $expected_name = $this->fake('sentence');
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'name' => $expected_name,
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::fromNow(5, 'minutes'),
        ]);
        $user = UserFactory::create([
            'validated_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $collection = $collection->reload();
        $this->assertSame($expected_name, $collection->name);
        $links_number = count($collection->links());
        $this->assertSame(0, $links_number);
    }

    public function testPerformIgnoresFeedThatDidNotChange(): void
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        /** @var string */
        $expected_name = $this->fakeUnique('sentence');
        // The trick of this test is to create the collection with the hash of
        // the feed that will be fetched. In real life, the hash of the
        // collection would be different. To do so, the feed can’t contain
        // random content (or we would have to calcule the feed hash, which is
        // a bit tedious here).
        $feed_hash = '38f9e30ef7c4b63def59105bc58d363b5147373beeffd9677a0f9e9d22edaebd';
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'name' => $expected_name,
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
            'feed_last_hash' => $feed_hash,
        ]);
        $user = UserFactory::create([
            'validated_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
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
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $collection = $collection->reload();
        $this->assertSame($feed_hash, $collection->feed_last_hash);
        $this->assertSame($expected_name, $collection->name);
    }

    public function testPerformDuplicatesLinkUrlIfNotInCollection(): void
    {
        $support_user = models\User::supportUser();
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'user_id' => $support_user->id,
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
        ]);
        $user = UserFactory::create([
            'validated_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);
        $link_url = 'https://flus.fr/carnet/nouveautes-mars-2021.html';
        $link_entry_id = 'urn:uuid:027e66f5-8137-5040-919d-6377c478ae9d';
        $link_published = '2021-03-30T09:26:00+00:00';
        $original_link = LinkFactory::create([
            'url' => $link_url,
            'user_id' => $support_user->id,
            'feed_entry_id' => null,
            'created_at' => \Minz\Time::now(),
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
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $this->assertSame(1, models\Link::count());

        $feeds_sync_job->perform();

        $this->assertSame(2, models\Link::count());
        $link = models\Link::take(1);
        $this->assertNotNull($link);
        $this->assertNotSame($original_link->id, $link->id);
    }

    public function testPerformSkipsFetchIfReachedRateLimit(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $host = 'flus.fr';
        foreach (range(1, 25) as $i) {
            /** @var int */
            $seconds = $this->fake('numberBetween', 0, 60);
            $created_at = \Minz\Time::ago($seconds, 'seconds');
            FetchLogFactory::create([
                'created_at' => $created_at,
                'url' => $feed_url,
                'host' => $host,
            ]);
        }
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'name' => $feed_url,
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
            'feed_fetched_at' => null,
            'feed_fetched_code' => 0,
        ]);
        $user = UserFactory::create([
            'validated_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $collection = $collection->reload();
        $this->assertSame($feed_url, $collection->name);
        $this->assertSame(0, $collection->feed_fetched_code);
        $this->assertNull($collection->feed_fetched_at);
    }

    public function testPerformUsesIdAsLinkIfEntryHasNoLink(): void
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $link_url = 'https://flus.fr/carnet/nouveautes-mars-2021.html';
        /** @var string */
        $old_name = $this->fakeUnique('sentence');
        /** @var string */
        $expected_name = $this->fakeUnique('sentence');
        /** @var string */
        $expected_title = $this->fakeUnique('sentence');
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'name' => $old_name,
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
        ]);
        $user = UserFactory::create([
            'validated_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
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
                <id>{$link_url}</id>
                <author><name>Marien</name></author>
                <published>2021-03-30T11:26:00+02:00</published>
                <updated>2021-03-30T11:26:00+02:00</updated>
                <content type="html"></content>
            </entry>
        </feed>
        XML;
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $this->assertSame(1, models\Link::count());
        $link = models\Link::take();
        $this->assertNotNull($link);
        $this->assertSame($link_url, $link->url);
    }

    public function testPerformIgnoresEntriesWithNoLink(): void
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        /** @var string */
        $old_name = $this->fakeUnique('sentence');
        /** @var string */
        $expected_name = $this->fakeUnique('sentence');
        /** @var string */
        $expected_title = $this->fakeUnique('sentence');
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'name' => $old_name,
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
        ]);
        $user = UserFactory::create([
            'validated_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
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
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $collection = $collection->reload();
        $this->assertEmpty($collection->links());
    }

    public function testPerformIgnoresEntriesWithInvalidUrl(): void
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
        ]);
        $user = UserFactory::create([
            'validated_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);
        $link_url = 'invalid://example.com';
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
                <link href="{$link_url}" rel="alternate" type="text/html" />
                <published>2021-03-30T11:26:00+02:00</published>
                <updated>2021-03-30T11:26:00+02:00</updated>
                <content type="html"></content>
            </entry>
        </feed>
        XML;
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $collection = $collection->reload();
        $this->assertEmpty($collection->links());
    }

    public function testPerformIgnoresEntriesIfUrlExistsInCollection(): void
    {
        $support_user = models\User::supportUser();
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'user_id' => $support_user->id,
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
        ]);
        $user = UserFactory::create([
            'validated_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);
        $link_url = 'https://flus.fr/carnet/nouveautes-mars-2021.html';
        $link_entry_id = 'urn:uuid:027e66f5-8137-5040-919d-6377c478ae9d';
        $link_published = '2021-03-30T09:26:00+00:00';
        $link = LinkFactory::create([
            'url' => $link_url,
            'user_id' => $support_user->id,
            'feed_entry_id' => null,
            'created_at' => \Minz\Time::now(),
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'link_id' => $link->id,
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
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $link = $link->reload();
        $this->assertNull($link->feed_entry_id);
        $this->assertNotSame($link_published, $link->created_at->format(\DateTimeInterface::ATOM));
    }

    public function testPerformIgnoresEntriesIfOverKeepMaximum(): void
    {
        \App\Configuration::$application['feeds_links_keep_maximum'] = 1;

        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $published_at_1 = \Minz\Time::ago(1, 'months');
        $published_at_2 = \Minz\Time::ago(2, 'months');
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
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
                <link href="https://flus.fr/carnet/nouveautes-mars-2021.html" rel="alternate" type="text/html"/>
                <published>{$published_at_1->format(DATE_ATOM)}</published>
                <updated>2021-03-30T11:26:00+02:00</updated>
                <content type="html"></content>
            </entry>
            <entry>
                <title>Bilan 2021</title>
                <id>urn:uuid:d4281ca0-f103-529b-9a47-adee05477c31</id>
                <author><name>Marien</name></author>
                <link href="https://flus.fr/carnet/bilan-2021.html" rel="alternate" type="text/html" />
                <published>{$published_at_2->format(DATE_ATOM)}</published>
                <updated>2022-01-05T17:30:00+01:00</updated>
                <content type="html"></content>
            </entry>
        </feed>
        XML;
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        \App\Configuration::$application['feeds_links_keep_maximum'] = 0;

        $this->assertSame(1, models\Link::count());
        $collection = $collection->reload();
        $links = $collection->links();
        $this->assertSame(1, count($links));
        $this->assertSame('Les nouveautés de mars 2021', $links[0]->title);
    }

    public function testPerformIgnoresEntriesIfOlderThanKeepPeriod(): void
    {
        \App\Configuration::$application['feeds_links_keep_period'] = 6;

        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        /** @var int */
        $months = $this->fake('numberBetween', 7, 100);
        $published_at = \Minz\Time::ago($months, 'months');
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
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
                <link href="https://flus.fr/carnet/nouveautes-mars-2021.html" rel="alternate" type="text/html"/>
                <published>{$published_at->format(DATE_ATOM)}</published>
                <updated>2021-03-30T11:26:00+02:00</updated>
                <content type="html"></content>
            </entry>
        </feed>
        XML;
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        \App\Configuration::$application['feeds_links_keep_period'] = 0;

        $this->assertSame(0, models\Link::count());
    }

    public function testPerformTakesEntriesIfRecentEnoughWhenKeepPeriodIsSet(): void
    {
        \App\Configuration::$application['feeds_links_keep_period'] = 6;

        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        /** @var int */
        $months = $this->fake('numberBetween', 0, 6);
        $published_at = \Minz\Time::ago($months, 'months');
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
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
                <link href="https://flus.fr/carnet/nouveautes-mars-2021.html" rel="alternate" type="text/html"/>
                <published>{$published_at->format(DATE_ATOM)}</published>
                <updated>2021-03-30T11:26:00+02:00</updated>
                <content type="html"></content>
            </entry>
        </feed>
        XML;
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        \App\Configuration::$application['feeds_links_keep_period'] = 0;

        $this->assertSame(1, models\Link::count());
        $collection = $collection->reload();
        $links = $collection->links();
        $this->assertSame(1, count($links));
        $this->assertSame('Les nouveautés de mars 2021', $links[0]->title);
    }

    public function testPerformTakesEntriesIfOlderThanKeepPeriodUntilMinimum(): void
    {
        \App\Configuration::$application['feeds_links_keep_period'] = 6;
        \App\Configuration::$application['feeds_links_keep_minimum'] = 1;

        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        /** @var int */
        $months = $this->fake('numberBetween', 7, 100);
        $published_at_old = \Minz\Time::ago($months, 'months');
        $published_at_older = \Minz\Time::ago($months + 1, 'months');
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
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
                <link href="https://flus.fr/carnet/nouveautes-mars-2021.html" rel="alternate" type="text/html"/>
                <published>{$published_at_old->format(DATE_ATOM)}</published>
                <updated>2021-03-30T11:26:00+02:00</updated>
                <content type="html"></content>
            </entry>
            <entry>
                <title>Bilan 2021</title>
                <id>urn:uuid:d4281ca0-f103-529b-9a47-adee05477c31</id>
                <author><name>Marien</name></author>
                <link href="https://flus.fr/carnet/bilan-2021.html" rel="alternate" type="text/html" />
                <published>{$published_at_older->format(DATE_ATOM)}</published>
                <updated>2022-01-05T17:30:00+01:00</updated>
                <content type="html"></content>
            </entry>
        </feed>
        XML;
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        \App\Configuration::$application['feeds_links_keep_period'] = 0;
        \App\Configuration::$application['feeds_links_keep_minimum'] = 0;

        $collection = $collection->reload();
        $links = $collection->links();
        $this->assertSame(1, count($links));
        $this->assertSame('Les nouveautés de mars 2021', $links[0]->title);
    }

    public function testPerformForcesEntryIdIfMissing(): void
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        /** @var string */
        $old_name = $this->fakeUnique('sentence');
        /** @var string */
        $expected_name = $this->fakeUnique('sentence');
        /** @var string */
        $expected_title = $this->fakeUnique('sentence');
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'name' => $old_name,
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
        ]);
        $user = UserFactory::create([
            'validated_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
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
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $collection = $collection->reload();
        $link = $collection->links()[0];
        $this->assertSame($link->url, $link->feed_entry_id);
    }

    public function testPerformUpdatesUrlIfEntryIdIsIdentical(): void
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        /** @var string */
        $old_url = $this->fakeUnique('url');
        /** @var string */
        $new_url = $this->fakeUnique('url');
        /** @var string */
        $uuid = $this->fake('uuid');
        $entry_id = 'urn:uuid: ' . $uuid;
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
        ]);
        $link = LinkFactory::create([
            'url' => $old_url,
            'feed_entry_id' => $entry_id,
        ]);
        $link_to_collection = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);
        $user = UserFactory::create([
            'validated_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
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
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $link = $link->reload();
        $this->assertSame($new_url, $link->url);
        $this->assertSame($new_url, $link->title);
        $this->assertNull($link->fetched_at);
        $link_to_collection = $link_to_collection->reload();
        $this->assertSame(1617096360, $link_to_collection->created_at->getTimestamp());
    }

    public function testPerformAbsolutizesLinks(): void
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
        ]);
        $user = UserFactory::create([
            'validated_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
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
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $collection = $collection->reload();
        $this->assertSame('https://flus.fr/carnet/', $collection->feed_site_url);
        $link = $collection->links()[0];
        $this->assertSame('https://flus.fr/carnet/nouveautes-mars-2021.html', $link->url);
    }

    public function testPerformUsesFeedUrlIfSiteUrlIsMissing(): void
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
        ]);
        $user = UserFactory::create([
            'validated_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
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
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $collection = $collection->reload();
        $this->assertSame($feed_url, $collection->feed_site_url);
    }

    public function testPerformHandlesLongMultiByteFeedTitle(): void
    {
        // In a first version of the code, titles were trimed with `substr`
        // which should be used only on single-byte encodings. Otherwise, it
        // can cut the strings between the bytes. This led to the database
        // rejecting invalid strings.
        // In this example, Unicode codepoint U+0800 is encoded on 3-bytes, so
        // substr would cut between bytes (3 not being a multiple of 100),
        // while mb_substr handles the size correctly.
        $title = str_repeat("\u{0800}", models\Collection::NAME_MAX_LENGTH);
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
        ]);
        $user = UserFactory::create([
            'validated_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);
        $hash = \SpiderBits\Cache::hash($feed_url);
        $raw_response = <<<XML
        HTTP/2 200 OK
        Content-Type: application/xml

        <?xml version='1.0' encoding='UTF-8'?>
        <feed xmlns="http://www.w3.org/2005/Atom">
            <title>{$title}</title>
            <link href="https://flus.fr/carnet/feeds/all.atom.xml" rel="self" type="application/atom+xml" />
            <link href="https://flus.fr/carnet/" rel="alternate" type="text/html" />
            <id>urn:uuid:4c04fe8e-c966-5b7e-af89-74d092a6ccb0</id>
            <updated>2021-03-30T11:26:00+02:00</updated>
        </feed>

        XML;
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $collection = $collection->reload();
        $this->assertSame($title, $collection->name);
    }

    public function testPerformSavesTheLinksUrlReplies(): void
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
        ]);
        $user = UserFactory::create([
            'validated_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);
        $hash = \SpiderBits\Cache::hash($feed_url);
        $raw_response = <<<XML
        HTTP/2 200 OK
        Content-Type: application/xml

        <?xml version='1.0' encoding='UTF-8'?>
        <feed xmlns="http://www.w3.org/2005/Atom">
            <title>Carnet de Flus</title>
            <link href="https://flus.fr/carnet/feeds/all.atom.xml" rel="self" type="application/atom+xml" />
            <link href="https://flus.fr/carnet/" rel="alternate" type="text/html" />
            <id>urn:uuid:4c04fe8e-c966-5b7e-af89-74d092a6ccb0</id>
            <updated>2021-03-30T11:26:00+02:00</updated>
            <entry>
                <title>Les nouveautés de mars 2021</title>
                <id>urn:uuid:027e66f5-8137-5040-919d-6377c478ae9d</id>
                <author><name>Marien</name></author>
                <link href="https://flus.fr/carnet/nouveautes-mars-2021.html" rel="alternate" type="text/html" />
                <link href="https://flus.fr/carnet/nouveautes-mars-2021.html#comments" rel="replies" type="text/html" />
                <published>2021-03-30T11:26:00+02:00</published>
                <updated>2021-03-30T11:26:00+02:00</updated>
                <content type="html"></content>
            </entry>
        </feed>

        XML;
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $collection = $collection->reload();
        $link = $collection->links()[0];
        $this->assertSame('https://flus.fr/carnet/nouveautes-mars-2021.html#comments', $link->url_replies);
    }

    public function testPerformDoesNotFetchFeedIfLockedDuringLastHour(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 0, 59);
        $locked_at = \Minz\Time::ago($minutes, 'minutes');
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        /** @var string */
        $name = $this->fakeUnique('sentence');
        /** @var string */
        $old_name = $this->fakeUnique('sentence');
        /** @var string */
        $title = $this->fakeUnique('sentence');
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'name' => $old_name,
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
            'locked_at' => $locked_at,
        ]);
        $user = UserFactory::create([
            'validated_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);
        $hash = \SpiderBits\Cache::hash($feed_url);
        $raw_response = <<<XML
        HTTP/2 200 OK
        Content-Type: application/xml

        <?xml version='1.0' encoding='UTF-8'?>
        <feed xmlns="http://www.w3.org/2005/Atom">
            <title>{$name}</title>
            <link href="https://flus.fr/carnet/feeds/all.atom.xml" rel="self" type="application/atom+xml" />
            <link href="https://flus.fr/carnet/" rel="alternate" type="text/html" />
            <id>urn:uuid:4c04fe8e-c966-5b7e-af89-74d092a6ccb0</id>
            <updated>2021-03-30T11:26:00+02:00</updated>
            <entry>
                <title>{$title}</title>
                <id>urn:uuid:027e66f5-8137-5040-919d-6377c478ae9d</id>
                <author><name>Marien</name></author>
                <link href="https://flus.fr/carnet/nouveautes-mars-2021.html" rel="alternate" type="text/html" />
                <published>2021-03-30T11:26:00+02:00</published>
                <updated>2021-03-30T11:26:00+02:00</updated>
                <content type="html"></content>
            </entry>
        </feed>
        XML;
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $collection = $collection->reload();
        $this->assertNotSame($name, $collection->name);
        $this->assertEmpty($collection->links());
    }

    public function testPerformFetchesFeedIfLockedAfterAnHour(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 60, 1000);
        $locked_at = \Minz\Time::ago($minutes, 'minutes');
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        /** @var string */
        $old_name = $this->fakeUnique('sentence');
        /** @var string */
        $expected_name = $this->fakeUnique('sentence');
        /** @var string */
        $expected_title = $this->fakeUnique('sentence');
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'name' => $old_name,
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
            'locked_at' => $locked_at,
        ]);
        $user = UserFactory::create([
            'validated_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
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
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $collection = $collection->reload();
        $this->assertSame($expected_name, $collection->name);
        $link = $collection->links()[0];
        $this->assertSame($expected_title, $link->title);
    }

    public function testPerformHandlesWrongEncoding(): void
    {
        // Create a XML string declaring encoding UTF-8
        $xml_feed = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0">
                <channel>
                    <title>My feed with an àccent</title>
                    <link>https://example.com</link>
                </channel>
            </rss>
            XML;
        // … but its real encoding is ISO-8859-1!
        $xml_feed = mb_convert_encoding($xml_feed, 'ISO-8859-1', 'UTF-8');

        // Setup the feed
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
        ]);
        $user = UserFactory::create([
            'validated_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);
        $hash = \SpiderBits\Cache::hash($feed_url);
        $raw_response = <<<XML
        HTTP/2 200 OK
        Content-Type: application/xml

        {$xml_feed}
        XML;
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $collection = $collection->reload();
        $this->assertSame('My feed with an ?ccent', $collection->name);
    }

    public function testPerformSendsAcceptHeader(): void
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithEcho($feed_url);
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'feed_url' => $feed_url,
            'feed_fetched_next_at' => \Minz\Time::now(),
        ]);
        $user = UserFactory::create([
            'validated_at' => \Minz\Time::now(),
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);
        $feeds_sync_job = new FeedsSync();

        $feeds_sync_job->perform();

        $hash = \SpiderBits\Cache::hash($feed_url);
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $response_text = $cache->get($hash);
        $this->assertIsString($response_text);
        $response = \SpiderBits\Response::fromText($response_text);
        $data = json_decode($response->data, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('headers', $data);
        $headers = $data['headers'];
        $this->assertIsArray($headers);
        $this->assertArrayHasKey('HTTP_ACCEPT', $headers);
        $accept = $headers['HTTP_ACCEPT'];
        $expected_accept = 'application/atom+xml,application/rss+xml,application/xml';
        $this->assertSame($expected_accept, $accept);
    }
}
