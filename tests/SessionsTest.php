<?php

namespace flusio;

class SessionsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testChangeLocaleSetsSessionLocale()
    {
        $this->assertArrayNotHasKey('locale', $_SESSION);

        $response = $this->appRun('post', '/sessions/locale', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'locale' => 'fr_FR',
            'back' => 'home',
        ]);

        $this->assertResponse($response, 302, '/');
        $this->assertSame('fr_FR', $_SESSION['locale']);
    }

    public function testChangeLocaleWithWrongCsrfDoesntSetsSessionLocale()
    {
        (new \Minz\CSRF())->generateToken();

        $response = $this->appRun('post', '/sessions/locale', [
            'csrf' => 'not the token',
            'locale' => 'fr_FR',
            'back' => 'home',
        ]);

        $this->assertResponse($response, 302, '/');
        $this->assertArrayNotHasKey('locale', $_SESSION);
    }

    public function testChangeLocaleWithUnsupportedLocaleDoesntSetsSessionLocale()
    {
        $response = $this->appRun('post', '/sessions/locale', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'locale' => 'zu',
            'back' => 'home',
        ]);

        $this->assertResponse($response, 302, '/');
        $this->assertArrayNotHasKey('locale', $_SESSION);
    }
}
