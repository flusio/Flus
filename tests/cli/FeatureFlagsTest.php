<?php

namespace flusio\cli;

use flusio\models;

class FeatureFlagsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;

    /**
     * @beforeClass
     */
    public static function loadApplication()
    {
        self::$application = new \flusio\cli\Application();
    }

    public function testIndexRendersCorrectly()
    {
        $response = $this->appRun('cli', '/features');

        $this->assertResponse($response, 200);
        $output = $response->render();
        $this->assertSame('beta', $output);
    }

    public function testFlagsRendersCorrectly()
    {
        $email = $this->fake('email');
        $user_id = $this->create('user', [
            'email' => $email,
        ]);
        $this->create('feature_flag', [
            'type' => 'beta',
            'user_id' => $user_id,
        ]);

        $response = $this->appRun('cli', '/features/flags');

        $this->assertResponse($response, 200);
        $output = $response->render();
        $this->assertSame("beta {$user_id} {$email}", $output);
    }

    public function testFlagsDisplaysIfNoFeatureFlags()
    {
        $response = $this->appRun('cli', '/features/flags');

        $this->assertResponse($response, 200);
        $output = $response->render();
        $this->assertSame('No feature flags', $output);
    }

    public function testEnableCreatesAFeatureFlagAndRendersCorrectly()
    {
        $email = $this->fake('email');
        $user_id = $this->create('user', [
            'email' => $email,
        ]);

        $this->assertSame(0, models\FeatureFlag::count());

        $response = $this->appRun('cli', '/features/enable', [
            'type' => 'beta',
            'user_id' => $user_id,
        ]);

        $this->assertResponse($response, 200, "beta is enabled for user {$user_id} ({$email})");
        $this->assertSame(1, models\FeatureFlag::count());
    }

    public function testEnableFailsIfTypeIsInvalid()
    {
        $email = $this->fake('email');
        $user_id = $this->create('user', [
            'email' => $email,
        ]);

        $response = $this->appRun('cli', '/features/enable', [
            'type' => 'not a type',
            'user_id' => $user_id,
        ]);

        $this->assertResponse($response, 400, 'not a type is not a valid feature flag type');
        $this->assertSame(0, models\FeatureFlag::count());
    }

    public function testEnableFailsIfUserDoesNotExist()
    {
        $response = $this->appRun('cli', '/features/enable', [
            'type' => 'beta',
            'user_id' => 'not an id',
        ]);

        $this->assertResponse($response, 404, 'User not an id doesn’t exist');
        $this->assertSame(0, models\FeatureFlag::count());
    }

    public function testDisableDeletesAFeatureFlagsAndRendersCorrectly()
    {
        $email = $this->fake('email');
        $user_id = $this->create('user', [
            'email' => $email,
        ]);
        $this->create('feature_flag', [
            'type' => 'beta',
            'user_id' => $user_id,
        ]);

        $this->assertSame(1, models\FeatureFlag::count());

        $response = $this->appRun('cli', '/features/disable', [
            'type' => 'beta',
            'user_id' => $user_id,
        ]);

        $this->assertResponse($response, 200, "beta is disabled for user {$user_id} ({$email})");
        $this->assertSame(0, models\FeatureFlag::count());
    }

    public function testDisableFailsIfUserDoesNotExist()
    {
        $email = $this->fake('email');
        $user_id = $this->create('user', [
            'email' => $email,
        ]);
        $this->create('feature_flag', [
            'type' => 'beta',
            'user_id' => $user_id,
        ]);

        $this->assertSame(1, models\FeatureFlag::count());

        $response = $this->appRun('cli', '/features/disable', [
            'type' => 'beta',
            'user_id' => 'not an id',
        ]);

        $this->assertResponse($response, 404, 'User not an id doesn’t exist');
        $this->assertSame(1, models\FeatureFlag::count());
    }

    public function testDisableFailsIfFeatureFlagIsNotEnabledForUser()
    {
        $email = $this->fake('email');
        $user_id = $this->create('user', [
            'email' => $email,
        ]);

        $this->assertSame(0, models\FeatureFlag::count());

        $response = $this->appRun('cli', '/features/disable', [
            'type' => 'beta',
            'user_id' => $user_id,
        ]);

        $this->assertResponse($response, 400, "Feature flag beta isn’t enabled for user {$user_id}");
        $this->assertSame(0, models\FeatureFlag::count());
    }
}
