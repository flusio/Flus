<?php

namespace App\controllers\my;

use App\auth;
use App\forms;
use App\images;
use tests\factories\UserFactory;

class AvatarTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\FilesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\FilesystemHelper;
    use \tests\LoginHelper;

    public function testEditRendersCorrectly(): void
    {
        $this->login();

        $response = $this->appRun('GET', '/my/profile/avatar');

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'my/avatar/edit.html.twig');
    }

    public function testEditRedirectsToLoginIfUserNotConnected(): void
    {
        $response = $this->appRun('GET', '/my/profile/avatar');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Fprofile%2Favatar');
    }

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
        $expected_filename = hash_file('sha256', $image_filepath) . '.webp';
        $this->assertSame($expected_filename, $user->avatar_filename);
        $this->assertTrue(file_exists(images\Storage::filepath('avatars', $expected_filename)));
    }

    public function testUpdateRemovesThePreviousAvatar(): void
    {
        $previous_avatar_filename = $this->createAvatarFile();
        $previous_avatar_filepath = images\Storage::filepath('avatars', $previous_avatar_filename);
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login([
            'avatar_filename' => $previous_avatar_filename,
        ]);

        $response = $this->appRun('POST', '/my/profile/avatar', [
            'csrf_token' => $this->csrfToken(forms\users\EditAvatar::class),
            'avatar' => [
                'name' => 'avatar.png',
                'tmp_name' => $tmp_filepath,
                'error' => UPLOAD_ERR_OK,
            ],
        ]);

        $this->assertResponseCode($response, 302, "/p/{$user->id}");
        $this->assertFalse(file_exists($previous_avatar_filepath));
    }

    public function testUpdateKeepsThePreviousAvatarIfUsedByAnotherUser(): void
    {
        $previous_avatar_filename = $this->createAvatarFile();
        $previous_avatar_filepath = images\Storage::filepath('avatars', $previous_avatar_filename);
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login([
            'avatar_filename' => $previous_avatar_filename,
        ]);
        UserFactory::create([
            'avatar_filename' => $previous_avatar_filename,
        ]);

        $response = $this->appRun('POST', '/my/profile/avatar', [
            'csrf_token' => $this->csrfToken(forms\users\EditAvatar::class),
            'avatar' => [
                'name' => 'avatar.png',
                'tmp_name' => $tmp_filepath,
                'error' => UPLOAD_ERR_OK,
            ],
        ]);

        $this->assertResponseCode($response, 302, "/p/{$user->id}");
        $this->assertTrue(file_exists($previous_avatar_filepath));
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

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Fprofile%2Favatar');
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

    /**
     * Copy an image in the avatars directory, and return its filename.
     */
    private function createAvatarFile(): string
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/og-card.png';
        $filename = hash_file('sha256', $image_filepath) . '.webp';
        $filepath = images\Storage::filepath('avatars', $filename);
        @mkdir(dirname($filepath), 0755, true);
        copy($image_filepath, $filepath);
        return $filename;
    }
}
