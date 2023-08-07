<?php

namespace flusio\controllers\my;

use flusio\auth;
use flusio\models;
use flusio\utils;
use tests\factories\UserFactory;

class AvatarTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FilesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testUpdateCreatesAvatarAndRedirects(): void
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

        $response = $this->appRun('POST', '/my/profile/avatar', [
            'csrf' => $user->csrf,
            'avatar' => $file,
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 302, '/my/profile');
        $user = $user->reload();
        $this->assertSame($user->id . '.webp', $user->avatar_filename);
        /** @var string */
        $media_path = \Minz\Configuration::$application['media_path'];
        $subpath = utils\Belt::filenameToSubpath($user->avatar_filename);
        $avatar_path = "{$media_path}/avatars/{$subpath}/{$user->avatar_filename}";
        $this->assertTrue(file_exists($avatar_path));
    }

    public function testUpdateDeletesOldFile(): void
    {
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        // we also copy the image as the existing avatar. Note the extension is
        // JPG instead of PNG: we just want to check that the file is deleted.
        /** @var string */
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

        $response = $this->appRun('POST', '/my/profile/avatar', [
            'csrf' => $user->csrf,
            'avatar' => $file,
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 302, '/my/profile');
        $user = $user->reload();
        $this->assertSame($user->id . '.webp', $user->avatar_filename);
        $subpath = utils\Belt::filenameToSubpath($user->avatar_filename);
        $avatar_filepath = "{$media_path}/avatars/{$subpath}/{$user->avatar_filename}";
        $this->assertTrue(file_exists($avatar_filepath));
        $this->assertFalse(file_exists($previous_avatar_filepath));
    }

    public function testUpdateRedirectsToLoginIfNotConnected(): void
    {
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = UserFactory::create([
            'avatar_filename' => null,
            'csrf' => 'a token',
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', '/my/profile/avatar', [
            'csrf' => 'a token',
            'avatar' => $file,
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Fprofile');
        $user = $user->reload();
        $this->assertNull($user->avatar_filename);
    }

    public function testUpdateFailsIfCsrfIsInvalid(): void
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

        $response = $this->appRun('POST', '/my/profile/avatar', [
            'csrf' => 'not the token',
            'avatar' => $file,
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 302, '/my/profile');
        $this->assertSame('A security verification failed.', \Minz\Flash::get('error'));
        $user = $user->reload();
        $this->assertNull($user->avatar_filename);
    }

    public function testUpdateFailsIfFileIsMissing(): void
    {
        $user = $this->login([
            'avatar_filename' => null,
        ]);

        $response = $this->appRun('POST', '/my/profile/avatar', [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 302, '/my/profile');
        $this->assertSame('The file is required.', \Minz\Flash::get('error'));
        $user = $user->reload();
        $this->assertNull($user->avatar_filename);
    }

    public function testUpdateFailsIfWrongFileType(): void
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

        $response = $this->appRun('POST', '/my/profile/avatar', [
            'csrf' => $user->csrf,
            'avatar' => $file,
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 302, '/my/profile');
        $this->assertSame('The photo must be <abbr>PNG</abbr> or <abbr>JPG</abbr>.', \Minz\Flash::get('error'));
        $user = $user->reload();
        $this->assertNull($user->avatar_filename);
    }

    public function testUpdateFailsIfIsUploadedFileReturnsFalse(): void
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

        $response = $this->appRun('POST', '/my/profile/avatar', [
            'csrf' => $user->csrf,
            'avatar' => $file,
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 302, '/my/profile');
        $this->assertSame('This file cannot be uploaded (error -1).', \Minz\Flash::get('error'));
        $user = $user->reload();
        $this->assertNull($user->avatar_filename);
    }

    /**
     * @dataProvider tooLargeErrorsProvider
     */
    public function testUpdateFailsIfTooLarge(int $error): void
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

        $response = $this->appRun('POST', '/my/profile/avatar', [
            'csrf' => $user->csrf,
            'avatar' => $file,
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 302, '/my/profile');
        $this->assertSame('This file is too large.', \Minz\Flash::get('error'));
        $user = $user->reload();
        $this->assertNull($user->avatar_filename);
    }

    /**
     * @dataProvider otherFileErrorsProvider
     */
    public function testUpdateFailsIfFileFailedToUpload(int $error): void
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

        $response = $this->appRun('POST', '/my/profile/avatar', [
            'csrf' => $user->csrf,
            'avatar' => $file,
            'from' => \Minz\Url::for('edit profile'),
        ]);

        $this->assertResponseCode($response, 302, '/my/profile');
        $this->assertSame("This file cannot be uploaded (error {$error}).", \Minz\Flash::get('error'));
        $user = $user->reload();
        $this->assertNull($user->avatar_filename);
    }

    /**
     * @return array<array{int}>
     */
    public static function tooLargeErrorsProvider(): array
    {
        return [
            [UPLOAD_ERR_INI_SIZE],
            [UPLOAD_ERR_FORM_SIZE],
        ];
    }

    /**
     * @return array<array{int}>
     */
    public static function otherFileErrorsProvider(): array
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
