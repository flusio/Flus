<?php

namespace flusio\controllers\my;

use flusio\models;
use flusio\utils;

// In PHP, this function verifies a file has been uploaded via HTTP POST. We
// want it for security reasons. Unfortunately, it prevents us to test the file
// upload because we can’t bypass it. One solution would be to create a wrapper
// around this function, but the cheapest solution is to redeclare it in the
// current namespace.
//
// If uploading files become a thing in flusio, I should consider a design less
// "hacky".
//
// @see https://www.php.net/manual/fr/function.is-uploaded-file.php
function is_uploaded_file($filename)
{
    return $filename !== 'not_uploaded_file';
}

class ProfileTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    /**
     * @beforeClass
     */
    public static function createAvatarsPath()
    {
        mkdir(\Minz\Configuration::$application['media_path'] . '/avatars');
    }

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
        $user = models\User::find($user_id);
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

    public function testUpdateAvatarCreatesAvatarAndRedirects()
    {
        // we copy an existing file as a tmp file to simulate an image upload
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.png';
        $tmp_filepath = $tmp_path . '/' . $tmp_filename;
        copy($image_filepath, $tmp_filepath);

        $user = $this->login([
            'avatar_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('post', '/my/profile/avatar', [
            'csrf' => $user->csrf,
            'avatar' => $file,
        ]);

        $this->assertResponse($response, 302, '/my/profile');
        $user = utils\CurrentUser::reload();
        $this->assertSame($user->id . '.png', $user->avatar_filename);
        $media_path = \Minz\Configuration::$application['media_path'];
        $avatar_path = "{$media_path}/avatars/{$user->avatar_filename}";
        $this->assertTrue(file_exists($avatar_path));
    }

    public function testUpdateAvatarDeletesOldFile()
    {
        // we copy an existing file as a tmp file to simulate an image upload
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.png';
        $tmp_filepath = $tmp_path . '/' . $tmp_filename;
        copy($image_filepath, $tmp_filepath);

        // we also copy the image as the existing avatar. Note the extension is
        // JPG instead of PNG: we just want to check that the file is deleted.
        $media_path = \Minz\Configuration::$application['media_path'];
        $previous_avatar_filename = $this->fake('md5') . '.jpg';
        $previous_avatar_path = "{$media_path}/avatars/{$previous_avatar_filename}";
        copy($image_filepath, $previous_avatar_path);

        $user = $this->login([
            'avatar_filename' => $previous_avatar_filename,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('post', '/my/profile/avatar', [
            'csrf' => $user->csrf,
            'avatar' => $file,
        ]);

        $this->assertResponse($response, 302, '/my/profile');
        $user = utils\CurrentUser::reload();
        $this->assertSame($user->id . '.png', $user->avatar_filename);
        $avatar_path = "{$media_path}/avatars/{$user->avatar_filename}";
        $this->assertTrue(file_exists($avatar_path));
        $this->assertFalse(file_exists($previous_avatar_path));
    }

    public function testUpdateAvatarRedirectsToLoginIfNotConnected()
    {
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.png';
        $tmp_filepath = $tmp_path . '/' . $tmp_filename;
        $user_id = $this->create('user', [
            'avatar_filename' => null,
            'csrf' => 'a token',
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('post', '/my/profile/avatar', [
            'csrf' => 'a token',
            'avatar' => $file,
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fmy%2Fprofile');
        $user = models\User::find($user_id);
        $this->assertNull($user->avatar_filename);
    }

    public function testUpdateAvatarFailsIfCsrfIsInvalid()
    {
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.png';
        $tmp_filepath = $tmp_path . '/' . $tmp_filename;
        $user = $this->login([
            'avatar_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('post', '/my/profile/avatar', [
            'csrf' => 'not the token',
            'avatar' => $file,
        ]);

        $this->assertResponse($response, 302, '/my/profile');
        $this->assertFlash('error', 'A security verification failed.');
        $user = utils\CurrentUser::reload();
        $this->assertNull($user->avatar_filename);
    }

    public function testUpdateAvatarFailsIfWrongFileType()
    {
        // we copy an existing file as a tmp file to simulate an image upload
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-avatar.svg';
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.svg';
        $tmp_filepath = $tmp_path . '/' . $tmp_filename;
        copy($image_filepath, $tmp_filepath);

        $user = $this->login([
            'avatar_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('post', '/my/profile/avatar', [
            'csrf' => $user->csrf,
            'avatar' => $file,
        ]);

        $this->assertResponse($response, 302, '/my/profile');
        $this->assertFlash('error', 'The photo must be <abbr>PNG</abbr> or <abbr>JPG</abbr>.');
        $user = utils\CurrentUser::reload();
        $this->assertNull($user->avatar_filename);
    }

    public function testUpdateAvatarFailsIfIsUploadedFileReturnsFalse()
    {
        $user = $this->login([
            'avatar_filename' => null,
        ]);
        $file = [
            // see is_uploaded_file override at the top of the file: it will
            // return false if the tmp_name is 'not_uploaded_file'.
            'tmp_name' => 'not_uploaded_file',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('post', '/my/profile/avatar', [
            'csrf' => $user->csrf,
            'avatar' => $file,
        ]);

        $this->assertResponse($response, 302, '/my/profile');
        $this->assertFlash('error', 'This file cannot be uploaded.');
        $user = utils\CurrentUser::reload();
        $this->assertNull($user->avatar_filename);
    }

    /**
     * @dataProvider tooLargeErrorsProvider
     */
    public function testUpdateAvatarFailsIfTooLarge($error)
    {
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.svg';
        $tmp_filepath = $tmp_path . '/' . $tmp_filename;
        $user = $this->login([
            'avatar_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => $error,
        ];

        $response = $this->appRun('post', '/my/profile/avatar', [
            'csrf' => $user->csrf,
            'avatar' => $file,
        ]);

        $this->assertResponse($response, 302, '/my/profile');
        $this->assertFlash('error', 'This file is too large.');
        $user = utils\CurrentUser::reload();
        $this->assertNull($user->avatar_filename);
    }

    /**
     * @dataProvider otherFileErrorsProvider
     */
    public function testUpdateAvatarFailsIfFileFailedToUpload($error)
    {
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.svg';
        $tmp_filepath = $tmp_path . '/' . $tmp_filename;
        $user = $this->login([
            'avatar_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => $error,
        ];

        $response = $this->appRun('post', '/my/profile/avatar', [
            'csrf' => $user->csrf,
            'avatar' => $file,
        ]);

        $this->assertResponse($response, 302, '/my/profile');
        $this->assertFlash('error', "This file cannot be uploaded (error {$error}).");
        $user = utils\CurrentUser::reload();
        $this->assertNull($user->avatar_filename);
    }

    public function testInfoRendersCorrectly()
    {
        $user = $this->login();
        $url_1 = $this->fakeUnique('url');
        $url_2 = $this->fakeUnique('url');
        $bookmarks_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_id_1 = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url_1,
        ]);
        $link_id_2 = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url_2,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $bookmarks_id,
            'link_id' => $link_id_1,
        ]);

        $response = $this->appRun('get', '/my/info.json');

        $this->assertResponse($response, 200, null, [
            'Content-Type' => 'application/json',
        ]);
        $output = json_decode($response->render(), true);
        $this->assertSame($user->csrf, $output['csrf']);
        $this->assertSame($bookmarks_id, $output['bookmarks_id']);
        $this->assertSame([$url_1], $output['bookmarked_urls']);
    }

    public function testInfoRedirectsIfUserNotConnected()
    {
        $response = $this->appRun('get', '/my/info.json');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fmy%2Finfo.json');
    }

    public function tooLargeErrorsProvider()
    {
        return [
            [UPLOAD_ERR_INI_SIZE],
            [UPLOAD_ERR_FORM_SIZE],
        ];
    }

    public function otherFileErrorsProvider()
    {
        return [
            [UPLOAD_ERR_PARTIAL],
            [UPLOAD_ERR_NO_FILE],
            [UPLOAD_ERR_NO_TMP_DIR],
            [UPLOAD_ERR_CANT_WRITE],
            [UPLOAD_ERR_EXTENSION],
        ];
    }
}
