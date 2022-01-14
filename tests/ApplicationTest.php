<?php

namespace flusio;

class ApplicationTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

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

        $user = auth\CurrentUser::get();
        $this->assertNull($user);

        $request = new \Minz\Request('GET', '/', [], [
            'COOKIE' => [
                'flusio_session_token' => $token,
            ],
        ]);
        $application = new Application();
        $response = $application->run($request);

        $user = auth\CurrentUser::get();
        $this->assertSame($user_id, $user->id);
    }

    public function testRunRedirectsIfUserOlderThan1DaysNotValidated()
    {
        $created_at = \Minz\Time::ago($this->fake('numberBetween', 2, 42), 'days');
        $this->login([
            'created_at' => $created_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => null,
        ]);
        $request = new \Minz\Request('GET', '/news');

        $application = new Application();
        $response = $application->run($request);

        $this->assertResponse($response, 302, '/my/account/validation');
    }

    public function testRunRedirectsIfUserSubscriptionIsOverdue()
    {
        \Minz\Configuration::$application['subscriptions_enabled'] = true;
        $expired_at = \Minz\Time::ago($this->fake('randomDigitNotNull'), 'days');
        $this->login([
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $request = new \Minz\Request('GET', '/news');

        $application = new Application();
        $response = $application->run($request);

        \Minz\Configuration::$application['subscriptions_enabled'] = false;

        $this->assertResponse($response, 302, '/my/account');
    }

    public function testRunLogoutAndRedirectsIfConnectedWithSupportUser()
    {
        $this->login([
            'email' => \Minz\Configuration::$application['support_email'],
        ]);
        $request = new \Minz\Request('GET', '/news');

        $application = new Application();
        $response = $application->run($request);

        $this->assertResponse($response, 302, '/login');
        $current_user = auth\CurrentUser::get();
        $this->assertNull($current_user);
    }

    public function testHeaders()
    {
        $request = new \Minz\Request('GET', '/');
        $application = new Application();

        $response = $application->run($request);

        $headers = $response->headers(true);
        $this->assertSame('interest-cohort=()', $headers['Permissions-Policy']);
        $this->assertSame('deny', $headers['X-Frame-Options']);
        $this->assertSame([
            'default-src' => "'self'",
            'style-src' => "'self' 'unsafe-inline'",
        ], $headers['Content-Security-Policy']);
    }
}
