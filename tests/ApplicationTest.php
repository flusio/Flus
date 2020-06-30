<?php

namespace flusio;

class ApplicationTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\FactoriesHelper;

    public function testRunSetsTheDefaultLocale()
    {
        $request = new \Minz\Request('GET', '/');

        $application = new Application();
        $response = $application->run($request);

        $variables = \Minz\Output\View::defaultVariables();
        $this->assertSame('en_GB', $variables['current_locale']);
        $this->assertSame('en_GB', utils\Locale::currentLocale());
    }

    public function testRunSetsTheLocaleFromSessionLocale()
    {
        $_SESSION['locale'] = 'fr_FR';
        $request = new \Minz\Request('GET', '/');

        $application = new Application();
        $response = $application->run($request);

        $variables = \Minz\Output\View::defaultVariables();
        $this->assertSame('fr_FR', $variables['current_locale']);
        $this->assertSame('fr_FR', utils\Locale::currentLocale());
    }

    public function testRunSetsTheLocaleFromConnectedUser()
    {
        $this->login([
            'locale' => 'fr_FR',
        ]);
        $request = new \Minz\Request('GET', '/');

        $application = new Application();
        $response = $application->run($request);

        $variables = \Minz\Output\View::defaultVariables();
        $this->assertSame('fr_FR', $variables['current_locale']);
        $this->assertSame('fr_FR', utils\Locale::currentLocale());
    }

    public function testRunSetsCurrentUserFromCookie()
    {
        $expired_at = \Minz\Time::fromNow(30, 'days');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user');
        $this->create('session', [
            'user_id' => $user_id,
            'token' => $token,
        ]);

        $user = utils\CurrentUser::get();
        $this->assertNull($user);

        $request = new \Minz\Request('GET', '/', [], [
            'COOKIE' => [
                'flusio_session_token' => $token,
            ],
        ]);
        $application = new Application();
        $response = $application->run($request);

        $user = utils\CurrentUser::get();
        $this->assertSame($user_id, $user->id);
    }
}
