<?php

namespace flusio;

use Minz\Tests\IntegrationTestCase;

class SessionsTest extends IntegrationTestCase
{
    public function testChangeLocaleSetsSessionLocale()
    {
        $request = new \Minz\Request('post', '/sessions/locale', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'locale' => 'fr_FR',
            'back' => 'home',
        ]);

        $this->assertArrayNotHasKey('locale', $_SESSION);

        $response = self::$application->run($request);

        $this->assertResponse($response, 302, '/');
        $this->assertSame('fr_FR', $_SESSION['locale']);
    }

    public function testChangeLocaleWithWrongCsrfDoesntSetsSessionLocale()
    {
        (new \Minz\CSRF())->generateToken();
        $request = new \Minz\Request('post', '/sessions/locale', [
            'csrf' => 'not the token',
            'locale' => 'fr_FR',
            'back' => 'home',
        ]);

        $response = self::$application->run($request);

        $this->assertResponse($response, 302, '/');
        $this->assertArrayNotHasKey('locale', $_SESSION);
    }

    public function testChangeLocaleWithUnsupportedLocaleDoesntSetsSessionLocale()
    {
        $request = new \Minz\Request('post', '/sessions/locale', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'locale' => 'zu',
            'back' => 'home',
        ]);

        $response = self::$application->run($request);

        $this->assertResponse($response, 302, '/');
        $this->assertArrayNotHasKey('locale', $_SESSION);
    }
}
