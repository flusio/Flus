<?php

namespace flusio\controllers\my;

use flusio\auth;
use flusio\models;
use flusio\utils;

class PreferencesTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testEditRendersCorrectly()
    {
        $this->login();

        $response = $this->appRun('get', '/my/preferences');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'my/preferences/edit.phtml');
    }

    public function testEditRedirectsToLoginIfUserNotConnected()
    {
        $response = $this->appRun('get', '/my/preferences');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Fpreferences');
    }

    public function testUpdateSavesTheUserAndRedirects()
    {
        $user = $this->login([
            'locale' => 'en_GB',
            'option_compact_mode' => 0,
        ]);

        $response = $this->appRun('post', '/my/preferences', [
            'csrf' => $user->csrf,
            'locale' => 'fr_FR',
            'option_compact_mode' => true,
            'from' => \Minz\Url::for('preferences'),
        ]);

        $this->assertResponseCode($response, 302, '/my/preferences');
        $user = auth\CurrentUser::reload();
        $this->assertSame('fr_FR', $user->locale);
        $this->assertSame('fr_FR', utils\Locale::currentLocale());
        $this->assertTrue($user->option_compact_mode);
    }

    public function testUpdateCanEnableBetaFeatures()
    {
        $user = $this->login();

        $this->assertFalse(models\FeatureFlag::isEnabled('beta', $user->id));

        $response = $this->appRun('post', '/my/preferences', [
            'csrf' => $user->csrf,
            'beta_enabled' => true,
            'locale' => 'fr_FR',
            'from' => \Minz\Url::for('preferences'),
        ]);

        $this->assertResponseCode($response, 302, '/my/preferences');
        $this->assertTrue(models\FeatureFlag::isEnabled('beta', $user->id));
    }

    public function testUpdateCanDisableBetaFeatures()
    {
        $user = $this->login();
        models\FeatureFlag::enable('beta', $user->id);

        $this->assertTrue(models\FeatureFlag::isEnabled('beta', $user->id));

        $response = $this->appRun('post', '/my/preferences', [
            'csrf' => $user->csrf,
            'beta_enabled' => false,
            'locale' => 'fr_FR',
            'from' => \Minz\Url::for('preferences'),
        ]);

        $this->assertResponseCode($response, 302, '/my/preferences');
        $this->assertFalse(models\FeatureFlag::isEnabled('beta', $user->id));
    }

    public function testUpdateRedirectsToLoginIfUserNotConnected()
    {
        $user_id = $this->create('user', [
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/my/preferences', [
            'csrf' => \Minz\CSRF::generate(),
            'locale' => 'fr_FR',
            'from' => \Minz\Url::for('preferences'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Fpreferences');
        $user = models\User::find($user_id);
        $this->assertSame('en_GB', $user->locale);
    }

    public function testUpdateFailsIfCsrfIsInvalid()
    {
        $user = $this->login([
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/my/preferences', [
            'csrf' => 'not the token',
            'locale' => 'fr_FR',
            'from' => \Minz\Url::for('preferences'),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $user = auth\CurrentUser::reload();
        $this->assertSame('en_GB', $user->locale);
    }

    public function testUpdateFailsIfLocaleIsMissing()
    {
        $user = $this->login([
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/my/preferences', [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('preferences'),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The locale is required');
        $user = auth\CurrentUser::reload();
        $this->assertSame('en_GB', $user->locale);
    }

    public function testUpdateFailsIfLocaleIsInvalid()
    {
        $user = $this->login([
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/my/preferences', [
            'csrf' => $user->csrf,
            'locale' => 'not a locale',
            'from' => \Minz\Url::for('preferences'),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The locale is invalid');
        $user = auth\CurrentUser::reload();
        $this->assertSame('en_GB', $user->locale);
    }
}
