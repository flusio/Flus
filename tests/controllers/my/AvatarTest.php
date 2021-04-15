<?php

namespace flusio\controllers\my;

use flusio\auth;
use flusio\models;

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

class AvatarTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testUpdateCreatesAvatarAndRedirects()
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
        $user = auth\CurrentUser::reload();
        $this->assertSame($user->id . '.png', $user->avatar_filename);
        $media_path = \Minz\Configuration::$application['media_path'];
        $avatar_path = "{$media_path}/avatars/{$user->avatar_filename}";
        $this->assertTrue(file_exists($avatar_path));
    }

    public function testUpdateDeletesOldFile()
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
        $user = auth\CurrentUser::reload();
        $this->assertSame($user->id . '.png', $user->avatar_filename);
        $avatar_path = "{$media_path}/avatars/{$user->avatar_filename}";
        $this->assertTrue(file_exists($avatar_path));
        $this->assertFalse(file_exists($previous_avatar_path));
    }

    public function testUpdateRedirectsToLoginIfNotConnected()
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

    public function testUpdateFailsIfCsrfIsInvalid()
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
        $user = auth\CurrentUser::reload();
        $this->assertNull($user->avatar_filename);
    }

    public function testUpdateFailsIfWrongFileType()
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
        $user = auth\CurrentUser::reload();
        $this->assertNull($user->avatar_filename);
    }

    public function testUpdateFailsIfIsUploadedFileReturnsFalse()
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
        $user = auth\CurrentUser::reload();
        $this->assertNull($user->avatar_filename);
    }

    /**
     * @dataProvider tooLargeErrorsProvider
     */
    public function testUpdateFailsIfTooLarge($error)
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
        $user = auth\CurrentUser::reload();
        $this->assertNull($user->avatar_filename);
    }

    /**
     * @dataProvider otherFileErrorsProvider
     */
    public function testUpdateFailsIfFileFailedToUpload($error)
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
        $user = auth\CurrentUser::reload();
        $this->assertNull($user->avatar_filename);
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
