<?php

namespace flusio\my;

use flusio\models;
use flusio\utils;

class ProfileTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $this->login();

        $response = $this->appRun('get', '/my/profile');

        $this->assertResponse($response, 200);
        $this->assertPointer($response, 'my/profile/show.phtml');
    }

    public function testShowRedirectsToLoginIfUserNotConnected()
    {
        $response = $this->appRun('get', '/my/profile');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fmy%2Fprofile');
    }

    public function testUpdateRendersCorrectlyAndSavesTheUser()
    {
        $old_username = $this->fakeUnique('username');
        $new_username = $this->fakeUnique('username');
        $user = $this->login([
            'username' => $old_username,
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/my/profile', [
            'csrf' => $user->csrf,
            'username' => $new_username,
            'locale' => 'fr_FR',
        ]);

        $this->assertResponse($response, 200);
        $this->assertPointer($response, 'my/profile/show.phtml');
        $user = utils\CurrentUser::reload();
        $this->assertSame($new_username, $user->username);
        $this->assertSame('fr_FR', $user->locale);
    }

    public function testUpdateSetsTheCurrentLocale()
    {
        $user = $this->login([
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/my/profile', [
            'csrf' => $user->csrf,
            'username' => $this->fake('username'),
            'locale' => 'fr_FR',
        ]);

        $this->assertSame('fr_FR', utils\Locale::currentLocale());
    }

    public function testUpdateChangesTopics()
    {
        $user = $this->login();
        $old_topic_id = $this->create('topic');
        $new_topic_id = $this->create('topic');
        $this->create('user_to_topic', [
            'user_id' => $user->id,
            'topic_id' => $old_topic_id,
        ]);

        $response = $this->appRun('post', '/my/profile', [
            'csrf' => $user->csrf,
            'username' => $this->fake('username'),
            'locale' => 'fr_FR',
            'topic_ids' => [$new_topic_id],
        ]);

        $user = utils\CurrentUser::reload();
        $topic_ids = array_column($user->topics(), 'id');
        $this->assertSame([$new_topic_id], $topic_ids);
    }

    public function testUpdateRedirectsToLoginIfUserNotConnected()
    {
        $user_dao = new models\dao\User();
        $old_username = $this->fakeUnique('username');
        $new_username = $this->fakeUnique('username');
        $user_id = $this->create('user', [
            'username' => $old_username,
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/my/profile', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $new_username,
            'locale' => 'fr_FR',
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fmy%2Fprofile');
        $user = new models\User($user_dao->find($user_id));
        $this->assertSame($old_username, $user->username);
        $this->assertSame('en_GB', $user->locale);
    }

    public function testUpdateFailsIfCsrfIsInvalid()
    {
        $old_username = $this->fakeUnique('username');
        $new_username = $this->fakeUnique('username');
        $user = $this->login([
            'username' => $old_username,
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/my/profile', [
            'csrf' => 'not the token',
            'username' => $new_username,
            'locale' => 'fr_FR',
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $user = utils\CurrentUser::reload();
        $this->assertSame($old_username, $user->username);
        $this->assertSame('en_GB', $user->locale);
    }

    public function testUpdateFailsIfUsernameIsInvalid()
    {
        $old_username = $this->fakeUnique('username');
        $new_username = $this->fake('sentence', 50, false);
        $user = $this->login([
            'username' => $old_username,
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/my/profile', [
            'csrf' => $user->csrf,
            'username' => $new_username,
            'locale' => 'fr_FR',
        ]);

        $this->assertResponse($response, 400, 'The username must be less than 50 characters');
        $user = utils\CurrentUser::reload();
        $this->assertSame($old_username, $user->username);
        $this->assertSame('en_GB', $user->locale);
    }

    public function testUpdateFailsIfUsernameIsMissing()
    {
        $old_username = $this->fakeUnique('username');
        $user = $this->login([
            'username' => $old_username,
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/my/profile', [
            'csrf' => $user->csrf,
            'locale' => 'fr_FR',
        ]);

        $this->assertResponse($response, 400, 'The username is required');
        $user = utils\CurrentUser::reload();
        $this->assertSame($old_username, $user->username);
        $this->assertSame('en_GB', $user->locale);
    }

    public function testUpdateFailsIfLocaleIsMissing()
    {
        $old_username = $this->fakeUnique('username');
        $new_username = $this->fakeUnique('username');
        $user = $this->login([
            'username' => $old_username,
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/my/profile', [
            'csrf' => $user->csrf,
            'username' => $new_username,
        ]);

        $this->assertResponse($response, 400, 'The locale is required');
        $user = utils\CurrentUser::reload();
        $this->assertSame($old_username, $user->username);
        $this->assertSame('en_GB', $user->locale);
    }

    public function testUpdateFailsIfLocaleIsInvalid()
    {
        $old_username = $this->fakeUnique('username');
        $new_username = $this->fakeUnique('username');
        $user = $this->login([
            'username' => $old_username,
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/my/profile', [
            'csrf' => $user->csrf,
            'username' => $new_username,
            'locale' => 'not a locale',
        ]);

        $this->assertResponse($response, 400, 'The locale is invalid');
        $user = utils\CurrentUser::reload();
        $this->assertSame($old_username, $user->username);
        $this->assertSame('en_GB', $user->locale);
    }

    public function testUpdateFailsIfTopicIdsIsInvalid()
    {
        $user = $this->login();
        $old_topic_id = $this->create('topic');
        $this->create('user_to_topic', [
            'user_id' => $user->id,
            'topic_id' => $old_topic_id,
        ]);

        $response = $this->appRun('post', '/my/profile', [
            'csrf' => $user->csrf,
            'username' => $this->fake('username'),
            'locale' => 'fr_FR',
            'topic_ids' => ['not an id'],
        ]);

        $this->assertResponse($response, 400, 'One of the associated topic doesn’t exist.');
        $user = utils\CurrentUser::reload();
        $topic_ids = array_column($user->topics(), 'id');
        $this->assertSame([$old_topic_id], $topic_ids);
    }
}
