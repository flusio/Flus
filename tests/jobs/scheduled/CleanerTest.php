<?php

namespace flusio\jobs\scheduled;

use flusio\models;

class CleanerTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\TimeHelper;

    public function testQueue()
    {
        $cleaner_job = new Cleaner();

        $this->assertSame('default', $cleaner_job->queue);
    }

    public function testSchedule()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);

        $cleaner_job = new Cleaner();

        $expected_perform_at = \Minz\Time::relative('tomorrow 1:00');
        $this->assertSame(
            $expected_perform_at->getTimestamp(),
            $cleaner_job->perform_at->getTimestamp()
        );
        $this->assertSame('+1 day', $cleaner_job->frequency);
    }

    public function testPerformDeletesFilesOutsideValidityInterval()
    {
        $cache_path = \Minz\Configuration::$application['cache_path'];
        $filepath = $cache_path . '/foo';
        $validity_interval = 7 * 24 * 60 * 60;
        $modification_time = time() - $validity_interval;
        touch($filepath, $modification_time);
        $cleaner_job = new Cleaner();

        $this->assertTrue(file_exists($filepath));

        $cleaner_job->perform();

        $this->assertFalse(file_exists($filepath));
    }

    public function testPerformKeepsFilesWithinValidityInterval()
    {
        $cache_path = \Minz\Configuration::$application['cache_path'];
        $filepath = $cache_path . '/foo';
        $validity_interval = 7 * 24 * 60 * 60;
        $modification_time = time() - $validity_interval + 1;
        touch($filepath, $modification_time);
        $cleaner_job = new Cleaner();

        $this->assertTrue(file_exists($filepath));

        $cleaner_job->perform();

        $this->assertTrue(file_exists($filepath));
    }

    public function testPerformDeletesOldFetchLogs()
    {
        $cleaner_job = new Cleaner();
        $days = $this->fake('numberBetween', 4, 9000);
        $created_at = \Minz\Time::ago($days, 'days');
        $fetch_log_id = $this->create('fetch_log', [
            'created_at' => $created_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $cleaner_job->perform();

        $this->assertFalse(models\FetchLog::exists($fetch_log_id));
    }

    public function testPerformKeepsFreshFetchLogs()
    {
        $cleaner_job = new Cleaner();
        // logs are kept up to 3 days, but the test can fail if $days = 3 and it takes too long to execute
        $days = $this->fake('numberBetween', 0, 2);
        $created_at = \Minz\Time::ago($days, 'days');
        $fetch_log_id = $this->create('fetch_log', [
            'created_at' => $created_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $cleaner_job->perform();

        $this->assertTrue(models\FetchLog::exists($fetch_log_id));
    }

    public function testPerformDeletesExpiredSession()
    {
        $cleaner_job = new Cleaner();
        $days = $this->fake('numberBetween', 0, 9000);
        $expired_at = \Minz\Time::ago($days, 'days');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $session_id = $this->create('session', [
            'token' => $token,
        ]);

        $cleaner_job->perform();

        $this->assertFalse(models\Session::exists($session_id));
        $this->assertFalse(models\Token::exists($token));
    }

    public function testPerformKeepsCurrentSession()
    {
        $cleaner_job = new Cleaner();
        $days = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($days, 'days');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $session_id = $this->create('session', [
            'token' => $token,
        ]);

        $cleaner_job->perform();

        $this->assertTrue(models\Session::exists($session_id));
        $this->assertTrue(models\Token::exists($token));
    }

    public function testPerformDeletesOldInvalidatedUsers()
    {
        $this->freeze($this->fake('dateTime'));
        $cleaner_job = new Cleaner();
        $months = $this->fake('numberBetween', 7, 24);
        $created_at = \Minz\Time::ago($months, 'months');
        $user_id = $this->create('user', [
            'created_at' => $created_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => null,
        ]);

        $cleaner_job->perform();

        $this->assertFalse(models\User::exists($user_id));
    }

    public function testPerformKeepsOldValidatedUsers()
    {
        $this->freeze($this->fake('dateTime'));
        $cleaner_job = new Cleaner();
        $months = $this->fake('numberBetween', 7, 24);
        $created_at = \Minz\Time::ago($months, 'months');
        $user_id = $this->create('user', [
            'created_at' => $created_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $cleaner_job->perform();

        $this->assertTrue(models\User::exists($user_id));
    }

    public function testPerformKeepsRecentInvalidatedUsers()
    {
        $this->freeze($this->fake('dateTime'));
        $cleaner_job = new Cleaner();
        $months = $this->fake('numberBetween', 0, 6);
        $created_at = \Minz\Time::ago($months, 'months');
        $user_id = $this->create('user', [
            'created_at' => $created_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => null,
        ]);

        $cleaner_job->perform();

        $this->assertTrue(models\User::exists($user_id));
    }

    public function testPerformDeletesOldEnoughUnfollowedFeeds()
    {
        $this->freeze($this->fake('dateTime'));
        $cleaner_job = new Cleaner();
        $support_user = models\User::supportUser();
        $days = $this->fake('numberBetween', 8, 100);
        $created_at = \Minz\Time::ago($days, 'days');
        $collection_id = $this->create('collection', [
            'created_at' => $created_at->format(\Minz\Model::DATETIME_FORMAT),
            'user_id' => $support_user->id,
        ]);

        $cleaner_job->perform();

        $this->assertFalse(models\Collection::exists($collection_id));
    }

    public function testPerformKeepsOldEnoughFollowedFeeds()
    {
        $this->freeze($this->fake('dateTime'));
        $cleaner_job = new Cleaner();
        $support_user = models\User::supportUser();
        $user_id = $this->create('user');
        $days = $this->fake('numberBetween', 8, 100);
        $created_at = \Minz\Time::ago($days, 'days');
        $collection_id = $this->create('collection', [
            'created_at' => $created_at->format(\Minz\Model::DATETIME_FORMAT),
            'user_id' => $support_user->id,
        ]);
        $this->create('followed_collection', [
            'collection_id' => $collection_id,
            'user_id' => $user_id,
        ]);

        $cleaner_job->perform();

        $this->assertTrue(models\Collection::exists($collection_id));
    }

    public function testPerformKeepsRecentUnfollowedFeeds()
    {
        $this->freeze($this->fake('dateTime'));
        $cleaner_job = new Cleaner();
        $support_user = models\User::supportUser();
        $days = $this->fake('numberBetween', 0, 7);
        $created_at = \Minz\Time::ago($days, 'days');
        $collection_id = $this->create('collection', [
            'created_at' => $created_at->format(\Minz\Model::DATETIME_FORMAT),
            'user_id' => $support_user->id,
        ]);

        $cleaner_job->perform();

        $this->assertTrue(models\Collection::exists($collection_id));
    }

    public function testPerformDeletesOldEnoughNotStoredLinks()
    {
        $this->freeze($this->fake('dateTime'));
        $cleaner_job = new Cleaner();
        $support_user = models\User::supportUser();
        $days = $this->fake('numberBetween', 8, 100);
        $created_at = \Minz\Time::ago($days, 'days');
        $link_id = $this->create('link', [
            'created_at' => $created_at->format(\Minz\Model::DATETIME_FORMAT),
            'user_id' => $support_user->id,
        ]);

        $cleaner_job->perform();

        $this->assertFalse(models\Link::exists($link_id));
    }

    public function testPerformKeepsOldEnoughStoredLinks()
    {
        $this->freeze($this->fake('dateTime'));
        $cleaner_job = new Cleaner();
        $support_user = models\User::supportUser();
        $days = $this->fake('numberBetween', 8, 100);
        $created_at = \Minz\Time::ago($days, 'days');
        $link_id = $this->create('link', [
            'created_at' => $created_at->format(\Minz\Model::DATETIME_FORMAT),
            'user_id' => $support_user->id,
        ]);
        $collection_id = $this->create('collection');
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $cleaner_job->perform();

        $this->assertTrue(models\Link::exists($link_id));
    }

    public function testPerformKeepsRecentNotStoredLinks()
    {
        $this->freeze($this->fake('dateTime'));
        $cleaner_job = new Cleaner();
        $support_user = models\User::supportUser();
        $days = $this->fake('numberBetween', 0, 7);
        $created_at = \Minz\Time::ago($days, 'days');
        $link_id = $this->create('link', [
            'created_at' => $created_at->format(\Minz\Model::DATETIME_FORMAT),
            'user_id' => $support_user->id,
        ]);

        $cleaner_job->perform();

        $this->assertTrue(models\Link::exists($link_id));
    }

    public function testPerformKeepsOldEnoughNotStoredLinksIfNotOwnedBySupportUser()
    {
        $this->freeze($this->fake('dateTime'));
        $cleaner_job = new Cleaner();
        $user_id = $this->create('user');
        $days = $this->fake('numberBetween', 8, 100);
        $created_at = \Minz\Time::ago($days, 'days');
        $link_id = $this->create('link', [
            'created_at' => $created_at->format(\Minz\Model::DATETIME_FORMAT),
            'user_id' => $user_id,
        ]);

        $cleaner_job->perform();

        $this->assertTrue(models\Link::exists($link_id));
    }

    public function testPerformDeletesOldEnoughFeedsLinks()
    {
        \Minz\Configuration::$application['feeds_links_keep_period'] = 6;
        $this->freeze($this->fake('dateTime'));
        $cleaner_job = new Cleaner();
        $support_user = models\User::supportUser();
        $months = $this->fake('numberBetween', 7, 100);
        $published_at = \Minz\Time::ago($months, 'months');
        $link_id = $this->create('link', [
            'user_id' => $support_user->id,
        ]);
        $collection_id = $this->create('collection', [
            'type' => 'feed',
            'user_id' => $support_user->id,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
            'created_at' => $published_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        // follow the feed, otherwise the cleaner may delete it
        $this->create('followed_collection', [
            'collection_id' => $collection_id,
        ]);

        $cleaner_job->perform();

        \Minz\Configuration::$application['feeds_links_keep_period'] = 0;

        $this->assertFalse(models\Link::exists($link_id));
    }

    public function testPerformKeepsRecentFeedsLinks()
    {
        \Minz\Configuration::$application['feeds_links_keep_period'] = 6;
        $this->freeze($this->fake('dateTime'));
        $cleaner_job = new Cleaner();
        $support_user = models\User::supportUser();
        $months = $this->fake('numberBetween', 0, 6);
        $published_at = \Minz\Time::ago($months, 'months');
        $link_id = $this->create('link', [
            'user_id' => $support_user->id,
        ]);
        $collection_id = $this->create('collection', [
            'type' => 'feed',
            'user_id' => $support_user->id,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
            'created_at' => $published_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        // follow the feed, otherwise the cleaner may delete it
        $this->create('followed_collection', [
            'collection_id' => $collection_id,
        ]);

        $cleaner_job->perform();

        \Minz\Configuration::$application['feeds_links_keep_period'] = 0;

        $this->assertTrue(models\Link::exists($link_id));
    }

    public function testPerformDeletesDataIfDemoIsEnabled()
    {
        \Minz\Configuration::$application['demo'] = true;
        $cleaner_job = new Cleaner();
        $days = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($days, 'days');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user', [
            'validation_token' => $token,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user_id,
        ]);

        $cleaner_job->perform();

        \Minz\Configuration::$application['demo'] = false;

        $this->assertSame(2, models\User::count()); // count the support and demo users
        $this->assertGreaterThan(0, models\Collection::count());
        $this->assertSame(0, models\Token::count());
        $this->assertSame(0, models\Link::count());
        $user = models\User::take(1);
        $this->assertNotSame($user_id, $user->id);
        $this->assertSame('demo@flus.io', $user->email);
        $this->assertTrue($user->verifyPassword('demo'));
        $this->assertFalse(models\Collection::exists($collection_id));
        $bookmarks = models\Collection::findBy([
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $news = models\Collection::findBy([
            'user_id' => $user->id,
            'type' => 'news',
        ]);
        $read_list = models\Collection::findBy([
            'user_id' => $user->id,
            'type' => 'read',
        ]);
        $never_list = models\Collection::findBy([
            'user_id' => $user->id,
            'type' => 'never',
        ]);
        $this->assertNotNull($bookmarks);
        $this->assertNotNull($news);
        $this->assertNotNull($read_list);
        $this->assertNotNull($never_list);
    }

    public function testPerformKeepsDataIfDemoIsDisabled()
    {
        \Minz\Configuration::$application['demo'] = false;
        $cleaner_job = new Cleaner();
        $days = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($days, 'days');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user', [
            'validation_token' => $token,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user_id,
        ]);

        $cleaner_job->perform();

        $this->assertSame(2, models\User::count()); // include the support user
        $this->assertSame(1, models\Collection::count());
        $this->assertSame(1, models\Token::count());
        $this->assertSame(1, models\Link::count());
        $this->assertTrue(models\User::exists($user_id));
        $this->assertTrue(models\Collection::exists($collection_id));
        $this->assertTrue(models\Link::exists($link_id));
        $this->assertTrue(models\Token::exists($token));
    }
}
