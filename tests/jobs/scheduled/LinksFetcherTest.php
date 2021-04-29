<?php

namespace flusio\jobs\scheduled;

use flusio\models;

class LinksFetcherTest extends \PHPUnit\Framework\TestCase
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
        $links_fetcher_job = new LinksFetcher();

        $this->assertSame('fetchers', $links_fetcher_job->queue);
    }

    public function testSchedule()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);

        $links_fetcher_job = new LinksFetcher();

        $this->assertSame(
            $now->getTimestamp(),
            $links_fetcher_job->perform_at->getTimestamp()
        );
        $this->assertSame('+15 seconds', $links_fetcher_job->frequency);
    }

    public function testPerform()
    {
        $link_id = $this->create('link', [
            'url' => 'https://github.com/flusio/flusio',
            'title' => 'https://github.com/flusio/flusio',
        ]);
        $links_fetcher_job = new LinksFetcher();

        $links_fetcher_job->perform();

        $link = models\Link::find($link_id);
        $this->assertSame('flusio/flusio', $link->title);
        $this->assertSame(200, $link->fetched_code);
    }

    public function testPerformLogsFetch()
    {
        $link_id = $this->create('link', [
            'url' => 'https://github.com/flusio/flusio',
            'title' => 'https://github.com/flusio/flusio',
        ]);
        $links_fetcher_job = new LinksFetcher();

        $this->assertSame(0, models\FetchLog::count());

        $links_fetcher_job->perform();

        $this->assertSame(1, models\FetchLog::count());
        $fetch_log = models\FetchLog::take();
        $this->assertSame('https://github.com/flusio/flusio', $fetch_log->url);
        $this->assertSame('github.com', $fetch_log->host);
    }

    public function testPerformSavesResponseInCache()
    {
        $url = 'https://github.com/flusio/flusio';
        $link_id = $this->create('link', [
            'url' => $url,
            'title' => $url,
        ]);
        $links_fetcher_job = new LinksFetcher();

        $links_fetcher_job->perform();

        $hash = \SpiderBits\Cache::hash($url);
        $cache_filepath = \Minz\Configuration::$application['cache_path'] . '/' . $hash;
        $this->assertTrue(file_exists($cache_filepath));
    }

    public function testPerformUsesCache()
    {
        $url = 'https://github.com/flusio/flusio';
        $link_id = $this->create('link', [
            'url' => $url,
            'title' => $url,
        ]);
        $links_fetcher_job = new LinksFetcher();
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
        $cache = new \SpiderBits\Cache(\Minz\Configuration::$application['cache_path']);
        $cache->save($hash, $raw_response);

        $links_fetcher_job->perform();

        $link = models\Link::find($link_id);
        $this->assertSame($expected_title, $link->title);
    }

    public function testPerformSkipsFetchIfReachedRateLimit()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $url = 'https://github.com/flusio/flusio';
        $host = 'github.com';
        foreach (range(1, 25) as $i) {
            $seconds = $this->fake('numberBetween', 0, 60);
            $created_at = \Minz\Time::ago($seconds, 'seconds');
            $this->create('fetch_log', [
                'created_at' => $created_at->format(\Minz\Model::DATETIME_FORMAT),
                'url' => $url,
                'host' => $host,
            ]);
        }
        $link_id = $this->create('link', [
            'url' => $url,
            'title' => $url,
        ]);
        $links_fetcher_job = new LinksFetcher();

        $links_fetcher_job->perform();

        $link = models\Link::find($link_id);
        $this->assertSame($url, $link->title);
        $this->assertSame(0, $link->fetched_code);
        $this->assertNull($link->fetched_at);
    }
}
