<?php

namespace flusio;

class OnboardingTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
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

    public function testShowAtStep4RendersAllTopics()
    {
        $this->login();
        $label1 = 'lorem ipsum';
        $label2 = 'dolor sit amet';
        $this->create('topic', ['label' => $label1]);
        $this->create('topic', ['label' => $label2]);

        $response = $this->appRun('GET', '/onboarding', [
            'step' => 4,
        ]);

        $output = $response->render();
        $this->assertStringContainsString($label1, $output);
        $this->assertStringContainsString($label2, $output);
    }

    public function testShowAtStep4RendersOnlyUserTopicsIfAssociated()
    {
        $user = $this->login();
        $label1 = 'lorem ipsum';
        $label2 = 'dolor sit amet';
        $topic_id1 = $this->create('topic', ['label' => $label1]);
        $topic_id2 = $this->create('topic', ['label' => $label2]);
        $this->create('user_to_topic', [
            'user_id' => $user->id,
            'topic_id' => $topic_id1,
        ]);

        $response = $this->appRun('GET', '/onboarding', [
            'step' => 4,
        ]);

        $output = $response->render();
        $this->assertStringContainsString($label1, $output);
        $this->assertStringNotContainsString($label2, $output);
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

    public function testUpdateTopicsSetsTopicsAndRedirect()
    {
        $user = $this->login();
        $topic_id = $this->create('topic');

        $response = $this->appRun('POST', '/onboarding/topics', [
            'csrf' => $user->csrf,
            'topic_ids' => [$topic_id],
        ]);

        $this->assertResponse($response, 302, '/onboarding?step=5');
        $user = models\User::find($user->id);
        $topic_ids = array_column($user->topics(), 'id');
        $this->assertSame([$topic_id], $topic_ids);
    }

    public function testUpdateTopicsRedirectsIfNotConnected()
    {
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $topic_id = $this->create('topic');

        $response = $this->appRun('POST', '/onboarding/topics', [
            'csrf' => 'a token',
            'topic_ids' => [$topic_id],
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fonboarding%3Fstep%3D4');
        $user = models\User::find($user_id);
        $topic_ids = array_column($user->topics(), 'id');
        $this->assertSame([], $topic_ids);
    }

    public function testUpdateTopicsRedirectsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $topic_id = $this->create('topic');

        $response = $this->appRun('POST', '/onboarding/topics', [
            'csrf' => 'not the token',
            'topic_ids' => [$topic_id],
        ]);

        $this->assertResponse($response, 302, '/onboarding?step=4');
        $user = models\User::find($user->id);
        $topic_ids = array_column($user->topics(), 'id');
        $this->assertSame([], $topic_ids);
    }

    public function testUpdateTopicsRedirectsIfTopicIdsIsInvalid()
    {
        $user = $this->login();
        $topic_id = $this->create('topic');

        $response = $this->appRun('POST', '/onboarding/topics', [
            'csrf' => 'not the token',
            'topic_ids' => [$topic_id, 'not an id'],
        ]);

        $this->assertResponse($response, 302, '/onboarding?step=4');
        $user = models\User::find($user->id);
        $topic_ids = array_column($user->topics(), 'id');
        $this->assertSame([], $topic_ids);
    }

    public function validStepsProvider()
    {
        return [
            [1],
            [2],
            [3],
            [4],
            [5],
        ];
    }

    public function invalidStepsProvider()
    {
        $faker = \Faker\Factory::create();
        return [
            [$faker->numberBetween(-42, -1)],
            [0],
            [6],
            [$faker->numberBetween(7, 42)],
        ];
    }
}
