<?php

namespace flusio;

class AccountsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testDeletionRendersCorrectly()
    {
        $this->login();

        $response = $this->appRun('get', '/account/deletion');

        $this->assertResponse($response, 200);
    }

    public function testDeletionRedirectsToLoginIfUserNotConnected()
    {
        $response = $this->appRun('get', '/account/deletion');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Faccount%2Fdeletion');
    }

    public function testDeleteRedirectsToTheHomePageAndDeletesTheUser()
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

        $this->assertResponse($response, 302, '/');
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
