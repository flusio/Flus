<?php

namespace flusio\cli;

use flusio\jobs;
use flusio\models;

class JobsWorkerTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\MailerAsserts;
    use \Minz\Tests\ResponseAsserts;

    /**
     * @beforeClass
     */
    public static function loadApplication()
    {
        self::$application = new \flusio\cli\Application();
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
            'handler' => json_encode([
                'job_class' => 'flusio\jobs\Mailer',
                'job_args' => ['Users', 'sendAccountValidationEmail', $user_id],
            ]),
        ]);

        $this->assertEmailsCount(0);
        $this->assertSame(1, $job_dao->count());

        $response = $this->appRun('cli', '/jobs/run');

        $this->assertResponse($response, 200, "job#{$job_id}: done");
        $this->assertEmailsCount(1);
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
            'handler' => json_encode([
                'job_class' => 'flusio\jobs\Mailer',
                'job_args' => ['Users', 'sendAccountValidationEmail', $user_id],
            ]),
        ]);

        $response = $this->appRun('cli', '/jobs/run');

        $this->assertResponse($response, 204);
        $this->assertEmailsCount(0);
        $this->assertSame(1, $job_dao->count());
    }

    public function testRunDoesNotSelectLockedJob()
    {
        $job_dao = new models\dao\Job();
        $token = $this->create('token');
        $user_id = $this->create('user', [
            'validation_token' => $token,
        ]);
        $job_id = $this->create('job', [
            'perform_at' => \Minz\Time::ago(1, 'second')->format(\Minz\Model::DATETIME_FORMAT),
            'locked_at' => $this->fake('iso8601'),
            'handler' => json_encode([
                'job_class' => 'flusio\jobs\Mailer',
                'job_args' => ['Users', 'sendAccountValidationEmail', $user_id],
            ]),
        ]);

        $response = $this->appRun('cli', '/jobs/run');

        $this->assertResponse($response, 204);
        $this->assertEmailsCount(0);
        $this->assertSame(1, $job_dao->count());
    }
}
