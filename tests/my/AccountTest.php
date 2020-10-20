<?php

namespace flusio\my;

use flusio\models;
use flusio\services;
use flusio\utils;

class AccountTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    /**
     * @before
     */
    public function initializeSubscriptionConfiguration()
    {
        \Minz\Configuration::$application['subscriptions_enabled'] = true;
    }

    /**
     * @afterClass
     */
    public static function resetSubscriptionConfiguration()
    {
        \Minz\Configuration::$application['subscriptions_enabled'] = false;
    }

    public function testShowRendersCorrectly()
    {
        $this->login();

        $response = $this->appRun('get', '/my/account');

        $this->assertResponse($response, 200);
        $this->assertPointer($response, 'my/account/show.phtml');
    }

    public function testShowRendersIfSubscriptionIsNotOverdue()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('randomDigitNotNull'), 'weeks');
        $this->login([
            'subscription_account_id' => $this->fake('regexify', '\w{32}'),
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', '/my/account');

        $this->assertResponse($response, 200, 'Your subscription will expire on');
    }

    public function testShowRendersIfSubscriptionIsOverdue()
    {
        $expired_at = \Minz\Time::ago($this->fake('randomDigitNotNull'), 'weeks');
        $this->login([
            'subscription_account_id' => $this->fake('regexify', '\w{32}'),
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', '/my/account');

        $this->assertResponse($response, 200, 'Your subscription expired on');
    }

    public function testShowRendersIfSubscriptionIsExempted()
    {
        $expired_at = new \DateTime('1970-01-01');
        $this->login([
            'subscription_account_id' => $this->fake('regexify', '\w{32}'),
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', '/my/account');

        $this->assertResponse($response, 200, 'You have a <strong>free subscription</strong>');
    }

    public function testShowRendersIfUserHasNoSubscriptionAccountId()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('randomDigitNotNull'), 'weeks');
        $this->login([
            'subscription_account_id' => null,
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', '/my/account');

        $this->assertResponse($response, 200, 'Create your payment account');
    }

    public function testShowRendersIfUserIsNotValidated()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('randomDigitNotNull'), 'weeks');
        $this->login([
            'subscription_account_id' => $this->fake('regexify', '\w{32}'),
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'created_at' => \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => null,
        ]);

        $response = $this->appRun('get', '/my/account');

        $this->assertResponse($response, 200, 'Validate your account');
    }

    public function testShowSyncsExpiredAtIfOverdue()
    {
        $user_dao = new models\dao\User();
        $app_conf = \Minz\Configuration::$application;
        $subscriptions_service = new services\Subscriptions(
            $app_conf['subscriptions_host'],
            $app_conf['subscriptions_private_key']
        );
        $account = $subscriptions_service->account($this->fake('email'));
        $expired_at = \Minz\Time::ago($this->fake('randomDigitNotNull'), 'weeks');
        $user = $this->login([
            'subscription_account_id' => $account['id'],
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', '/my/account');

        $user = new models\User($user_dao->find($user->id));
        $this->assertNotSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testShowRedirectsToLoginIfUserNotConnected()
    {
        $response = $this->appRun('get', '/my/account');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fmy%2Faccount');
    }

    public function testDeletionRendersCorrectly()
    {
        $this->login();

        $response = $this->appRun('get', '/my/account/deletion');

        $this->assertResponse($response, 200);
        $this->assertPointer($response, 'my/account/deletion.phtml');
    }

    public function testDeletionRedirectsToLoginIfUserNotConnected()
    {
        $response = $this->appRun('get', '/my/account/deletion');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fmy%2Faccount%2Fdeletion');
    }

    public function testDeleteRedirectsToLoginAndDeletesTheUser()
    {
        $user_dao = new models\dao\User();

        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => $user->csrf,
            'password' => $password,
        ]);

        $this->assertResponse($response, 302, '/login');
        $this->assertFlash('status', 'user_deleted');
        $this->assertNull($user_dao->find($user->id));
        $this->assertNull(utils\CurrentUser::get());
    }

    public function testDeleteRedirectsToLoginIfUserIsNotConnected()
    {
        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'password' => $this->fake('password'),
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fmy%2Faccount%2Fdeletion');
    }

    public function testDeleteDeletesSessionsAssociatedToTheUser()
    {
        $user_dao = new models\dao\User();
        $session_dao = new models\dao\Session();

        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $this->assertSame(1, $session_dao->count());

        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => $user->csrf,
            'password' => $password,
        ]);

        $this->assertSame(0, $session_dao->count());
    }

    public function testDeleteFailsIfPasswordIsIncorrect()
    {
        $user_dao = new models\dao\User();

        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => $user->csrf,
            'password' => 'not the password',
        ]);

        $this->assertResponse($response, 400, 'The password is incorrect.');
        $this->assertNotNull($user_dao->find($user->id));
    }

    public function testDeleteFailsIfCsrfIsInvalid()
    {
        $user_dao = new models\dao\User();

        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => 'not the token',
            'password' => $password,
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $this->assertNotNull($user_dao->find($user->id));
    }

    public function testDeleteFailsIfTryingToDeleteDemoAccount()
    {
        $user_dao = new models\dao\User();
        \Minz\Configuration::$application['demo'] = true;

        $password = $this->fake('password');
        $user = $this->login([
            'email' => 'demo@flus.io',
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => $user->csrf,
            'password' => $password,
        ]);

        \Minz\Configuration::$application['demo'] = false;
        $this->assertResponse($response, 400, 'Sorry but you cannot delete the demo account 😉');
        $this->assertNotNull($user_dao->find($user->id));
    }
}
