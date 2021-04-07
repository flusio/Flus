<?php

namespace flusio\cli;

use flusio\models;

class FeedsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
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
            'Feed https://flus.fr/carnet/feeds/all.atom.xml (carnet de flus) has been added.'
        );
        $this->assertSame(1, models\Collection::count());
        $this->assertGreaterThan(0, models\Link::count());
        $collection = models\Collection::take();
        $this->assertSame('carnet de flus', $collection->name);
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
}
