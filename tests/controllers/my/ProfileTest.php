<?php

namespace App\controllers\my;

use App\auth;
use App\models;
use tests\factories\UserFactory;

class ProfileTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testEditRendersCorrectly(): void
    {
        $this->login();

        $response = $this->appRun('GET', '/my/profile', [
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'my/profile/edit.phtml');
    }

    public function testEditRedirectsToLoginIfUserNotConnected(): void
    {
        $response = $this->appRun('GET', '/my/profile', [
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Fprofile');
    }

    public function testUpdateSavesTheUserAndRedirects(): void
    {
        /** @var string */
        $old_username = $this->fakeUnique('username');
        /** @var string */
        $new_username = $this->fakeUnique('username');
        $user = $this->login([
            'username' => $old_username,
        ]);

        $response = $this->appRun('POST', '/my/profile', [
            'csrf' => $user->csrf,
            'username' => $new_username,
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 302, '/my/profile');
        $user = $user->reload();
        $this->assertSame($new_username, $user->username);
    }

    public function testUpdateRedirectsToLoginIfUserNotConnected(): void
    {
        /** @var string */
        $old_username = $this->fakeUnique('username');
        /** @var string */
        $new_username = $this->fakeUnique('username');
        $user = UserFactory::create([
            'username' => $old_username,
        ]);

        $response = $this->appRun('POST', '/my/profile', [
            'csrf' => \Minz\Csrf::generate(),
            'username' => $new_username,
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Fprofile');
        $user = $user->reload();
        $this->assertSame($old_username, $user->username);
    }

    public function testUpdateFailsIfCsrfIsInvalid(): void
    {
        /** @var string */
        $old_username = $this->fakeUnique('username');
        /** @var string */
        $new_username = $this->fakeUnique('username');
        $user = $this->login([
            'username' => $old_username,
        ]);

        $response = $this->appRun('POST', '/my/profile', [
            'csrf' => 'not the token',
            'username' => $new_username,
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $user = $user->reload();
        $this->assertSame($old_username, $user->username);
    }

    public function testUpdateFailsIfUsernameIsTooLong(): void
    {
        /** @var string */
        $old_username = $this->fakeUnique('username');
        /** @var string */
        $new_username = $this->fake('sentence', 50, false);
        $user = $this->login([
            'username' => $old_username,
        ]);

        $response = $this->appRun('POST', '/my/profile', [
            'csrf' => $user->csrf,
            'username' => $new_username,
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The username must be less than 50 characters');
        $user = $user->reload();
        $this->assertSame($old_username, $user->username);
    }

    public function testUpdateFailsIfUsernameContainsAnAt(): void
    {
        /** @var string */
        $old_username = $this->fakeUnique('username');
        /** @var string */
        $new_username = $this->fakeUnique('username') . '@';
        $user = $this->login([
            'username' => $old_username,
        ]);

        $response = $this->appRun('POST', '/my/profile', [
            'csrf' => $user->csrf,
            'username' => $new_username,
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The username cannot contain the character ‘@’');
        $user = $user->reload();
        $this->assertSame($old_username, $user->username);
    }

    public function testUpdateFailsIfUsernameIsMissing(): void
    {
        /** @var string */
        $old_username = $this->fakeUnique('username');
        $user = $this->login([
            'username' => $old_username,
        ]);

        $response = $this->appRun('POST', '/my/profile', [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The username is required');
        $user = $user->reload();
        $this->assertSame($old_username, $user->username);
    }
}
