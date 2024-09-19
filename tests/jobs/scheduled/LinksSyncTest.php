<?php

namespace App\jobs\scheduled;

use App\models;
use tests\factories\FetchLogFactory;
use tests\factories\LinkFactory;

class LinksSyncTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\TimeHelper;
    use \tests\FakerHelper;
    use \tests\MockHttpHelper;

    #[\PHPUnit\Framework\Attributes\Before]
    public function emptyCachePath(): void
    {
        $files = glob(\Minz\Configuration::$application['cache_path'] . '/*');

        assert($files !== false);

        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function testQueue(): void
    {
        $links_fetcher_job = new LinksSync();

        $this->assertSame('fetchers', $links_fetcher_job->queue);
    }

    public function testSchedule(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);

        $links_fetcher_job = new LinksSync();

        $this->assertSame('+15 seconds', $links_fetcher_job->frequency);
    }

    public function testInstallWithJobsToCreate(): void
    {
        \Minz\Configuration::$application['job_links_sync_count'] = 2;
        \Minz\Configuration::$jobs_adapter = 'database';
        $links_fetcher_job = new LinksSync();

        $this->assertSame(0, \Minz\Job::count());

        $links_fetcher_job->install();

        \Minz\Configuration::$application['job_links_sync_count'] = 1;
        \Minz\Configuration::$jobs_adapter = 'test';

        $this->assertSame(2, \Minz\Job::count());
    }

    public function testInstallWithJobsToDelete(): void
    {
        \Minz\Configuration::$jobs_adapter = 'database';
        $links_fetcher_job = new LinksSync();
        $links_fetcher_job->performAsap();
        $links_fetcher_job = new LinksSync();
        $links_fetcher_job->performAsap();

        $this->assertSame(2, \Minz\Job::count());

        $links_fetcher_job->install();

        \Minz\Configuration::$jobs_adapter = 'test';

        $this->assertSame(1, \Minz\Job::count());
    }

    public function testPerform(): void
    {
        $url = 'https://flus.fr/carnet/';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        $link = LinkFactory::create([
            'url' => $url,
            'title' => $url,
            'fetched_at' => null,
            'fetched_code' => 0,
            'fetched_count' => 0,
        ]);
        $links_fetcher_job = new LinksSync();

        $links_fetcher_job->perform();

        $link = $link->reload();
        $this->assertSame('Carnet de Flus', $link->title);
        $this->assertNotNull($link->fetched_at);
        $this->assertSame(200, $link->fetched_code);
        $this->assertSame(1, $link->fetched_count);
        $this->assertNull($link->locked_at);
    }

    public function testPerformLogsFetch(): void
    {
        $link = LinkFactory::create([
            'url' => 'https://github.com/flusio/Flus',
            'title' => 'https://github.com/flusio/Flus',
            'fetched_at' => null,
        ]);
        $links_fetcher_job = new LinksSync();

        $this->assertSame(0, models\FetchLog::count());

        $links_fetcher_job->perform();

        $this->assertGreaterThanOrEqual(1, models\FetchLog::count());
        $fetch_log = models\FetchLog::take();
        $this->assertNotNull($fetch_log);
        $this->assertSame('https://github.com/flusio/Flus', $fetch_log->url);
        $this->assertSame('github.com', $fetch_log->host);
    }

    public function testPerformSavesResponseInCache(): void
    {
        $url = 'https://flus.fr/carnet/';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        $link = LinkFactory::create([
            'url' => $url,
            'title' => $url,
            'fetched_at' => null,
        ]);
        $links_fetcher_job = new LinksSync();

        $links_fetcher_job->perform();

        $hash = \SpiderBits\Cache::hash($url);
        /** @var string */
        $cache_path = \Minz\Configuration::$application['cache_path'];
        $cache_filepath = $cache_path . '/' . $hash;
        $this->assertTrue(file_exists($cache_filepath));
    }

    public function testPerformUsesCache(): void
    {
        $url = 'https://github.com/flusio/Flus';
        $link = LinkFactory::create([
            'url' => $url,
            'title' => $url,
            'fetched_at' => null,
        ]);
        $links_fetcher_job = new LinksSync();
        /** @var string */
        $expected_title = $this->fake('sentence');
        $hash = \SpiderBits\Cache::hash($url);
        $raw_response = <<<TEXT
        HTTP/2 200 OK
        Content-Type: text/html

        <html>
            <head>
                <title>{$expected_title}</title>
            </head>
        </html>
        TEXT;
        /** @var string */
        $cache_path = \Minz\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);

        $links_fetcher_job->perform();

        $link = $link->reload();
        $this->assertSame($expected_title, $link->title);
    }

    public function testPerformSkipsFetchIfReachedRateLimit(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $url = 'https://github.com/flusio/Flus';
        $host = 'github.com';
        foreach (range(1, 25) as $i) {
            /** @var int */
            $seconds = $this->fake('numberBetween', 0, 60);
            $created_at = \Minz\Time::ago($seconds, 'seconds');
            FetchLogFactory::create([
                'created_at' => $created_at,
                'url' => $url,
                'host' => $host,
            ]);
        }
        $link = LinkFactory::create([
            'url' => $url,
            'title' => $url,
            'fetched_at' => null,
        ]);
        $links_fetcher_job = new LinksSync();

        $links_fetcher_job->perform();

        $link = $link->reload();
        $this->assertSame($url, $link->title);
        $this->assertSame(0, $link->fetched_code);
        $this->assertNull($link->fetched_at);
    }

    public function testPerformFetchesLinksInError(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $fetched_count = $this->fake('numberBetween', 1, 25);
        $interval_to_wait = 5 + pow($fetched_count, 4);
        /** @var int */
        $seconds = $this->fake('numberBetween', $interval_to_wait + 1, $interval_to_wait + 9000);
        $fetched_at = \Minz\Time::ago($seconds, 'seconds');
        $url = 'https://flus.fr/carnet/';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        $link = LinkFactory::create([
            'url' => $url,
            'title' => $url,
            'fetched_at' => $fetched_at,
            'fetched_code' => 404,
            'fetched_error' => 'not found',
            'fetched_count' => $fetched_count,
        ]);
        $links_fetcher_job = new LinksSync();

        $links_fetcher_job->perform();

        $link = $link->reload();
        $this->assertSame('Carnet de Flus', $link->title);
        $this->assertNotEquals($fetched_at, $link->fetched_at);
        $this->assertSame(200, $link->fetched_code);
        $this->assertNull($link->fetched_error);
        $this->assertSame($fetched_count + 1, $link->fetched_count);
    }

    public function testPerformDoesNotFetchLinksInErrorIfFetchedCountIsGreaterThan25(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $fetched_count = $this->fake('numberBetween', 26, 42);
        $interval_to_wait = 5 + pow($fetched_count, 4);
        /** @var int */
        $seconds = $this->fake('numberBetween', $interval_to_wait + 1, $interval_to_wait + 9000);
        $fetched_at = \Minz\Time::ago($seconds, 'seconds');
        $link = LinkFactory::create([
            'url' => 'https://github.com/flusio/Flus',
            'title' => 'https://github.com/flusio/Flus',
            'fetched_at' => $fetched_at,
            'fetched_code' => 404,
            'fetched_error' => 'not found',
            'fetched_count' => $fetched_count,
        ]);
        $links_fetcher_job = new LinksSync();

        $links_fetcher_job->perform();

        $link = $link->reload();
        $this->assertSame('https://github.com/flusio/Flus', $link->title);
        $this->assertEquals($fetched_at, $link->fetched_at);
        $this->assertSame(404, $link->fetched_code);
        $this->assertSame($fetched_count, $link->fetched_count);
    }

    public function testPerformDoesNotFetchLinksInErrorIfFetchedAtIsWithinIntervalToWait(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $fetched_count = $this->fake('numberBetween', 1, 25);
        /** @var int */
        $seconds = $this->fake('numberBetween', 0, 4 * 60);
        $interval_to_wait = 5 + pow($fetched_count, 4);
        /** @var int */
        $seconds = $this->fake('numberBetween', 0, $interval_to_wait);
        $fetched_at = \Minz\Time::ago($seconds, 'seconds');
        $link = LinkFactory::create([
            'url' => 'https://github.com/flusio/Flus',
            'title' => 'https://github.com/flusio/Flus',
            'fetched_at' => $fetched_at,
            'fetched_code' => 404,
            'fetched_error' => 'not found',
            'fetched_count' => $fetched_count,
        ]);
        $links_fetcher_job = new LinksSync();

        $links_fetcher_job->perform();

        $link = $link->reload();
        $this->assertSame('https://github.com/flusio/Flus', $link->title);
        $this->assertEquals($fetched_at, $link->fetched_at);
        $this->assertSame(404, $link->fetched_code);
        $this->assertSame($fetched_count, $link->fetched_count);
    }

    public function testPerformDoesNotFetchLinkIfLockedDuringLastHour(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 0, 59);
        $locked_at = \Minz\Time::ago($minutes, 'minutes');
        $url = 'https://github.com/flusio/Flus';
        $link = LinkFactory::create([
            'url' => $url,
            'title' => $url,
            'fetched_at' => null,
            'fetched_code' => 0,
            'fetched_count' => 0,
            'locked_at' => $locked_at,
        ]);
        $links_fetcher_job = new LinksSync();
        /** @var string */
        $title = $this->fake('sentence');
        $hash = \SpiderBits\Cache::hash($url);
        $raw_response = <<<TEXT
        HTTP/2 200 OK
        Content-Type: text/html

        <html>
            <head>
                <title>{$title}</title>
            </head>
        </html>
        TEXT;
        /** @var string */
        $cache_path = \Minz\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);

        $links_fetcher_job->perform();

        $link = $link->reload();
        $this->assertNotSame($title, $link->title);
        $this->assertNull($link->fetched_at);
        $this->assertSame(0, $link->fetched_code);
        $this->assertSame(0, $link->fetched_count);
        $this->assertNotNull($link->locked_at);
    }

    public function testPerformFetchesLinkIfLockedAfterAnHour(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 60, 1000);
        $locked_at = \Minz\Time::ago($minutes, 'minutes');
        $url = 'https://github.com/flusio/Flus';
        $link = LinkFactory::create([
            'url' => $url,
            'title' => $url,
            'fetched_at' => null,
            'fetched_code' => 0,
            'fetched_count' => 0,
            'locked_at' => $locked_at,
        ]);
        $links_fetcher_job = new LinksSync();
        /** @var string */
        $title = $this->fake('sentence');
        $hash = \SpiderBits\Cache::hash($url);
        $raw_response = <<<TEXT
        HTTP/2 200 OK
        Content-Type: text/html

        <html>
            <head>
                <title>{$title}</title>
            </head>
        </html>
        TEXT;
        /** @var string */
        $cache_path = \Minz\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $cache->save($hash, $raw_response);

        $links_fetcher_job->perform();

        $link = $link->reload();
        $this->assertSame($title, $link->title);
        $this->assertNotNull($link->fetched_at);
        $this->assertSame(200, $link->fetched_code);
        $this->assertSame(1, $link->fetched_count);
        $this->assertNull($link->locked_at);
    }
}
