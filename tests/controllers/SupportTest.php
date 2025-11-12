<?php

namespace App\controllers;

use App\forms;
use App\models;
use tests\factories\UserFactory;

class SupportTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\MailerAsserts;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;
    use \tests\MockHttpHelper;

    public function testShowRendersCorrectly(): void
    {
        $user = $this->login();

        $response = $this->appRun('GET', '/support');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'you can contact me via this form');
        $this->assertResponseTemplateName($response, 'support/show.phtml');
    }

    public function testShowRendersSuccessParagraphIfMessageSent(): void
    {
        $user = $this->login();
        \Minz\Flash::set('message_sent', true);

        $response = $this->appRun('GET', '/support');

        $this->assertResponseContains($response, 'Your message has been sent');
    }

    public function testShowRedirectsIfNotConnected(): void
    {
        $response = $this->appRun('GET', '/support');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fsupport');
    }

    public function testCreateSendsTwoEmails(): void
    {
        $support_user = models\User::supportUser();
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $subject = $this->fake('sentence');
        /** @var string */
        $message = $this->fake('paragraph');
        $user = $this->login([
            'email' => $email,
        ]);

        $response = $this->appRun('POST', '/support', [
            'csrf_token' => $this->csrfToken(forms\Support::class),
            'subject' => $subject,
            'message' => $message,
        ]);

        $this->assertResponseCode($response, 302, '/support');
        $this->assertTrue(\Minz\Flash::get('message_sent'));
        $this->assertEmailsCount(2);
        $email_1 = \Minz\Tests\Mailer::take(0);
        $this->assertNotNull($email_1);
        $this->assertEmailSubject($email_1, "[Flus] Contact: {$subject}");
        $this->assertEmailContainsTo($email_1, $support_user->email);
        $this->assertEmailContainsReplyTo($email_1, $email);
        $this->assertEmailContainsBody($email_1, $message);
        $email_2 = \Minz\Tests\Mailer::take(1);
        $this->assertNotNull($email_2);
        $this->assertEmailSubject($email_2, '[Flus] Your message has been sent');
        $this->assertEmailContainsTo($email_2, $email);
        $this->assertEmailContainsBody($email_2, 'Someone will reply to you as soon as possible');
    }

    public function testCreateSendsToBiletoIfConfigured(): void
    {
        $bileto_url = 'https://support.example.org';
        \App\Configuration::$application['bileto_url'] = $bileto_url;
        \App\Configuration::$application['bileto_api_token'] = 'some-token';
        $support_user = models\User::supportUser();
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $subject = $this->fake('sentence');
        /** @var string */
        $message = $this->fake('paragraph');
        $user = $this->login([
            'email' => $email,
        ]);
        $endpoint_url = "{$bileto_url}/api/tickets";
        $this->mockHttpWithResponse($endpoint_url, <<<TEXT
            HTTP/2 200
            server: nginx
            date: Fri, 16 Apr 2025 16:10:00 GMT
            content-type: application/json

            {'message': 'ok'}
            TEXT
        );

        $response = $this->appRun('POST', '/support', [
            'csrf_token' => $this->csrfToken(forms\Support::class),
            'subject' => $subject,
            'message' => $message,
        ]);

        \App\Configuration::$application['bileto_url'] = '';
        \App\Configuration::$application['bileto_api_token'] = '';

        $this->assertResponseCode($response, 302, '/support');
        $this->assertTrue(\Minz\Flash::get('message_sent'));
        $this->assertEmailsCount(0);
    }

    public function testCreateRedirectsIfNotConnected(): void
    {
        $support_user = models\User::supportUser();
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $subject = $this->fake('sentence');
        /** @var string */
        $message = $this->fake('paragraph');
        $user = UserFactory::create([
            'email' => $email,
        ]);

        $response = $this->appRun('POST', '/support', [
            'csrf_token' => $this->csrfToken(forms\Support::class),
            'subject' => $subject,
            'message' => $message,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fsupport');
        $this->assertNull(\Minz\Flash::get('message_sent'));
        $this->assertEmailsCount(0);
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $support_user = models\User::supportUser();
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $subject = $this->fake('sentence');
        /** @var string */
        $message = $this->fake('paragraph');
        $user = $this->login([
            'email' => $email,
        ]);

        $response = $this->appRun('POST', '/support', [
            'csrf_token' => 'not the token',
            'subject' => $subject,
            'message' => $message,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertResponseTemplateName($response, 'support/show.phtml');
        $this->assertNull(\Minz\Flash::get('message_sent'));
        $this->assertEmailsCount(0);
    }

    public function testCreateFailsIfSubjectIsEmpty(): void
    {
        $support_user = models\User::supportUser();
        /** @var string */
        $email = $this->fake('email');
        $subject = '';
        /** @var string */
        $message = $this->fake('paragraph');
        $user = $this->login([
            'email' => $email,
        ]);

        $response = $this->appRun('POST', '/support', [
            'csrf_token' => $this->csrfToken(forms\Support::class),
            'subject' => $subject,
            'message' => $message,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The subject is required.');
        $this->assertResponseTemplateName($response, 'support/show.phtml');
        $this->assertNull(\Minz\Flash::get('message_sent'));
        $this->assertEmailsCount(0);
    }

    public function testCreateFailsIfMessageIsEmpty(): void
    {
        $support_user = models\User::supportUser();
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $subject = $this->fake('sentence');
        $message = '';
        $user = $this->login([
            'email' => $email,
        ]);

        $response = $this->appRun('POST', '/support', [
            'csrf_token' => $this->csrfToken(forms\Support::class),
            'subject' => $subject,
            'message' => $message,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The message is required.');
        $this->assertResponseTemplateName($response, 'support/show.phtml');
        $this->assertNull(\Minz\Flash::get('message_sent'));
        $this->assertEmailsCount(0);
    }
}
