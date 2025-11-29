<?php

namespace App\jobs\scheduled;

use App\http;
use App\services;
use tests\factories\FetchLogFactory;
use tests\factories\LinkFactory;
use tests\factories\LockFactory;

class LinksSyncTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\TimeHelper;
    use \tests\FakerHelper;
    use \tests\FilesystemHelper;
    use \tests\HttpHelper;

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
        \App\Configuration::$application['job_links_sync_count'] = 2;
        \App\Configuration::$jobs_adapter = 'database';
        $links_fetcher_job = new LinksSync();

        $this->assertSame(0, \Minz\Job::count());

        $links_fetcher_job->install();

        \App\Configuration::$application['job_links_sync_count'] = 1;
        \App\Configuration::$jobs_adapter = 'test';

        $this->assertSame(2, \Minz\Job::count());
    }

    public function testInstallWithJobsToDelete(): void
    {
        \App\Configuration::$jobs_adapter = 'database';
        $links_fetcher_job = new LinksSync();
        $links_fetcher_job->performAsap();
        $links_fetcher_job = new LinksSync();
        $links_fetcher_job->performAsap();

        $this->assertSame(2, \Minz\Job::count());

        $links_fetcher_job->install();

        \App\Configuration::$jobs_adapter = 'test';

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
        ]);
        $links_fetcher_job = new LinksSync();

        $links_fetcher_job->perform();

        $link = $link->reload();
        $this->assertSame('Carnet de Flus', $link->title);
        $this->assertNotNull($link->fetched_at);
        $this->assertSame(200, $link->fetched_code);
        $this->assertSame(1, $link->fetched_count);
    }

    public function testPerformLogsFetch(): void
    {
        $url = 'https://flus.fr/carnet/';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        $link = LinkFactory::create([
            'url' => $url,
            'title' => $url,
            'fetched_at' => null,
        ]);
        $links_fetcher_job = new LinksSync();

        $this->assertSame(0, http\FetchLog::count());

        $links_fetcher_job->perform();

        $this->assertGreaterThanOrEqual(1, http\FetchLog::count());
        $fetch_log = http\FetchLog::take();
        $this->assertNotNull($fetch_log);
        $this->assertSame($url, $fetch_log->url);
        $this->assertSame('flus.fr', $fetch_log->host);
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

        $this->assertTrue(self::$http_cache->hasItem($url));
    }

    public function testPerformFetchesLinksAgainIfFetchedRetryAtIsBeforeNow(): void
    {
        $this->freeze();
        $url = 'https://flus.fr/carnet/';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        $fetched_count = 1;
        $fetched_at = \Minz\Time::ago(1, 'hour');
        $link = LinkFactory::create([
            'url' => $url,
            'title' => $url,
            'fetched_at' => $fetched_at,
            'fetched_count' => $fetched_count,
            'fetched_retry_at' => \Minz\Time::ago(1, 'hour'),
        ]);
        $links_fetcher_job = new LinksSync();

        $links_fetcher_job->perform();

        $link = $link->reload();
        $this->assertSame(200, $link->fetched_code);
        $this->assertSame('Carnet de Flus', $link->title);
        $this->assertEquals(\Minz\Time::now()->getTimestamp(), $link->fetched_at?->getTimestamp());
        $this->assertSame($fetched_count + 1, $link->fetched_count);
    }

    public function testPerformUsesCache(): void
    {
        $url = 'https://flus.fr/carnet/';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        $link = LinkFactory::create([
            'url' => $url,
            'title' => $url,
            'fetched_at' => null,
        ]);
        $links_fetcher_job = new LinksSync();
        /** @var string */
        $expected_title = $this->fake('sentence');
        $this->cacheHttpResponse($url, <<<TEXT
            HTTP/2 200 OK
            Content-Type: text/html

            <html>
                <head>
                    <title>{$expected_title}</title>
                </head>
            </html>
            TEXT);

        $links_fetcher_job->perform();

        $link = $link->reload();
        $this->assertSame($expected_title, $link->title);
    }

    public function testPerformSkipsFetchIfReachedRateLimit(): void
    {
        $url = 'https://flus.fr/carnet/';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        $host = 'flus.fr';
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
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

    public function testPerformDoesNotFetchLinksIfFetchedRetryAtIsAfterNow(): void
    {
        $this->freeze();
        $url = 'https://flus.fr/carnet/';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        $fetched_count = 1;
        $fetched_at = \Minz\Time::ago(1, 'hour');
        $link = LinkFactory::create([
            'url' => $url,
            'title' => $url,
            'fetched_at' => $fetched_at,
            'fetched_count' => $fetched_count,
            'fetched_retry_at' => \Minz\Time::fromNow(1, 'hour'),
        ]);
        $links_fetcher_job = new LinksSync();

        $links_fetcher_job->perform();

        $link = $link->reload();
        $this->assertSame($url, $link->title);
        $this->assertEquals($fetched_at->getTimestamp(), $link->fetched_at?->getTimestamp());
        $this->assertSame($fetched_count, $link->fetched_count);
    }

    public function testPerformDoesNotFetchLinkIfLocked(): void
    {
        $url = 'https://flus.fr/carnet/';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 60);
        $lock_expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $link = LinkFactory::create([
            'url' => $url,
            'title' => $url,
            'fetched_at' => null,
        ]);
        $lock = LockFactory::create([
            'key' => "link:{$link->url_hash}",
            'expired_at' => $lock_expired_at,
        ]);
        $links_fetcher_job = new LinksSync();

        $links_fetcher_job->perform();

        $link = $link->reload();
        $this->assertSame($url, $link->title);
        $this->assertNull($link->fetched_at);
        $this->assertSame(0, $link->fetched_code);
        $this->assertSame(0, $link->fetched_count);
        $this->assertTrue(services\Lock::exists($lock->key));
    }

    public function testPerformFetchesLinkIfLockIsExpired(): void
    {
        $url = 'https://flus.fr/carnet/';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 0, 60);
        $lock_expired_at = \Minz\Time::ago($minutes, 'minutes');
        $link = LinkFactory::create([
            'url' => $url,
            'title' => $url,
            'fetched_at' => null,
        ]);
        $lock = LockFactory::create([
            'key' => "link:{$link->url_hash}",
            'expired_at' => $lock_expired_at,
        ]);
        $links_fetcher_job = new LinksSync();

        $links_fetcher_job->perform();

        $link = $link->reload();
        $this->assertSame('Carnet de Flus', $link->title);
        $this->assertNotNull($link->fetched_at);
        $this->assertSame(200, $link->fetched_code);
        $this->assertSame(1, $link->fetched_count);
        $this->assertFalse(services\Lock::exists($lock->key));
    }
}
