<?php

namespace flusio\controllers;

use flusio\models;
use tests\factories\UserFactory;

class SupportTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;
    use \Minz\Tests\MailerAsserts;

    public function testShowRendersCorrectly()
    {
        $user = $this->login();

        $response = $this->appRun('GET', '/support');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'you can contact me via this form');
        $this->assertResponsePointer($response, 'support/show.phtml');
    }

    public function testShowRendersSuccessParagraphIfMessageSent()
    {
        $user = $this->login();
        \Minz\Flash::set('message_sent', true);

        $response = $this->appRun('GET', '/support');

        $this->assertResponseContains($response, 'Your message has been sent');
    }

    public function testShowRedirectsIfNotConnected()
    {
        $response = $this->appRun('GET', '/support');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fsupport');
    }

    public function testCreateSendsTwoEmails()
    {
        $support_user = models\User::supportUser();
        $email = $this->fake('email');
        $subject = $this->fake('sentence');
        $message = $this->fake('paragraph');
        $user = $this->login([
            'email' => $email,
        ]);

        $response = $this->appRun('POST', '/support', [
            'csrf' => $user->csrf,
            'subject' => $subject,
            'message' => $message,
        ]);

        $this->assertResponseCode($response, 302, '/support');
        $this->assertTrue(\Minz\Flash::get('message_sent'));
        $this->assertEmailsCount(2);
        $email_1 = \Minz\Tests\Mailer::take(0);
        $this->assertEmailSubject($email_1, "[flusio] Contact: {$subject}");
        $this->assertEmailContainsTo($email_1, $support_user->email);
        $this->assertEmailContainsReplyTo($email_1, $email);
        $this->assertEmailContainsBody($email_1, $message);
        $email_2 = \Minz\Tests\Mailer::take(1);
        $this->assertEmailSubject($email_2, '[flusio] Your message has been sent');
        $this->assertEmailContainsTo($email_2, $email);
        $this->assertEmailContainsBody($email_2, 'Someone will reply to you as soon as possible');
    }

    public function testCreateRedirectsIfNotConnected()
    {
        $support_user = models\User::supportUser();
        $email = $this->fake('email');
        $subject = $this->fake('sentence');
        $message = $this->fake('paragraph');
        $user = UserFactory::create([
            'csrf' => 'a token',
            'email' => $email,
        ]);

        $response = $this->appRun('POST', '/support', [
            'csrf' => 'a token',
            'subject' => $subject,
            'message' => $message,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fsupport');
        $this->assertNull(\Minz\Flash::get('message_sent'));
        $this->assertEmailsCount(0);
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $support_user = models\User::supportUser();
        $email = $this->fake('email');
        $subject = $this->fake('sentence');
        $message = $this->fake('paragraph');
        $user = $this->login([
            'email' => $email,
        ]);

        $response = $this->appRun('POST', '/support', [
            'csrf' => 'not the token',
            'subject' => $subject,
            'message' => $message,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertResponsePointer($response, 'support/show.phtml');
        $this->assertNull(\Minz\Flash::get('message_sent'));
        $this->assertEmailsCount(0);
    }

    public function testCreateFailsIfSubjectIsEmpty()
    {
        $support_user = models\User::supportUser();
        $email = $this->fake('email');
        $subject = '';
        $message = $this->fake('paragraph');
        $user = $this->login([
            'email' => $email,
        ]);

        $response = $this->appRun('POST', '/support', [
            'csrf' => $user->csrf,
            'subject' => $subject,
            'message' => $message,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The subject is required.');
        $this->assertResponsePointer($response, 'support/show.phtml');
        $this->assertNull(\Minz\Flash::get('message_sent'));
        $this->assertEmailsCount(0);
    }

    public function testCreateFailsIfMessageIsEmpty()
    {
        $support_user = models\User::supportUser();
        $email = $this->fake('email');
        $subject = $this->fake('sentence');
        $message = '';
        $user = $this->login([
            'email' => $email,
        ]);

        $response = $this->appRun('POST', '/support', [
            'csrf' => $user->csrf,
            'subject' => $subject,
            'message' => $message,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The message is required.');
        $this->assertResponsePointer($response, 'support/show.phtml');
        $this->assertNull(\Minz\Flash::get('message_sent'));
        $this->assertEmailsCount(0);
    }
}
