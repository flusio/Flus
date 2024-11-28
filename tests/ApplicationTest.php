<?php

namespace App;

use tests\factories\SessionFactory;
use tests\factories\TokenFactory;
use tests\factories\UserFactory;

class ApplicationTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \Minz\Tests\TimeHelper;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testRunSetsTheDefaultLocale(): void
    {
        $request = new \Minz\Request('GET', '/');

        $application = new Application();
        $response = $application->run($request);

        $variables = \Minz\Output\View::defaultVariables();
        $this->assertSame('en_GB', $variables['current_locale']);
        $this->assertSame('en_GB', utils\Locale::currentLocale());
    }

    public function testRunSetsTheLocaleFromSessionLocale(): void
    {
        $_SESSION['locale'] = 'fr_FR';
        $request = new \Minz\Request('GET', '/');

        $application = new Application();
        $response = $application->run($request);

        $variables = \Minz\Output\View::defaultVariables();
        $this->assertSame('fr_FR', $variables['current_locale']);
        $this->assertSame('fr_FR', utils\Locale::currentLocale());
    }

    public function testRunSetsTheLocaleFromConnectedUser(): void
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

    public function testRunSetsCurrentUserFromCookie(): void
    {
        $expired_at = \Minz\Time::fromNow(30, 'days');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $user = UserFactory::create();
        SessionFactory::create([
            'user_id' => $user->id,
            'token' => $token->token,
        ]);

        $current_user = auth\CurrentUser::get();
        $this->assertNull($current_user);

        $request = new \Minz\Request('GET', '/', [], [
            'COOKIE' => [
                'session_token' => $token->token,
            ],
        ]);
        $application = new Application();
        $response = $application->run($request);

        $current_user = auth\CurrentUser::get();
        $this->assertNotNull($current_user);
        $this->assertSame($user->id, $current_user->id);
    }

    public function testRunRefreshesLastActivity(): void
    {
        $last_activity = new \DateTimeImmutable('2024-11-01');
        $current_datetime = new \DateTimeImmutable('2024-11-30 12:42:42');
        $current_date = new \DateTimeImmutable('2024-11-30 00:00:00');
        $this->freeze($current_datetime);
        $user = $this->login([
            'last_activity_at' => $last_activity,
        ]);
        $request = new \Minz\Request('GET', '/news');

        $application = new Application();
        $response = $application->run($request);

        $this->assertResponseCode($response, 200);
        $user = $user->reload();
        $this->assertEquals($current_date, $user->last_activity_at);
    }

    public function testRunSetsAutoloadModal(): void
    {
        $user = $this->login([
            'autoload_modal' => 'showcase navigation',
        ]);
        $request = new \Minz\Request('GET', '/news');

        $application = new Application();
        $response = $application->run($request);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, \Minz\Url::for('showcase', ['id' => 'navigation']));
        $user = $user->reload();
        $this->assertEmpty($user->autoload_modal);
    }

    public function testRunDoesNotResetAutoloadModalOnRedirections(): void
    {
        $user = $this->login([
            'autoload_modal' => 'showcase navigation',
        ]);
        $request = new \Minz\Request('GET', '/collections');

        $application = new Application();
        $response = $application->run($request);

        $this->assertResponseCode($response, 301);
        $user = $user->reload();
        $this->assertSame('showcase navigation', $user->autoload_modal);
    }

    public function testRunRedirectsIfUserOlderThan1DaysNotValidated(): void
    {
        /** @var int */
        $days = $this->fake('numberBetween', 2, 42);
        $created_at = \Minz\Time::ago($days, 'days');
        $this->login([
            'created_at' => $created_at,
            'validated_at' => null,
        ]);
        $request = new \Minz\Request('GET', '/news');

        $application = new Application();
        $response = $application->run($request);

        $this->assertResponseCode($response, 302, '/my/account/validation');
    }

    public function testRunRedirectsIfUserSubscriptionIsOverdue(): void
    {
        \Minz\Configuration::$application['subscriptions_enabled'] = true;
        /** @var int */
        $days = $this->fake('randomDigitNotNull');
        $expired_at = \Minz\Time::ago($days, 'days');
        $this->login([
            'subscription_expired_at' => $expired_at,
        ]);
        $request = new \Minz\Request('GET', '/news');

        $application = new Application();
        $response = $application->run($request);

        \Minz\Configuration::$application['subscriptions_enabled'] = false;

        $this->assertResponseCode($response, 302, '/my/account');
    }

    public function testRunLogoutAndRedirectsIfConnectedWithSupportUser(): void
    {
        /** @var string */
        $support_email = \Minz\Configuration::$application['support_email'];
        $this->login([
            'email' => $support_email,
        ]);
        $request = new \Minz\Request('GET', '/news');

        $application = new Application();
        $response = $application->run($request);

        $this->assertResponseCode($response, 302, '/login');
        $current_user = auth\CurrentUser::get();
        $this->assertNull($current_user);
    }

    public function testHeaders(): void
    {
        $request = new \Minz\Request('GET', '/');
        $application = new Application();

        $response = $application->run($request);

        $this->assertInstanceOf(\Minz\Response::class, $response);
        $headers = $response->headers(true);
        $this->assertSame('interest-cohort=()', $headers['Permissions-Policy']);
        $this->assertSame('same-origin', $headers['Referrer-Policy']);
        $this->assertSame('nosniff', $headers['X-Content-Type-Options']);
        $this->assertSame('deny', $headers['X-Frame-Options']);
        $this->assertSame([
            'default-src' => "'self'",
            'style-src' => "'self' 'unsafe-inline'",
        ], $headers['Content-Security-Policy']);
    }
}
