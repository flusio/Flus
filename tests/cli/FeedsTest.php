<?php

namespace App\cli;

use App\http;
use App\models;
use tests\factories\FeedFactory;

class FeedsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\FilesystemHelper;
    use \tests\HttpHelper;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function loadApplication(): void
    {
        self::$application = new \App\cli\Application();
    }

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function changeJobAdapterToDatabase(): void
    {
        // Adding a feed will fetch its links one by one via a job.
        // When jobs_adapter is set to test, jobs are automatically triggered.
        // We don't want to fetch the links because it's too long.
        \App\Configuration::$jobs_adapter = 'database';
    }

    #[\PHPUnit\Framework\Attributes\AfterClass]
    public static function changeJobAdapterToTest(): void
    {
        \App\Configuration::$jobs_adapter = 'test';
    }

    public function testIndexRendersCorrectly(): void
    {
        /** @var string */
        $feed_url_1 = $this->fake('url');
        /** @var string */
        $feed_url_2 = $this->fake('url');
        $feed_1 = FeedFactory::create([
            'feed_url' => $feed_url_1,
        ]);
        $feed_2 = FeedFactory::create([
            'feed_url' => $feed_url_2,
        ]);

        $response = $this->appRun('CLI', '/feeds');

        $expected_output = <<<TEXT
        {$feed_1->id} {$feed_url_1}
        {$feed_2->id} {$feed_url_2}
        TEXT;
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, $expected_output);
    }

    public function testIndexRendersCorrectlyWhenNoFeed(): void
    {
        $response = $this->appRun('CLI', '/feeds');

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'No feeds to list.');
    }

    public function testAddCreatesCollectionAndLinksAndRendersCorrectly(): void
    {
        $url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_feeds_all.atom.xml');

        $this->assertSame(0, models\Feed::count());
        $this->assertSame(0, models\Link::count());

        $response = $this->appRun('CLI', '/feeds/add', [
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "Feed {$url} (Carnet de Flus) has been added.");
        $this->assertSame(1, models\Feed::count());
        $this->assertGreaterThan(0, models\Link::count());
        $feed = models\Feed::take();
        $this->assertNotNull($feed);
        $this->assertSame($url, $feed->feed_url);
        $this->assertSame('Carnet de Flus', $feed->name);
        $this->assertSame('https://flus.fr/carnet/', $feed->feed_site_url);
    }

    public function testAddCreatesCollectionButFailsIfNotAFeed(): void
    {
        $url = 'https://flus.fr/carnet/';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('CLI', '/feeds/add', [
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'Can’t parse the given string');
        $this->assertSame(1, models\Feed::count());
        $this->assertSame(0, models\Link::count());
        $feed = models\Feed::take();
        $this->assertNotNull($feed);
        $this->assertSame('https://flus.fr/carnet/', $feed->name);
        $this->assertSame('https://flus.fr/carnet/', $feed->feed_url);
        $this->assertSame(200, $feed->feed_fetched_code);
        $this->assertNotNull($feed->feed_fetched_error);
        $this->assertStringContainsString('Can’t parse the given string', $feed->feed_fetched_error);
    }

    public function testAddCreatesCollectionButFailsIfUrlIsUnreachable(): void
    {
        $url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpAsUnreachable($url);

        $response = $this->appRun('CLI', '/feeds/add', [
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertSame(1, models\Feed::count());
        $this->assertSame(0, models\Link::count());
        $feed = models\Feed::take();
        $this->assertNotNull($feed);
        $this->assertSame($url, $feed->name);
        $this->assertSame($url, $feed->feed_url);
        $this->assertSame(0, $feed->feed_fetched_code);
        $this->assertNotEmpty($feed->feed_fetched_error);
    }

    public function testAddFailsIfUrlIsInvalid(): void
    {
        $response = $this->appRun('CLI', '/feeds/add', [
            'url' => '',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The name is required');
        $this->assertSame(0, models\Feed::count());
        $this->assertSame(0, models\Link::count());
    }

    public function testAddFailsIfFeedAlreadyInDatabase(): void
    {
        $url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        FeedFactory::create([
            'feed_url' => $url,
        ]);

        $response = $this->appRun('CLI', '/feeds/add', [
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseEquals($response, 'Feed already in database.');
        $this->assertSame(1, models\Feed::count());
        $this->assertSame(0, models\Link::count());
    }

    public function testSyncSyncsFeedAndRendersCorrectly(): void
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithFixture($feed_url, 'responses/flus.fr_carnet_feeds_all.atom.xml');
        $feed = FeedFactory::create([
            'feed_url' => $feed_url,
        ]);

        $response = $this->appRun('CLI', '/feeds/sync', [
            'id' => $feed->id,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "Feed {$feed->id} ({$feed_url}) has been synchronized.");
        $feed = $feed->reload();
        $this->assertSame('Carnet de Flus', $feed->name);
        $links_number = count($feed->links());
        $this->assertEquals(3, $links_number);
    }

    public function testSyncSavesResponseInCache(): void
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithFixture($feed_url, 'responses/flus.fr_carnet_feeds_all.atom.xml');
        $feed = FeedFactory::create([
            'feed_url' => $feed_url,
        ]);

        $response = $this->appRun('CLI', '/feeds/sync', [
            'id' => $feed->id,
        ]);

        $this->assertTrue(self::$http_cache->hasItem($feed_url));
    }

    public function testSyncUsesCache(): void
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $feed = FeedFactory::create([
            'feed_url' => $feed_url,
        ]);
        /** @var string */
        $expected_name = $this->fake('sentence');
        /** @var string */
        $expected_title = $this->fake('sentence');
        $this->mockHttpWithFixture('https://flus.fr/carnet/', 'responses/flus.fr_carnet_index.html');
        $this->cacheHttpResponse($feed_url, <<<TEXT
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
            TEXT);

        $response = $this->appRun('CLI', '/feeds/sync', [
            'id' => $feed->id,
        ]);

        $feed = $feed->reload();
        $this->assertSame($expected_name, $feed->name);
        $link = $feed->links()[0];
        $this->assertSame($expected_title, $link->title);
    }

    public function testSyncDoesNotUseCacheIfParamNocache(): void
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->mockHttpWithFixture($feed_url, 'responses/flus.fr_carnet_feeds_all.atom.xml');
        $feed = FeedFactory::create([
            'feed_url' => $feed_url,
        ]);
        /** @var string */
        $not_expected_name = $this->fake('sentence');
        /** @var string */
        $not_expected_title = $this->fake('sentence');
        $this->cacheHttpResponse($feed_url, <<<TEXT
            HTTP/2 200 OK
            Content-Type: application/xml

            <?xml version='1.0' encoding='UTF-8'?>
            <feed xmlns="http://www.w3.org/2005/Atom">
            <title>{$not_expected_name}</title>
                <link href="https://flus.fr/carnet/feeds/all.atom.xml" rel="self" type="application/atom+xml" />
                <link href="https://flus.fr/carnet/" rel="alternate" type="text/html" />
                <id>urn:uuid:4c04fe8e-c966-5b7e-af89-74d092a6ccb0</id>
                <updated>2021-03-30T11:26:00+02:00</updated>
                <entry>
                    <title>{$not_expected_title}</title>
                    <id>urn:uuid:027e66f5-8137-5040-919d-6377c478ae9d</id>
                    <author><name>Marien</name></author>
                    <link href="https://flus.fr/carnet/nouveautes-mars-2021.html" rel="alternate" type="text/html" />
                    <published>2021-03-30T11:26:00+02:00</published>
                    <updated>2021-03-30T11:26:00+02:00</updated>
                    <content type="html"></content>
                </entry>
            </feed>
            TEXT);

        $response = $this->appRun('CLI', '/feeds/sync', [
            'id' => $feed->id,
            'nocache' => true,
        ]);

        $feed = $feed->reload();
        $this->assertNotSame($not_expected_name, $feed->name);
        $link = $feed->links()[0];
        $this->assertNotSame($not_expected_title, $link->title);
    }

    public function testSyncFailsIfIdInvalid(): void
    {
        $response = $this->appRun('CLI', '/feeds/sync', [
            'id' => 'not an id',
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertResponseEquals($response, 'Feed id `not an id` does not exist.');
    }

    public function testResetHashesSetsAllFeedLastHashToEmptyString(): void
    {
        $feed = FeedFactory::create([
            'feed_last_hash' => hash('sha256', 'foo'),
        ]);

        $response = $this->appRun('CLI', '/feeds/reset-hashes');

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Feeds hashes have been reset.');
        $feed = $feed->reload();
        $this->assertSame('', $feed->feed_last_hash);
    }
}
