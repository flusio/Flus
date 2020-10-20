<?php

namespace flusio\my;

use flusio\models;
use flusio\utils;

class AccountTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $this->login();

        $response = $this->appRun('get', '/my/account');

        $this->assertResponse($response, 200);
        $this->assertPointer($response, 'my/account/show.phtml');
    }

    public function testShowRedirectsToLoginIfUserNotConnected()
    {
        $response = $this->appRun('get', '/my/account');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fmy%2Faccount');
    }

    public function testDeletionRendersCorrectly()
    {
        $this->login();

        $response = $this->appRun('get', '/my/account/deletion');

        $this->assertResponse($response, 200);
        $this->assertPointer($response, 'my/account/deletion.phtml');
    }

    public function testDeletionRedirectsToLoginIfUserNotConnected()
    {
        $response = $this->appRun('get', '/my/account/deletion');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fmy%2Faccount%2Fdeletion');
    }

    public function testDeleteRedirectsToLoginAndDeletesTheUser()
    {
        $user_dao = new models\dao\User();

        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => $user->csrf,
            'password' => $password,
        ]);

        $this->assertResponse($response, 302, '/login');
        $this->assertFlash('status', 'user_deleted');
        $this->assertNull($user_dao->find($user->id));
        $this->assertNull(utils\CurrentUser::get());
    }

    public function testDeleteRedirectsToLoginIfUserIsNotConnected()
    {
        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'password' => $this->fake('password'),
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fmy%2Faccount%2Fdeletion');
    }

    public function testDeleteDeletesSessionsAssociatedToTheUser()
    {
        $user_dao = new models\dao\User();
        $session_dao = new models\dao\Session();

        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $this->assertSame(1, $session_dao->count());

        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => $user->csrf,
            'password' => $password,
        ]);

        $this->assertSame(0, $session_dao->count());
    }

    public function testDeleteFailsIfPasswordIsIncorrect()
    {
        $user_dao = new models\dao\User();

        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => $user->csrf,
            'password' => 'not the password',
        ]);

        $this->assertResponse($response, 400, 'The password is incorrect.');
        $this->assertNotNull($user_dao->find($user->id));
    }

    public function testDeleteFailsIfCsrfIsInvalid()
    {
        $user_dao = new models\dao\User();

        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => 'not the token',
            'password' => $password,
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $this->assertNotNull($user_dao->find($user->id));
    }

    public function testDeleteFailsIfTryingToDeleteDemoAccount()
    {
        $user_dao = new models\dao\User();
        \Minz\Configuration::$application['demo'] = true;

        $password = $this->fake('password');
        $user = $this->login([
            'email' => 'demo@flus.io',
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => $user->csrf,
            'password' => $password,
        ]);

        \Minz\Configuration::$application['demo'] = false;
        $this->assertResponse($response, 400, 'Sorry but you cannot delete the demo account 😉');
        $this->assertNotNull($user_dao->find($user->id));
    }
}
