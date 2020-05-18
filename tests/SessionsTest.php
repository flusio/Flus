<?php

namespace flusio;

class SessionsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testChangeLocaleSetsSessionLocale()
    {
        $this->assertArrayNotHasKey('locale', $_SESSION);

        $response = $this->appRun('post', '/sessions/locale', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'locale' => 'fr_FR',
        ]);

        $this->assertResponse($response, 302, '/');
        $this->assertSame('fr_FR', $_SESSION['locale']);
    }

    public function testChangeLocaleSavesTheLocaleInUserIfConnected()
    {
        $user_dao = new models\dao\User();
        $user = $this->login([
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/sessions/locale', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'locale' => 'fr_FR',
        ]);

        $user = new models\User($user_dao->find($user->id)); // reload the user
        $this->assertSame('fr_FR', $user->locale);
    }

    public function testChangeLocaleRedirectsToRedirectTo()
    {
        $response = $this->appRun('post', '/sessions/locale', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'locale' => 'fr_FR',
            'redirect_to' => 'registration',
        ]);

        $this->assertResponse($response, 302, '/registration');
    }

    public function testChangeLocaleWithWrongCsrfDoesntSetsSessionLocale()
    {
        (new \Minz\CSRF())->generateToken();

        $response = $this->appRun('post', '/sessions/locale', [
            'csrf' => 'not the token',
            'locale' => 'fr_FR',
        ]);

        $this->assertResponse($response, 302, '/');
        $this->assertArrayNotHasKey('locale', $_SESSION);
    }

    public function testChangeLocaleWithUnsupportedLocaleDoesntSetsSessionLocale()
    {
        $response = $this->appRun('post', '/sessions/locale', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'locale' => 'zu',
        ]);

        $this->assertResponse($response, 302, '/');
        $this->assertArrayNotHasKey('locale', $_SESSION);
    }
}
