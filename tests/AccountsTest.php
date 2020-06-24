<?php

namespace flusio;

class AccountsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
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
        $faker = \Faker\Factory::create();
        $old_username = $faker->unique()->username;
        $new_username = $faker->unique()->username;
        $this->login([
            'username' => $old_username,
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/account', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
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
        $faker = \Faker\Factory::create();
        $this->login([
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/account', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $faker->username,
            'locale' => 'fr_FR',
        ]);

        $this->assertSame('fr_FR', utils\Locale::currentLocale());
    }

    public function testUpdateRedirectsToLoginIfUserNotConnected()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        $old_username = $faker->unique()->username;
        $new_username = $faker->unique()->username;
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
        $faker = \Faker\Factory::create();
        $old_username = $faker->unique()->username;
        $new_username = $faker->unique()->username;
        $this->login([
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
        $faker = \Faker\Factory::create();
        $old_username = $faker->unique()->username;
        $new_username = $faker->sentence(50, false);
        $this->login([
            'username' => $old_username,
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/account', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
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
        $faker = \Faker\Factory::create();
        $old_username = $faker->unique()->username;
        $this->login([
            'username' => $old_username,
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/account', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'locale' => 'fr_FR',
        ]);

        $this->assertResponse($response, 400, 'The username is required');
        $user = utils\CurrentUser::reload();
        $this->assertSame($old_username, $user->username);
        $this->assertSame('en_GB', $user->locale);
    }

    public function testUpdateFailsIfLocaleIsMissing()
    {
        $faker = \Faker\Factory::create();
        $old_username = $faker->unique()->username;
        $new_username = $faker->unique()->username;
        $this->login([
            'username' => $old_username,
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/account', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $new_username,
        ]);

        $this->assertResponse($response, 400, 'The locale is required');
        $user = utils\CurrentUser::reload();
        $this->assertSame($old_username, $user->username);
        $this->assertSame('en_GB', $user->locale);
    }

    public function testUpdateFailsIfLocaleIsInvalid()
    {
        $faker = \Faker\Factory::create();
        $old_username = $faker->unique()->username;
        $new_username = $faker->unique()->username;
        $this->login([
            'username' => $old_username,
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/account', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $new_username,
            'locale' => 'not a locale',
        ]);

        $this->assertResponse($response, 400, 'The locale is invalid');
        $user = utils\CurrentUser::reload();
        $this->assertSame($old_username, $user->username);
        $this->assertSame('en_GB', $user->locale);
    }

    public function testDeletionRendersCorrectly()
    {
        $this->login();

        $response = $this->appRun('get', '/account/deletion');

        $this->assertResponse($response, 200);
        $this->assertPointer($response, 'accounts/deletion.phtml');
    }

    public function testDeletionRedirectsToLoginIfUserNotConnected()
    {
        $response = $this->appRun('get', '/account/deletion');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Faccount%2Fdeletion');
    }

    public function testDeleteRedirectsToLoginAndDeletesTheUser()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();

        $password = $faker->password;
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/account/deletion', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'password' => $password,
        ]);

        $this->assertResponse($response, 302, '/login');
        $this->assertFlash('status', 'user_deleted');
        $this->assertNull($user_dao->find($user->id));
        $this->assertNull(utils\CurrentUser::get());
    }

    public function testDeleteRedirectsToLoginIfUserIsNotConnected()
    {
        $faker = \Faker\Factory::create();

        $response = $this->appRun('post', '/account/deletion', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'password' => $faker->password,
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Faccount%2Fdeletion');
    }

    public function testDeleteDeletesSessionsAssociatedToTheUser()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        $session_dao = new models\dao\Session();

        $password = $faker->password;
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $this->assertSame(1, $session_dao->count());

        $response = $this->appRun('post', '/account/deletion', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'password' => $password,
        ]);

        $this->assertSame(0, $session_dao->count());
    }

    public function testDeleteFailsIfPasswordIsIncorrect()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();

        $password = $faker->password;
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/account/deletion', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'password' => 'not the password',
        ]);

        $this->assertResponse($response, 400, 'The password is incorrect.');
        $this->assertNotNull($user_dao->find($user->id));
    }

    public function testDeleteFailsIfCsrfIsInvalid()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();

        $password = $faker->password;
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);
        (new \Minz\CSRF())->generateToken();

        $response = $this->appRun('post', '/account/deletion', [
            'csrf' => 'not the token',
            'password' => $password,
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $this->assertNotNull($user_dao->find($user->id));
    }
}
