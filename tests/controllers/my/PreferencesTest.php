<?php

namespace App\controllers\my;

use App\auth;
use App\models;
use App\utils;
use tests\factories\UserFactory;

class PreferencesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testEditRendersCorrectly(): void
    {
        $this->login();

        $response = $this->appRun('GET', '/my/preferences');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'my/preferences/edit.phtml');
    }

    public function testEditRedirectsToLoginIfUserNotConnected(): void
    {
        $response = $this->appRun('GET', '/my/preferences');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Fpreferences');
    }

    public function testUpdateSavesTheUserAndRedirects(): void
    {
        $user = $this->login([
            'locale' => 'en_GB',
            'option_compact_mode' => false,
        ]);

        $response = $this->appRun('POST', '/my/preferences', [
            'csrf' => $user->csrf,
            'locale' => 'fr_FR',
            'option_compact_mode' => true,
            'from' => \Minz\Url::for('preferences'),
        ]);

        $this->assertResponseCode($response, 302, '/my/preferences');
        $user = $user->reload();
        $this->assertSame('fr_FR', $user->locale);
        $this->assertSame('fr_FR', utils\Locale::currentLocale());
        $this->assertTrue($user->option_compact_mode);
    }

    public function testUpdateCanEnableBetaFeatures(): void
    {
        $user = $this->login();

        $this->assertFalse(models\FeatureFlag::isEnabled('beta', $user->id));

        $response = $this->appRun('POST', '/my/preferences', [
            'csrf' => $user->csrf,
            'beta_enabled' => true,
            'locale' => 'fr_FR',
            'from' => \Minz\Url::for('preferences'),
        ]);

        $this->assertResponseCode($response, 302, '/my/preferences');
        $this->assertTrue(models\FeatureFlag::isEnabled('beta', $user->id));
    }

    public function testUpdateCanDisableBetaFeatures(): void
    {
        $user = $this->login();
        models\FeatureFlag::enable('beta', $user->id);

        $this->assertTrue(models\FeatureFlag::isEnabled('beta', $user->id));

        $response = $this->appRun('POST', '/my/preferences', [
            'csrf' => $user->csrf,
            'beta_enabled' => false,
            'locale' => 'fr_FR',
            'from' => \Minz\Url::for('preferences'),
        ]);

        $this->assertResponseCode($response, 302, '/my/preferences');
        $this->assertFalse(models\FeatureFlag::isEnabled('beta', $user->id));
    }

    public function testUpdateRedirectsToLoginIfUserNotConnected(): void
    {
        $user = UserFactory::create([
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('POST', '/my/preferences', [
            'csrf' => \Minz\Csrf::generate(),
            'locale' => 'fr_FR',
            'from' => \Minz\Url::for('preferences'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Fpreferences');
        $user = $user->reload();
        $this->assertSame('en_GB', $user->locale);
    }

    public function testUpdateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login([
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('POST', '/my/preferences', [
            'csrf' => 'not the token',
            'locale' => 'fr_FR',
            'from' => \Minz\Url::for('preferences'),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $user = $user->reload();
        $this->assertSame('en_GB', $user->locale);
    }

    public function testUpdateFailsIfLocaleIsMissing(): void
    {
        $user = $this->login([
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('POST', '/my/preferences', [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('preferences'),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The locale is required');
        $user = $user->reload();
        $this->assertSame('en_GB', $user->locale);
    }

    public function testUpdateFailsIfLocaleIsInvalid(): void
    {
        $user = $this->login([
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('POST', '/my/preferences', [
            'csrf' => $user->csrf,
            'locale' => 'not a locale',
            'from' => \Minz\Url::for('preferences'),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The locale is invalid');
        $user = $user->reload();
        $this->assertSame('en_GB', $user->locale);
    }
}
