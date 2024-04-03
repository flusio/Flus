<?php

namespace App\controllers\my;

use App\auth;
use App\models;
use App\services;
use App\utils;

class AccountTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \tests\MockHttpHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    /**
     * @before
     */
    public function initializeSubscriptionConfiguration(): void
    {
        \Minz\Configuration::$application['subscriptions_enabled'] = true;
    }

    /**
     * @afterClass
     */
    public static function resetSubscriptionConfiguration(): void
    {
        \Minz\Configuration::$application['subscriptions_enabled'] = false;
    }

    public function testShowRendersCorrectly(): void
    {
        /** @var string */
        $username = $this->fake('username');
        $this->login([
            'username' => $username,
        ]);

        $response = $this->appRun('GET', '/my/account');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $username);
        $this->assertResponsePointer($response, 'my/account/show.phtml');
    }

    public function testShowRendersIfSubscriptionIsNotOverdue(): void
    {
        /** @var int */
        $weeks = $this->fake('randomDigitNotNull');
        $expired_at = \Minz\Time::fromNow($weeks, 'weeks');
        /** @var string */
        $account_id = $this->fake('regexify', '\w{32}');
        $this->login([
            'subscription_account_id' => $account_id,
            'subscription_expired_at' => $expired_at,
            'validated_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('GET', '/my/account');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Your subscription will expire on');
    }

    public function testShowRendersIfSubscriptionIsOverdue(): void
    {
        /** @var int */
        $weeks = $this->fake('randomDigitNotNull');
        $expired_at = \Minz\Time::ago($weeks, 'weeks');
        /** @var string */
        $account_id = $this->fake('regexify', '\w{32}');
        $this->login([
            'subscription_account_id' => $account_id,
            'subscription_expired_at' => $expired_at,
            'validated_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('GET', '/my/account');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Your subscription expired on');
    }

    public function testShowRendersIfSubscriptionIsExempted(): void
    {
        $expired_at = new \DateTimeImmutable('1970-01-01');
        /** @var string */
        $account_id = $this->fake('regexify', '\w{32}');
        $this->login([
            'subscription_account_id' => $account_id,
            'subscription_expired_at' => $expired_at,
            'validated_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('GET', '/my/account');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'You have a <strong>free subscription</strong>');
    }

    public function testShowRendersIfUserHasNoSubscriptionAccountId(): void
    {
        /** @var int */
        $weeks = $this->fake('randomDigitNotNull');
        $expired_at = \Minz\Time::fromNow($weeks, 'weeks');
        $this->login([
            'subscription_account_id' => null,
            'subscription_expired_at' => $expired_at,
            'validated_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('GET', '/my/account');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Create your payment account');
    }

    public function testShowRendersIfUserIsNotValidated(): void
    {
        /** @var int */
        $weeks = $this->fake('randomDigitNotNull');
        $expired_at = \Minz\Time::fromNow($weeks, 'weeks');
        /** @var string */
        $account_id = $this->fake('regexify', '\w{32}');
        $this->login([
            'subscription_account_id' => $account_id,
            'subscription_expired_at' => $expired_at,
            'created_at' => \Minz\Time::now(),
            'validated_at' => null,
        ]);

        $response = $this->appRun('GET', '/my/account');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Validate your account');
    }

    public function testShowSyncsExpiredAtIfOverdue(): void
    {
        /** @var string */
        $subscriptions_host = \Minz\Configuration::$application['subscriptions_host'];
        /** @var int */
        $weeks = $this->fake('randomDigitNotNull');
        $old_expired_at = \Minz\Time::ago($weeks, 'weeks');
        /** @var \DateTimeImmutable */
        $new_expired_at = $this->fake('dateTime');
        /** @var string */
        $account_id = $this->fake('uuid');
        $subscription_api_url = "{$subscriptions_host}/api/account/expired-at?account_id={$account_id}";
        $this->mockHttpWithResponse($subscription_api_url, <<<TEXT
            HTTP/2 200
            Content-type: application/json

            {
                "expired_at": "{$new_expired_at->format(\Minz\Database\Column::DATETIME_FORMAT)}"
            }
            TEXT
        );
        $user = $this->login([
            'subscription_account_id' => $account_id,
            'subscription_expired_at' => $old_expired_at,
            'validated_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('GET', '/my/account');

        $user = $user->reload();
        $this->assertEquals($new_expired_at, $user->subscription_expired_at);
    }

    public function testShowRedirectsToLoginIfUserNotConnected(): void
    {
        $response = $this->appRun('GET', '/my/account');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Faccount');
    }

    public function testDeletionRendersCorrectly(): void
    {
        $this->login();

        $response = $this->appRun('GET', '/my/account/deletion');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'my/account/deletion.phtml');
    }

    public function testDeletionRedirectsToLoginIfUserNotConnected(): void
    {
        $response = $this->appRun('GET', '/my/account/deletion');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Faccount%2Fdeletion');
    }

    public function testDeleteRedirectsToLoginAndDeletesTheUser(): void
    {
        /** @var string */
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('POST', '/my/account/deletion', [
            'csrf' => $user->csrf,
            'password' => $password,
        ]);

        $this->assertResponseCode($response, 302, '/login');
        $this->assertSame('user_deleted', \Minz\Flash::get('status'));
        $this->assertFalse(models\User::exists($user->id));
        $this->assertNull(auth\CurrentUser::get());
    }

    public function testDeleteDeletesAvatarIfSet(): void
    {
        // we copy an existing file as an avatar file.
        $image_path = \Minz\Configuration::$app_path . '/public/static/default-card.png';
        /** @var string */
        $media_path = \Minz\Configuration::$application['media_path'];
        /** @var string */
        $avatar_filename = $this->fake('md5');
        $avatar_filename = $avatar_filename . '.png';
        $subpath = utils\Belt::filenameToSubpath($avatar_filename);
        $avatar_path = "{$media_path}/avatars/{$subpath}";
        $avatar_filepath = "{$avatar_path}/{$avatar_filename}";
        @mkdir($avatar_path, 0755, true);
        copy($image_path, $avatar_filepath);

        /** @var string */
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'avatar_filename' => $avatar_filename,
        ]);

        $this->assertTrue(file_exists($avatar_filepath));

        $response = $this->appRun('POST', '/my/account/deletion', [
            'csrf' => $user->csrf,
            'password' => $password,
        ]);

        $this->assertFalse(file_exists($avatar_filepath));
    }

    public function testDeleteRedirectsToLoginIfUserIsNotConnected(): void
    {
        $response = $this->appRun('POST', '/my/account/deletion', [
            'csrf' => \Minz\Csrf::generate(),
            'password' => $this->fake('password'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Faccount%2Fdeletion');
    }

    public function testDeleteDeletesSessionsAssociatedToTheUser(): void
    {
        /** @var string */
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $this->assertSame(1, models\Session::count());

        $response = $this->appRun('POST', '/my/account/deletion', [
            'csrf' => $user->csrf,
            'password' => $password,
        ]);

        $this->assertSame(0, models\Session::count());
    }

    public function testDeleteFailsIfPasswordIsIncorrect(): void
    {
        /** @var string */
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('POST', '/my/account/deletion', [
            'csrf' => $user->csrf,
            'password' => 'not the password',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The password is incorrect.');
        $this->assertTrue(models\User::exists($user->id));
    }

    public function testDeleteFailsIfCsrfIsInvalid(): void
    {
        /** @var string */
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('POST', '/my/account/deletion', [
            'csrf' => 'not the token',
            'password' => $password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertTrue(models\User::exists($user->id));
    }

    public function testDeleteFailsIfTryingToDeleteDemoAccount(): void
    {
        \Minz\Configuration::$application['demo'] = true;

        /** @var string */
        $password = $this->fake('password');
        $user = $this->login([
            'email' => 'demo@flus.io',
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('POST', '/my/account/deletion', [
            'csrf' => $user->csrf,
            'password' => $password,
        ]);

        \Minz\Configuration::$application['demo'] = false;
        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'Sorry but you cannot delete the demo account 😉');
        $this->assertTrue(models\User::exists($user->id));
    }
}
