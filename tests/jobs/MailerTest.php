<?php

namespace flusio\jobs;

use tests\factories\TokenFactory;
use tests\factories\UserFactory;

class MailerTest extends \PHPUnit\Framework\TestCase
{
    use \tests\InitializerHelper;
    use \Minz\Tests\MailerAsserts;

    /**
     * @beforeClass
     */
    public static function initEngine()
    {
        $router = \flusio\Router::load();
        \Minz\Engine::init($router);
    }

    public function testQueue()
    {
        $mailer_job = new Mailer();

        $this->assertSame('mailers', $mailer_job->queue);
    }

    public function testPerform()
    {
        $mailer_job = new Mailer();
        $token = TokenFactory::create();
        $user = UserFactory::create([
            'validation_token' => $token->token,
        ]);

        $this->assertEmailsCount(0);

        $mailer_job->perform('Users', 'sendAccountValidationEmail', $user->id);

        $this->assertEmailsCount(1);
    }
}
