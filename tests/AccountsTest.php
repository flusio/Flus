<?php

namespace flusio;

class AccountsTest extends \PHPUnit\Framework\TestCase
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

        $response = $this->appRun('get', '/account');

        $this->assertResponse($response, 200);
        $this->assertPointer($response, 'accounts/show.phtml');
    }

    public function testShowRedirectsToLoginIfUserNotConnected()
    {
        $response = $this->appRun('get', '/account');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Faccount');
    }

    public function testUpdateRendersCorrectlyAndSavesTheUser()
    {
        $old_username = $this->fakeUnique('username');
        $new_username = $this->fakeUnique('username');
        $user = $this->login([
            'username' => $old_username,
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/account', [
            'csrf' => $user->csrf,
            'username' => $new_username,
            'locale' => 'fr_FR',
        ]);

        $this->assertResponse($response, 200);
        $this->assertPointer($response, 'accounts/show.phtml');
        $user = utils\CurrentUser::reload();
        $this->assertSame($new_username, $user->username);
        $this->assertSame('fr_FR', $user->locale);
    }

    public function testUpdateSetsTheCurrentLocale()
    {
        $user = $this->login([
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/account', [
            'csrf' => $user->csrf,
            'username' => $this->fake('username'),
            'locale' => 'fr_FR',
        ]);

        $this->assertSame('fr_FR', utils\Locale::currentLocale());
    }

    public function testUpdateRedirectsToLoginIfUserNotConnected()
    {
        $user_dao = new models\dao\User();
        $old_username = $this->fakeUnique('username');
        $new_username = $this->fakeUnique('username');
        $user_id = $this->create('user', [
            'username' => $old_username,
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/account', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $new_username,
            'locale' => 'fr_FR',
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Faccount');
        $user = new models\User($user_dao->find($user_id));
        $this->assertSame($old_username, $user->username);
        $this->assertSame('en_GB', $user->locale);
    }

    public function testUpdateFailsIfCsrfIsInvalid()
    {
        $old_username = $this->fakeUnique('username');
        $new_username = $this->fakeUnique('username');
        $user = $this->login([
            'username' => $old_username,
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/account', [
            'csrf' => 'not the token',
            'username' => $new_username,
            'locale' => 'fr_FR',
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $user = utils\CurrentUser::reload();
        $this->assertSame($old_username, $user->username);
        $this->assertSame('en_GB', $user->locale);
    }

    public function testUpdateFailsIfUsernameIsInvalid()
    {
        $old_username = $this->fakeUnique('username');
        $new_username = $this->fake('sentence', 50, false);
        $user = $this->login([
            'username' => $old_username,
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/account', [
            'csrf' => $user->csrf,
            'username' => $new_username,
            'locale' => 'fr_FR',
        ]);

        $this->assertResponse($response, 400, 'The username must be less than 50 characters');
        $user = utils\CurrentUser::reload();
        $this->assertSame($old_username, $user->username);
        $this->assertSame('en_GB', $user->locale);
    }

    public function testUpdateFailsIfUsernameIsMissing()
    {
        $old_username = $this->fakeUnique('username');
        $user = $this->login([
            'username' => $old_username,
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/account', [
            'csrf' => $user->csrf,
            'locale' => 'fr_FR',
        ]);

        $this->assertResponse($response, 400, 'The username is required');
        $user = utils\CurrentUser::reload();
        $this->assertSame($old_username, $user->username);
        $this->assertSame('en_GB', $user->locale);
    }

    public function testUpdateFailsIfLocaleIsMissing()
    {
        $old_username = $this->fakeUnique('username');
        $new_username = $this->fakeUnique('username');
        $user = $this->login([
            'username' => $old_username,
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/account', [
            'csrf' => $user->csrf,
            'username' => $new_username,
        ]);

        $this->assertResponse($response, 400, 'The locale is required');
        $user = utils\CurrentUser::reload();
        $this->assertSame($old_username, $user->username);
        $this->assertSame('en_GB', $user->locale);
    }

    public function testUpdateFailsIfLocaleIsInvalid()
    {
        $old_username = $this->fakeUnique('username');
        $new_username = $this->fakeUnique('username');
        $user = $this->login([
            'username' => $old_username,
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/account', [
            'csrf' => $user->csrf,
            'username' => $new_username,
            'locale' => 'not a locale',
        ]);

        $this->assertResponse($response, 400, 'The locale is invalid');
        $user = utils\CurrentUser::reload();
        $this->assertSame($old_username, $user->username);
        $this->assertSame('en_GB', $user->locale);
    }

    public function testShowDeleteRendersCorrectly()
    {
        $this->login();

        $response = $this->appRun('get', '/account/delete');

        $this->assertResponse($response, 200);
        $this->assertPointer($response, 'accounts/delete.phtml');
    }

    public function testShowDeleteRedirectsToLoginIfUserNotConnected()
    {
        $response = $this->appRun('get', '/account/delete');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Faccount%2Fdelete');
    }

    public function testDeleteRedirectsToLoginAndDeletesTheUser()
    {
        $user_dao = new models\dao\User();

        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/account/delete', [
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
        $response = $this->appRun('post', '/account/delete', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'password' => $this->fake('password'),
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Faccount%2Fdelete');
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

        $response = $this->appRun('post', '/account/delete', [
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

        $response = $this->appRun('post', '/account/delete', [
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

        $response = $this->appRun('post', '/account/delete', [
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

        $response = $this->appRun('post', '/account/delete', [
            'csrf' => $user->csrf,
            'password' => $password,
        ]);

        \Minz\Configuration::$application['demo'] = false;
        $this->assertResponse($response, 400, 'Sorry but you cannot delete the demo account 😉');
        $this->assertNotNull($user_dao->find($user->id));
    }
}
