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

        $this->assertResponse($response, 302, null, [
            'Location' => '/',
        ]);
        $this->assertSame('fr_FR', $_SESSION['locale']);
    }
}
