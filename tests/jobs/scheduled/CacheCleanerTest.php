<?php

namespace flusio\jobs\scheduled;

use flusio\models;

class CacheCleanerTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\TimeHelper;

    public function testQueue()
    {
        $cache_cleaner_job = new CacheCleaner();

        $this->assertSame('default', $cache_cleaner_job->queue);
    }

    public function testSchedule()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);

        $cache_cleaner_job = new CacheCleaner();

        $expected_perform_at = \Minz\Time::relative('tomorrow 1:00');
        $this->assertSame(
            $expected_perform_at->getTimestamp(),
            $cache_cleaner_job->perform_at->getTimestamp()
        );
        $this->assertSame('+1 day', $cache_cleaner_job->frequency);
    }

    public function testPerform()
    {
        $cache_path = \Minz\Configuration::$application['cache_path'];
        $filepath = $cache_path . '/foo';
        $validity_interval = 7 * 24 * 60 * 60;
        $modification_time = time() - $validity_interval;
        touch($filepath, $modification_time);
        $cache_cleaner_job = new CacheCleaner();

        $this->assertTrue(file_exists($filepath));

        $cache_cleaner_job->perform();

        $this->assertFalse(file_exists($filepath));
    }

    public function testPerformKeepsFilesWithinValidityInterval()
    {
        $cache_path = \Minz\Configuration::$application['cache_path'];
        $filepath = $cache_path . '/foo';
        $validity_interval = 7 * 24 * 60 * 60;
        $modification_time = time() - $validity_interval + 1;
        touch($filepath, $modification_time);
        $cache_cleaner_job = new CacheCleaner();

        $this->assertTrue(file_exists($filepath));

        $cache_cleaner_job->perform();

        $this->assertTrue(file_exists($filepath));
    }
}
