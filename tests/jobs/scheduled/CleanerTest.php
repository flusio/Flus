<?php

namespace flusio\jobs\scheduled;

use flusio\models;
use tests\factories\CollectionFactory;
use tests\factories\FetchLogFactory;
use tests\factories\FollowedCollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\SessionFactory;
use tests\factories\TokenFactory;
use tests\factories\UserFactory;

class CleanerTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\TimeHelper;

    public function testQueue(): void
    {
        $cleaner_job = new Cleaner();

        $this->assertSame('default', $cleaner_job->queue);
    }

    public function testSchedule(): void
    {
        /** @var \DateTimeImmutable */
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

    public function testPerformDeletesFilesOutsideValidityInterval(): void
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

    public function testPerformKeepsFilesWithinValidityInterval(): void
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

    public function testPerformDeletesOldFetchLogs(): void
    {
        $cleaner_job = new Cleaner();
        /** @var int */
        $days = $this->fake('numberBetween', 4, 9000);
        $created_at = \Minz\Time::ago($days, 'days');
        $fetch_log = FetchLogFactory::create([
            'created_at' => $created_at,
        ]);

        $cleaner_job->perform();

        $this->assertFalse(models\FetchLog::exists($fetch_log->id));
    }

    public function testPerformKeepsFreshFetchLogs(): void
    {
        $cleaner_job = new Cleaner();
        // logs are kept up to 3 days, but the test can fail if $days = 3 and it takes too long to execute
        /** @var int */
        $days = $this->fake('numberBetween', 0, 2);
        $created_at = \Minz\Time::ago($days, 'days');
        $fetch_log = FetchLogFactory::create([
            'created_at' => $created_at,
        ]);

        $cleaner_job->perform();

        $this->assertTrue(models\FetchLog::exists($fetch_log->id));
    }

    public function testPerformDeletesExpiredSession(): void
    {
        $cleaner_job = new Cleaner();
        /** @var int */
        $days = $this->fake('numberBetween', 0, 9000);
        $expired_at = \Minz\Time::ago($days, 'days');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $session = SessionFactory::create([
            'token' => $token->token,
        ]);

        $cleaner_job->perform();

        $this->assertFalse(models\Session::exists($session->id));
        $this->assertFalse(models\Token::exists($token->token));
    }

    public function testPerformKeepsCurrentSession(): void
    {
        $cleaner_job = new Cleaner();
        /** @var int */
        $days = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($days, 'days');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $session = SessionFactory::create([
            'token' => $token->token,
        ]);

        $cleaner_job->perform();

        $this->assertTrue(models\Session::exists($session->id));
        $this->assertTrue(models\Token::exists($token->token));
    }

    public function testPerformDeletesOldInvalidatedUsers(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $cleaner_job = new Cleaner();
        /** @var int */
        $months = $this->fake('numberBetween', 7, 24);
        $created_at = \Minz\Time::ago($months, 'months');
        $user = UserFactory::create([
            'created_at' => $created_at,
            'validated_at' => null,
        ]);

        $cleaner_job->perform();

        $this->assertFalse(models\User::exists($user->id));
    }

    public function testPerformKeepsOldValidatedUsers(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $cleaner_job = new Cleaner();
        /** @var int */
        $months = $this->fake('numberBetween', 7, 24);
        $created_at = \Minz\Time::ago($months, 'months');
        $user = UserFactory::create([
            'created_at' => $created_at,
            'validated_at' => \Minz\Time::now(),
        ]);

        $cleaner_job->perform();

        $this->assertTrue(models\User::exists($user->id));
    }

    public function testPerformKeepsRecentInvalidatedUsers(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $cleaner_job = new Cleaner();
        /** @var int */
        $months = $this->fake('numberBetween', 0, 6);
        $created_at = \Minz\Time::ago($months, 'months');
        $user = UserFactory::create([
            'created_at' => $created_at,
            'validated_at' => null,
        ]);

        $cleaner_job->perform();

        $this->assertTrue(models\User::exists($user->id));
    }

    public function testPerformDeletesOldEnoughUnfollowedFeeds(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $cleaner_job = new Cleaner();
        $support_user = models\User::supportUser();
        /** @var int */
        $days = $this->fake('numberBetween', 8, 100);
        $created_at = \Minz\Time::ago($days, 'days');
        $collection = CollectionFactory::create([
            'created_at' => $created_at,
            'user_id' => $support_user->id,
        ]);

        $cleaner_job->perform();

        $this->assertFalse(models\Collection::exists($collection->id));
    }

    public function testPerformKeepsOldEnoughFollowedFeeds(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $cleaner_job = new Cleaner();
        $support_user = models\User::supportUser();
        $user = UserFactory::create();
        /** @var int */
        $days = $this->fake('numberBetween', 8, 100);
        $created_at = \Minz\Time::ago($days, 'days');
        $collection = CollectionFactory::create([
            'created_at' => $created_at,
            'user_id' => $support_user->id,
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);

        $cleaner_job->perform();

        $this->assertTrue(models\Collection::exists($collection->id));
    }

    public function testPerformKeepsRecentUnfollowedFeeds(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $cleaner_job = new Cleaner();
        $support_user = models\User::supportUser();
        /** @var int */
        $days = $this->fake('numberBetween', 0, 7);
        $created_at = \Minz\Time::ago($days, 'days');
        $collection = CollectionFactory::create([
            'created_at' => $created_at,
            'user_id' => $support_user->id,
        ]);

        $cleaner_job->perform();

        $this->assertTrue(models\Collection::exists($collection->id));
    }

    public function testPerformDeletesOldEnoughNotStoredLinks(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $cleaner_job = new Cleaner();
        $support_user = models\User::supportUser();
        /** @var int */
        $days = $this->fake('numberBetween', 8, 100);
        $created_at = \Minz\Time::ago($days, 'days');
        $link = LinkFactory::create([
            'created_at' => $created_at,
            'user_id' => $support_user->id,
        ]);

        $cleaner_job->perform();

        $this->assertFalse(models\Link::exists($link->id));
    }

    public function testPerformKeepsOldEnoughStoredLinks(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $cleaner_job = new Cleaner();
        $support_user = models\User::supportUser();
        /** @var int */
        $days = $this->fake('numberBetween', 8, 100);
        $created_at = \Minz\Time::ago($days, 'days');
        $link = LinkFactory::create([
            'created_at' => $created_at,
            'user_id' => $support_user->id,
        ]);
        $collection = CollectionFactory::create();
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);

        $cleaner_job->perform();

        $this->assertTrue(models\Link::exists($link->id));
    }

    public function testPerformKeepsRecentNotStoredLinks(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $cleaner_job = new Cleaner();
        $support_user = models\User::supportUser();
        /** @var int */
        $days = $this->fake('numberBetween', 0, 7);
        $created_at = \Minz\Time::ago($days, 'days');
        $link = LinkFactory::create([
            'created_at' => $created_at,
            'user_id' => $support_user->id,
        ]);

        $cleaner_job->perform();

        $this->assertTrue(models\Link::exists($link->id));
    }

    public function testPerformKeepsOldEnoughNotStoredLinksIfNotOwnedBySupportUser(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $cleaner_job = new Cleaner();
        $user = UserFactory::create();
        /** @var int */
        $days = $this->fake('numberBetween', 8, 100);
        $created_at = \Minz\Time::ago($days, 'days');
        $link = LinkFactory::create([
            'created_at' => $created_at,
            'user_id' => $user->id,
        ]);

        $cleaner_job->perform();

        $this->assertTrue(models\Link::exists($link->id));
    }

    public function testPerformDeletesFeedsLinksInExcess(): void
    {
        \Minz\Configuration::$application['feeds_links_keep_maximum'] = 1;
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $cleaner_job = new Cleaner();
        $support_user = models\User::supportUser();
        $published_at_1 = \Minz\Time::ago(1, 'months');
        $published_at_2 = \Minz\Time::ago(2, 'months');
        $link_1 = LinkFactory::create([
            'user_id' => $support_user->id,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $support_user->id,
        ]);
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'user_id' => $support_user->id,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link_1->id,
            'collection_id' => $collection->id,
            'created_at' => $published_at_1,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link_2->id,
            'collection_id' => $collection->id,
            'created_at' => $published_at_2,
        ]);
        // follow the feed, otherwise the cleaner may delete it
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
        ]);

        $cleaner_job->perform();

        \Minz\Configuration::$application['feeds_links_keep_maximum'] = 0;

        $this->assertTrue(models\Link::exists($link_1->id));
        $this->assertFalse(models\Link::exists($link_2->id));
    }

    public function testPerformDeletesOldEnoughFeedsLinks(): void
    {
        \Minz\Configuration::$application['feeds_links_keep_period'] = 6;
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $cleaner_job = new Cleaner();
        $support_user = models\User::supportUser();
        /** @var int */
        $months = $this->fake('numberBetween', 7, 100);
        $published_at = \Minz\Time::ago($months, 'months');
        $link = LinkFactory::create([
            'user_id' => $support_user->id,
        ]);
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'user_id' => $support_user->id,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
            'created_at' => $published_at,
        ]);
        // follow the feed, otherwise the cleaner may delete it
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
        ]);

        $cleaner_job->perform();

        \Minz\Configuration::$application['feeds_links_keep_period'] = 0;

        $this->assertFalse(models\Link::exists($link->id));
    }

    public function testPerformKeepsAllFeedsLinksIfMaximumIsZero(): void
    {
        \Minz\Configuration::$application['feeds_links_keep_maximum'] = 0;
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $cleaner_job = new Cleaner();
        $support_user = models\User::supportUser();
        $published_at_1 = \Minz\Time::ago(1, 'months');
        $published_at_2 = \Minz\Time::ago(2, 'months');
        $link_1 = LinkFactory::create([
            'user_id' => $support_user->id,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $support_user->id,
        ]);
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'user_id' => $support_user->id,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link_1->id,
            'collection_id' => $collection->id,
            'created_at' => $published_at_1,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link_2->id,
            'collection_id' => $collection->id,
            'created_at' => $published_at_2,
        ]);
        // follow the feed, otherwise the cleaner may delete it
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
        ]);

        $cleaner_job->perform();

        $this->assertTrue(models\Link::exists($link_1->id));
        $this->assertTrue(models\Link::exists($link_2->id));
    }

    public function testPerformKeepsRecentFeedsLinks(): void
    {
        \Minz\Configuration::$application['feeds_links_keep_period'] = 6;
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $cleaner_job = new Cleaner();
        $support_user = models\User::supportUser();
        /** @var int */
        $months = $this->fake('numberBetween', 0, 6);
        $published_at = \Minz\Time::ago($months, 'months');
        $link = LinkFactory::create([
            'user_id' => $support_user->id,
        ]);
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'user_id' => $support_user->id,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
            'created_at' => $published_at,
        ]);
        // follow the feed, otherwise the cleaner may delete it
        FollowedCollectionFactory::create([
            'collection_id' => $collection->id,
        ]);

        $cleaner_job->perform();

        \Minz\Configuration::$application['feeds_links_keep_period'] = 0;

        $this->assertTrue(models\Link::exists($link->id));
    }

    public function testPerformKeepsOldEnoughFeedsLinksInMinimumLimit(): void
    {
        \Minz\Configuration::$application['feeds_links_keep_period'] = 6;
        \Minz\Configuration::$application['feeds_links_keep_minimum'] = 1;
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $cleaner_job = new Cleaner();
        $support_user = models\User::supportUser();
        /** @var int */
        $months = $this->fake('numberBetween', 7, 100);
        $published_at_old = \Minz\Time::ago($months, 'months');
        $published_at_older = \Minz\Time::ago($months + 1, 'months');
        $link_1 = LinkFactory::create([
            'user_id' => $support_user->id,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $support_user->id,
        ]);
        $link_3 = LinkFactory::create([
            'user_id' => $support_user->id,
        ]);
        $collection_1 = CollectionFactory::create([
            'type' => 'feed',
            'user_id' => $support_user->id,
        ]);
        $collection_2 = CollectionFactory::create([
            'type' => 'feed',
            'user_id' => $support_user->id,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link_1->id,
            'collection_id' => $collection_1->id,
            'created_at' => $published_at_older,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link_2->id,
            'collection_id' => $collection_1->id,
            'created_at' => $published_at_old,
        ]);
        // Attach the last link to another collection to check that the
        // deletion is done per feed and not globally.
        LinkToCollectionFactory::create([
            'link_id' => $link_3->id,
            'collection_id' => $collection_2->id,
            'created_at' => $published_at_older,
        ]);
        // follow the feeds, otherwise the cleaner may delete them
        FollowedCollectionFactory::create([
            'collection_id' => $collection_1->id,
        ]);
        FollowedCollectionFactory::create([
            'collection_id' => $collection_2->id,
        ]);

        $cleaner_job->perform();

        \Minz\Configuration::$application['feeds_links_keep_period'] = 0;
        \Minz\Configuration::$application['feeds_links_keep_minimum'] = 0;

        $this->assertSame(2, models\Link::count());
        $this->assertFalse(models\Link::exists($link_1->id));
        $this->assertTrue(models\Link::exists($link_2->id));
        $this->assertTrue(models\Link::exists($link_3->id));
    }

    public function testPerformDeletesDataIfDemoIsEnabled(): void
    {
        \Minz\Configuration::$application['demo'] = true;
        $cleaner_job = new Cleaner();
        /** @var int */
        $days = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($days, 'days');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $user = UserFactory::create([
            'validation_token' => $token->token,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $cleaner_job->perform();

        \Minz\Configuration::$application['demo'] = false;

        $this->assertSame(2, models\User::count()); // count the support and demo users
        $this->assertGreaterThan(0, models\Collection::count());
        $this->assertSame(0, models\Token::count());
        $this->assertSame(0, models\Link::count());
        $demo_user = models\User::take(1);
        $this->assertNotNull($demo_user);
        $this->assertNotSame($user->id, $demo_user->id);
        $this->assertSame('demo@flus.io', $demo_user->email);
        $this->assertTrue($demo_user->verifyPassword('demo'));
        $this->assertFalse(models\Collection::exists($collection->id));
        $bookmarks = models\Collection::findBy([
            'user_id' => $demo_user->id,
            'type' => 'bookmarks',
        ]);
        $news = models\Collection::findBy([
            'user_id' => $demo_user->id,
            'type' => 'news',
        ]);
        $read_list = models\Collection::findBy([
            'user_id' => $demo_user->id,
            'type' => 'read',
        ]);
        $never_list = models\Collection::findBy([
            'user_id' => $demo_user->id,
            'type' => 'never',
        ]);
        $this->assertNotNull($bookmarks);
        $this->assertNotNull($news);
        $this->assertNotNull($read_list);
        $this->assertNotNull($never_list);
    }

    public function testPerformKeepsDataIfDemoIsDisabled(): void
    {
        \Minz\Configuration::$application['demo'] = false;
        $cleaner_job = new Cleaner();
        /** @var int */
        $days = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($days, 'days');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $user = UserFactory::create([
            'validation_token' => $token->token,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $cleaner_job->perform();

        $this->assertSame(2, models\User::count()); // include the support user
        $this->assertSame(1, models\Collection::count());
        $this->assertSame(1, models\Token::count());
        $this->assertSame(1, models\Link::count());
        $this->assertTrue(models\User::exists($user->id));
        $this->assertTrue(models\Collection::exists($collection->id));
        $this->assertTrue(models\Link::exists($link->id));
        $this->assertTrue(models\Token::exists($token->token));
    }
}
