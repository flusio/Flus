<?php

namespace flusio\jobs;

class MailerTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\FactoriesHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\MailerAsserts;

    /**
     * @beforeClass
     */
    public static function loadApplication()
    {
        self::$application = new \flusio\cli\Application();
    }

    public function testQueue()
    {
        $mailer_job = new Mailer();

        $this->assertSame('mailers', $mailer_job->queue);
    }

    public function testPerform()
    {
        $mailer_job = new Mailer();
        $token = $this->create('token');
        $user_id = $this->create('user', [
            'validation_token' => $token,
        ]);

        $this->assertEmailsCount(0);

        $mailer_job->perform('Users', 'sendAccountValidationEmail', $user_id);

        $this->assertEmailsCount(1);
    }
}
