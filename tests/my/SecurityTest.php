<?php

namespace flusio\my;

use flusio\models;
use flusio\utils;

class SecurityTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\TimeHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectlyIfPasswordIsConfirmed()
    {
        $this->freeze($this->fake('dateTime'));
        $confirmed_at = \Minz\Time::ago($this->fake('numberBetween', 0, 15), 'minutes');
        $this->login([], [], [
            'confirmed_password_at' => $confirmed_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $response = $this->appRun('get', '/my/security');

        $this->assertResponse($response, 200, 'You can change your login details here');
        $this->assertPointer($response, 'my/security/show_confirmed.phtml');
    }

    public function testShowRendersConfirmFormIfPasswordWasNeverConfirmed()
    {
        $this->login([], [], [
            'confirmed_password_at' => null,
        ]);

        $response = $this->appRun('get', '/my/security');

        $this->assertResponse($response, 200, 'We need you to confirm your password');
        $this->assertPointer($response, 'my/security/show_to_confirm.phtml');
    }

    public function testShowRendersConfirmFormIfPasswordIsNotConfirmed()
    {
        $this->freeze($this->fake('dateTime'));
        $confirmed_at = \Minz\Time::ago($this->fake('numberBetween', 16, 9000), 'minutes');
        $this->login([], [], [
            'confirmed_password_at' => $confirmed_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $response = $this->appRun('get', '/my/security');

        $this->assertResponse($response, 200, 'We need you to confirm your password');
        $this->assertPointer($response, 'my/security/show_to_confirm.phtml');
    }

    public function testShowRedirectsIfUserIsNotConnected()
    {
        $response = $this->appRun('get', '/my/security');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fmy%2Fsecurity');
    }

    public function testConfirmPasswordSetsConfirmedPasswordAtAndRedirects()
    {
        $this->freeze($this->fake('dateTime'));
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => null,
        ]);

        $response = $this->appRun('post', '/my/security/confirm', [
            'csrf' => $user->csrf,
            'password' => $password,
        ]);

        $this->assertResponse($response, 302, '/my/security');
        $session = utils\CurrentUser::session();
        $now = \Minz\Time::now();
        $this->assertSame($now->getTimestamp(), $session->confirmed_password_at->getTimestamp());
    }

    public function testConfirmPasswordRedirectsIfUserIsNotConnected()
    {
        $password = $this->fake('password');
        $user_id = $this->create('user', [
            'csrf' => 'a token',
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/my/security/confirm', [
            'csrf' => 'a token',
            'password' => $password,
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fmy%2Fsecurity');
    }

    public function testConfirmPasswordFailsIfCsrfIsInvalid()
    {
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => null,
        ]);

        $response = $this->appRun('post', '/my/security/confirm', [
            'csrf' => 'not the token',
            'password' => $password,
        ]);

        $this->assertResponse($response, 302, '/my/security');
        $this->assertFlash('error', 'A security verification failed: you should retry to submit the form.');
        $session = utils\CurrentUser::session();
        $this->assertNull($session->confirmed_password_at);
    }

    public function testConfirmPasswordFailsIfPasswordIsInvalid()
    {
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => null,
        ]);

        $response = $this->appRun('post', '/my/security/confirm', [
            'csrf' => $user->csrf,
            'password' => 'not the password',
        ]);

        $this->assertResponse($response, 302, '/my/security');
        $this->assertFlash('errors', [
            'password_hash' => 'The password is incorrect.',
        ]);
        $session = utils\CurrentUser::session();
        $this->assertNull($session->confirmed_password_at);
    }
}
