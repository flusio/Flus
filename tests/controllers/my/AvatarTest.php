<?php

namespace App\controllers\my;

use App\auth;
use App\forms;
use App\models;
use App\utils;
use tests\factories\UserFactory;

class AvatarTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\FilesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testUpdateCreatesAvatarAndRedirects(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login([
            'avatar_filename' => null,
        ]);
        $file = [
            'name' => 'avatar.png',
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', '/my/profile/avatar', [
            'csrf_token' => $this->csrfToken(forms\users\EditAvatar::class),
            'avatar' => $file,
        ]);

        $this->assertResponseCode($response, 302, "/p/{$user->id}");
        $user = $user->reload();
        $this->assertSame($user->id . '.webp', $user->avatar_filename);
        $media_path = \App\Configuration::$application['media_path'];
        $subpath = utils\Belt::filenameToSubpath($user->avatar_filename);
        $avatar_path = "{$media_path}/avatars/{$subpath}/{$user->avatar_filename}";
        $this->assertTrue(file_exists($avatar_path));
    }

    public function testUpdateDeletesOldFile(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        // we also copy the image as the existing avatar. Note the extension is
        // JPG instead of PNG: we just want to check that the file is deleted.
        $media_path = \App\Configuration::$application['media_path'];
        /** @var string */
        $previous_avatar_filename = $this->fake('md5');
        $previous_avatar_filename = $previous_avatar_filename . '.jpg';
        $subpath = utils\Belt::filenameToSubpath($previous_avatar_filename);
        $previous_avatar_path = "{$media_path}/avatars/{$subpath}";
        $previous_avatar_filepath = "{$previous_avatar_path}/{$previous_avatar_filename}";
        @mkdir($previous_avatar_path, 0755, true);
        copy($image_filepath, $previous_avatar_filepath);

        $user = $this->login([
            'avatar_filename' => $previous_avatar_filename,
        ]);
        $file = [
            'name' => 'avatar.png',
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', '/my/profile/avatar', [
            'csrf_token' => $this->csrfToken(forms\users\EditAvatar::class),
            'avatar' => $file,
        ]);

        $this->assertResponseCode($response, 302, "/p/{$user->id}");
        $user = $user->reload();
        $this->assertSame($user->id . '.webp', $user->avatar_filename);
        $subpath = utils\Belt::filenameToSubpath($user->avatar_filename);
        $avatar_filepath = "{$media_path}/avatars/{$subpath}/{$user->avatar_filename}";
        $this->assertTrue(file_exists($avatar_filepath));
        $this->assertFalse(file_exists($previous_avatar_filepath));
    }

    public function testUpdateRedirectsToLoginIfNotConnected(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = UserFactory::create([
            'avatar_filename' => null,
        ]);
        $file = [
            'name' => 'avatar.png',
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', '/my/profile/avatar', [
            'csrf_token' => $this->csrfToken(forms\users\EditAvatar::class),
            'avatar' => $file,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $user = $user->reload();
        $this->assertNull($user->avatar_filename);
    }

    public function testUpdateFailsIfCsrfIsInvalid(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login([
            'avatar_filename' => null,
        ]);
        $file = [
            'name' => 'avatar.png',
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', '/my/profile/avatar', [
            'csrf_token' => 'not the token',
            'avatar' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $user = $user->reload();
        $this->assertNull($user->avatar_filename);
    }

    public function testUpdateFailsIfFileIsMissing(): void
    {
        $user = $this->login([
            'avatar_filename' => null,
        ]);

        $response = $this->appRun('POST', '/my/profile/avatar', [
            'csrf_token' => $this->csrfToken(forms\users\EditAvatar::class),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The file is required.');
        $user = $user->reload();
        $this->assertNull($user->avatar_filename);
    }

    public function testUpdateFailsIfWrongFileType(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-avatar.svg';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login([
            'avatar_filename' => null,
        ]);
        $file = [
            'name' => 'avatar.svg',
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', '/my/profile/avatar', [
            'csrf_token' => $this->csrfToken(forms\users\EditAvatar::class),
            'avatar' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The file type must be one of the following: PNG, JPG, JPEG, WEBP.');
        $user = $user->reload();
        $this->assertNull($user->avatar_filename);
    }

    public function testUpdateFailsIfIsUploadedFileReturnsFalse(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login([
            'avatar_filename' => null,
        ]);
        $file = [
            'name' => 'avatar.png',
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
            'is_uploaded_file' => false, // this is possible only during tests!
        ];

        $response = $this->appRun('POST', '/my/profile/avatar', [
            'csrf_token' => $this->csrfToken(forms\users\EditAvatar::class),
            'avatar' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'This file cannot be uploaded (error -1).');
        $user = $user->reload();
        $this->assertNull($user->avatar_filename);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('tooLargeErrorsProvider')]
    public function testUpdateFailsIfTooLarge(int $error): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login([
            'avatar_filename' => null,
        ]);
        $file = [
            'name' => 'avatar.png',
            'tmp_name' => $tmp_filepath,
            'error' => $error,
        ];

        $response = $this->appRun('POST', '/my/profile/avatar', [
            'csrf_token' => $this->csrfToken(forms\users\EditAvatar::class),
            'avatar' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'This file is too large');
        $user = $user->reload();
        $this->assertNull($user->avatar_filename);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('otherFileErrorsProvider')]
    public function testUpdateFailsIfFileFailedToUpload(int $error): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login([
            'avatar_filename' => null,
        ]);
        $file = [
            'name' => 'avatar.png',
            'tmp_name' => $tmp_filepath,
            'error' => $error,
        ];

        $response = $this->appRun('POST', '/my/profile/avatar', [
            'csrf_token' => $this->csrfToken(forms\users\EditAvatar::class),
            'avatar' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, "This file cannot be uploaded (error {$error}).");
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
