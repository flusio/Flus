<?php

namespace flusio\cli;

use flusio\models;

class FeedsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;

    /**
     * @beforeClass
     */
    public static function loadApplication()
    {
        self::$application = new \flusio\cli\Application();
    }

    /**
     * @beforeClass
     */
    public static function changeJobAdapterToDatabase()
    {
        // Adding a feed will fetch its links one by one via a job.
        // When job_adapter is set to test, jobs are automatically triggered.
        // We don't want to fetch the links because it's too long.
        \Minz\Configuration::$application['job_adapter'] = 'database';
    }

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

    /**
     * @afterClass
     */
    public static function changeJobAdapterToTest()
    {
        \Minz\Configuration::$application['job_adapter'] = 'test';
    }

    public function testIndexRendersCorrectly()
    {
        $feed_url_1 = $this->fake('url');
        $feed_url_2 = $this->fake('url');
        $feed_id_1 = $this->create('collection', [
            'type' => 'feed',
            'feed_url' => $feed_url_1,
        ]);
        $feed_id_2 = $this->create('collection', [
            'type' => 'feed',
            'feed_url' => $feed_url_2,
        ]);

        $response = $this->appRun('cli', '/feeds');

        $expected_output = <<<TEXT
        {$feed_id_1} {$feed_url_1}
        {$feed_id_2} {$feed_url_2}
        TEXT;
        $this->assertResponse($response, 200, $expected_output);
    }

    public function testIndexRendersCorrectlyWhenNoFeed()
    {
        $response = $this->appRun('cli', '/feeds');

        $this->assertResponse($response, 200, 'No feeds to list.');
    }

    public function testAddCreatesCollectionAndLinksAndRendersCorrectly()
    {
        $this->assertSame(0, models\Collection::count());
        $this->assertSame(0, models\Link::count());

        $response = $this->appRun('cli', '/feeds/add', [
            'url' => 'https://flus.fr/carnet/feeds/all.atom.xml',
        ]);

        $this->assertResponse(
            $response,
            200,
            'Feed https://flus.fr/carnet/feeds/all.atom.xml (Carnet de Flus) has been added.'
        );
        $this->assertSame(1, models\Collection::count());
        $this->assertGreaterThan(0, models\Link::count());
        $collection = models\Collection::take();
        $this->assertSame('Carnet de Flus', $collection->name);
        $this->assertSame('https://flus.fr/carnet/feeds/all.atom.xml', $collection->feed_url);
        $this->assertSame('https://flus.fr/carnet/', $collection->feed_site_url);
    }

    public function testAddCreatesCollectionButFailsIfNotAFeed()
    {
        $response = $this->appRun('cli', '/feeds/add', [
            'url' => 'https://flus.fr/carnet/',
        ]);

        $this->assertResponse($response, 400, 'Invalid content type: text/html.');
        $this->assertSame(1, models\Collection::count());
        $this->assertSame(0, models\Link::count());
        $collection = models\Collection::take();
        $this->assertSame('https://flus.fr/carnet/', $collection->name);
        $this->assertSame('https://flus.fr/carnet/', $collection->feed_url);
        $this->assertSame(200, $collection->feed_fetched_code);
        $this->assertSame('Invalid content type: text/html', $collection->feed_fetched_error);
    }

    public function testAddCreatesCollectionButFailsIfUrlIsNotSuccessful()
    {
        $response = $this->appRun('cli', '/feeds/add', [
            'url' => 'https://not.a.domain.flus.fr/',
        ]);

        $this->assertResponse($response, 400, 'Could not resolve host: not.a.domain.flus.fr');
        $this->assertSame(1, models\Collection::count());
        $this->assertSame(0, models\Link::count());
        $collection = models\Collection::take();
        $this->assertSame('https://not.a.domain.flus.fr/', $collection->name);
        $this->assertSame('https://not.a.domain.flus.fr/', $collection->feed_url);
        $this->assertSame(0, $collection->feed_fetched_code);
        $this->assertSame('Could not resolve host: not.a.domain.flus.fr', $collection->feed_fetched_error);
    }

    public function testAddFailsIfUrlIsInvalid()
    {
        $response = $this->appRun('cli', '/feeds/add', [
            'url' => '',
        ]);

        $this->assertResponse($response, 400, 'The name is required');
        $this->assertSame(0, models\Collection::count());
        $this->assertSame(0, models\Link::count());
    }

    public function testAddFailsIfFeedAlreadyInDatabase()
    {
        $support_user = models\User::supportUser();
        $url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $this->create('collection', [
            'type' => 'feed',
            'feed_url' => $url,
            'user_id' => $support_user->id,
        ]);

        $response = $this->appRun('cli', '/feeds/add', [
            'url' => $url,
        ]);

        $this->assertResponse($response, 400, 'Feed collection already in database.');
        $this->assertSame(1, models\Collection::count());
        $this->assertSame(0, models\Link::count());
    }

    public function testSyncSyncsFeedAndRendersCorrectly()
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $collection_id = $this->create('collection', [
            'type' => 'feed',
            'feed_url' => $feed_url,
        ]);

        $response = $this->appRun('cli', '/feeds/sync', [
            'id' => $collection_id,
        ]);

        $this->assertResponse($response, 200, "Feed {$collection_id} ({$feed_url}) has been synchronized.");
        $collection = models\Collection::find($collection_id);
        $this->assertSame('Carnet de Flus', $collection->name);
        $links_number = count($collection->links());
        $this->assertGreaterThan(0, $links_number);
    }

    public function testSyncSavesResponseInCache()
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $collection_id = $this->create('collection', [
            'type' => 'feed',
            'feed_url' => $feed_url,
        ]);

        $response = $this->appRun('cli', '/feeds/sync', [
            'id' => $collection_id,
        ]);

        $hash = \SpiderBits\Cache::hash($feed_url);
        $cache_filepath = \Minz\Configuration::$application['cache_path'] . '/' . $hash;
        $this->assertTrue(file_exists($cache_filepath));
    }

    public function testSyncUsesCache()
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $collection_id = $this->create('collection', [
            'type' => 'feed',
            'feed_url' => $feed_url,
        ]);
        $expected_name = $this->fake('sentence');
        $expected_title = $this->fake('sentence');
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

        $response = $this->appRun('cli', '/feeds/sync', [
            'id' => $collection_id,
        ]);

        $collection = models\Collection::find($collection_id);
        $this->assertSame($expected_name, $collection->name);
        $link = $collection->links()[0];
        $this->assertSame($expected_title, $link->title);
    }

    public function testSyncDoesNotUseCacheIfParamNocache()
    {
        $feed_url = 'https://flus.fr/carnet/feeds/all.atom.xml';
        $collection_id = $this->create('collection', [
            'type' => 'feed',
            'feed_url' => $feed_url,
        ]);
        $not_expected_name = $this->fake('sentence');
        $not_expected_title = $this->fake('sentence');
        $hash = \SpiderBits\Cache::hash($feed_url);
        $raw_response = <<<XML
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
        XML;
        $cache = new \SpiderBits\Cache(\Minz\Configuration::$application['cache_path']);
        $cache->save($hash, $raw_response);

        $response = $this->appRun('cli', '/feeds/sync', [
            'id' => $collection_id,
            'nocache' => true,
        ]);

        $collection = models\Collection::find($collection_id);
        $this->assertNotSame($not_expected_name, $collection->name);
        $link = $collection->links()[0];
        $this->assertNotSame($not_expected_title, $link->title);
    }

    public function testSyncFailsIfIdInvalid()
    {
        $response = $this->appRun('cli', '/feeds/sync', [
            'id' => 'not an id',
        ]);

        $this->assertResponse($response, 404, 'Feed id `not an id` does not exist.');
    }
}
