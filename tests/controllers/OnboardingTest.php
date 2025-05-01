<?php

namespace App\controllers;

use App\models;
use tests\factories\UserFactory;

class OnboardingTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testShowRendersCorrectly(): void
    {
        $this->login();

        $response = $this->appRun('GET', '/onboarding');

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'onboarding/step1.phtml');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validStepsProvider')]
    public function testShowAcceptsAStep(int $step): void
    {
        $this->login();

        $response = $this->appRun('GET', '/onboarding', [
            'step' => $step,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, "onboarding/step{$step}.phtml");
    }

    public function testShowRedirectsIfNotConnected(): void
    {
        $response = $this->appRun('GET', '/onboarding');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fonboarding');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidStepsProvider')]
    public function testShowFailsIfStepIsOutOfBound(int $step): void
    {
        $this->login();

        $response = $this->appRun('GET', '/onboarding', [
            'step' => $step,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testUpdateLocaleChangeLocaleAndRedirect(): void
    {
        $user = $this->login([
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('POST', '/onboarding/locale', [
            'csrf' => \App\Csrf::generate(),
            'locale' => 'fr_FR',
        ]);

        $this->assertResponseCode($response, 302, '/onboarding');
        $user = $user->reload();
        $this->assertSame('fr_FR', $user->locale);
    }

    public function testUpdateLocaleRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create([
            'csrf' => 'a token',
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('POST', '/onboarding/locale', [
            'csrf' => 'a token',
            'locale' => 'fr_FR',
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fonboarding');
        $user = $user->reload();
        $this->assertSame('en_GB', $user->locale);
    }

    public function testUpdateLocaleRedirectsIfCsrfIsInvalid(): void
    {
        $user = $this->login([
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('POST', '/onboarding/locale', [
            'csrf' => 'not the token',
            'locale' => 'fr_FR',
        ]);

        $this->assertResponseCode($response, 302, '/onboarding');
        $user = $user->reload();
        $this->assertSame('en_GB', $user->locale);
    }

    public function testUpdateLocaleRedirectsIfLocaleIsInvalid(): void
    {
        $user = $this->login([
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('POST', '/onboarding/locale', [
            'csrf' => \App\Csrf::generate(),
            'locale' => 'not a locale',
        ]);

        $this->assertResponseCode($response, 302, '/onboarding');
        $user = $user->reload();
        $this->assertSame('en_GB', $user->locale);
    }

    /**
     * @return array<array{int}>
     */
    public static function validStepsProvider(): array
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

    /**
     * @return array<array{int}>
     */
    public static function invalidStepsProvider(): array
    {
        $faker = \Faker\Factory::create();
        /** @var int */
        $number_before = $faker->numberBetween(-42, -1);
        /** @var int */
        $number_after = $faker->numberBetween(8, 42);
        return [
            [$number_before],
            [0],
            [7],
            [$number_after],
        ];
    }
}
