<?php

namespace App\jobs\scheduled;

use tests\factories\UserFactory;

class InactivityNotifierTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\MailerAsserts;
    use \Minz\Tests\TimeHelper;
    use \tests\FakerHelper;
    use \tests\HttpHelper;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function initEngine(): void
    {
        $router = \App\Router::load();
        \Minz\Engine::init($router);
    }

    #[\PHPUnit\Framework\Attributes\After]
    public function resetSubscriptionConfiguration(): void
    {
        \App\Configuration::$application['subscriptions_enabled'] = false;
    }

    public function testQueue(): void
    {
        $job = new InactivityNotifier();

        $this->assertSame('default', $job->queue);
    }

    public function testSchedule(): void
    {
        $job = new InactivityNotifier();

        $this->assertSame('+1 day', $job->frequency);
    }

    public function testInstall(): void
    {
        \App\Configuration::$jobs_adapter = 'database';

        $this->assertSame(0, \Minz\Job::count());

        InactivityNotifier::install();

        \App\Configuration::$jobs_adapter = 'test';

        $this->assertSame(1, \Minz\Job::count());
    }

    public function testPerformNotifiesUserAboutInactivity(): void
    {
        $this->freeze();
        $now = \Minz\Time::now();
        $job = new InactivityNotifier();
        $inactivity_months = 11;
        $notified_at = null;
        $validated_at = \Minz\Time::ago(1, 'year');
        $user = UserFactory::create([
            'last_activity_at' => \Minz\Time::ago($inactivity_months, 'months'),
            'deletion_notified_at' => $notified_at,
            'validated_at' => $validated_at,
        ]);

        $job->perform();

        $user = $user->reload();
        $this->assertSame($now->getTimestamp(), $user->deletion_notified_at?->getTimestamp());
        $this->assertEmailsCount(1);
        $email_sent = \Minz\Tests\Mailer::take();
        $this->assertNotNull($email_sent);
        $this->assertEmailSubject($email_sent, '[Flus] Your account will be deleted soon due to inactivity');
        $this->assertEmailContainsTo($email_sent, $user->email);
        $this->assertEmailContainsBody(
            $email_sent,
            'You receive this email because you haven’t been active on Flus for several months.'
        );
    }

    public function testPerformDoesNotSendEmailToNotValidatedUsers(): void
    {
        $this->freeze();
        $now = \Minz\Time::now();
        $job = new InactivityNotifier();
        $inactivity_months = 11;
        $notified_at = null;
        $validated_at = null;
        $user = UserFactory::create([
            'last_activity_at' => \Minz\Time::ago($inactivity_months, 'months'),
            'deletion_notified_at' => $notified_at,
            'validated_at' => $validated_at,
        ]);

        $job->perform();

        $user = $user->reload();
        $this->assertSame($now->getTimestamp(), $user->deletion_notified_at?->getTimestamp());
        $this->assertEmailsCount(0);
    }

    public function testPerformIgnoresAlreadyNotifiedUsers(): void
    {
        $this->freeze();
        $now = \Minz\Time::now();
        $job = new InactivityNotifier();
        $inactivity_months = 11;
        $notified_at = \Minz\Time::ago(1, 'month');
        $validated_at = \Minz\Time::ago(1, 'year');
        $user = UserFactory::create([
            'last_activity_at' => \Minz\Time::ago($inactivity_months, 'months'),
            'deletion_notified_at' => $notified_at,
            'validated_at' => $validated_at,
        ]);

        $job->perform();

        $user = $user->reload();
        $this->assertSame($notified_at->getTimestamp(), $user->deletion_notified_at?->getTimestamp());
        $this->assertEmailsCount(0);
    }

    public function testPerformIgnoresNotYetInactive(): void
    {
        $this->freeze();
        $now = \Minz\Time::now();
        $job = new InactivityNotifier();
        $inactivity_months = 10;
        $notified_at = null;
        $validated_at = \Minz\Time::ago(1, 'year');
        $user = UserFactory::create([
            'last_activity_at' => \Minz\Time::ago($inactivity_months, 'months'),
            'deletion_notified_at' => $notified_at,
            'validated_at' => $validated_at,
        ]);

        $job->perform();

        $user = $user->reload();
        $this->assertNull($user->deletion_notified_at);
        $this->assertEmailsCount(0);
    }

    public function testPerformNotifiesUsersWithOverdueSubscription(): void
    {
        \App\Configuration::$application['subscriptions_enabled'] = true;
        $this->freeze();
        $now = \Minz\Time::now();
        $job = new InactivityNotifier();
        $inactivity_months = 11;
        $notified_at = null;
        $validated_at = \Minz\Time::ago(1, 'year');
        $subscription_expired_at = \Minz\Time::ago(1, 'month');
        $user = UserFactory::create([
            'last_activity_at' => \Minz\Time::ago($inactivity_months, 'months'),
            'deletion_notified_at' => $notified_at,
            'validated_at' => $validated_at,
            'subscription_expired_at' => $subscription_expired_at,
        ]);

        $job->perform();

        $user = $user->reload();
        $this->assertSame($now->getTimestamp(), $user->deletion_notified_at?->getTimestamp());
        $this->assertEmailsCount(1);
    }

    public function testPerformIgnoresUsersWithActiveSubscription(): void
    {
        \App\Configuration::$application['subscriptions_enabled'] = true;
        $this->freeze();
        $job = new InactivityNotifier();
        $inactivity_months = 11;
        $notified_at = null;
        $validated_at = \Minz\Time::ago(1, 'year');
        $subscription_expired_at = \Minz\Time::fromNow(1, 'month');
        $user = UserFactory::create([
            'last_activity_at' => \Minz\Time::ago($inactivity_months, 'months'),
            'deletion_notified_at' => $notified_at,
            'validated_at' => $validated_at,
            'subscription_expired_at' => $subscription_expired_at,
        ]);

        $job->perform();

        $user = $user->reload();
        $this->assertNull($user->deletion_notified_at);
        $this->assertEmailsCount(0);
    }

    public function testPerformIgnoresUsersWithExemptedSubscription(): void
    {
        \App\Configuration::$application['subscriptions_enabled'] = true;
        $this->freeze();
        $job = new InactivityNotifier();
        $inactivity_months = 11;
        $notified_at = null;
        $validated_at = \Minz\Time::ago(1, 'year');
        $subscription_expired_at = new \DateTimeImmutable('1970-01-01');
        $user = UserFactory::create([
            'last_activity_at' => \Minz\Time::ago($inactivity_months, 'months'),
            'deletion_notified_at' => $notified_at,
            'validated_at' => $validated_at,
            'subscription_expired_at' => $subscription_expired_at,
        ]);

        $job->perform();

        $user = $user->reload();
        $this->assertNull($user->deletion_notified_at);
        $this->assertEmailsCount(0);
    }
}
