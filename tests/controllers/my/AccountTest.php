<?php

namespace flusio\controllers\my;

use flusio\auth;
use flusio\models;
use flusio\services;
use flusio\utils;

class AccountTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \tests\MockHttpHelper;
    use \Minz\Tests\ApplicationHelper;
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

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'my/account/show.phtml');
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

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Your subscription will expire on');
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

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Your subscription expired on');
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

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'You have a <strong>free subscription</strong>');
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

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Create your payment account');
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

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Validate your account');
    }

    public function testShowSyncsExpiredAtIfOverdue()
    {
        $old_expired_at = \Minz\Time::ago($this->fake('randomDigitNotNull'), 'weeks');
        $new_expired_at = $this->fake('dateTime');
        $account_id = $this->fake('uuid');
        $subscription_api_url = "https://next.flus.io/api/account/expired-at?account_id={$account_id}";
        $this->mockHttpWithResponse($subscription_api_url, <<<TEXT
            HTTP/2 200
            Content-type: application/json

            {
                "expired_at": "{$new_expired_at->format(\Minz\Model::DATETIME_FORMAT)}"
            }
            TEXT
        );
        $user = $this->login([
            'subscription_account_id' => $account_id,
            'subscription_expired_at' => $old_expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', '/my/account');

        $user = models\User::find($user->id);
        $this->assertEquals($new_expired_at, $user->subscription_expired_at);
    }

    public function testShowRedirectsToLoginIfUserNotConnected()
    {
        $response = $this->appRun('get', '/my/account');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Faccount');
    }

    public function testDeletionRendersCorrectly()
    {
        $this->login();

        $response = $this->appRun('get', '/my/account/deletion');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'my/account/deletion.phtml');
    }

    public function testDeletionRedirectsToLoginIfUserNotConnected()
    {
        $response = $this->appRun('get', '/my/account/deletion');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Faccount%2Fdeletion');
    }

    public function testDeleteRedirectsToLoginAndDeletesTheUser()
    {
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => $user->csrf,
            'password' => $password,
        ]);

        $this->assertResponseCode($response, 302, '/login');
        $this->assertFlash('status', 'user_deleted');
        $this->assertFalse(models\User::exists($user->id));
        $this->assertNull(auth\CurrentUser::get());
    }

    public function testDeleteDeletesAvatarIfSet()
    {
        // we copy an existing file as an avatar file.
        $image_path = \Minz\Configuration::$app_path . '/public/static/default-card.png';
        $media_path = \Minz\Configuration::$application['media_path'];
        $avatar_filename = $this->fake('md5') . '.png';
        $subpath = utils\Belt::filenameToSubpath($avatar_filename);
        $avatar_path = "{$media_path}/avatars/{$subpath}";
        $avatar_filepath = "{$avatar_path}/{$avatar_filename}";
        @mkdir($avatar_path, 0755, true);
        copy($image_path, $avatar_filepath);

        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'avatar_filename' => $avatar_filename,
        ]);

        $this->assertTrue(file_exists($avatar_filepath));

        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => $user->csrf,
            'password' => $password,
        ]);

        $this->assertFalse(file_exists($avatar_filepath));
    }

    public function testDeleteRedirectsToLoginIfUserIsNotConnected()
    {
        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => \Minz\CSRF::generate(),
            'password' => $this->fake('password'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Faccount%2Fdeletion');
    }

    public function testDeleteDeletesSessionsAssociatedToTheUser()
    {
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $this->assertSame(1, models\Session::count());

        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => $user->csrf,
            'password' => $password,
        ]);

        $this->assertSame(0, models\Session::count());
    }

    public function testDeleteFailsIfPasswordIsIncorrect()
    {
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => $user->csrf,
            'password' => 'not the password',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The password is incorrect.');
        $this->assertTrue(models\User::exists($user->id));
    }

    public function testDeleteFailsIfCsrfIsInvalid()
    {
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => 'not the token',
            'password' => $password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertTrue(models\User::exists($user->id));
    }

    public function testDeleteFailsIfTryingToDeleteDemoAccount()
    {
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
        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'Sorry but you cannot delete the demo account 😉');
        $this->assertTrue(models\User::exists($user->id));
    }
}
