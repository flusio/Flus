<?php

namespace flusio\controllers\my;

use flusio\auth;
use flusio\models;
use flusio\utils;

class AvatarTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\FilesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testUpdateCreatesAvatarAndRedirects()
    {
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
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
        $subpath = utils\Belt::filenameToSubpath($user->avatar_filename);
        $avatar_path = "{$media_path}/avatars/{$subpath}/{$user->avatar_filename}";
        $this->assertTrue(file_exists($avatar_path));
    }

    public function testUpdateDeletesOldFile()
    {
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        // we also copy the image as the existing avatar. Note the extension is
        // JPG instead of PNG: we just want to check that the file is deleted.
        $media_path = \Minz\Configuration::$application['media_path'];
        $previous_avatar_filename = $this->fake('md5') . '.jpg';
        $subpath = utils\Belt::filenameToSubpath($previous_avatar_filename);
        $previous_avatar_path = "{$media_path}/avatars/{$subpath}";
        $previous_avatar_filepath = "{$previous_avatar_path}/{$previous_avatar_filename}";
        @mkdir($previous_avatar_path, 0755, true);
        copy($image_filepath, $previous_avatar_filepath);

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
        $subpath = utils\Belt::filenameToSubpath($user->avatar_filename);
        $avatar_filepath = "{$media_path}/avatars/{$subpath}/{$user->avatar_filename}";
        $this->assertTrue(file_exists($avatar_filepath));
        $this->assertFalse(file_exists($previous_avatar_filepath));
    }

    public function testUpdateRedirectsToLoginIfNotConnected()
    {
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
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
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
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

    public function testUpdateFailsIfFileIsMissing()
    {
        $user = $this->login([
            'avatar_filename' => null,
        ]);

        $response = $this->appRun('post', '/my/profile/avatar', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/my/profile');
        $this->assertFlash('error', 'The file is required.');
        $user = auth\CurrentUser::reload();
        $this->assertNull($user->avatar_filename);
    }

    public function testUpdateFailsIfWrongFileType()
    {
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-avatar.svg';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
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
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login([
            'avatar_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
            'is_uploaded_file' => false, // this is possible only during tests!
        ];

        $response = $this->appRun('post', '/my/profile/avatar', [
            'csrf' => $user->csrf,
            'avatar' => $file,
        ]);

        $this->assertResponse($response, 302, '/my/profile');
        $this->assertFlash('error', 'This file cannot be uploaded (error -1).');
        $user = auth\CurrentUser::reload();
        $this->assertNull($user->avatar_filename);
    }

    /**
     * @dataProvider tooLargeErrorsProvider
     */
    public function testUpdateFailsIfTooLarge($error)
    {
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
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
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
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
