<?php

namespace flusio\controllers\my;

use flusio\auth;
use flusio\models;

class ProfileTest extends \PHPUnit\Framework\TestCase
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

        $response = $this->appRun('get', '/my/profile', [
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'my/profile/edit.phtml');
    }

    public function testEditRedirectsToLoginIfUserNotConnected()
    {
        $response = $this->appRun('get', '/my/profile', [
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Fprofile');
    }

    public function testUpdateSavesTheUserAndRedirects()
    {
        $old_username = $this->fakeUnique('username');
        $new_username = $this->fakeUnique('username');
        $user = $this->login([
            'username' => $old_username,
        ]);

        $response = $this->appRun('post', '/my/profile', [
            'csrf' => $user->csrf,
            'username' => $new_username,
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 302, '/my/profile');
        $user = auth\CurrentUser::reload();
        $this->assertSame($new_username, $user->username);
    }

    public function testUpdateRedirectsToLoginIfUserNotConnected()
    {
        $old_username = $this->fakeUnique('username');
        $new_username = $this->fakeUnique('username');
        $user_id = $this->create('user', [
            'username' => $old_username,
        ]);

        $response = $this->appRun('post', '/my/profile', [
            'csrf' => \Minz\CSRF::generate(),
            'username' => $new_username,
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Fprofile');
        $user = models\User::find($user_id);
        $this->assertSame($old_username, $user->username);
    }

    public function testUpdateFailsIfCsrfIsInvalid()
    {
        $old_username = $this->fakeUnique('username');
        $new_username = $this->fakeUnique('username');
        $user = $this->login([
            'username' => $old_username,
        ]);

        $response = $this->appRun('post', '/my/profile', [
            'csrf' => 'not the token',
            'username' => $new_username,
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $user = auth\CurrentUser::reload();
        $this->assertSame($old_username, $user->username);
    }

    public function testUpdateFailsIfUsernameIsTooLong()
    {
        $old_username = $this->fakeUnique('username');
        $new_username = $this->fake('sentence', 50, false);
        $user = $this->login([
            'username' => $old_username,
        ]);

        $response = $this->appRun('post', '/my/profile', [
            'csrf' => $user->csrf,
            'username' => $new_username,
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The username must be less than 50 characters');
        $user = auth\CurrentUser::reload();
        $this->assertSame($old_username, $user->username);
    }

    public function testUpdateFailsIfUsernameContainsAnAt()
    {
        $old_username = $this->fakeUnique('username');
        $new_username = $this->fakeUnique('username') . '@';
        $user = $this->login([
            'username' => $old_username,
        ]);

        $response = $this->appRun('post', '/my/profile', [
            'csrf' => $user->csrf,
            'username' => $new_username,
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The username cannot contain the character ‘@’');
        $user = auth\CurrentUser::reload();
        $this->assertSame($old_username, $user->username);
    }

    public function testUpdateFailsIfUsernameIsMissing()
    {
        $old_username = $this->fakeUnique('username');
        $user = $this->login([
            'username' => $old_username,
        ]);

        $response = $this->appRun('post', '/my/profile', [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The username is required');
        $user = auth\CurrentUser::reload();
        $this->assertSame($old_username, $user->username);
    }
}
