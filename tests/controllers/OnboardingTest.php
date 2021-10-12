<?php

namespace flusio\controllers;

use flusio\models;

class OnboardingTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $this->login();

        $response = $this->appRun('GET', '/onboarding');

        $this->assertResponse($response, 200);
        $this->assertPointer($response, 'onboarding/step1.phtml');
    }

    /**
     * @dataProvider validStepsProvider
     */
    public function testShowAcceptsAStep($step)
    {
        $this->login();

        $response = $this->appRun('GET', '/onboarding', [
            'step' => $step,
        ]);

        $this->assertResponse($response, 200);
        $this->assertPointer($response, "onboarding/step{$step}.phtml");
    }

    public function testShowRedirectsIfNotConnected()
    {
        $response = $this->appRun('GET', '/onboarding');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fonboarding');
    }

    /**
     * @dataProvider invalidStepsProvider
     */
    public function testShowFailsIfStepIsOutOfBound($step)
    {
        $this->login();

        $response = $this->appRun('GET', '/onboarding', [
            'step' => $step,
        ]);

        $this->assertResponse($response, 404);
    }

    public function testUpdateLocaleChangeLocaleAndRedirect()
    {
        $user = $this->login([
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('POST', '/onboarding/locale', [
            'csrf' => $user->csrf,
            'locale' => 'fr_FR',
        ]);

        $this->assertResponse($response, 302, '/onboarding');
        $user = models\User::find($user->id);
        $this->assertSame('fr_FR', $user->locale);
    }

    public function testUpdateLocaleRedirectsIfNotConnected()
    {
        $user_id = $this->create('user', [
            'csrf' => 'a token',
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('POST', '/onboarding/locale', [
            'csrf' => 'a token',
            'locale' => 'fr_FR',
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fonboarding');
        $user = models\User::find($user_id);
        $this->assertSame('en_GB', $user->locale);
    }

    public function testUpdateLocaleRedirectsIfCsrfIsInvalid()
    {
        $user = $this->login([
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('POST', '/onboarding/locale', [
            'csrf' => 'not the token',
            'locale' => 'fr_FR',
        ]);

        $this->assertResponse($response, 302, '/onboarding');
        $user = models\User::find($user->id);
        $this->assertSame('en_GB', $user->locale);
    }

    public function testUpdateLocaleRedirectsIfLocaleIsInvalid()
    {
        $user = $this->login([
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('POST', '/onboarding/locale', [
            'csrf' => $user->csrf,
            'locale' => 'not a locale',
        ]);

        $this->assertResponse($response, 302, '/onboarding');
        $user = models\User::find($user->id);
        $this->assertSame('en_GB', $user->locale);
    }

    public function validStepsProvider()
    {
        return [
            [1],
            [2],
            [3],
            [4],
            [5],
            [6],
        ];
    }

    public function invalidStepsProvider()
    {
        $faker = \Faker\Factory::create();
        return [
            [$faker->numberBetween(-42, -1)],
            [0],
            [7],
            [$faker->numberBetween(8, 42)],
        ];
    }
}
