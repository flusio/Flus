<?php

namespace flusio\importations;

use flusio\models;
use flusio\services;
use flusio\utils;

class PocketTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    /** @var string */
    private static $pocket_consumer_key;

    /**
     * @beforeClass
     */
    public static function savePocketConsumerKey()
    {
        self::$pocket_consumer_key = \Minz\Configuration::$application['pocket_consumer_key'];
    }

    /**
     * @before
     */
    public function forcePocketConsumerKey()
    {
        // because some tests disable Pocket to test 404 pages
        \Minz\Configuration::$application['pocket_consumer_key'] = self::$pocket_consumer_key;
    }

    public function testShowRendersCorrectly()
    {
        $this->login();

        $response = $this->appRun('get', '/pocket');

        $this->assertResponse($response, 200, 'Importation from Pocket');
        $this->assertPointer($response, 'importations/pocket/show.phtml');
    }

    public function testShowRedirectsToLoginIfNotConnected()
    {
        $response = $this->appRun('get', '/pocket');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fpocket');
    }

    public function testShowFailsIfPocketNotConfigured()
    {
        \Minz\Configuration::$application['pocket_consumer_key'] = null;
        $this->login();

        $response = $this->appRun('get', '/pocket');

        $this->assertResponse($response, 404);
    }

    public function testRequestAccessSetRequestTokenAndRedirectsUser()
    {
        $user = $this->login([
            'pocket_request_token' => null,
        ]);

        $response = $this->appRun('post', '/pocket/request', [
            'csrf' => $user->csrf,
        ]);

        $user = models\User::find($user->id);
        $this->assertNotNull($user->pocket_request_token);

        $auth_url = urlencode(\Minz\Url::absoluteFor('pocket auth'));
        $expected_url = 'https://getpocket.com/auth/authorize';
        $expected_url .= "?request_token={$user->pocket_request_token}";
        $expected_url .= "&redirect_uri={$auth_url}";
        $this->assertResponse($response, 302, $expected_url);
    }

    public function testRequestAccessRedirectsToLoginIfNotConnected()
    {
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);

        $response = $this->appRun('post', '/pocket/request', [
            'csrf' => 'a token',
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fpocket');
        $user = models\User::find($user_id);
        $this->assertNull($user->pocket_request_token);
    }

    public function testRequestAccessFailsIfCsrfIsInvalid()
    {
        $user = $this->login([
            'pocket_request_token' => null,
        ]);

        $response = $this->appRun('post', '/pocket/request', [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 302, '/pocket');
        $this->assertFlash('error', 'A security verification failed.');
        $user = models\User::find($user->id);
        $this->assertNull($user->pocket_request_token);
    }

    public function testRequestAccessFailsIfPocketNotConfigured()
    {
        \Minz\Configuration::$application['pocket_consumer_key'] = null;
        $user = $this->login([
            'pocket_request_token' => null,
        ]);

        $response = $this->appRun('post', '/pocket/request', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 404);
        $user = models\User::find($user->id);
        $this->assertNull($user->pocket_request_token);
    }

    public function testAuthorizationRendersCorrectly()
    {
        $this->login([
            'pocket_request_token' => 'some token',
        ]);

        $response = $this->appRun('get', '/pocket/auth');

        $this->assertResponse($response, 200, 'Please wait while we’re verifying access to Pocket');
        $this->assertPointer($response, 'importations/pocket/authorization.phtml');
    }

    public function testAuthorizationRedirectsToLoginIfNotConnected()
    {
        $response = $this->appRun('get', '/pocket/auth');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fpocket%2Fauth');
    }

    public function testAuthorizationRedirectsIfUserHasNoRequestToken()
    {
        $this->login([
            'pocket_request_token' => null,
        ]);

        $response = $this->appRun('get', '/pocket/auth');

        $this->assertResponse($response, 302, '/pocket');
    }

    public function testAuthorizationFailsIfPocketNotConfigured()
    {
        \Minz\Configuration::$application['pocket_consumer_key'] = null;
        $this->login([
            'pocket_request_token' => 'some token',
        ]);

        $response = $this->appRun('get', '/pocket/auth');

        $this->assertResponse($response, 404);
    }

    public function testAuthorizeRedirectsToLoginIfNotConnected()
    {
        $this->create('user', [
            'csrf' => 'some token',
        ]);

        $response = $this->appRun('post', '/pocket/auth', [
            'csrf' => 'some token',
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fpocket%2Fauth');
    }

    public function testAuthorizeRedirectsIfUserHasNoRequestToken()
    {
        $user = $this->login([
            'pocket_request_token' => null,
        ]);

        $response = $this->appRun('post', '/pocket/auth', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/pocket');
    }

    public function testAuthorizeFailsIfPocketNotConfigured()
    {
        \Minz\Configuration::$application['pocket_consumer_key'] = null;
        $user = $this->login([
            'pocket_request_token' => 'some token',
        ]);

        $response = $this->appRun('post', '/pocket/auth', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 404);
    }

    public function testAuthorizeFailsIfCsrfIsInvalid()
    {
        $user = $this->login([
            'pocket_request_token' => 'some token',
        ]);

        $response = $this->appRun('post', '/pocket/auth', [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
    }
}
