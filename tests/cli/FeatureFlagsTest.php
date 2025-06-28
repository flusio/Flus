<?php

namespace App\cli;

use App\models;
use tests\factories\UserFactory;
use tests\factories\FeatureFlagFactory;

class FeatureFlagsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function loadApplication(): void
    {
        self::$application = new \App\cli\Application();
    }

    public function testIndexRendersCorrectly(): void
    {
        $response = $this->appRun('CLI', '/features');

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "alpha\nbeta");
    }

    public function testFlagsRendersCorrectly(): void
    {
        /** @var string */
        $email = $this->fake('email');
        $user = UserFactory::create([
            'email' => $email,
        ]);
        FeatureFlagFactory::create([
            'type' => 'beta',
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('CLI', '/features/flags');

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "beta {$user->id} {$email}");
    }

    public function testFlagsDisplaysIfNoFeatureFlags(): void
    {
        $response = $this->appRun('CLI', '/features/flags');

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'No feature flags');
    }

    public function testEnableCreatesAFeatureFlagAndRendersCorrectly(): void
    {
        /** @var string */
        $email = $this->fake('email');
        $user = UserFactory::create([
            'email' => $email,
        ]);

        $this->assertSame(0, models\FeatureFlag::count());

        $response = $this->appRun('CLI', '/features/enable', [
            'type' => 'beta',
            'user_id' => $user->id,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "beta is enabled for user {$user->id} ({$email})");
        $this->assertSame(1, models\FeatureFlag::count());
    }

    public function testEnableFailsIfTypeIsInvalid(): void
    {
        /** @var string */
        $email = $this->fake('email');
        $user = UserFactory::create([
            'email' => $email,
        ]);

        $response = $this->appRun('CLI', '/features/enable', [
            'type' => 'not a type',
            'user_id' => $user->id,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseEquals($response, 'not a type is not a valid feature flag type');
        $this->assertSame(0, models\FeatureFlag::count());
    }

    public function testEnableFailsIfUserDoesNotExist(): void
    {
        $response = $this->appRun('CLI', '/features/enable', [
            'type' => 'beta',
            'user_id' => 'not an id',
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertResponseEquals($response, 'User not an id doesn’t exist');
        $this->assertSame(0, models\FeatureFlag::count());
    }

    public function testDisableDeletesAFeatureFlagsAndRendersCorrectly(): void
    {
        /** @var string */
        $email = $this->fake('email');
        $user = UserFactory::create([
            'email' => $email,
        ]);
        FeatureFlagFactory::create([
            'type' => 'beta',
            'user_id' => $user->id,
        ]);

        $this->assertSame(1, models\FeatureFlag::count());

        $response = $this->appRun('CLI', '/features/disable', [
            'type' => 'beta',
            'user_id' => $user->id,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "beta is disabled for user {$user->id} ({$email})");
        $this->assertSame(0, models\FeatureFlag::count());
    }

    public function testDisableFailsIfUserDoesNotExist(): void
    {
        /** @var string */
        $email = $this->fake('email');
        $user = UserFactory::create([
            'email' => $email,
        ]);
        FeatureFlagFactory::create([
            'type' => 'beta',
            'user_id' => $user->id,
        ]);

        $this->assertSame(1, models\FeatureFlag::count());

        $response = $this->appRun('CLI', '/features/disable', [
            'type' => 'beta',
            'user_id' => 'not an id',
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertResponseEquals($response, 'User not an id doesn’t exist');
        $this->assertSame(1, models\FeatureFlag::count());
    }
}
