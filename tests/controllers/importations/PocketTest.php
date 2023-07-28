<?php

namespace flusio\controllers\importations;

use flusio\jobs;
use flusio\models;
use flusio\services;
use flusio\utils;
use tests\factories\ImportationFactory;
use tests\factories\PocketAccountFactory;
use tests\factories\UserFactory;

class PocketTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \tests\MockHttpHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    /** @var string */
    private static $pocket_consumer_key;

    /**
     * @beforeClass
     */
    public static function savePocketConsumerKey(): void
    {
        /** @var string */
        $pocket_consumer_key = \Minz\Configuration::$application['pocket_consumer_key'];
        self::$pocket_consumer_key = $pocket_consumer_key;
    }

    /**
     * @before
     */
    public function forcePocketConsumerKey(): void
    {
        // because some tests disable Pocket to test 404 pages
        \Minz\Configuration::$application['pocket_consumer_key'] = self::$pocket_consumer_key;
    }

    public function testShowRendersCorrectly(): void
    {
        $this->login();

        $response = $this->appRun('GET', '/pocket');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Importation from Pocket');
        $this->assertResponsePointer($response, 'importations/pocket/show.phtml');
    }

    public function testShowIfImportationIsOngoing(): void
    {
        $user = $this->login();
        ImportationFactory::create([
            'type' => 'pocket',
            'user_id' => $user->id,
            'status' => 'ongoing',
        ]);

        $response = $this->appRun('GET', '/pocket');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'We’re importing your data from Pocket');
    }

    public function testShowIfImportationIsFinished(): void
    {
        $user = $this->login();
        ImportationFactory::create([
            'type' => 'pocket',
            'user_id' => $user->id,
            'status' => 'finished',
        ]);

        $response = $this->appRun('GET', '/pocket');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'We’ve imported your data from Pocket.');
    }

    public function testShowIfImportationIsInError(): void
    {
        $user = $this->login();
        /** @var string */
        $error = $this->fake('sentence');
        ImportationFactory::create([
            'type' => 'pocket',
            'user_id' => $user->id,
            'status' => 'error',
            'error' => $error,
        ]);

        $response = $this->appRun('GET', '/pocket');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $error);
    }

    public function testShowRedirectsToLoginIfNotConnected(): void
    {
        $response = $this->appRun('GET', '/pocket');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fpocket');
    }

    public function testShowFailsIfPocketNotConfigured(): void
    {
        \Minz\Configuration::$application['pocket_consumer_key'] = null;
        $this->login();

        $response = $this->appRun('GET', '/pocket');

        $this->assertResponseCode($response, 404);
    }

    public function testImportRegistersAPocketImportatorJobAndRedirects(): void
    {
        \Minz\Configuration::$jobs_adapter = 'database';
        $user = $this->login();
        $pocket_account = PocketAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'some token',
        ]);

        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());

        $response = $this->appRun('POST', '/pocket', [
            'csrf' => $user->csrf,
        ]);

        \Minz\Configuration::$jobs_adapter = 'test';

        $this->assertSame(1, models\Importation::count());
        $this->assertSame(1, \Minz\Job::count());

        $this->assertResponseCode($response, 302, '/pocket');
        $importation = models\Importation::take();
        $this->assertNotNull($importation);
        $job = \Minz\Job::take();
        $this->assertNotNull($job);
        $this->assertSame(jobs\PocketImportator::class, $job->name);
        $this->assertSame([$importation->id], $job->args);
    }

    public function testImportRedirectsToLoginIfNotConnected(): void
    {
        \Minz\Configuration::$jobs_adapter = 'database';
        $user = UserFactory::create([
            'csrf' => 'some token',
        ]);
        $pocket_account = PocketAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'some token',
        ]);

        $response = $this->appRun('POST', '/pocket', [
            'csrf' => 'some token',
        ]);

        \Minz\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fpocket');
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());
    }

    public function testImportFailsIfPocketNotConfigured(): void
    {
        \Minz\Configuration::$application['pocket_consumer_key'] = null;
        \Minz\Configuration::$jobs_adapter = 'database';
        $user = $this->login();
        $pocket_account = PocketAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'some token',
        ]);

        $response = $this->appRun('POST', '/pocket', [
            'csrf' => $user->csrf,
        ]);

        \Minz\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 404);
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());
    }

    public function testImportFailsIfUserHasNoAccessToken(): void
    {
        \Minz\Configuration::$jobs_adapter = 'database';
        $user = $this->login();
        $pocket_account = PocketAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => null,
        ]);

        $response = $this->appRun('POST', '/pocket', [
            'csrf' => $user->csrf,
        ]);

        \Minz\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'You didn’t authorize us to access your Pocket data');
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());
    }

    public function testImportFailsIfCsrfIsInvalid(): void
    {
        \Minz\Configuration::$jobs_adapter = 'database';
        $user = $this->login();
        $pocket_account = PocketAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'some token',
        ]);

        $response = $this->appRun('POST', '/pocket', [
            'csrf' => 'not the token',
        ]);

        \Minz\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());
    }

    public function testImportFailsIfAnImportAlreadyExists(): void
    {
        \Minz\Configuration::$jobs_adapter = 'database';
        $user = $this->login();
        $pocket_account = PocketAccountFactory::create([
            'user_id' => $user->id,
            'access_token' => 'some token',
        ]);
        ImportationFactory::create([
            'type' => 'pocket',
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', '/pocket', [
            'csrf' => $user->csrf,
        ]);

        \Minz\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'You already have an ongoing Pocket importation');
        $this->assertSame(1, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());
    }

    public function testRequestAccessSetRequestTokenAndRedirectsUser(): void
    {
        /** @var string */
        $code = $this->fake('uuid');
        $this->mockHttpWithResponse('https://getpocket.com/v3/oauth/request', <<<TEXT
            HTTP/2 200
            Content-type: application/json

            {"code": "{$code}"}
            TEXT
        );
        $user = $this->login();

        $this->assertSame(0, models\PocketAccount::count());

        $response = $this->appRun('POST', '/pocket/request', [
            'csrf' => $user->csrf,
        ]);

        $pocket_account = models\PocketAccount::take();
        $this->assertNotNull($pocket_account);
        $this->assertSame($code, $pocket_account->request_token);
        $auth_url = urlencode(\Minz\Url::absoluteFor('pocket auth'));
        $expected_url = 'https://getpocket.com/auth/authorize';
        $expected_url .= "?request_token={$pocket_account->request_token}";
        $expected_url .= "&redirect_uri={$auth_url}";
        $this->assertResponseCode($response, 302, $expected_url);
    }

    public function testRequestAccessRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create([
            'csrf' => 'a token',
        ]);
        $pocket_account = PocketAccountFactory::create([
            'user_id' => $user->id,
            'request_token' => null,
        ]);

        $response = $this->appRun('POST', '/pocket/request', [
            'csrf' => 'a token',
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fpocket');
        $pocket_account = $pocket_account->reload();
        $this->assertNull($pocket_account->request_token);
    }

    public function testRequestAccessFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $pocket_account = PocketAccountFactory::create([
            'user_id' => $user->id,
            'request_token' => null,
        ]);

        $response = $this->appRun('POST', '/pocket/request', [
            'csrf' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/pocket');
        $this->assertSame('A security verification failed.', \Minz\Flash::get('error'));
        $pocket_account = $pocket_account->reload();
        $this->assertNull($pocket_account->request_token);
    }

    public function testRequestAccessFailsIfPocketNotConfigured(): void
    {
        \Minz\Configuration::$application['pocket_consumer_key'] = null;
        $user = $this->login();
        $pocket_account = PocketAccountFactory::create([
            'user_id' => $user->id,
            'request_token' => null,
        ]);

        $response = $this->appRun('POST', '/pocket/request', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 404);
        $pocket_account = $pocket_account->reload();
        $this->assertNull($pocket_account->request_token);
    }

    public function testAuthorizationRendersCorrectly(): void
    {
        $user = $this->login();
        $pocket_account = PocketAccountFactory::create([
            'user_id' => $user->id,
            'request_token' => 'some token',
        ]);

        $response = $this->appRun('GET', '/pocket/auth');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Please wait while we’re verifying access to Pocket');
        $this->assertResponsePointer($response, 'importations/pocket/authorization.phtml');
    }

    public function testAuthorizationRedirectsToLoginIfNotConnected(): void
    {
        $response = $this->appRun('GET', '/pocket/auth');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fpocket%2Fauth');
    }

    public function testAuthorizationRedirectsIfUserHasNoRequestToken(): void
    {
        $user = $this->login();
        $pocket_account = PocketAccountFactory::create([
            'user_id' => $user->id,
            'request_token' => null,
        ]);

        $response = $this->appRun('GET', '/pocket/auth');

        $this->assertResponseCode($response, 302, '/pocket');
    }

    public function testAuthorizationFailsIfPocketNotConfigured(): void
    {
        \Minz\Configuration::$application['pocket_consumer_key'] = null;
        $user = $this->login();
        $pocket_account = PocketAccountFactory::create([
            'user_id' => $user->id,
            'request_token' => 'some token',
        ]);

        $response = $this->appRun('GET', '/pocket/auth');

        $this->assertResponseCode($response, 404);
    }

    public function testAuthorizeRedirectsToLoginIfNotConnected(): void
    {
        UserFactory::create([
            'csrf' => 'some token',
        ]);

        $response = $this->appRun('POST', '/pocket/auth', [
            'csrf' => 'some token',
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fpocket%2Fauth');
    }

    public function testAuthorizeRedirectsIfUserHasNoRequestToken(): void
    {
        $user = $this->login();
        $pocket_account = PocketAccountFactory::create([
            'user_id' => $user->id,
            'request_token' => null,
        ]);

        $response = $this->appRun('POST', '/pocket/auth', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, '/pocket');
    }

    public function testAuthorizeFailsIfPocketNotConfigured(): void
    {
        \Minz\Configuration::$application['pocket_consumer_key'] = null;
        $user = $this->login();
        $pocket_account = PocketAccountFactory::create([
            'user_id' => $user->id,
            'request_token' => 'some token',
        ]);

        $response = $this->appRun('POST', '/pocket/auth', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testAuthorizeFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $pocket_account = PocketAccountFactory::create([
            'user_id' => $user->id,
            'request_token' => 'some token',
        ]);

        $response = $this->appRun('POST', '/pocket/auth', [
            'csrf' => 'not the token',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
    }
}
