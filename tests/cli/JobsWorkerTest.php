<?php

namespace flusio\cli;

use flusio\jobs;
use flusio\models;

class JobsWorkerTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\MailerAsserts;
    use \Minz\Tests\ResponseAsserts;
    use \Minz\Tests\TimeHelper;

    /**
     * @beforeClass
     */
    public static function loadApplication()
    {
        self::$application = new \flusio\cli\Application();
    }

    public function testIndexRendersCorrectly()
    {
        $job_dao = new models\dao\Job();
        $job_perform_at_1 = substr(str_replace('T', ' ', $this->fake('iso8601')), 0, -2);
        $job_attempts_1 = $this->fake('numberBetween', 0, 100);
        $job_name_1 = $this->fake('word');
        $job_id_1 = $this->create('job', [
            'name' => $job_name_1,
            'perform_at' => $job_perform_at_1,
            'locked_at' => $this->fake('iso8601'),
            'frequency' => '+15 seconds',
            'number_attempts' => $job_attempts_1,
            'handler' => json_encode([
                'job_class' => 'flusio\jobs\Mailer',
                'job_args' => [],
            ]),
        ]);
        $job_perform_at_2 = substr(str_replace('T', ' ', $this->fake('iso8601')), 0, -2);
        $job_attempts_2 = $this->fake('numberBetween', 0, 100);
        $job_name_2 = $this->fake('word');
        $job_id_2 = $this->create('job', [
            'name' => $job_name_2,
            'perform_at' => $job_perform_at_2,
            'locked_at' => null,
            'number_attempts' => $job_attempts_2,
            'handler' => json_encode([
                'job_class' => 'flusio\jobs\Mailer',
                'job_args' => [],
            ]),
        ]);
        $job_perform_at_3 = substr(str_replace('T', ' ', $this->fake('iso8601')), 0, -2);
        $job_attempts_3 = $this->fake('numberBetween', 0, 100);
        $job_name_3 = $this->fake('word');
        $job_id_3 = $this->create('job', [
            'name' => $job_name_3,
            'perform_at' => $job_perform_at_3,
            'locked_at' => null,
            'failed_at' => $this->fake('iso8601'),
            'number_attempts' => $job_attempts_3,
            'handler' => json_encode([
                'job_class' => 'flusio\jobs\Mailer',
                'job_args' => [],
            ]),
        ]);
        $job_perform_at_4 = substr(str_replace('T', ' ', $this->fake('iso8601')), 0, -2);
        $job_attempts_4 = $this->fake('numberBetween', 0, 100);
        $job_name_4 = $this->fake('word');
        $job_id_4 = $this->create('job', [
            'name' => $job_name_4,
            'perform_at' => $job_perform_at_4,
            'locked_at' => $this->fake('iso8601'),
            'failed_at' => $this->fake('iso8601'),
            'number_attempts' => $job_attempts_4,
            'handler' => json_encode([
                'job_class' => 'flusio\jobs\Mailer',
                'job_args' => [],
            ]),
        ]);

        $response = $this->appRun('cli', '/jobs');

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, <<<TEXT
        job {$job_id_1} ({$job_name_1}) scheduled each +15 seconds, next at {$job_perform_at_1} (locked)
        job {$job_id_2} ({$job_name_2}) at {$job_perform_at_2} {$job_attempts_2} attempts
        job {$job_id_3} ({$job_name_3}) at {$job_perform_at_3} {$job_attempts_3} attempts (failed)
        job {$job_id_4} ({$job_name_4}) at {$job_perform_at_4} {$job_attempts_4} attempts (locked) (failed)
        TEXT);
    }

    public function testInstallRendersCorrectlyAndInstallsTheJobs()
    {
        $job_dao = new models\dao\Job();

        \Minz\Configuration::$application['job_adapter'] = 'database';

        $response = $this->appRun('cli', '/jobs/install');

        \Minz\Configuration::$application['job_adapter'] = 'test';

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Jobs installed.');
        $this->assertSame(1, $job_dao->countBy(['name' => 'flusio\\jobs\\scheduled\\FeedsSync']));
        $this->assertSame(1, $job_dao->countBy(['name' => 'flusio\\jobs\\scheduled\\LinksSync']));
        $this->assertSame(1, $job_dao->countBy(['name' => 'flusio\\jobs\\scheduled\\Cleaner']));
        $this->assertSame(0, $job_dao->countBy(['name' => 'flusio\\jobs\\scheduled\\SubscriptionsSync']));
    }

    public function testInstallInstallsSubscriptionsSyncIfEnabled()
    {
        $job_dao = new models\dao\Job();

        \Minz\Configuration::$application['subscriptions_enabled'] = true;
        \Minz\Configuration::$application['job_adapter'] = 'database';

        $response = $this->appRun('cli', '/jobs/install');

        \Minz\Configuration::$application['subscriptions_enabled'] = false;
        \Minz\Configuration::$application['job_adapter'] = 'test';

        $this->assertResponseCode($response, 200);
        $this->assertSame(1, $job_dao->countBy(['name' => 'flusio\\jobs\\scheduled\\SubscriptionsSync']));
    }

    public function testUnlockRemovesLockAndRendersCorrectly()
    {
        $job_dao = new models\dao\Job();
        $job_id = $this->create('job', [
            'locked_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('cli', '/jobs/unlock', [
            'id' => $job_id,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "job {$job_id} lock has been released");
        $db_job = $job_dao->find($job_id);
        $this->assertNull($db_job['locked_at']);
    }

    public function testUnlockAcknowledgesIfNotLocked()
    {
        $job_dao = new models\dao\Job();
        $job_id = $this->create('job', [
            'locked_at' => null,
        ]);

        $response = $this->appRun('cli', '/jobs/unlock', [
            'id' => $job_id,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "job {$job_id} was not locked");
        $db_job = $job_dao->find($job_id);
        $this->assertNull($db_job['locked_at']);
    }

    public function testRunExecutesAJobAndRendersCorrectly()
    {
        $job_dao = new models\dao\Job();
        $token = $this->create('token');
        $user_id = $this->create('user', [
            'validation_token' => $token,
        ]);
        $job_id = $this->create('job', [
            'perform_at' => \Minz\Time::ago(1, 'second')->format(\Minz\Model::DATETIME_FORMAT),
            'locked_at' => null,
            'number_attempts' => $this->fake('numberBetween', 0, 25),
            'handler' => json_encode([
                'job_class' => 'flusio\jobs\Mailer',
                'job_args' => ['Users', 'sendAccountValidationEmail', $user_id],
            ]),
        ]);

        $this->assertEmailsCount(0);
        $this->assertSame(1, $job_dao->count());

        $response = $this->appRun('cli', '/jobs/run');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, "job#{$job_id} (Mailer): done");
        $this->assertEmailsCount(1);
        $this->assertSame(0, $job_dao->count());
    }

    public function testRunSelectsOnlyJobsWithGivenQueue()
    {
        $job_dao = new models\dao\Job();
        $token = $this->create('token');
        $user_id = $this->create('user', [
            'validation_token' => $token,
        ]);
        $queue_1 = $this->fakeUnique('word');
        $queue_2 = $this->fakeUnique('word');
        $job_id_1 = $this->create('job', [
            'perform_at' => \Minz\Time::ago(10, 'seconds')->format(\Minz\Model::DATETIME_FORMAT),
            'locked_at' => null,
            'number_attempts' => $this->fake('numberBetween', 0, 25),
            'queue' => $queue_1,
            'handler' => json_encode([
                'job_class' => 'flusio\jobs\Mailer',
                'job_args' => ['Users', 'sendAccountValidationEmail', $user_id],
            ]),
        ]);
        $job_id_2 = $this->create('job', [
            'perform_at' => \Minz\Time::ago(5, 'seconds')->format(\Minz\Model::DATETIME_FORMAT),
            'locked_at' => null,
            'number_attempts' => $this->fake('numberBetween', 0, 25),
            'queue' => $queue_2,
            'handler' => json_encode([
                'job_class' => 'flusio\jobs\Mailer',
                'job_args' => ['Users', 'sendAccountValidationEmail', $user_id],
            ]),
        ]);

        $this->assertEmailsCount(0);
        $this->assertSame(2, $job_dao->count());

        $response = $this->appRun('cli', '/jobs/run', [
            'queue' => $queue_2,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, "job#{$job_id_2} (Mailer): done");
        $this->assertSame(1, $job_dao->count());
        $this->assertTrue($job_dao->exists($job_id_1));
    }

    public function testRunSelectsJobsWithGivenQueueByRtrimingNumbers()
    {
        $job_dao = new models\dao\Job();
        $token = $this->create('token');
        $user_id = $this->create('user', [
            'validation_token' => $token,
        ]);
        $queue = $this->fake('word');
        $job_id = $this->create('job', [
            'perform_at' => \Minz\Time::ago(10, 'seconds')->format(\Minz\Model::DATETIME_FORMAT),
            'locked_at' => null,
            'number_attempts' => $this->fake('numberBetween', 0, 25),
            'queue' => $queue,
            'handler' => json_encode([
                'job_class' => 'flusio\jobs\Mailer',
                'job_args' => ['Users', 'sendAccountValidationEmail', $user_id],
            ]),
        ]);

        $this->assertSame(1, $job_dao->count());

        $response = $this->appRun('cli', '/jobs/run', [
            'queue' => $queue . $this->fake('randomNumber'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, "job#{$job_id} (Mailer): done");
        $this->assertSame(0, $job_dao->count());
    }

    public function testRunDoesNotSelectJobToBeExecutedLater()
    {
        $job_dao = new models\dao\Job();
        $token = $this->create('token');
        $user_id = $this->create('user', [
            'validation_token' => $token,
        ]);
        $job_id = $this->create('job', [
            'perform_at' => \Minz\Time::fromNow(1, 'minute')->format(\Minz\Model::DATETIME_FORMAT),
            'locked_at' => null,
            'number_attempts' => $this->fake('numberBetween', 0, 25),
            'handler' => json_encode([
                'job_class' => 'flusio\jobs\Mailer',
                'job_args' => ['Users', 'sendAccountValidationEmail', $user_id],
            ]),
        ]);

        $response = $this->appRun('cli', '/jobs/run');

        $this->assertResponseCode($response, 204);
        $this->assertEmailsCount(0);
        $this->assertSame(1, $job_dao->count());
    }

    public function testRunDoesNotSelectLockedJob()
    {
        $this->freeze($this->fake('dateTime'));
        $job_dao = new models\dao\Job();
        $token = $this->create('token');
        $user_id = $this->create('user', [
            'validation_token' => $token,
        ]);
        $minutes = $this->fake('numberBetween', 0, 59);
        $locked_at = \Minz\Time::ago($minutes, 'minutes');
        $job_id = $this->create('job', [
            'perform_at' => \Minz\Time::ago(1, 'second')->format(\Minz\Model::DATETIME_FORMAT),
            'locked_at' => $locked_at->format(\Minz\Model::DATETIME_FORMAT),
            'number_attempts' => $this->fake('numberBetween', 0, 25),
            'handler' => json_encode([
                'job_class' => 'flusio\jobs\Mailer',
                'job_args' => ['Users', 'sendAccountValidationEmail', $user_id],
            ]),
        ]);

        $response = $this->appRun('cli', '/jobs/run');

        $this->assertResponseCode($response, 204);
        $this->assertEmailsCount(0);
        $this->assertSame(1, $job_dao->count());
    }

    public function testRunDoesNotSelectJobWithTooManyAttempts()
    {
        $job_dao = new models\dao\Job();
        $token = $this->create('token');
        $user_id = $this->create('user', [
            'validation_token' => $token,
        ]);
        $job_id = $this->create('job', [
            'perform_at' => \Minz\Time::ago(1, 'second')->format(\Minz\Model::DATETIME_FORMAT),
            'locked_at' => null,
            'number_attempts' => $this->fake('numberBetween', 26, 100),
            'handler' => json_encode([
                'job_class' => 'flusio\jobs\Mailer',
                'job_args' => ['Users', 'sendAccountValidationEmail', $user_id],
            ]),
        ]);

        $response = $this->appRun('cli', '/jobs/run');

        $this->assertResponseCode($response, 204);
        $this->assertEmailsCount(0);
        $this->assertSame(1, $job_dao->count());
    }

    public function testRunKeepsFailingJobs()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $job_dao = new models\dao\Job();
        $number_attempts = $this->fake('numberBetween', 0, 25);
        $job_id = $this->create('job', [
            'perform_at' => \Minz\Time::ago(1, 'second')->format(\Minz\Model::DATETIME_FORMAT),
            'locked_at' => null,
            'number_attempts' => $number_attempts,
            'handler' => json_encode([
                'job_class' => 'tests\jobs\MyFailingJob',
                'job_args' => [],
            ]),
        ]);

        $response = $this->appRun('cli', '/jobs/run');

        $this->assertResponseCode($response, 500);
        $this->assertResponseContains($response, "job#{$job_id} (tests\\jobs\\MyFailingJob): failed");
        $this->assertSame(1, $job_dao->count());
        $db_job = $job_dao->find($job_id);
        $expected_perform_at = \Minz\Time::fromNow(5 + pow($number_attempts + 1, 4), 'seconds');
        $perform_at = date_create_from_format(\Minz\Model::DATETIME_FORMAT, $db_job['perform_at']);
        $failed_at = date_create_from_format(\Minz\Model::DATETIME_FORMAT, $db_job['failed_at']);
        $this->assertNull($db_job['locked_at']);
        $this->assertSame($number_attempts + 1, $db_job['number_attempts']);
        $this->assertSame($expected_perform_at->getTimestamp(), $perform_at->getTimestamp());
        $this->assertStringContainsString('I failed you :(', $db_job['last_error']);
        $this->assertSame($now->getTimestamp(), $failed_at->getTimestamp());
    }

    public function testRunReschedulesJobWithFrequency()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $job_dao = new models\dao\Job();
        $token = $this->create('token');
        $user_id = $this->create('user', [
            'validation_token' => $token,
        ]);
        $job_id = $this->create('job', [
            'perform_at' => $now->format(\Minz\Model::DATETIME_FORMAT),
            'frequency' => '+1 day',
            'locked_at' => null,
            'number_attempts' => 0,
            'handler' => json_encode([
                'job_class' => 'flusio\jobs\Mailer',
                'job_args' => ['Users', 'sendAccountValidationEmail', $user_id],
            ]),
        ]);

        $response = $this->appRun('cli', '/jobs/run');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, "job#{$job_id} (Mailer): done");
        $this->assertSame(1, $job_dao->count());
        $db_job = $job_dao->find($job_id);
        $perform_at = date_create_from_format(\Minz\Model::DATETIME_FORMAT, $db_job['perform_at']);
        $this->assertSame(
            \Minz\Time::fromNow(1, 'day')->getTimestamp(),
            $perform_at->getTimestamp(),
        );
        $this->assertNull($db_job['locked_at']);
    }

    public function testRunReschedulesJobWithFrequencyEvenIfFailing()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $job_dao = new models\dao\Job();
        $job_id = $this->create('job', [
            'perform_at' => $now->format(\Minz\Model::DATETIME_FORMAT),
            'frequency' => '+1 month',
            'locked_at' => null,
            'number_attempts' => 0,
            'handler' => json_encode([
                'job_class' => 'tests\jobs\MyFailingJob',
                'job_args' => [],
            ]),
        ]);

        $response = $this->appRun('cli', '/jobs/run');

        $this->assertResponseCode($response, 500);
        $this->assertResponseContains($response, "job#{$job_id} (tests\\jobs\\MyFailingJob): failed");
        $this->assertSame(1, $job_dao->count());
        $db_job = $job_dao->find($job_id);
        $perform_at = date_create_from_format(\Minz\Model::DATETIME_FORMAT, $db_job['perform_at']);
        $this->assertSame(
            \Minz\Time::fromNow(1, 'month')->getTimestamp(),
            $perform_at->getTimestamp(),
        );
        $this->assertNull($db_job['locked_at']);
    }

    public function testRunReschedulesJobWithTooManyAttempts()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $job_dao = new models\dao\Job();
        $token = $this->create('token');
        $user_id = $this->create('user', [
            'validation_token' => $token,
        ]);
        $number_attempts = $this->fake('numberBetween', 26, 100);
        $job_id = $this->create('job', [
            'perform_at' => $now->format(\Minz\Model::DATETIME_FORMAT),
            'frequency' => '+1 day',
            'locked_at' => null,
            'number_attempts' => $number_attempts,
            'handler' => json_encode([
                'job_class' => 'flusio\jobs\Mailer',
                'job_args' => ['Users', 'sendAccountValidationEmail', $user_id],
            ]),
        ]);

        $response = $this->appRun('cli', '/jobs/run');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, "job#{$job_id} (Mailer): done");
        $this->assertSame(1, $job_dao->count());
        $db_job = $job_dao->find($job_id);
        $perform_at = date_create_from_format(\Minz\Model::DATETIME_FORMAT, $db_job['perform_at']);
        $this->assertSame(
            \Minz\Time::fromNow(1, 'day')->getTimestamp(),
            $perform_at->getTimestamp(),
        );
        $this->assertSame($number_attempts + 1, $db_job['number_attempts']);
        $this->assertNull($db_job['locked_at']);
    }

    public function testRunReschedulesJobAlwaysInFuture()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $job_dao = new models\dao\Job();
        $token = $this->create('token');
        $user_id = $this->create('user', [
            'validation_token' => $token,
        ]);
        $three_hours_ago = \Minz\Time::ago(3, 'hours');
        $job_id = $this->create('job', [
            'perform_at' => $three_hours_ago->format(\Minz\Model::DATETIME_FORMAT),
            'frequency' => '+1 hour',
            'locked_at' => null,
            'number_attempts' => 0,
            'handler' => json_encode([
                'job_class' => 'flusio\jobs\Mailer',
                'job_args' => ['Users', 'sendAccountValidationEmail', $user_id],
            ]),
        ]);

        $response = $this->appRun('cli', '/jobs/run');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, "job#{$job_id} (Mailer): done");
        $this->assertSame(1, $job_dao->count());
        $db_job = $job_dao->find($job_id);
        $perform_at = date_create_from_format(\Minz\Model::DATETIME_FORMAT, $db_job['perform_at']);
        $this->assertSame(
            \Minz\Time::fromNow(1, 'hour')->getTimestamp(),
            $perform_at->getTimestamp(),
        );
    }

    public function testRunReschedulesJobAndClearsErrors()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $job_dao = new models\dao\Job();
        $token = $this->create('token');
        $user_id = $this->create('user', [
            'validation_token' => $token,
        ]);
        $job_id = $this->create('job', [
            'perform_at' => $now->format(\Minz\Model::DATETIME_FORMAT),
            'frequency' => '+1 day',
            'locked_at' => null,
            'number_attempts' => 0,
            'failed_at' => $now->format(\Minz\Model::DATETIME_FORMAT),
            'last_error' => $this->fake('sentence'),
            'handler' => json_encode([
                'job_class' => 'flusio\jobs\Mailer',
                'job_args' => ['Users', 'sendAccountValidationEmail', $user_id],
            ]),
        ]);

        $response = $this->appRun('cli', '/jobs/run');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, "job#{$job_id} (Mailer): done");
        $this->assertSame(1, $job_dao->count());
        $db_job = $job_dao->find($job_id);
        $this->assertNull($db_job['failed_at']);
        $this->assertNull($db_job['last_error']);
    }

    public function testRunReschedulesJobTakingCareOfDst()
    {
        $initial_timezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Paris');
        // In France, timezone offset was +01 on 25th March and +02 on 26th
        // March. This is because of Daylight Saving Time (DST).
        $now = new \DateTime('2023-03-25 04:00:00');
        $this->freeze($now);
        $job_dao = new models\dao\Job();
        $token = $this->create('token');
        $user_id = $this->create('user', [
            'validation_token' => $token,
        ]);
        $job_id = $this->create('job', [
            'perform_at' => $now->format(\Minz\Model::DATETIME_FORMAT),
            'frequency' => '+1 day',
            'locked_at' => null,
            'number_attempts' => 0,
            'handler' => json_encode([
                'job_class' => 'flusio\jobs\Mailer',
                'job_args' => ['Users', 'sendAccountValidationEmail', $user_id],
            ]),
        ]);

        $db_job = $job_dao->find($job_id);
        $this->assertSame('2023-03-25 03:00:00+00', $db_job['perform_at']); // PGSQL does not store the timezone

        $response = $this->appRun('cli', '/jobs/run');

        date_default_timezone_set($initial_timezone);

        $this->assertResponseCode($response, 200);
        $db_job = $job_dao->find($job_id);
        // If DST wasn't considered, the time would still be 03:00:00+00
        $this->assertSame('2023-03-26 02:00:00+00', $db_job['perform_at']);
    }

    public function testWatchRendersCorrectly()
    {
        $job_dao = new models\dao\Job();
        $token = $this->create('token');
        $user_id = $this->create('user', [
            'validation_token' => $token,
        ]);
        $job_id = $this->create('job', [
            'perform_at' => \Minz\Time::ago(1, 'second')->format(\Minz\Model::DATETIME_FORMAT),
            'locked_at' => null,
            'number_attempts' => $this->fake('numberBetween', 0, 25),
            'handler' => json_encode([
                'job_class' => 'flusio\jobs\Mailer',
                'job_args' => ['Users', 'sendAccountValidationEmail', $user_id],
            ]),
        ]);

        \pcntl_alarm(3); // the worker will get a SIGALRM signal and stop in 3s
        $response_generator = $this->appRun('cli', '/jobs/watch');

        $response = $response_generator->current();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, '[Job worker (all) started]');
        $response_generator->next();
        $response = $response_generator->current();
        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, "job#{$job_id} (Mailer): done");
        $response_generator->next();
        $response = $response_generator->current();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, '[Job worker (all) stopped]');
        $this->assertEmailsCount(1);
        $this->assertSame(0, $job_dao->count());
    }
}
